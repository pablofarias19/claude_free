# 26 — Edición de Video y Video-to-Video

Modificar, estilizar o transformar video existente con ComfyUI.

---

## Video-to-Video: el concepto

Video-to-video aplica el principio de img2img a cada frame de un video:
1. Cargar video → extraer frames
2. Procesar cada frame con img2img (+ conditioning)
3. Reensamblar frames en video

**Desafío principal**: mantener consistencia temporal entre frames (ver [doc 25](25-consistencia-temporal.md)).

---

## Cargar Video en ComfyUI

### Nodo: VHS_LoadVideo (VideoHelperSuite)

**Parámetros**:
- `video`: ruta al archivo de video
- `force_rate`: forzar FPS específico (0 = original)
- `force_size`: redimensionar al cargar
- `frame_load_cap`: máximo de frames a cargar (evita OOM)
- `skip_first_frames`: saltar N frames iniciales
- `select_every_nth`: cargar 1 de cada N frames (para reducir carga)

**Salidas**:
- `IMAGE`: batch de frames
- `frame_count`: número de frames
- `audio`: audio del video (si existe)

**Formatos soportados**: MP4, WebM, GIF, AVI, MOV (requiere ffmpeg instalado)

---

## Flujo Video-to-Video Básico

```
[VHS_LoadVideo] ── IMAGE batch (N frames)
        ↓
[VAEEncodeTiled] ── LATENT batch
        ↓
[KSampler denoise=0.5–0.7, batch_size=N]
        ↓
[VAEDecodeTiled] ── IMAGE batch procesado
        ↓
[VHS_VideoCombine fps=original]
        ↓
     video.mp4
```

**Nota**: usar `VAEEncodeTiled` y `VAEDecodeTiled` para no explotar VRAM con muchos frames.

---

## Estilización de Video

### Cambio de estilo completo
Usar un checkpoint de estilo diferente con denoise alto (0.7–0.9):
```
Video realista → checkpoint anime + denoise=0.75 → video anime
```

### Estilización con LoRA
Agregar un LoRA de estilo manteniendo la composición:
```
Video original → [Load LoRA estilo] + denoise=0.5 → video estilizado
```

### Style Transfer con IP-Adapter
Usar una imagen de referencia para transferir estilo frame a frame:
```
[Imagen de estilo] → IPAdapter weight=0.6
[Frame del video] → VAEEncode → KSampler denoise=0.5
```

---

## ControlNet en Video

Aplicar ControlNet a cada frame para preservar estructura mientras se cambia estilo:

```
[VHS_LoadVideo] ── IMAGE batch
        ├── [CannyEdgePreprocessor (batch)] ── imagen control
        └── [VAEEncode] ── LATENT

[ApplyControlNet] ── CONDITIONING modificado
        ↓
[KSampler denoise=0.7] ── LATENT
        ↓
[VAEDecode] ── IMAGE
        ↓
[VHS_VideoCombine]
```

---

## Inpainting en Video

Editar una zona específica de todos los frames:

1. Generar una máscara estática (si el objeto no se mueve)
2. O extraer máscara dinámica por frame (con SAM o segmentación)
3. Aplicar inpainting a cada frame en la zona enmascarada

### Máscara dinámica con SAM
```
[VHS_LoadVideo] ── IMAGE batch
        ↓
[SAMModelLoader + SAMPredictor] ── segmenta el objeto en cada frame
        ↓
     MASK batch ── [SetLatentNoiseMask] ── inpainting
```

---

## Audio con Video

### Preservar audio original
`VHS_VideoCombine` tiene parámetro `audio` — conectar el audio del `VHS_LoadVideo` para preservarlo.

### AudioReactiveAnimateDiff
Some custom nodes permiten sincronizar parámetros de animación con amplitud/frecuencia del audio. Buscar `ComfyUI-AudioReactive`.

---

## Herramientas de Video Post-Proceso

### VHS_VideoCombine
El nodo principal de exportación. Parámetros importantes:
- `frame_rate`: FPS del video de salida
- `loop_count`: repeticiones (0 = sin loop, útil para GIFs)
- `format`: GIF, WebP animado, MP4 (H264/H265), WebM (VP9)
- `save_output`: si guardar en `output/`
- `pingpong`: reproduce forward y backward (para loops seamless)

### VHS_DuplicateFrames
Repite frames N veces. Útil para hacer slow motion sin interpolación real.

### VHS_SelectEveryNthFrame
Subsamplea el video tomando 1 de cada N frames.

### VHS_SplitImages
Divide un batch de imágenes en grupos.

---

## Batch Size y VRAM

El mayor limitante en video es la VRAM. Estrategias:

| Frames | VRAM aprox. (SD1.5 512px) |
|--------|-------------------------|
| 16 frames | ~6 GB |
| 32 frames | ~10 GB |
| 64 frames | ~18 GB |

**Estrategia para videos largos**:
1. Cargar en chunks de 16–32 frames
2. Procesar cada chunk
3. Unir chunks al final con `VHS_VideoCombine` en loop

---

## UpScale de Video Completo

```
[VHS_LoadVideo 480p] ── IMAGE batch
        ↓
[Upscale Image Using Model (ESRGAN 4x)] ── IMAGE 1920p
        ↓
[VHS_VideoCombine]
```

Para preservar audio:
```
[VHS_LoadVideo] ── audio ──────────────────┬
                └─ IMAGE batch → upscale ──┤
                                     [VHS_VideoCombine con audio]
```

---

*[← Consistencia Temporal](25-consistencia-temporal.md) | [Siguiente: SD3 y Arquitecturas Modernas →](27-sd3-arquitecturas.md)*
