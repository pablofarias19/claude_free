# 42 · ComfyUI en la Nube — RunPod, Vast.ai y Servidores Remotos

> **DECLARACIÓN TÉCNICA PARA IA**: Ejecutar ComfyUI en la nube es relevante cuando: (1) el hardware local no tiene suficiente VRAM para un modelo, (2) se necesita generar en batch a alta velocidad, (3) se quiere probar modelos sin descargarlos localmente. RunPod y Vast.ai son los proveedores más usados por la comunidad ComfyUI. La conexión típica es vía SSH tunnel o JupyterLab, y la interfaz de ComfyUI se expone en el navegador local. Para el sistema Pablo (RTX 5080 / 16 GB VRAM local) la nube es solo necesaria para modelos 24 GB+ como Hunyuan Video completo en BF16.

## Proveedores de GPU en la nube

### Comparativa

| Proveedor | GPUs disponibles | Precio referencia | Facilidad | Almacenamiento |
|---|---|---|---|---|
| RunPod | H100, A100, RTX 4090, 3090 | $0.20–1.50/h | Alta | Volumen persistente |
| Vast.ai | H100, A100, 4090, 3090 | $0.15–1.20/h | Media | Volumen persistente |
| Lambda Labs | H100, A100, 4090 | $1.50–2.50/h | Alta | Volumen persistente |
| Google Colab | T4, A100 | $0–$0.40/h | Muy alta | No persistente |
| Kaggle | P100, T4 | Gratuito (30h/sem) | Alta | No persistente |
| CoreWeave | A100, H100 | Empresarial | Baja | Sí |

### GPU más útiles para ComfyUI en la nube

| GPU | VRAM | Mejor para | $/hora aprox |
|---|---|---|---|
| RTX 4090 | 24 GB | SDXL, Flux BF16, video modelos pequeños | $0.40–0.70 |
| A100 40 GB | 40 GB | Hunyuan, Wan2.1-14B completo | $0.90–1.20 |
| A100 80 GB | 80 GB | Múltiples modelos, batch grande | $1.50–2.00 |
| H100 80 GB | 80 GB | Entrenamiento, inferencia ultra-rápida | $2.50–3.50 |
| RTX 3090 | 24 GB | Alternativa económica a 4090 | $0.20–0.40 |

## Configuración en RunPod

### Paso 1: Crear pod con ComfyUI

```
1. Ir a runpod.io → Deploy GPU Pod
2. Buscar template: "ComfyUI" en la sección de plantillas
   Templates recomendados:
   - "ComfyUI" (oficial, actualizado regularmente)
   - "ComfyUI + AUTOMATIC1111" (más modelos preinstalados)
3. Seleccionar GPU: RTX 4090 o A100 40GB según modelo
4. Configurar volumen:
   - Container Disk: 20 GB (sistema)
   - Volume Disk: 100-200 GB (para modelos)
   - Volume Mount Path: /workspace
5. Exponer puertos:
   - HTTP Port: 3000 (interfaz ComfyUI)
   - o usar Jupyter: 8888
```

### Paso 2: Acceder a ComfyUI remoto

```bash
# Opción A: Acceso vía URL de RunPod (más simple)
# RunPod genera una URL pública: https://XXXXXXXX-3000.proxy.runpod.net

# Opción B: SSH Tunnel (más seguro para producción)
ssh -L 8188:localhost:8188 root@<POD-IP> -p <SSH-PORT>
# Luego abrir: http://localhost:8188

# Opción C: JupyterLab integrado de RunPod
# Acceso via browser, terminal incluida
```

### Paso 3: Descargar modelos en el pod

```bash
# En el terminal del pod (JupyterLab o SSH):

# Descargar desde HuggingFace:
cd /workspace/ComfyUI/models/checkpoints
wget -O flux1-dev-fp8.safetensors \
  "https://huggingface.co/Kijai/flux-fp8/resolve/main/flux1-dev-fp8.safetensors"

# O con huggingface_hub (más confiable para archivos grandes):
pip install huggingface_hub
python -c "
from huggingface_hub import hf_hub_download
hf_hub_download(
    repo_id='black-forest-labs/FLUX.1-dev',
    filename='flux1-dev.safetensors',
    local_dir='/workspace/ComfyUI/models/unet/'
)
"

# Herramienta de la comunidad (civitai + huggingface):
pip install civitai-cli
civitai download MODEL_ID -o /workspace/ComfyUI/models/checkpoints/
```

### Volumen persistente en RunPod

```bash
# El volumen en /workspace persiste aunque el pod se detenga
# IMPORTANTE: Los archivos fuera de /workspace se PIERDEN al detener el pod

# Estructura recomendada:
/workspace/
  ComfyUI/         ← instalar aquí
    models/        ← todos los modelos
    custom_nodes/  ← extensiones
    output/        ← imágenes generadas

# Script de inicio (en Jupyter):
# Crear start.sh en /workspace/
cat > /workspace/start.sh << 'EOF'
#!/bin/bash
cd /workspace/ComfyUI
python main.py --listen 0.0.0.0 --port 3000
EOF
chmod +x /workspace/start.sh
```

## Configuración en Vast.ai

```bash
# 1. vast.ai: crear cuenta y configurar SSH key
# 2. Buscar instancia con la GPU deseada
# 3. Filtrar: imagen Docker = PyTorch, RAM > 32 GB, Disk > 100 GB
# 4. Rentar instancia

# 5. Conectar via SSH:
ssh -p <PORT> root@<IP>

# 6. Instalar ComfyUI:
git clone https://github.com/comfyanonymous/ComfyUI.git
cd ComfyUI
pip install -r requirements.txt

# 7. Iniciar con exposición de puerto:
python main.py --listen 0.0.0.0 --port 8188

# 8. Crear tunnel desde tu PC local:
ssh -L 8188:localhost:8188 -p <PORT> root@<IP>
# Abrir: http://localhost:8188
```

## Transferir workflows y resultados

### Enviar workflow al servidor

```bash
# SCP (simple copia):
scp -P <PORT> mi_workflow.json root@<IP>:/workspace/ComfyUI/user/default/workflows/

# SFTP (más interactivo):
sftp -P <PORT> root@<IP>
put mi_workflow.json /workspace/ComfyUI/user/default/workflows/

# rsync (sincronización de carpetas):
rsync -avz -e "ssh -p <PORT>" ./workflows/ root@<IP>:/workspace/ComfyUI/user/default/workflows/
```

### Descargar resultados generados

```bash
# SCP — descargar carpeta output completa:
scp -P <PORT> -r root@<IP>:/workspace/ComfyUI/output/ ./output_cloud/

# rsync — sincronizar solo los nuevos archivos:
rsync -avz -e "ssh -p <PORT>" root@<IP>:/workspace/ComfyUI/output/ ./output_cloud/
```

## Google Colab (gratuito con limitaciones)

```python
# Notebook básico para ComfyUI en Colab:

# Celda 1: Instalar
!git clone https://github.com/comfyanonymous/ComfyUI.git
%cd ComfyUI
!pip install -r requirements.txt -q

# Celda 2: Descargar modelo
!wget -q -O models/checkpoints/sdxl_base.safetensors \
  "https://huggingface.co/stabilityai/stable-diffusion-xl-base-1.0/resolve/main/sd_xl_base_1.0.safetensors"

# Celda 3: Iniciar con ngrok (exposición pública)
!pip install pyngrok -q
from pyngrok import ngrok
tunnel = ngrok.connect(8188)
print(f"ComfyUI accesible en: {tunnel.public_url}")

import subprocess
subprocess.Popen(['python', 'main.py', '--listen', '0.0.0.0', '--port', '8188'])

import time
time.sleep(5)
print("ComfyUI iniciado")
```

> **LIMITACIONES COLAB**: Sesión máxima 12 horas (Colab gratuito). Modelos NO persisten entre sesiones. Para persistencia, montar Google Drive.

## ComfyUI API en workflows remotos

```python
# Usar la API de ComfyUI remoto desde tu PC local
# Con el tunnel SSH activo en puerto 8188:

import requests, json, time

SERVER = "http://localhost:8188"  # Tunnel apunta aquí

# Cargar workflow
with open("mi_workflow_api.json") as f:
    workflow = json.load(f)

# Modificar paramétros dinámicamente
workflow["6"]["inputs"]["text"] = "a beautiful sunset, oil painting"
workflow["3"]["inputs"]["seed"] = 42

# Enviar al servidor remoto
response = requests.post(f"{SERVER}/prompt", json={"prompt": workflow})
prompt_id = response.json()["prompt_id"]

# Esperar y descargar resultado
while True:
    history = requests.get(f"{SERVER}/history/{prompt_id}").json()
    if prompt_id in history:
        outputs = history[prompt_id]["outputs"]
        for node_id, output in outputs.items():
            if "images" in output:
                for img in output["images"]:
                    img_data = requests.get(
                        f"{SERVER}/view?filename={img['filename']}&subfolder={img['subfolder']}"
                    ).content
                    with open(f"resultado_{img['filename']}", "wb") as f:
                        f.write(img_data)
        break
    time.sleep(2)
```

## Gestionar costos en la nube

```
Estrategias para minimizar costo:

1. POD ON/OFF: Detener el pod cuando no se usa (paga solo por tiempo activo)
   RunPod: Stop Pod (no borra volumen, solo pausa cómputo)

2. Spot/Interruptible: 50-80% más barato, pero puede terminar sin aviso
   Bueno para: generación en batch que puede reiniciarse
   Malo para: workflows interactivos

3. Presupuesto diario: configurar límite de gasto en RunPod/Vast.ai

4. Usar modelos GGUF: generan más rápido → menos tiempo de GPU → menor costo

5. Batch nocturno: ejecutar generaciones en horarios de menor demanda
   (tarifa spot más baja fuera de horas pico EE.UU., 9am-6pm EST)
```

## Casos excepcionales

1. **Sistema Pablo y la nube**: La RTX 5080 local cubre el 95% de los casos. La nube solo es necesaria para Hunyuan Video BF16 (>20 GB VRAM) o para generación en batch masiva sin bloquear el equipo local.
2. **Latencia de red**: ComfyUI en la nube tiene latencia de red para cada acción de UI (300-1000ms según conexión). Para workflows de prueba rápida, esto es molesto. Recomendado: prototipar localmente y escalar en la nube.
3. **Modelos en volumen vs contenedor**: En RunPod, el volumen es persistente pero el contenedor no. Custom nodes y configuraciones fuera del volumen se pierden al recrear el pod. Instalar siempre en `/workspace`.
4. **ngrok gratuito en Colab**: URL única por sesión, cambia en cada reinicio. Para compartir la URL con colaboradores, usar ngrok de pago o alternativas como Cloudflare Tunnel.
5. **Firewall corporativo**: Si se trabaja desde empresa, el puerto SSH (22) puede estar bloqueado. Usar RunPod que ofrece acceso HTTPS (443) que rara vez está bloqueado.

## Recursos

- RunPod: `https://runpod.io`
- Vast.ai: `https://vast.ai`
- Lambda Labs: `https://lambdalabs.com`
- Google Colab: `https://colab.research.google.com`
- Kaggle Notebooks (gratuito): `https://www.kaggle.com/code`
- ComfyUI templates RunPod: `https://runpod.io/console/gpu-cloud?ref=comfyui`
- Tutorial RunPod ComfyUI: `https://www.youtube.com/results?search_query=runpod+comfyui`
