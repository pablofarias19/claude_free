# 17 — Mejora de Rostros y Personas

Uno de los problemas más comunes en generación de imágenes: los rostros y manos salen mal. Esta sección cubre las soluciones.

---

## El Problema

Los modelos de difusión generan a resolución latente baja. Un rostro que ocupa 10% de una imagen de 1024px solo tiene ~30×30 píxeles de información latente. Con tan poca resolución, los detalles faciales (ojos, dientes, simetría) se degradan.

---

## FaceDetailer (Impact Pack)

El nodo más popular para mejorar rostros automáticamente.

### ¿Cómo funciona?
1. Detecta caras en la imagen con un detector (bbox)
2. Recorta cada cara con padding
3. Aplica inpainting/img2img sobre el recorte
4. Reinserta el resultado en la imagen original

### Nodo: `FaceDetailer`

**Entradas**:
- `image` — imagen con rostros a mejorar
- `model` — el checkpoint
- `clip` — CLIP
- `vae` — VAE
- `positive` / `negative` — conditioning
- `bbox_detector` — detector de caras (ultralytics)
- `sam_model` (opcional) — SAM para máscaras más precisas

**Parámetros clave**:
- `guide_size` — resolución a la que se procesa la cara (512 recomendado)
- `guide_size_for` — si guide_size es bbox o crop_region
- `max_size` — tamaño máximo del recorte
- `denoise` — qué tanto regenerar (0.35–0.55 recomendado)
- `feather` — suavizado de bordes al reinsertar
- `noise_mask` — si agregar máscara de ruido

### Modelos necesarios
```
ComfyUI/models/ultralytics/bbox/face_yolov8m.pt    ← detector de caras
ComfyUI/models/sams/sam_vit_b_01ec64.pth           ← SAM (opcional)
```

Instalar con ComfyUI Manager → Install Models.

---

## ADetailer (equivalente sin Impact Pack)

Algunos custom nodes implementan ADetailer directamente:
- `ComfyUI-Impact-Pack` incluye funcionalidad equivalente
- El concepto es idéntico: detect → crop → inpaint → merge

---

## Detectors disponibles en Impact Pack

| Modelo | Detecta |
|--------|--------|
| `face_yolov8m.pt` | Rostros humanos |
| `face_yolov8n.pt` | Rostros (más rápido, menos preciso) |
| `hand_yolov8s.pt` | Manos |
| `person_yolov8m-seg.pt` | Personas completas |
| `eyes_yolov8s.pt` | Ojos |

---

## Flujo Completo de Mejora

```
[Generación principal] → imagen 1024px
        ↓
[FaceDetailer denoise=0.4] → rostros mejorados
        ↓
[Upscale x2 con ESRGAN]
        ↓
[Segundo FaceDetailer denoise=0.3] → detalle final
        ↓
[Save Image]
```

---

## ReActor — Face Swap

Permite reemplazar el rostro generado por el de una imagen de referencia.

### Nodo: `ReActorFaceSwap`
- `input_image` — imagen donde está el rostro a reemplazar
- `source_image` — imagen con el rostro deseado
- `face_model` — modelo de detección (inswapper_128.onnx)
- `face_restore_model` — model de restauración (GFPGAN, CodeFormer)
- `face_restore_visibility` — qué tanto restaurar (0.5–1.0)
- `codeformer_weight` — si usas CodeFormer, balance fidelidad/calidad (0–1)

**Consideración ética**: solo usar con consentimiento de las personas involucradas.

---

## IPAdapter FaceID — Consistencia de Identidad

Más útil que face swap para workflows de generación: mantiene la identidad de un rostro a través de múltiples generaciones SIN reemplazar la cara a posteriori.

Ver [IP-Adapter](09-ip-adapter.md) sección FaceID.

---

## Mejora de Manos

Las manos son el segundo problema más común. Soluciones:

### 1. FaceDetailer con detector de manos
Mismo nodo, usar `hand_yolov8s.pt` como detector.

### 2. ControlNet OpenPose con manos
`DWPoseEstimator` puede extraer keypoints de manos. Usar ControlNet con la pose extraída para mantener posiciones correctas.

### 3. Prompt negativo agresivo
```
extra fingers, missing fingers, fused fingers, malformed hands, bad anatomy
```

### 4. Modelos fine-tuned
Algunos checkpoints modernos (Juggernaut XL v9+, RealVisXL v4+) tienen mejor anatomía de manos incorporada.

---

## Restauración con GFPGAN / CodeFormer

Modelos clásicos de restauración de rostros. Disponibles como nodos en algunos custom nodes:

- **GFPGAN**: rápido, buen balance
- **CodeFormer**: mayor fidelidad a la identidad original
- Se pueden usar independientemente del FaceDetailer

---

*[← AnimateDiff](16-animatediff-video.md) | [Siguiente: Regional Prompting →](18-regional-prompting.md)*
