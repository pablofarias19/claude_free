# 48 — IP-Adapter v2 y Multi-Referencia Avanzado

> **DECLARACIÓN TÉCNICA PARA IA**: Este documento cubre IP-Adapter v2 (Plus, FaceID, Full-Face, SDXL), técnicas de multi-referencia, composición de estilo + identidad simultánea, y workflows avanzados de control de imagen. Incluye configuraciones validadas para RTX 5080 (16 GB VRAM). El repositorio de referencia es `cubiq/ComfyUI_IPAdapter_plus`.

---

## ¿Qué es IP-Adapter?

**IP-Adapter** (Image Prompt Adapter) condicionará el modelo de difusión usando **imágenes como referencia** en lugar de (o además de) texto. El resultado es que el modelo "imita" el contenido visual de la imagen de referencia.

```
TEXTO → CLIP Text Encoder → conditioning
IMAGEN → CLIP Vision Encoder → image_embeds → IP-Adapter → conditioning

Ambos condicionamientos se combinan en el KSampler
```

**Versiones principales:**

| Versión | Arquitectura | Uso principal |
|---|---|---|
| ip-adapter | SD1.5 | Referencia básica de estilo/contenido |
| ip-adapter-plus | SD1.5 | Mayor fidelidad a la imagen referencia |
| ip-adapter-plus-face | SD1.5 | Solo extrae rostro de referencia |
| ip-adapter-full-face | SD1.5 | Identidad máxima (vs PhotoMaker) |
| ip-adapter_sdxl | SDXL | Referencia básica para SDXL |
| ip-adapter-plus_sdxl_vit-h | SDXL | Mayor fidelidad, SDXL |
| ip-adapter-faceid-plus | SD1.5/SDXL | FaceID con insightface |
| ip-adapter-faceid-plusv2 | SD1.5/SDXL | FaceID v2, mayor expresividad |

---

## Instalación y modelos

### Custom node

```
Repositorio: https://github.com/cubiq/ComfyUI_IPAdapter_plus
Instalación: ComfyUI Manager → buscar "IPAdapter Plus" → instalar
```

### Estructura de carpetas

```
models/
├── ipadapter/
│   ├── ip-adapter_sd15.safetensors
│   ├── ip-adapter-plus_sd15.safetensors
│   ├── ip-adapter-plus-face_sd15.safetensors
│   ├── ip-adapter-full-face_sd15.safetensors
│   ├── ip-adapter_sdxl_vit-h.safetensors
│   ├── ip-adapter-plus_sdxl_vit-h.safetensors
│   └── ip-adapter-faceid-plusv2_sdxl.bin   ← FaceID SDXL
├── clip_vision/
│   ├── clip-vit-large-patch14.safetensors  ← SD1.5
│   └── clip-vit-h-14-laion2B.safetensors   ← SDXL
└── insightface/                             ← para FaceID
    └── models/
        └── buffalo_l/
```

### Descargar modelos clave

```bash
# SD1.5 básico
huggingface-cli download h94/IP-Adapter models/ip-adapter_sd15.safetensors --local-dir models/ipadapter

# SDXL plus
huggingface-cli download h94/IP-Adapter sdxl_models/ip-adapter-plus_sdxl_vit-h.safetensors --local-dir models/ipadapter

# CLIP Vision (necesario para todos)
huggingface-cli download openai/clip-vit-large-patch14 --local-dir models/clip_vision

# FaceID SDXL
huggingface-cli download h94/IP-Adapter-FaceID ip-adapter-faceid-plusv2_sdxl.bin --local-dir models/ipadapter
```

---

## Nodos principales en IPAdapter Plus

```
IPAdapterModelLoader      ← carga el modelo IP-Adapter
IPAdapterUnifiedLoader    ← loader unificado (recomendado)
IPAdapter                 ← aplica IP-Adapter al modelo
IPAdapterAdvanced         ← más control (recomendado)
IPAdapterEmbeds           ← trabaja con embeddings directamente
IPAdapterFaceID           ← específico para FaceID
IPAdapterCombineEmbeds    ← combinar múltiples referencias
IPAdapterStyleComposition ← separar estilo vs composición (v2)
PrepImageForClipVision    ← preprocesamiento de imagen referencia
```

---

## Workflow básico IP-Adapter

```
[Load Checkpoint]
      │
      ├─ model ──────────────────────────────────┐
      │                                           │
[CLIP Text Encode] → positive                    │
[CLIP Text Encode] → negative                    │
                                                  │
[IPAdapterUnifiedLoader]                          │
  ├── preset: "PLUS (high strength)" ← SD1.5 plus│
  └── model ──────────────────────────────────►  │
                                                  ▼
[Load Image] → [PrepImageForClipVision]    [IPAdapterAdvanced]
                        │                     ├── model (de Unified)
                        └── image ──────────► ├── image (referencia)
                                              ├── weight: 0.8
                                              ├── weight_type: "linear"
                                              └── model_out ──► [KSampler]
                                                                    │
                                                              [VAE Decode]
```

---

## IPAdapterUnifiedLoader — presets

```
SD 1.5:
  "LIGHT - SD1.5 Only (low strength)"      ← referencia suave
  "STANDARD (medium strength)"              ← uso general
  "VIT-G (medium strength)"                 ← alternativa VIT-G
  "PLUS (high strength)"                    ← alta fidelidad ← recomendado
  "PLUS FACE (portraits)"                   ← para rostros
  "FULL FACE - SD1.5 Only (portrait)"       ← máxima fidelidad facial

SDXL:
  "STANDARD (medium strength)"
  "PLUS (high strength)"                    ← recomendado SDXL
  "PLUS FACE (portraits)"
```

---

## weight_type — cómo se aplica el peso

```
"linear"           → peso constante en todo el proceso (default)
"ease in"          → empieza bajo, sube al final (composición primero, detalles al final)
"ease out"         → empieza alto, baja (estructura inicial fuerte, libertad al final)
"ease in-out"      → curva suave, inicio y fin bajos, pico en medio
"reverse linear"   → inverso de linear
"weak input"       → baja influencia general
"strong input"     → alta influencia general
"style transfer"   → optimizado para transferencia de estilo ← útil
"composition"      → optimizado para copiar composición/layout
"style transfer precise" → más preciso que style transfer
```

> **CE-IPA001**: `weight_type = "style transfer"` es especialmente útil para tomar el estilo artístico de una imagen sin copiar el contenido. Combinado con un prompt descriptivo del nuevo sujeto.

---

## Multi-Referencia avanzado

### Método 1: IPAdapterCombineEmbeds

Combinar embeddings de múltiples imágenes en uno solo:

```
[Imagen A] → [PrepImageForClipVision] → [IPAdapterEncoder] → embed_A
[Imagen B] → [PrepImageForClipVision] → [IPAdapterEncoder] → embed_B
[Imagen C] → [PrepImageForClipVision] → [IPAdapterEncoder] → embed_C

[IPAdapterCombineEmbeds]
  ├── embed1: embed_A (weight: 0.5)
  ├── embed2: embed_B (weight: 0.3)
  ├── embed3: embed_C (weight: 0.2)
  ├── method: "concat" ← o "add", "subtract"
  └── combined_embed ──► [IPAdapterEmbeds]
```

**Métodos de combinación:**

```
"concat"      → concatenar (preserva características de cada imagen)
"add"         → suma de embeddings (combinación suave)
"subtract"    → A - B (quitar características de B desde A)
"average"     → promedio simple
"norm average"→ promedio normalizado (recomendado para 3+ imágenes)
```

### Método 2: IPAdapterBatch (múltiples adapters en cadena)

```
[modelo] ──► [IPAdapterAdvanced] ──► [IPAdapterAdvanced] ──► [KSampler]
                   ├── img_A               ├── img_B
                   ├── weight: 0.7          ├── weight: 0.5
                   └── weight_type:         └── weight_type:
                       "style transfer"         "composition"
```

> **CE-IPA002**: Encadenar IP-Adapters consume VRAM acumulativamente. En RTX 5080 (16 GB): hasta 3 adapters en SDXL sin problemas. Con 4+ reducir resolución o usar `--medvram`.

---

## IPAdapterStyleComposition (v2)

Técnica avanzada que separa la influencia en **estilo** (colores, texturas, paleta) y **composición** (layout, estructura, formas):

```
[IPAdapterStyleComposition]
  ├── model
  ├── image: referencia_imagen
  ├── style_weight: 1.0     ← cuánto tomar el estilo artístico
  ├── composition_weight: 1.0 ← cuánto copiar la composición/layout
  ├── combine_embeds: "average"
  └── model_out ──► [KSampler]
```

**Casos de uso:**

```yaml
Caso: Mismo estilo, diferente composición
  style_weight: 1.0
  composition_weight: 0.0
  → Toma paleta/textura, ignora layout

Caso: Misma composición, diferente estilo
  style_weight: 0.0
  composition_weight: 1.0
  → Copia estructura/posición, ignora colores

Caso: Estilo + composición balanceados
  style_weight: 0.7
  composition_weight: 0.5
  → Influencia mixta

Caso: Estilo desde imagen A, composición desde imagen B
  # Requiere dos nodos IPAdapterStyleComposition encadenados
  Nodo 1: style_weight=1.0, composition_weight=0.0, img=A
  Nodo 2: style_weight=0.0, composition_weight=1.0, img=B
```

---

## IP-Adapter FaceID v2

Especializado en preservación de identidad facial, requiere **InsightFace** para extracción de embeddings faciales:

### Instalación InsightFace

```bash
pip install insightface onnxruntime-gpu
# Modelos buffalo_l se descargan automáticamente en:
# models/insightface/models/buffalo_l/
```

### Workflow FaceID

```
[Load Image] (foto persona)
      │
[IPAdapterFaceID]
  ├── model: ip-adapter-faceid-plusv2_sdxl.bin
  ├── image
  ├── weight: 0.8          ← identidad principal
  ├── weight_faceidv2: 1.0 ← activar v2
  └── ...
      │
[IPAdapterAdvanced] (opcional — añadir referencia de estilo)
  ├── model (desde FaceID)
  ├── image: referencia_estilo
  ├── weight: 0.5
  └── weight_type: "style transfer"
      │
[KSampler]
```

### Parámetros FaceID

```yaml
# FaceID SDXL óptimo (perfil Pablo RTX 5080)
modelo: ip-adapter-faceid-plusv2_sdxl.bin
weight: 0.8
weight_faceidv2: 1.0
combine_embeds: "average" (para múltiples fotos de referencia)
start_at: 0.0
end_at: 1.0  # reducir a 0.7-0.8 para más creatividad

# Comparativa vs otras técnicas de identidad
# FaceID:    fidelidad media-alta, flexible en postura
# InstantID: fidelidad alta, requiere imagen de referencia de buena calidad
# PuLID:     fidelidad alta, específico para Flux
```

---

## Técnicas de control fino

### Temporal: start_at / end_at

```yaml
# Aplicar IP-Adapter solo en parte del proceso de difusión
start_at: 0.0  # inicio de la difusión
end_at: 1.0    # fin de la difusión

# Ejemplos de uso:
# Guiar estructura inicial, libertad al final:
start_at: 0.0, end_at: 0.5

# Solo refinamiento de detalles (dejar estructura libre):
start_at: 0.5, end_at: 1.0

# Aplicar estilo en mitad del proceso:
start_at: 0.2, end_at: 0.8
```

### Attn mask — enmascarar zona de influencia

```
[IPAdapterAdvanced]
  ├── ...
  ├── attn_mask: (imagen máscara opcional)
  └── ...

# La máscara limita DÓNDE aplica el IP-Adapter
# Blanco: zona donde el IP-Adapter es activo
# Negro: zona ignorada por el IP-Adapter
# Útil para: cambiar solo el fondo, o solo el sujeto
```

---

## Tabla de pesos recomendados por caso

| Caso de uso | weight | weight_type | start_at | end_at |
|---|---|---|---|---|
| Transferencia de estilo | 0.5–0.7 | style transfer | 0.0 | 1.0 |
| Copiar composición | 0.6–0.8 | composition | 0.0 | 0.5 |
| Identidad facial alta | 0.8–1.0 | linear | 0.0 | 0.8 |
| Influencia sutil | 0.3–0.5 | ease in | 0.1 | 0.9 |
| Referencia de color/paleta | 0.4–0.6 | style transfer | 0.0 | 0.7 |
| Multi-referencia (3 imgs) | 0.5/0.3/0.2 | linear | 0.0 | 1.0 |

---

## Configuración para RTX 5080 (16 GB VRAM)

```yaml
# Perfil Pablo — IP-Adapter SDXL
modelo_base: DreamShaperXL o JuggernautXL
ipadapter_model: ip-adapter-plus_sdxl_vit-h.safetensors
clip_vision: clip-vit-h-14-laion2B.safetensors
batch_size: 2  # seguro con SDXL + IPA
resolución: 1024x1024
weight: 0.75
weight_type: "style transfer"

# Con FaceID SDXL
ipadapter_model: ip-adapter-faceid-plusv2_sdxl.bin
weight: 0.8, weight_faceidv2: 1.0
insightface: buffalo_l (GPU)
batch_size: 1  # FaceID consume más VRAM

# Máximo viable en 16 GB (SDXL + IPA + ControlNet):
controlnet_strength: 0.7
ipadapter_weight: 0.6
resolución: 1024x1024 (no aumentar)
```

---

## Casos excepcionales

```
CE-IPA001: weight_type "style transfer" para estilo sin contenido
  Extraer solo paleta/textura de imagen referencia usando:
  weight_type="style transfer" + weight=0.5-0.7
  Con prompt que describe el nuevo sujeto explícitamente

CE-IPA002: Límite de IP-Adapters encadenados en VRAM
  SDXL: hasta 3 adapters en 16 GB (RTX 5080)
  SD1.5: hasta 5 adapters en 16 GB
  Síntoma de exceso: OOM durante la generación

CE-IPA003: InsightFace GPU vs CPU
  Por defecto InsightFace usa CPU (más lento pero sin VRAM extra)
  Para activar GPU: provider="CUDAExecutionProvider" en la config
  RTX 5080: puede usar GPU sin problema

CE-IPA004: IP-Adapter + LoRA — orden importa
  Aplicar LoRA PRIMERO al modelo, luego IP-Adapter
  LoRA después de IP-Adapter puede anular la influencia del adapter

CE-IPA005: FaceID v2 con múltiples fotos de referencia
  Pasar 2-5 fotos desde ángulos distintos mejora fidelidad
  Usar IPAdapterCombineEmbeds con method="norm average"
  Más de 5 fotos: ganancia marginal con costo VRAM

CE-IPA006: IP-Adapter en Flux — soporte limitado
  IPAdapter Plus no tiene soporte nativo para Flux (MMDiT)
  Usar PuLID para identidad facial en Flux
  Para estilo en Flux: usar LoRA de estilo o referencia en prompt textual
```

---

## Recursos y fuentes

```
REPOSITORIOS:
  IPAdapter Plus (cubiq):    https://github.com/cubiq/ComfyUI_IPAdapter_plus
  IP-Adapter original (Tencent): https://github.com/tencent-ailab/IP-Adapter
  FaceID models:             https://huggingface.co/h94/IP-Adapter-FaceID
  InsightFace:               https://github.com/deepinsight/insightface

MODELOS:
  IP-Adapter SD1.5 + SDXL:  https://huggingface.co/h94/IP-Adapter
  CLIP Vision ViT-H:         https://huggingface.co/laion/CLIP-ViT-H-14-laion2B-s32B-b79K
  CLIP Vision ViT-L:         https://huggingface.co/openai/clip-vit-large-patch14

PAPER TÉCNICO:
  IP-Adapter paper:          https://arxiv.org/abs/2308.06721
  IP-Adapter v2 improvements: https://ip-adapter.github.io

TUTORIALES Y COMUNIDAD:
  Guía oficial cubiq:        https://github.com/cubiq/ComfyUI_IPAdapter_plus/wiki
  Reddit r/comfyui:          https://www.reddit.com/r/comfyui/
  ComfyUI workflows:         https://comfyworkflows.com
```
