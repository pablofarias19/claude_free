# 30 — API de ComfyUI y Automatización

ComfyUI expone una API HTTP que permite controlarlo programáticamente desde cualquier lenguaje.

---

## Endpoints Principales

Base URL: `http://localhost:8188`

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/prompt` | POST | Enviar un workflow para ejecutar |
| `/queue` | GET | Ver la cola actual |
| `/history` | GET | Historial de ejecuciones |
| `/history/{prompt_id}` | GET | Resultado de una ejecución específica |
| `/view` | GET | Ver una imagen generada |
| `/upload/image` | POST | Subir una imagen de entrada |
| `/interrupt` | POST | Interrumpir la ejecución actual |
| `/system_stats` | GET | Estado de la GPU y sistema |
| `/object_info` | GET | Información de todos los nodos disponibles |
| `/object_info/{node}` | GET | Información de un nodo específico |

---

## Formato del Prompt (API)

Exportar el workflow en formato API desde el menú de ComfyUI:
`Settings → Dev Mode → Enable` luego `Export (API Format)`.

Estructura:
```json
{
  "prompt": {
    "3": {
      "class_type": "KSampler",
      "inputs": {
        "seed": 42,
        "steps": 20,
        "cfg": 7,
        "sampler_name": "euler",
        "scheduler": "normal",
        "denoise": 1,
        "model": ["4", 0],
        "positive": ["6", 0],
        "negative": ["7", 0],
        "latent_image": ["5", 0]
      }
    }
  },
  "client_id": "mi-app-id"
}
```

**Nota**: Las referencias entre nodos son `["node_id", output_index]`.

---

## Ejemplo en Python

```python
import json
import urllib.request
import urllib.parse
import random

SERVER = "127.0.0.1:8188"

def queue_prompt(workflow: dict, client_id: str) -> str:
    """Envía un workflow y devuelve el prompt_id."""
    payload = json.dumps({
        "prompt": workflow,
        "client_id": client_id
    }).encode()
    req = urllib.request.Request(
        f"http://{SERVER}/prompt",
        data=payload,
        headers={"Content-Type": "application/json"}
    )
    return json.loads(urllib.request.urlopen(req).read())["prompt_id"]

def get_history(prompt_id: str) -> dict:
    url = f"http://{SERVER}/history/{prompt_id}"
    return json.loads(urllib.request.urlopen(url).read())

def get_image(filename: str, subfolder: str, folder_type: str) -> bytes:
    params = urllib.parse.urlencode({
        "filename": filename,
        "subfolder": subfolder,
        "type": folder_type
    })
    url = f"http://{SERVER}/view?{params}"
    return urllib.request.urlopen(url).read()

# Cargar workflow exportado
with open("workflow_api.json") as f:
    workflow = json.load(f)

# Modificar parámetros dinámicamente
workflow["3"]["inputs"]["seed"] = random.randint(0, 2**32)
workflow["6"]["inputs"]["text"] = "a futuristic city at night"

client_id = "mi-cliente-001"
prompt_id = queue_prompt(workflow, client_id)
print(f"Generando: {prompt_id}")
```

---

## WebSocket para Progreso en Tiempo Real

```python
import websocket
import json

def on_message(ws, message):
    data = json.loads(message)
    if data["type"] == "progress":
        step = data["data"]["value"]
        total = data["data"]["max"]
        print(f"Progreso: {step}/{total}")
    elif data["type"] == "executing":
        node = data["data"]["node"]
        print(f"Ejecutando nodo: {node}")
    elif data["type"] == "executed":
        print("Generación completada")

ws = websocket.WebSocketApp(
    f"ws://{SERVER}/ws?clientId=mi-cliente",
    on_message=on_message
)
ws.run_forever()
```

---

## Subir Imágenes para img2img

```python
import requests

def upload_image(image_path: str, server: str = "127.0.0.1:8188") -> str:
    """Sube una imagen y devuelve el nombre asignado."""
    with open(image_path, "rb") as f:
        resp = requests.post(
            f"http://{server}/upload/image",
            files={"image": f},
            data={"overwrite": "true"}
        )
    return resp.json()["name"]

# Usar el nombre en el workflow
nombre = upload_image("mi_foto.jpg")
workflow["10"]["inputs"]["image"] = nombre
```

---

## Modificar Workflows Programáticamente

Patrón recomendado: usar un diccionario de IDs con nombres legibles:

```python
NODES = {
    "loader":    "4",   # CheckpointLoaderSimple
    "prompt_pos": "6",  # CLIPTextEncode positivo
    "prompt_neg": "7",  # CLIPTextEncode negativo
    "sampler":   "3",   # KSampler
    "latent":    "5",   # EmptyLatentImage
    "save":      "9",   # SaveImage
}

def set_prompt(workflow, positive, negative):
    workflow[NODES["prompt_pos"]]["inputs"]["text"] = positive
    workflow[NODES["prompt_neg"]]["inputs"]["text"] = negative

def set_size(workflow, width, height):
    workflow[NODES["latent"]]["inputs"]["width"] = width
    workflow[NODES["latent"]]["inputs"]["height"] = height

def set_seed(workflow, seed=-1):
    import random
    s = random.randint(0, 2**32) if seed == -1 else seed
    workflow[NODES["sampler"]]["inputs"]["seed"] = s
```

---

## ComfyUI como Backend en Aplicaciones

Patrón arquitectónico para apps:

```
[Tu App] ── POST /prompt ── [ComfyUI]
              WebSocket         ↓
         ──────────────── ejecuta workflow
         ── GET /history ── imagen lista
         ── GET /view    ── bytes de la imagen
```

### Librerías de terceros para simplificar
- **comfy-script**: API type-safe con autocompletado
- **comfyui-python-api**: wrapper simplificado
- **ComfyUI-Workflow-Runner**: ejecutor de workflows con variables

---

## Consejos para Producción

- Siempre usar `client_id` único por sesión
- Implementar polling del historial o WebSocket para saber cuándo termina
- Timeout recomendado: 5 minutos para SD1.5, 15 minutos para Flux
- Limpiar el historial periódicamente (`DELETE /history`)
- Para alta concurrencia: usar múltiples instancias de ComfyUI en diferentes puertos

---

*[← Entrenamiento LoRA](29-entrenamiento-lora.md) | [Siguiente: Guía para IA →](PARA-IA.md)*
