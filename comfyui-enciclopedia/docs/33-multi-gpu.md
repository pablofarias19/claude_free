# 33 · Configuraciones Multi-GPU en ComfyUI

> **DECLARACIÓN TÉCNICA PARA IA**: ComfyUI no tiene soporte nativo multi-GPU para un único workflow. Las estrategias actuales son: (1) múltiples instancias en puertos distintos, (2) distribución manual con nodos custom, (3) model offloading entre GPUs. VRAM combinada entre GPUs NO se suma automáticamente como en sistemas NVLink. Cada enfoque tiene trade-offs distintos en latencia, throughput y complejidad.

## Estado del soporte multi-GPU en ComfyUI

| Estrategia | Soporte nativo | Complejidad | Caso de uso |
|---|---|---|---|
| Múltiples instancias | ✅ Sí | Baja | Generación paralela en batch |
| Distribución por modelo | ⚠️ Custom nodes | Media | Modelos que no caben en 1 GPU |
| Tensor parallelism | ❌ No nativo | Alta | Investigación/experimental |
| NVLink pool | ❌ No | N/A | No aplicable |

## Estrategia 1: Múltiples instancias en puertos distintos

La forma más simple y estable de usar múltiples GPUs.

```bash
# Terminal 1 — GPU 0
CUDA_VISIBLE_DEVICES=0 python main.py --port 8188

# Terminal 2 — GPU 1
CUDA_VISIBLE_DEVICES=1 python main.py --port 8189

# Terminal 3 — GPU 2 (si existe)
CUDA_VISIBLE_DEVICES=2 python main.py --port 8190

# Acceder a cada instancia desde el navegador:
# http://localhost:8188  → GPU 0
# http://localhost:8189  → GPU 1
```

### Script de inicio multi-instancia

```bash
#!/bin/bash
# start_multi.sh — iniciar N instancias de ComfyUI

GPU_COUNT=$(nvidia-smi --query-gpu=name --format=csv,noheader | wc -l)
BASE_PORT=8188

for i in $(seq 0 $((GPU_COUNT - 1))); do
    PORT=$((BASE_PORT + i))
    echo "Iniciando ComfyUI en GPU $i, puerto $PORT"
    CUDA_VISIBLE_DEVICES=$i python main.py --port $PORT &
    sleep 3  # Esperar para evitar conflictos en inicialización
done

wait
```

### Load balancer con API

```python
# Distribuir jobs entre instancias via API
import random
import requests

INSTANCES = [
    "http://localhost:8188",
    "http://localhost:8189",
    "http://localhost:8190",
]

def queue_prompt_distributed(workflow: dict) -> dict:
    """Enviar al servidor con menor cola."""
    loads = []
    for url in INSTANCES:
        try:
            queue = requests.get(f"{url}/queue").json()
            pending = len(queue.get("queue_running", [])) + len(queue.get("queue_pending", []))
            loads.append((pending, url))
        except:
            loads.append((999, url))  # Instancia no disponible
    
    # Elegir el servidor con menos trabajo
    loads.sort(key=lambda x: x[0])
    target_url = loads[0][1]
    
    response = requests.post(
        f"{target_url}/prompt",
        json={"prompt": workflow}
    )
    return {"server": target_url, "result": response.json()}
```

## Estrategia 2: Distribución de modelos entre GPUs

Útil cuando un modelo no cabe en una sola GPU.

### Con ComfyUI-Manager y nodos de dispositivo

Algunos custom nodes permiten especificar dispositivo:

```
# Con comfy_mtb u otros nodos:
LoadCheckpointToDevice
    device: "cuda:0"  # GPU 0

VAEDecodeOnDevice
    device: "cuda:1"  # GPU 1
```

### Flux con UNet en GPU 0, CLIP+VAE en GPU 1

```python
# Conceptualmente (usando API de ComfyUI):
# No hay nodo estándar, requiere custom node o modificación

# Alternativa: usar --gpu-only y dejar ComfyUI gestionar
# el offloading automático entre GPU y CPU RAM
python main.py --gpu-only  # Todo en GPU (puede fallar por VRAM)
python main.py --highvram  # Mantener modelos en GPU siempre
```

### Accelerate (para uso programático, no ComfyUI GUI)

```python
# Si se usa la API de difusers en lugar de ComfyUI:
from accelerate import dispatch_model, infer_auto_device_map

device_map = infer_auto_device_map(
    model, 
    max_memory={0: "20GiB", 1: "20GiB", "cpu": "60GiB"}
)
model = dispatch_model(model, device_map=device_map)
```

## Estrategia 3: Offloading CPU como extensión de VRAM

```bash
# ComfyUI usa CPU RAM cuando la VRAM se agota
# Controlar con flags:

--lowvram      # Agresivo: mueve modelos a CPU cuando no se usan
--novram       # Extremo: no mantiene nada en VRAM entre operaciones
--cpu          # Todo en CPU (muy lento, último recurso)

# Para modelos muy grandes (Flux, Hunyuan) con poca VRAM:
# VRAM 8 GB + 32 GB RAM sistema → viable con --lowvram
```

## Monitoreo de múltiples GPUs

```bash
# Monitoreo en tiempo real de todas las GPUs
watch -n 1 nvidia-smi

# Formato compacto multi-GPU
nvidia-smi --query-gpu=index,name,utilization.gpu,memory.used,memory.total,temperature.gpu \
           --format=csv -l 1

# Ejemplo de salida:
# 0, NVIDIA RTX 3090, 87%, 19500 MiB, 24576 MiB, 72°C
# 1, NVIDIA RTX 3080, 92%, 9800 MiB, 10240 MiB, 68°C

# Con nvitop (más visual):
pip install nvitop
nvitop
```

## Configuración NVLink (si está disponible)

> **NOTA**: NVLink permite que dos GPUs NVIDIA compartan memoria de alta velocidad. Disponible en algunos pares de GPUs Quadro/Tesla y algunas RTX de prosumer. ComfyUI NO aprovecha NVLink automáticamente.

```bash
# Verificar si NVLink está activo:
nvidia-smi nvlink --status

# Para aprovechar NVLink con PyTorch:
import torch
# PyTorch puede usar NCCL sobre NVLink en operaciones distribuidas
# pero ComfyUI GUI no lo expone
```

## Casos de uso por perfil de hardware

### 2× RTX 3090 (48 GB VRAM total)
```bash
# Opción A: 2 instancias para throughput
CUDA_VISIBLE_DEVICES=0 python main.py --port 8188 &
CUDA_VISIBLE_DEVICES=1 python main.py --port 8189 &
# → Procesar 2 imágenes simultáneamente

# Opción B: Hunyuan Video en GPU 0 + SDXL en GPU 1
# → Generación de video + imágenes en paralelo
```

### 1× RTX 4090 + 1× RTX 3080
```bash
# 4090 para modelos grandes (Flux, SDXL)
CUDA_VISIBLE_DEVICES=0 python main.py --port 8188  # 4090
# 3080 para modelos livianos (SD1.5, LCM, previews)
CUDA_VISIBLE_DEVICES=1 python main.py --port 8189  # 3080
```

## Docker con múltiples GPUs

```dockerfile
# docker-compose.yml para multi-GPU
version: '3.8'
services:
  comfyui-gpu0:
    image: comfyui:latest
    deploy:
      resources:
        reservations:
          devices:
            - driver: nvidia
              device_ids: ['0']
              capabilities: [gpu]
    ports:
      - "8188:8188"
    command: python main.py --listen 0.0.0.0
  
  comfyui-gpu1:
    image: comfyui:latest
    deploy:
      resources:
        reservations:
          devices:
            - driver: nvidia
              device_ids: ['1']
              capabilities: [gpu]
    ports:
      - "8189:8188"
    command: python main.py --listen 0.0.0.0
```

## Casos excepcionales

1. **GPUs de diferentes generaciones**: Funcionan en instancias separadas sin problema. Los CUDA cores son independientes. La GPU más lenta determina el throughput en producción si se balancea en round-robin.
2. **VRAM asimétrica**: RTX 4090 (24GB) + RTX 3080 (10GB). Asignar modelos SDXL/Flux a 4090, modelos LCM/SD1.5 a 3080. No intentar repartir un solo modelo.
3. **PCIe Bandwidth**: En setups multi-GPU sin NVLink, la transferencia de tensores entre GPUs pasa por la CPU (PCIe). Puede ser un cuello de botella para pipelines que necesiten coordinar entre GPUs.
4. **CUDA Out of Memory en segunda GPU**: Al lanzar segunda instancia, la primera puede haber reservado memoria de driver compartida. Si hay errores, reiniciar el sistema y lanzar ambas instancias desde el inicio.
5. **Semillas y reproducibilidad**: Con múltiples instancias, cada una tiene su propio generador de números aleatorios. Misma seed en instancias diferentes produce resultados idénticos (comportamiento esperado y deseable).

## Recursos

- ComfyUI multi-GPU discussion: `https://github.com/comfyanonymous/ComfyUI/discussions`
- PyTorch multi-GPU guide: `https://pytorch.org/docs/stable/notes/cuda.html`
- nvidia-smi docs: `https://developer.nvidia.com/nvidia-system-management-interface`
- nvitop (monitor GPU): `https://github.com/XuehaiPan/nvitop`
- ComfyUI API docs: `https://github.com/comfyanonymous/ComfyUI/blob/master/script_examples/`
