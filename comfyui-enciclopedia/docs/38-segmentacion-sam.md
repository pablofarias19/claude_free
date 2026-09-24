# 38 · Segmentación Avanzada — SAM, YOLO y Masks Inteligentes

> **DECLARACIÓN TÉCNICA PARA IA**: SAM (Segment Anything Model de Meta) permite crear máscaras precisas de objetos arbitrarios en ComfyUI sin entrenamiento previo. Se usa principalmente junto con Impact Pack para FaceDetailer y detección de objetos. YOLO (ultralytics) detecta bounding boxes que se convierten en máscaras. La cadena típica es: YOLO detecta → SAM segmenta fino → máscara resultante alimenta inpainting o ControlNet.

## SAM — Segment Anything Model

```
Desarrollador: Meta AI Research
Modelos disponibles:
  sam_vit_b_01ec64.pth    (ViT-B, 376 MB, rápido)
  sam_vit_l_0b3195.pth    (ViT-L, 1.2 GB, equilibrio)
  sam_vit_h_4b8939.pth    (ViT-H, 2.4 GB, máxima calidad)
  sam2_hiera_large.pt     (SAM2, 2024, mejor en video)

Ubicación en ComfyUI:
  models/sams/  ← carpeta específica para SAM
  models/ultralytics/  ← para modelos YOLO

HuggingFace: facebook/sam-vit-huge
Custom node: ComfyUI Impact Pack (incluye nodos SAM)
```

### Nodos SAM en Impact Pack

```
SAMLoader
  model_name: sam_vit_h_4b8939.pth
  device_mode: AUTO

SAMModelPredict / GroundingDinoSAMSegment
  sam_model: [SAMLoader]
  image: [imagen fuente]
  detection_hint: "center-1"  # modo de punto de prompt
  dilation: 0
  threshold: 0.93
  bbox_expansion: 0
  mask_hint_threshold: 0.7
```

## YOLO — Detección de Objetos

### Modelos YOLO disponibles para ComfyUI

| Modelo | Detecta | Tamaño | Uso principal |
|---|---|---|---|
| `face_yolov8n.pt` | Caras | 6 MB | FaceDetailer |
| `face_yolov8m.pt` | Caras | 50 MB | FaceDetailer alta precisión |
| `face_yolov8s.pt` | Caras | 22 MB | Equilibrio |
| `hand_yolov8n.pt` | Manos | 6 MB | Mejorar manos |
| `person_yolov8n-seg.pt` | Personas | 6 MB | Silueta persona |
| `bbox/hand_yolov8s.pt` | Manos (bbox) | 22 MB | Detección manos |

Descarga: `https://huggingface.co/Bingsu/adetailer` y `https://huggingface.co/ultralytics`

### Nodo UltralyticsDetectorProvider

```
UltralyticsDetectorProvider
  model_name: face_yolov8n.pt

BBOXDetectorForEach
  bbox_detector: [UltralyticsDetectorProvider]
  image: [imagen]
  threshold: 0.5       # Confianza mínima de detección
  dilation: 10         # Expansión de la bbox en píxeles
  crop_factor: 3.0     # Factor de zoom para el crop
  drop_size: 10        # Ignorar detecciones menores a N píxeles
  labels: "all"        # O especificar clase: "face", "hand"
```

## Workflow: SAM + YOLO para inpainting preciso

```
[Load Image] ──────────────────────────────────────────┐
      │                                                  │
[UltralyticsDetectorProvider]                           │
      │  face_yolov8n.pt                                │
      │                                                  │
[BboxDetectorSEGS]  ──────  detecciones de caras       │
      │                                                  │
[SAMDetectorSegmented]                                  │
      │  sam_model: ViT-H                               │
      │  Genera máscara precisa por cada cara           │
      │                                                  ↓
[DetailerForEach]  ←────────────────  [imagen original]
      │  guide_size: 512
      │  denoise: 0.4
      │  model: mismo checkpoint
      │
[Save Image]  ← imagen con caras mejoradas precisamente
```

## Grounding DINO — Segmentación por texto

Permite segmentar objetos describiendo con texto qué segmentar:

```
GroundingDinoModelLoader
  model_name: GroundingDINO_SwinT_OGC.pth  # o SwinB para mayor precisión

GroundingDinoSAMSegment
  grounding_dino_model: [GroundingDinoModelLoader]
  sam_model: [SAMLoader]
  image: [imagen]
  prompt: "dog"      # Texto del objeto a segmentar
  threshold: 0.3     # Sensibilidad de detección (0.1=liberal, 0.5=estricto)

# Resultado: imagen segmentada + máscara del objeto
```

**Ejemplo de prompts Grounding DINO:**
```
"person"           → segmenta personas
"car"              → segmenta autos
"background"       → segmenta el fondo (para eliminarlo)
"clothing"         → ropa de personas
"face . eyes"      → cara + ojos (separar con punto)
```

## BiRefNet — Eliminación de Fondo de Alta Calidad

```
Mejor que REMBG para:
  · Cabello fino
  · Bordes complejos (encaje, árboles)
  · Transparencias parciales

Modelos:
  BiRefNet                 → general
  BiRefNet-portrait        → personas/retratos
  BiRefNet-HR              → alta resolución

Custom node: ComfyUI-BiRefNet
git clone https://github.com/ZhengPeng7/BiRefNet-matting

Nodo:
  BiRefNetBackgroundRemoval
    image: [imagen entrada]
    model: BiRefNet
  Salida: [imagen sin fondo] + [máscara]
```

### Comparativa removers de fondo

| Herramienta | Calidad cabello | Velocidad | VRAM | Transparencias |
|---|---|---|---|---|
| REMBG (u2net) | Media | Muy rápida | < 1 GB | No |
| IS-Net | Buena | Rápida | ~2 GB | Parcial |
| BiRefNet | Excelente | Moderada | ~3 GB | Parcial |
| SAM + DINO | Excelente | Moderada | ~4 GB | Sí (con máscara) |
| BiRefNet-HR | Excepcional | Lenta | ~4 GB | Parcial |

## Operaciones avanzadas con máscaras

### Combinar máscaras

```
[MaskCombine] (AND lógico, intersección)
  mask1: [máscara cara]
  mask2: [máscara área dañada]
  → solo el área dañada dentro de la cara

[MaskComposite] (operaciones lógicas)
  destination: [máscara A]
  source: [máscara B]
  operation: add|subtract|multiply|and|or|xor
  x, y: desplazamiento

[InvertMask]  → invierte (lo que estaba fuera entra y viceversa)

[GrowMask]
  mask: [máscara]
  expand: 10    # píxeles de expansión (positivo=crecer, negativo=encoger)
  tapered_corners: true  # bordes suavizados en esquinas

[FeatherMask] → suavizar bordes de la máscara (gradiente)
  mask: [máscara]
  left, top, right, bottom: 10  # píxeles de feathering
```

### Máscara desde color

```
[ImageColorToMask]
  image: [imagen]
  color: 16711680  # Rojo = 0xFF0000 en decimal
  → máscara de los píxeles de ese color exacto

# Uso: crear máscaras dibujando en rojo en un editor externo
# y luego importar la imagen pintada
```

## SAM2 — Segmentación de Video

```
SAM2 mejoras sobre SAM1:
  · Seguimiento de objetos entre frames (temporal)
  · Segmentación de video coherente
  · Más rápido que SAM1

Modelo: sam2_hiera_large.pt, sam2_hiera_base_plus.pt

Workflow video:
  [VHS_LoadVideo] → frames
      ↓
  [SAM2VideoPredictor]  ← punto inicial del objeto
      prompt_frame: 0
      x, y: coordenadas del objeto en frame 0
      ↓
  [Máscaras por cada frame] → segmentación temporal consistente
```

## Casos excepcionales

1. **SAM con fondos muy similares al objeto**: SAM puede fallar cuando el objeto y el fondo tienen colores/texturas similares. Combinar con Grounding DINO para mejor localización inicial.
2. **Múltiples instancias del mismo objeto**: `BboxDetectorSEGS` devuelve todas las instancias detectadas. Usar `ImpactSEGSFilter` para seleccionar por área o posición.
3. **SAM en Mac MPS**: SAM ViT-H puede requerir `PYTORCH_ENABLE_MPS_FALLBACK=1`. SAM ViT-B es más estable en MPS.
4. **Máscaras de alta resolución**: SAM internamente procesa a 1024x1024. Para imágenes más grandes, usar `SAMDetectorCombined` con `detection_hint: "mask-points"` y coordenadas proporcionales.
5. **YOLO falsos positivos**: Si detecta objetos que no son caras (o caras donde no las hay), bajar `threshold` a 0.3 o subir a 0.7 según el caso. Verificar siempre con PreviewImage la máscara antes del inpainting.

## Recursos

- SAM original Meta: `https://github.com/facebookresearch/segment-anything`
- SAM2: `https://github.com/facebookresearch/segment-anything-2`
- Impact Pack (incluye SAM/YOLO): `https://github.com/ltdrdata/ComfyUI-Impact-Pack`
- Grounding DINO: `https://github.com/IDEA-Research/GroundingDINO`
- BiRefNet: `https://github.com/ZhengPeng7/BiRefNet`
- Modelos YOLO para ComfyUI: `https://huggingface.co/Bingsu/adetailer`
- Ultralytics YOLO v8: `https://github.com/ultralytics/ultralytics`
