# 50 — Keyframing Avanzado y Narrativa Visual en Video

> **DECLARACIÓN TÉCNICA PARA IA**: Este documento cubre técnicas avanzadas de keyframing para video en ComfyUI: schedules de prompt, interpolación de conditioning, Deforum-style motion, AnimateDiff con control de movimiento por keyframe, y pipelines de narrativa visual multi-escena. Configuraciones validadas para RTX 5080 (16 GB VRAM) con AnimateDiff y Wan2.1.

---

## Concepto: Keyframing en Diffusion

En video de difusión, un **keyframe** define el estado de la generación en un momento temporal específico. Entre keyframes, el sistema **interpola** el conditioning (texto, imagen, embeddings) para producir transiciones suaves.

```
Frame 0: "bosque tranquilo de día"      ← keyframe A
          ↕ interpolación lineal
Frame 24: "bosque al atardecer, niebla" ← keyframe B
          ↕ interpolación lineal
Frame 48: "bosque de noche, estrellas"  ← keyframe C
```

**Diferencia con video sin keyframes:**
- Sin keyframes: el prompt es constante en todos los frames
- Con keyframes: el conditioning evoluciona a lo largo del video

---

## ConditioningSetTimestepRange — keyframes en ComfyUI

Nodo fundamental para asignar un conditioning a un rango de pasos de sampling:

```
[CLIP Text Encode] "paisaje de día"
      │
[ConditioningSetTimestepRange]
  ├── conditioning: (texto_día)
  ├── start: 0.0   ← desde el inicio
  └── end: 0.5     ← hasta la mitad del proceso
      │
      └── cond_A

[CLIP Text Encode] "paisaje de noche"
      │
[ConditioningSetTimestepRange]
  ├── conditioning: (texto_noche)
  ├── start: 0.5
  └── end: 1.0
      │
      └── cond_B

[ConditioningConcat]
  ├── conditioning_1: cond_A
  └── conditioning_2: cond_B
      │
      └── → [KSampler]
```

> **Nota**: Este método controla cuándo en el **proceso de difusión** se aplica cada prompt, NO cuándo en el tiempo del video. Para keyframes temporales de video, ver sección AnimateDiff.

---

## Prompt Scheduling con Wildcards

Usar `comfyui-inspire-pack` o `comfyui-animatediff-evolved` para scheduling por frame:

### Formato de schedule

```
# Inspire Pack — PromptScheduleHookKeyframe
0: "cielo azul, nubes blancas"
12: "cielo naranja, atardecer"
24: "cielo púrpura, crepúsculo"
36: "cielo negro, estrellas"
```

### Nodo: PromptScheduleEncodeSDXL (Inspire Pack)

```
[PromptScheduleEncodeSDXL]
  ├── schedule_positive:
  │    "0: 'landscape, blue sky, day'
  │     16: 'landscape, sunset orange sky'
  │     32: 'landscape, night sky, stars'"
  ├── schedule_negative: "ugly, blurry" (constante)
  ├── clip
  ├── width: 1280
  ├── height: 720
  └── conditioning_out → [KSampler]
```

---

## AnimateDiff con Keyframes

### Motion Schedule

AnimateDiff permite controlar la **intensidad del movimiento** por frame:

```
[ADE_AnimateDiffLoaderWithContext] (AnimateDiff-Evolved)
      │
[ADE_AnimateDiffKeyframe]
  ├── start_percent: 0.0  ← frame inicial
  ├── motion_scale: 1.0   ← escala de movimiento
  └── ...
      │
[ADE_AnimateDiffKeyframe]
  ├── start_percent: 0.5
  ├── motion_scale: 0.5   ← reducir movimiento a mitad del video
  └── ...
      │
[ADE_AnimateDiffKeyframe]
  ├── start_percent: 0.8
  ├── motion_scale: 1.5   ← acelerar al final
  └── ...
```

### ControlNet por keyframe en AnimateDiff

```
Frame 0: pose A (persona de pie)
Frame 16: pose B (persona en movimiento)
Frame 32: pose C (persona sentada)

[Load Image batch] × 3 poses
      │
[DWPose Estimation] × 3
      │
[ADE_ControlNetKeyframeNodeInterpolation]
  ├── pose_0_strength: 1.0, start=0.0,  end=0.3
  ├── pose_1_strength: 1.0, start=0.3,  end=0.7
  └── pose_2_strength: 1.0, start=0.7,  end=1.0
      │
[Apply ControlNet] con las poses interpoladas
      │
[AnimateDiff KSampler]
```

---

## Wan2.1 con Prompt Conditioning por Escena

Para Wan2.1 (no usa AnimateDiff), el control por keyframe se hace a nivel de prompt:

```yaml
# Wan2.1 Text-to-Video con narrativa en el prompt
# La descripción debe ser cinematográfica y progresiva

prompt_ejemplo: |
  "Opening shot: serene forest clearing with morning mist.
  The camera slowly pans forward as sunlight begins to pierce through
  the canopy, birds taking flight from the branches.
  A deer steps into frame from the right, pausing to look at the camera.
  The scene gradually brightens as noon approaches."

# Parámetros para Wan2.1 (RTX 5080, 16 GB)
modelo: wan2.1-14b-fp8 o wan2.1-1.3b
frames: 81 (≈3.4 segundos a 24fps)
cfg: 6.0
steps: 25
sampler: dpm++ 2m
width: 1280
height: 720
```

> **CE-KF001**: Wan2.1 interpreta el prompt como una narrativa secuencial. Estructurar el prompt con escenas progresivas ("Opening shot:", "Then:", "Finally:") ayuda al modelo a generar movimiento temporal coherente.

---

## Deforum-Style: Movimiento de Cámara

Técnica para simular movimiento de cámara usando transformaciones de latent frame a frame:

### Movimiento de cámara como transformación

```
Pan izquierda:   [TranslateX: -5px/frame]
Pan derecha:     [TranslateX: +5px/frame]
Tilt arriba:     [TranslateY: -5px/frame]
Zoom in:         [Scale: +0.02/frame]
Zoom out:        [Scale: -0.02/frame]
Rotación:        [Rotate: +1°/frame]
```

### Custom nodes para motion

```
ComfyUI-VideoHelperSuite: transformación de imágenes frame a frame
ComfyUI-deforum: implementación completa de Deforum en ComfyUI
  Repositorio: https://github.com/XmYx/deforum-comfy-nodes

Nodos disponibles (deforum-comfy-nodes):
  DeforumKeyframeNode       ← definir keyframe de movimiento
  DeforumAnimatorNode       ← animar entre keyframes
  DeforumTransformNode      ← aplicar transformaciones 2D/3D
  DeforumNoiseNode          ← añadir noise scheduleado
```

### Workflow Deforum básico

```
[EmptyLatentImage] ← frame inicial
      │
[KSampler] ← prompt inicial
      │
[VAE Decode] → img_frame_0
      │
[DefromTransformNode]
  ├── image: img_frame_0
  ├── zoom: 1.02        ← zoom in gradual
  ├── translation_x: 5  ← pan derecha
  ├── translation_y: 0
  └── rotation: 0.5     ← leve rotación
      │
      └── img_transformada → [VAE Encode]
                                    │
                                [KSampler]
                                  ├── denoise: 0.7 ← parcial
                                  └── prompt: (puede cambiar)
                                        │
                               [VAE Decode] → img_frame_1
                               (loop por N frames)
```

> **CE-KF002**: El denoise en el loop Deforum define el balance coherencia/movimiento. Valores: 0.4-0.5 = muy coherente, poco cambio; 0.6-0.7 = balance; 0.8+ = mucho cambio, puede perder coherencia temporal.

---

## Interpolación de Frames con RIFE / FILM

Para aumentar la fluidez del video generado:

```
24 fps generados → interpolación × 2 → 48 fps efectivos
                 → interpolación × 4 → 96 fps efectivos

Nodos:
  RIFE (real-time) → VHS_RIFEInterpolation (VideoHelperSuite)
  FILM (Google)    → disponible via comfyui-film node
```

```yaml
# RIFE interpolation settings
multiplier: 2    # 2x frames
fastmode: True   # velocidad vs calidad
scale: 1.0
```

> **CE-KF003**: RIFE puede crear artefactos en cambios abruptos de contenido. Para videos con cortes/transiciones duras: aplicar RIFE solo en segmentos continuos, no en los cortes.

---

## Pipeline de narrativa visual multi-escena

Para videos más largos estructurados en escenas:

```
ESCENA 1 (frames 0-30):
  prompt: "intro, establishing shot"
  motion: lento, zoom out
  
ESCENA 2 (frames 30-60):
  prompt: "acción principal"
  motion: rápido, pan
  
ESCENA 3 (frames 60-90):
  prompt: "resolución, cierre"
  motion: lento, fade

Workflow:
[Generar N batches separados por escena]
      │
[VHS_VideoCombine] ← concatenar todos los batches
      │
[RIFE Interpolation] ← suavizar transiciones
      │
[Audio MusicGen] ← añadir música generada
      │
[VHS_VideoCombine final] ← video + audio
```

---

## Transiciones entre escenas

### CrossFade con LatentBlend

```
[Último frame escena A] → VAE Encode → latent_A
[Primer frame escena B] → VAE Encode → latent_B

[LatentBlend]
  ├── latent_A
  ├── latent_B
  ├── blend_factor: 0.0 → 1.0 (gradual en N frames)
  └── → frames de transición
      │
[KSampler] × N frames (denoise: 0.5)
      │
[VAE Decode] → frames_transicion
```

### Fade to Black / Fade from Black

```javascript
// Usando ImageColorHSV o brightness adjustment
// Frame 24 → Frame 30: reducir brillo de 1.0 a 0.0
// Frame 30 → Frame 36: aumentar brillo de 0.0 a 1.0
```

---

## Parámetros por modelo para video animado

| Modelo | Frames típicos | Steps | CFG | Notas clave |
|---|---|---|---|---|
| AnimateDiff SD1.5 | 16–24 | 20 | 7.0 | context_length=16 |
| AnimateDiff SDXL | 16 | 20 | 7.0 | Consume más VRAM |
| Wan2.1-1.3B | 49–81 | 25 | 6.0 | Rápido, RTX 5080 OK |
| Wan2.1-14B FP8 | 49–81 | 25 | 6.0 | Alta calidad, ~10 min |
| CogVideoX-2B | 49 | 50 | 6.0 | Coherente, eficiente |
| LTX Video | 97–161 | 25 | 3.0 | Muy eficiente en VRAM |
| SVD (img2vid) | 25 | 25 | — | Desde imagen, controlado |

---

## Configuración RTX 5080 para video largo

```yaml
# Perfil Pablo — video narrativo Wan2.1
modelo: wan2.1-14b-fp8.safetensors
frames: 81        # ~3.4 seg a 24fps
cfg: 6.0
steps: 25
batch: 1          # una escena a la vez
resolución: 1280x720

# Para video más largo (multi-escena):
estrategia: generar escenas por separado y concatenar
tamaño_escena: 49-81 frames
herramienta_concat: VHS_VideoCombine
transición: crossfade 6-12 frames

# AnimateDiff en RTX 5080
motion_module: mm_sdxl_v10_beta.ckpt
context_length: 16
context_stride: 1
context_overlap: 4
batch_size: 1 (SDXL) o 2 (SD1.5)
```

---

## Casos excepcionales

```
CE-KF001: Wan2.1 interpreta prompt como narrativa progresiva
  Estructurar con señales temporales: "opening", "then", "finally"
  Ver sección Wan2.1 con Prompt Conditioning

CE-KF002: Denoise en loop Deforum controla coherencia/cambio
  0.4-0.5: muy coherente / 0.6-0.7: balance / 0.8+: mucho cambio
  Para mantener identidad de personaje: máx 0.6

CE-KF003: RIFE con cortes abruptos genera artefactos
  Aplicar RIFE solo en segmentos continuos, no cruzar transiciones

CE-KF004: AnimateDiff context_length y VRAM
  context_length > 16: mayor coherencia pero exponencialmente más VRAM
  RTX 5080 SDXL: context_length=16 máximo seguro
  RTX 5080 SD1.5: context_length=24-32 posible

CE-KF005: ConditioningSetTimestepRange NO es keyframe temporal
  Controla el PASO de difusión, no el momento del video
  Para control temporal real en video: usar AnimateDiff prompt scheduling

CE-KF006: Concatenar videos con distinto estilo causa incoherencia visual
  Para multi-escena coherente: mismo modelo, mismo VAE, mismos parámetros
  Usar StyleAligned si se generan escenas en batch
```

---

## Recursos y fuentes

```
ANIMATEDIFF:
  AnimateDiff-Evolved:      https://github.com/Kosinkadink/ComfyUI-AnimateDiff-Evolved
  Modelos AnimateDiff:      https://huggingface.co/guoyww/animatediff

DEFORUM STYLE:
  deforum-comfy-nodes:      https://github.com/XmYx/deforum-comfy-nodes
  Deforum art:              https://deforum.art
  Paper Deforum:            https://deforum.github.io

INTERPOLACION:
  RIFE (VideoHelperSuite):  https://github.com/Kosinkadink/ComfyUI-VideoHelperSuite
  FILM (Google):            https://film-net.github.io

HERRAMIENTAS VIDEO:
  VHS (VideoHelperSuite):   https://github.com/Kosinkadink/ComfyUI-VideoHelperSuite
  FFmpeg:                   https://ffmpeg.org

MODELOS VIDEO:
  Wan2.1:                   https://huggingface.co/Wan-AI/Wan2.1-T2V-14B
  CogVideoX:                https://huggingface.co/THUDM/CogVideoX-2b
  LTX Video:                https://huggingface.co/Lightricks/LTX-Video
  SVD:                      https://huggingface.co/stabilityai/stable-video-diffusion-img2vid-xt

COMUNIDAD:
  Reddit r/comfyui:         https://www.reddit.com/r/comfyui/
  ComfyUI examples (video): https://comfyanonymous.github.io/ComfyUI_examples/video/
```
