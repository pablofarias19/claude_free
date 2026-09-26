# 16 — AnimateDiff y Generación de Video

ComfyUI soporta generación de video/animación a través de AnimateDiff, Stable Video Diffusion y otros métodos.

---

## AnimateDiff

### ¿Qué es?
AnimateDiff es un módulo de movimiento que se inserta en cualquier modelo SD1.5 o SDXL y lo convierte en un generador de video frame-a-frame coherente.

**Clave**: AnimateDiff no genera cada frame independientemente. Usa **atención temporal** entre frames para mantener coherencia.

### Componentes
- **Motion Module**: el archivo de AnimateDiff (`mm_sd_v15_v2.ckpt`, etc.)
- **Motion LoRA** (opcional): controla el tipo de movimiento de cámara
- El checkpoint base de SD1.5 (o SDXL con SDXL-AnimateDiff)

### Instalación
```
ComfyUI/models/animatediff_models/   ← motion modules
ComfyUI/models/motion_lora/          ← motion LoRAs (opcional)
```
Custom node requerido: `ComfyUI-AnimateDiff-Evolved`

---

## Nodos de AnimateDiff

### ADE_AnimateDiffLoaderWithContext
Carga el motion module. Parámetros clave:
- `model_name`: el motion module a usar
- `beta_schedule`: `linear` (más suave) o `sqrt_linear` (más movimiento)

### ADE_UseEvolvedSampling
Remplaza al KSampler en workflows AnimateDiff. Parámetros:
- `motion_model`: del loader anterior
- `context_options`: ventana de frames que se ven simultáneamente

### ADE_AnimateDiffSettings
Configuración avanzada de motion:
- `pe_strength`: fuerza del position encoding temporal
- `attn_strength`: fuerza de la atención temporal

---

## Motion LoRAs

Controlan el movimiento de cámara generado:

| Motion LoRA | Movimiento |
|-------------|----------|
| v2_lora_PanLeft | Paneo izquierda |
| v2_lora_PanRight | Paneo derecha |
| v2_lora_TiltUp | Tilt hacia arriba |
| v2_lora_TiltDown | Tilt hacia abajo |
| v2_lora_ZoomIn | Zoom in |
| v2_lora_ZoomOut | Zoom out |
| v2_lora_RollingAnticlockwise | Rotación antihoraria |

Se pueden combinar varios con pesos distintos.

---

## Context Windows

AnimateDiff no puede procesar todos los frames a la vez (limitación de VRAM). Usa ventanas de contexto:

- `context_length`: cuántos frames se procesan juntos (16–32)
- `context_stride`: separación entre ventanas
- `context_overlap`: cuántos frames se comparten entre ventanas para suavizar

**Recomendación estándar**: context_length=16, stride=1, overlap=4

---

## Exportar el Video

Custom node requerido: `ComfyUI-VideoHelperSuite`

### VHS_VideoCombine
Combina los frames generados en un archivo de video:
- `frame_rate`: FPS del video (8–24)
- `format`: GIF, WebP, MP4, WebM
- `codec`: H264 (MP4), VP9 (WebM)
- `save_output`: si guardar en disco

---

## Stable Video Diffusion (SVD)

Modelo de Stability AI para convertir **una imagen en video** (imagen → video).

### Modelos
- `svd.safetensors` — 14 frames
- `svd_xt.safetensors` — 25 frames

```
models/checkpoints/svd.safetensors
```

### Nodo: ImageOnlyCheckpointLoader
Carga SVD. Genera solo a partir de una imagen, no de texto.

### Parámetros clave de SVD
- `motion_bucket_id` (1–255): intensidad del movimiento. 127 = moderado.
- `fps` (1–30): FPS objetivo interno
- `augmentation_level` (0–1): variación vs fidelidad a la imagen. 0 = más fiel.
- `min_cfg` / `max_cfg`: rango de guidance

---

## Wan2.1 y Hunyuan Video (modelos 2024-2025)

Nueva generación de modelos de video text-to-video y image-to-video:

### Wan2.1
- Soporta text-to-video e image-to-video
- Resoluciones: 480p y 720p
- Requiere 16–24 GB VRAM
- Custom node: `ComfyUI-WanVideoWrapper`

### Hunyuan Video
- Modelo de Tencent, alta calidad
- Muy pesado: ~60 GB FP16, ~13 GB FP8 quantizado
- Custom node: `ComfyUI-HunyuanVideoWrapper`

---

## Interpolación de Frames (RIFE)

Después de generar los frames, RIFE puede **interpolar** frames intermedios para aumentar los FPS:

- 8 FPS generados → 24 FPS con interpolación ×3
- Custom node: `ComfyUI-RIFE-VFI`
- Nodo: `RIFE VFI`

---

## Workflow Básico AnimateDiff

```
[Load Checkpoint SD1.5]
    ↓ MODEL, CLIP, VAE
[ADE_AnimateDiffLoaderWithContext] ← motion module
    ↓ MODEL con movimiento
[CLIP Text Encode +/-]
    ↓ CONDITIONING
[Empty Latent Image (batch_size=16 = 16 frames)]
    ↓ LATENT
[KSampler o ADE_UseEvolvedSampling]
    ↓ LATENT batch
[VAE Decode]
    ↓ IMAGE batch
[VHS_VideoCombine]
    ↓ video.mp4
```

---

*[← Flux Avanzado](15-flux-avanzado.md) | [Siguiente: Mejora de Rostros →](17-mejora-de-rostros.md)*
