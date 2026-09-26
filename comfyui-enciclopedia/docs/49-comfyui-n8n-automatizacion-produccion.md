# 49 — ComfyUI + n8n: Automatización de Producción

> **DECLARACIÓN TÉCNICA PARA IA**: Este documento cubre la integración de ComfyUI con n8n para automatización de workflows de producción, incluyendo: activadores externos, procesamiento batch automatizado, integración con APIs externas, webhooks, notificaciones y pipelines completos. Incluye configuraciones para Windows 11 con RTX 5080 y patrones de arquitectura para flujos de producción real.

---

## ¿Por qué automatizar ComfyUI?

ComfyUI tiene una API HTTP REST completa que permite:
- Enviar workflows desde cualquier sistema externo
- Monitorear el progreso de generación
- Descargar resultados automáticamente
- Encadenar con otros sistemas (Telegram, Slack, Google Drive, bases de datos)

**n8n** es el orquestador de workflows: sin código (o con poco código), visual, open-source, con 400+ integraciones.

```
n8n                          ComfyUI API
─────────────────────────────────────────────────
[Trigger]                    POST /prompt
    │                            │
    │──── workflow JSON ─────────►│
    │                             │ processing...
    │◄─── queue ID ──────────────│
    │                             │
    │──── GET /history/{id} ─────►│
    │◄─── result + imagen ───────│
    │                             │
[Procesar resultado]
    │
[Enviar: Telegram / Drive / Slack / BD]
```

---

## Requisitos

### ComfyUI (API mode)

```bash
# Arrancar con API habilitada (está habilitada por defecto)
python main.py --listen 0.0.0.0 --port 8188

# Para acceso desde n8n en otra máquina o Docker:
# Asegurarse que el puerto 8188 esté accesible
# Windows: Firewall → permitir puerto 8188 para red local

# Verificar API activa:
curl http://localhost:8188/system_stats
```

### n8n

```bash
# Opción A: n8n en Docker (recomendado para producción)
docker run -d --name n8n -p 5678:5678 \
  -v ~/.n8n:/home/node/.n8n \
  n8nio/n8n

# Opción B: n8n desktop (Windows)
# Descargar desde: https://n8n.io/download

# Opción C: npx (desarrollo)
npx n8n
```

---

## Nodo HTTP Request en n8n — ComfyUI básico

### Paso 1: Enviar workflow a ComfyUI

```json
// Nodo HTTP Request en n8n
{
  "method": "POST",
  "url": "http://localhost:8188/prompt",
  "headers": {
    "Content-Type": "application/json"
  },
  "body": {
    "prompt": "{{ $json.workflow_json }}",
    "client_id": "n8n_automation_01"
  }
}
```

### Paso 2: Obtener resultado (polling)

```javascript
// Nodo Function en n8n
// Esperar a que termine la generación
const promptId = $input.first().json.prompt_id;
let attempts = 0;
const maxAttempts = 60; // máximo 5 minutos (60 × 5 seg)

while (attempts < maxAttempts) {
  const response = await $http.get(`http://localhost:8188/history/${promptId}`);
  const history = response.body;
  
  if (history[promptId] && history[promptId].status.completed) {
    const outputs = history[promptId].outputs;
    // Buscar el nodo SaveImage
    for (const nodeId in outputs) {
      if (outputs[nodeId].images) {
        return [{ json: { 
          prompt_id: promptId,
          images: outputs[nodeId].images 
        }}];
      }
    }
  }
  
  await new Promise(r => setTimeout(r, 5000)); // esperar 5 seg
  attempts++;
}
throw new Error('Timeout: generación tardó más de 5 minutos');
```

### Paso 3: Descargar imagen generada

```json
// Nodo HTTP Request (descarga imagen)
{
  "method": "GET",
  "url": "http://localhost:8188/view",
  "queryParameters": {
    "filename": "{{ $json.images[0].filename }}",
    "type": "output",
    "subfolder": "{{ $json.images[0].subfolder }}"
  },
  "responseFormat": "binary"
}
```

---

## Workflow n8n completo: Telegram → ComfyUI → Telegram

**Flujo:** usuario envía mensaje a bot Telegram → ComfyUI genera imagen → bot responde con imagen

```
[Telegram Trigger] ← usuario envía texto
      │
      │ mensaje: "un gato en estilo anime"
      ▼
[Function Node] ← construir workflow JSON
      │
      │ workflow: {KSampler, prompt=mensaje, ...}
      ▼
[HTTP Request] → POST /prompt → ComfyUI
      │
      │ prompt_id
      ▼
[Wait/Polling] ← cada 5 seg hasta completar
      │
      │ filename
      ▼
[HTTP Request] → GET /view → imagen binaria
      │
      ▼
[Telegram Node] → enviar foto al usuario
```

### JavaScript para construir el workflow (Function Node)

```javascript
// Construir workflow mínimo con prompt del usuario
const userPrompt = $input.first().json.message.text;

const workflow = {
  "6": {
    "class_type": "CLIPTextEncode",
    "inputs": {
      "text": userPrompt + ", masterpiece, best quality",
      "clip": ["4", 1]
    }
  },
  "7": {
    "class_type": "CLIPTextEncode",
    "inputs": {
      "text": "ugly, blurry, low quality",
      "clip": ["4", 1]
    }
  },
  "4": {
    "class_type": "CheckpointLoaderSimple",
    "inputs": {
      "ckpt_name": "dreamshaper_xl.safetensors"
    }
  },
  "5": {
    "class_type": "EmptyLatentImage",
    "inputs": {
      "width": 1024,
      "height": 1024,
      "batch_size": 1
    }
  },
  "3": {
    "class_type": "KSampler",
    "inputs": {
      "model": ["4", 0],
      "positive": ["6", 0],
      "negative": ["7", 0],
      "latent_image": ["5", 0],
      "seed": Math.floor(Math.random() * 999999999),
      "steps": 20,
      "cfg": 7,
      "sampler_name": "dpm_2m",
      "scheduler": "karras",
      "denoise": 1.0
    }
  },
  "8": {
    "class_type": "VAEDecode",
    "inputs": {
      "samples": ["3", 0],
      "vae": ["4", 2]
    }
  },
  "9": {
    "class_type": "SaveImage",
    "inputs": {
      "images": ["8", 0],
      "filename_prefix": "n8n_"
    }
  }
};

return [{ json: { workflow_json: workflow } }];
```

---

## Workflows de producción avanzados

### Batch nocturno automático

**Caso:** generar 50 variaciones de un producto para catálogo, cada noche

```
[Schedule Trigger] ← 02:00 AM diariamente
      │
[Google Sheets] ← leer fila pendiente de producto
      │
[Function Node] ← construir workflow con datos de producto
      │
[HTTP Request × N] ← enviar 50 prompts en loop
      │
[Wait All] ← esperar todas las generaciones
      │
[Google Drive] ← subir imágenes a carpeta del producto
      │
[Gmail] ← notificar equipo con preview
      │
[Google Sheets] ← marcar fila como completada
```

### Queue inteligente con prioridades

```javascript
// n8n Function Node — ordenar cola por prioridad
const jobs = $input.all().map(item => item.json);

// Priorizar trabajos: high > medium > low
const sorted = jobs.sort((a, b) => {
  const priority = { high: 0, medium: 1, low: 2 };
  return priority[a.priority] - priority[b.priority];
});

// Verificar cola ComfyUI antes de enviar
const queueResponse = await $http.get('http://localhost:8188/queue');
const queueSize = queueResponse.body.queue_pending.length;

if (queueSize > 10) {
  // Queue llena, esperar
  return [{ json: { status: 'deferred', reason: 'queue_full' } }];
}

return sorted.map(job => ({ json: job }));
```

### Webhook receptor desde aplicación externa

```
[Webhook Trigger] ← recibe POST desde aplicación
      │
      │ { prompt, style, user_id, callback_url }
      ▼
[Switch Node] ← seleccionar workflow según style
  ├── "anime" → workflow_anime.json
  ├── "realistic" → workflow_realistic.json
  ├── "flux" → workflow_flux.json
  └── default → workflow_base.json
      │
[HTTP Request] → ComfyUI
      │
[Esperar resultado]
      │
[HTTP Request] → POST a callback_url con imagen
```

---

## Integración con bases de datos

### Guardar metadata de generaciones

```javascript
// n8n Function Node — preparar para PostgreSQL/MySQL
const metadata = {
  prompt_id: $json.prompt_id,
  user_prompt: $json.original_prompt,
  model: $json.model_used,
  steps: $json.steps,
  cfg: $json.cfg,
  seed: $json.seed,
  width: $json.width,
  height: $json.height,
  generation_time_ms: $json.end_time - $json.start_time,
  filename: $json.output_filename,
  created_at: new Date().toISOString()
};

return [{ json: metadata }];
```

### Galería con Airtable / Notion

```
[ComfyUI genera imagen]
      │
[Google Drive] ← subir imagen → obtener URL pública
      │
[Airtable / Notion] ← crear registro:
  - imagen: URL Google Drive
  - prompt: texto usado
  - parámetros: modelo, steps, cfg, seed
  - fecha: timestamp
  - tags: extraídos del prompt
```

---

## Monitoreo de sistema vía WebSocket

```javascript
// Nodo Code en n8n (o script externo) — WebSocket ComfyUI
const WebSocket = require('ws');
const ws = new WebSocket('ws://localhost:8188/ws?clientId=n8n_monitor');

ws.on('message', (data) => {
  const msg = JSON.parse(data);
  
  switch(msg.type) {
    case 'execution_start':
      console.log(`Generación iniciada: ${msg.data.prompt_id}`);
      break;
    case 'progress':
      const pct = Math.round(msg.data.value / msg.data.max * 100);
      console.log(`Progreso: ${pct}% (paso ${msg.data.value}/${msg.data.max})`);
      break;
    case 'execution_cached':
      console.log('Resultado desde caché');
      break;
    case 'executed':
      console.log('Generación completada');
      // Procesar outputs...
      break;
    case 'execution_error':
      console.error(`Error: ${msg.data.exception_message}`);
      break;
  }
});
```

---

## Configuración de producción recomendada (Windows 11)

```yaml
# Estructura de producción
comfyui_setup:
  puerto: 8188
  flags: ["--listen 0.0.0.0", "--preview-method auto"]
  output_dir: "D:/comfyui_output"  # HDD 2.7TB (perfil Pablo)
  input_dir: "D:/comfyui_input"

n8n_setup:
  modo: docker o desktop
  puerto: 5678
  datos: ~/.n8n/

firewall_windows:
  regla: "Permitir ComfyUI API en red local"
  puerto: 8188
  protocolo: TCP
  acción: Permitir entrada

# Variables de entorno n8n útiles
N8N_HOST: localhost
N8N_PORT: 5678
COMFYUI_URL: http://localhost:8188
COMFYUI_OUTPUT_PATH: D:/comfyui_output
```

---

## Patrones de error comunes

```
ERROR: Connection refused al Puerto 8188
  Causa: ComfyUI no está corriendo o no usa --listen 0.0.0.0
  Fix: verificar proceso ComfyUI activo, agregar flag --listen

ERROR: prompt_id no encontrado en history
  Causa: la generación falló silenciosamente
  Fix: consultar GET /history/{id} y revisar campo status.messages

ERROR: imagen binaria vacía desde /view
  Causa: filename o subfolder incorrectos
  Fix: inspeccionar el JSON completo de /history/{id}.outputs

ERROR: queue_remaining siempre > 0
  Causa: ComfyUI procesando pero muy lento
  Fix: reducir batch_size, verificar VRAM disponible (doc 37)

ERROR: n8n "timeout" en el wait loop
  Causa: generación tardó más que maxAttempts × intervalo
  Fix: aumentar maxAttempts o usar WebSocket en lugar de polling
```

---

## Casos excepcionales

```
CE-N8N001: Polling vs WebSocket
  Polling: simple, tolerante a desconexiones, añade latencia
  WebSocket: tiempo real, pero requiere manejo de reconexión
  Para producción: usar WebSocket con fallback a polling

CE-N8N002: Rate limit de llamadas a /prompt
  ComfyUI acepta múltiples prompts en cola
  No hay rate limit oficial, pero >100 en cola puede saturar memoria
  Para batch grande: enviar en grupos de 10-20 con pausa entre grupos

CE-N8N003: Output en subdirectory
  Si SaveImage usa filename_prefix con "/", crea subdirectorios
  Ejemplo: "n8n/user_123/img" → subfolder="n8n/user_123", filename="img_00001_.png"
  Usar la respuesta exacta de /history para construir la URL de /view

CE-N8N004: Seguridad de la API ComfyUI
  Por defecto: SIN autenticación
  Para exponer en internet: usar reverse proxy (nginx) con autenticación básica
  Nunca exponer el puerto 8188 directamente a internet sin protección
```

---

## Recursos y fuentes

```
N8N:
  Documentación oficial:    https://docs.n8n.io
  n8n community:            https://community.n8n.io
  Docker install:           https://docs.n8n.io/hosting/installation/docker/
  HTTP Request node:        https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-base.httprequest/

COMFYUI API:
  API reference:            https://github.com/comfyanonymous/ComfyUI/blob/master/server.py
  API examples:             https://github.com/comfyanonymous/ComfyUI/blob/master/script_examples/websockets_api_example.py
  WebSocket API:            https://github.com/comfyanonymous/ComfyUI/blob/master/script_examples/websockets_api_example_ws_images.py

INTEGRACIONES:
  ComfyUI API Python:       https://github.com/digitaljohn/comfyui-proofs
  ComfyUI API wrapper:      https://github.com/mskar/comfyui-py
  n8n + Stable Diffusion:   https://community.n8n.io/t/stable-diffusion-workflows/
```
