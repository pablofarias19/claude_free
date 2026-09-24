# 12 — Workflows y JSON

Un workflow es la configuración completa de todos los nodos y sus conexiones.

---

## ¿Qué es un Workflow?

Es un archivo `.json` que describe:
- Qué nodos existen
- Sus parámetros (valores configurados)
- Las conexiones entre ellos

---

## Guardar y Cargar Workflows

### Guardar
- Menú → **Save** (guarda como JSON en tu carpeta local)
- Menú → **Export** (exporta para compartir, puede incluir o excluir parámetros de API)

### Cargar
- Menú → **Load**
- **Arrastrar el JSON** directamente al canvas
- **Arrastrar una imagen PNG generada por ComfyUI**: las imágenes guardadas con ComfyUI incluyen el workflow embebido en los metadatos

---

## Estructura del JSON

El JSON de un workflow tiene esta estructura:

```json
{
  "nodes": [
    {
      "id": 4,
      "type": "CheckpointLoaderSimple",
      "inputs": [],
      "outputs": [
        {"name": "MODEL", "type": "MODEL"},
        {"name": "CLIP", "type": "CLIP"},
        {"name": "VAE", "type": "VAE"}
      ],
      "widgets_values": ["v1-5-pruned-emaonly.safetensors"]
    },
    ...
  ],
  "links": [
    [1, 4, 0, 6, 0, "MODEL"],
    ...
  ],
  "groups": [],
  "config": {}
}
```

- `nodes`: lista de todos los nodos con sus configuraciones
- `links`: conexiones entre nodos `[link_id, nodo_origen, salida_idx, nodo_destino, entrada_idx, tipo]`
- `widgets_values`: los valores de los parámetros en orden

---

## Modo API

ComfyUI tiene una API HTTP que permite enviar workflows programaticamente.

### Exportar para API
Menú → **Export (API Format)** — genera un JSON ligeramente diferente optimizado para la API.

### Endpoint principal
```
POST http://localhost:8188/prompt
{
  "prompt": { ...workflow en formato API... },
  "client_id": "tu-id-unico"
}
```

---

## Nodos de Utilidad para Workflows

### Note
Nodo de texto sin conexiones. Para documentar el workflow.

### Primitive
Nodo que expone un valor para conectar a múltiples parámetros. Útil para centralizar el control de seed, steps, etc.

### Reroute
Nodo de redirección para organizar cables sin cruces.

### Group
Agrupa nodos visualmente. Clic derecho en el canvas → **Add Group**.

---

## Workflows Predeterminados

ComfyUI incluye workflows de ejemplo:
- `default` — text-to-image mínimo
- `img2img` — imagen a imagen
- Menu → **Browse Templates** para más ejemplos

---

## Recursos de Workflows

- **OpenArt.ai** — gran colección de workflows para descargar
- **Civitai** — workflows compartidos por la comunidad (en sección Models → Workflows)
- **ComfyUI Examples** (repositorio oficial) — ejemplos básicos

---

*[← Inpainting](11-inpainting.md) | [Siguiente: Custom Nodes →](13-custom-nodes.md)*
