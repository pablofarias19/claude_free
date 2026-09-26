# 08 — ControlNet

ControlNet permite controlar la composición, pose y estructura de la imagen generada usando una imagen de referencia transformada.

---

## ¿Qué es ControlNet?

Es una red neuronal adicional que se conecta al UNet y lo condiciona no solo con texto sino también con información espacial extraida de una imagen. Te permite decir: "genera esto, pero con esta pose" o "con esta profundidad" o "siguiendo estos bordes".

---

## Flujo básico

```
[Imagen de referencia]
        ↓
[Preprocessor] ───────────── imagen de control
        ↓
[Apply ControlNet] ── conditioning modificado ── [KSampler]
        ↑
[Load ControlNet]
```

---

## Tipos de ControlNet y sus Preprocessors

### Canny (bordes)
- **Preprocessor**: `CannyEdgePreprocessor`
- **Uso**: preservar contornos y siluetas exactas
- **Input**: cualquier imagen
- **Output**: imagen de bordes blancos sobre fondo negro

### Depth (profundidad)
- **Preprocessors**: `MiDaS-DepthMapPreprocessor`, `DPT-DepthMapPreprocessor`, `Zoe-DepthMapPreprocessor`
- **Uso**: preservar la distribución de profundidad (lejos/cerca)
- **Mejor para**: composiciones con perspectiva, arquitectura

### OpenPose (pose humana)
- **Preprocessor**: `OpenposePreprocessor`
- **Uso**: replicar poses corporales
- **Output**: esqueleto de puntos y líneas (keypoints)
- **Variantes**: DW Pose (más preciso), MediaPipe

### Normal Map
- **Preprocessor**: `BAE-NormalMapPreprocessor`
- **Uso**: preservar iluminación y volumen de superficies

### Lineart
- **Preprocessors**: `LineArtPreprocessor`, `LineArt-AnimePreprocessor`
- **Uso**: colorear bocetos o line art existentes

### Scribble
- **Preprocessor**: `ScribblePreprocessor`
- **Uso**: convertir garabatos simples en imágenes detalladas

### Segmentation (seg)
- **Preprocessor**: `OneFormer-COCO-SemSegPreprocessor`
- **Uso**: segmentación semántica (distinguir personas, cielo, edificios)

### Tile
- **Preprocessor**: generalmente la propia imagen
- **Uso**: añadir detalle durante upscaling sin cambiar composición
- Muy popular para el paso de refinamiento en hi-res fix

### IP2P (InstructPix2Pix)
- **Uso**: seguir instrucciones de edición en lenguaje natural

---

## Nodos Principales

### ControlNetLoader
Carga el modelo ControlNet desde `models/controlnet/`.

### Apply ControlNet
Aplica el ControlNet al conditioning.

**Entradas**:
- `positive` — conditioning positivo
- `negative` — conditioning negativo  
- `control_net` — del ControlNetLoader
- `image` — imagen de control (del preprocessor)
- `strength` — intensidad (0.0–1.5)
- `start_percent` — en qué step % empieza a aplicarse
- `end_percent` — en qué step % deja de aplicarse

---

## Parámetros Clave

### strength
- `0.3–0.5`: Influencia sutil, más libertad creativa
- `0.7–1.0`: Influencia estándar
- `>1.2`: Control extremo, puede degradar la calidad

### start_percent / end_percent
Permite que ControlNet influya solo durante ciertos steps:
- `start=0.0, end=0.5`: Solo durante la primera mitad (composición)
- `start=0.5, end=1.0`: Solo durante la segunda mitad (detalles)
- **Truco de calidad**: usar `end=0.85` en lugar de `1.0` suele dar mejores resultados en detalle

---

## Compatibilidad de Modelos ControlNet

| ControlNet | Compatible con |
|------------|---------------|
| ControlNet SD1.5 | Solo SD1.5 |
| ControlNet SDXL | Solo SDXL |
| T2I-Adapter | SD1.5 o SDXL según la versión |
| ControlNet Flux | Solo Flux (arquitectura diferente) |

---

## Dónde Descargar

- Hugging Face: `lllyasviel/ControlNet-v1-1` (SD1.5)
- Hugging Face: `diffusers/controlnet-canny-sdxl-1.0` (SDXL)
- Carpeta destino: `ComfyUI/models/controlnet/`

---

## Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| La imagen no respeta el control | `strength` demasiado baja | Subir a 0.7–1.0 |
| Imagen distorsionada | ControlNet de arquitectura incorrecta | Usar el modelo correcto para tu checkpoint |
| Preprocessor no disponible | Falta instalar custom nodes | Instalar `comfyui_controlnet_aux` |

---

*[← LoRA](07-lora-embeddings.md) | [Siguiente: IP-Adapter →](09-ip-adapter.md)*
