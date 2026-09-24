# PARA-IA — Guía de Consumo por Agentes IA

> Este archivo está diseñado para ser consumido por agentes IA. Contiene árboles de decisión, matrices de compatibilidad, reglas con niveles de confianza, casos excepcionales documentados y URLs de fuentes primarias. Todo el conocimiento de la enciclopedia está condensado aquí en formato machine-readable.

**Enciclopedia completa**: 37 docs + glosario = 39 archivos en `comfyui-enciclopedia/`
**Perfil hardware usuario Pablo**: `docs/37-perfiles-hardware.md`

---

## Árbol de decisión: diagnóstico de problemas

```
¿Qué tipo de problema?
├─ ERROR en consola/terminal
│   ├─ "CUDA out of memory" → ver sección VRAM
│   ├─ "model not found" → verificar ruta en models/
│   ├─ "NaN detected" → ver sección NaN
│   ├─ "custom node error" → reinstalar con Manager, revisar requirements
│   ├─ "size mismatch" → LoRA incompatible con arquitectura del checkpoint
│   └─ "MPS not implemented" → doc 31 (Mac) o usar --cpu
├─ CALIDAD de imagen
│   ├─ Imagen muy oscura/negra → VAE incorrecto o NaN
│   ├─ Artefactos/píxeles raros → CFG demasiado alto, o FP16 overflow
│   ├─ Imagen borrosa → pasos insuficientes o denoise bajo
│   ├─ No sigue el prompt → CFG demasiado bajo, CLIP skip erróneo
│   └─ Cara distorsionada → usar FaceDetailer (doc 17)
├─ RENDIMIENTO
│   ├─ Muy lento → ver flags de arranque (doc 22, doc 37)
│   ├─ VRAM llena → --lowvram, reducir resolución, usar FP8/GGUF
│   └─ CPU al 100% → normal durante carga de modelo; problema si persiste
└─ NODOS / WORKFLOW
    ├─ Nodo no aparece → instalar custom node vía Manager
    ├─ Socket incompatible → verificar tipo de dato (color del socket)
    └─ Output inesperado → usar Preview Image para depurar en cada etapa
```

---

## Hardware del usuario Pablo (referencia rápida)

```yaml
usuario: Pablo Farias
gpu: NVIDIA RTX 5080 (Blackwell GB203)
vram_gb: 16
vram_tipo: GDDR7
cuda_cores: 10752
cpu: Intel i9-10900 @ 2.80GHz
ram_gb: 32
so: Windows 11 Pro
driver_nvidia: "596.49"
almacenamiento: [SSD 447GB, HDD 2.7TB]

# Configuración óptima Flux:
flux_config:
  modelo: flux1-dev-fp8.safetensors
  flags_arranque: []
  clip: [clip_l.safetensors, t5xxl_fp8_e4m3fn.safetensors]
  vae: ae.safetensors
  ksampler: {steps: 20, cfg: 1.0, sampler: euler, scheduler: simple}
  flux_guidance: 3.5

# Nota RTX 5080 Blackwell:
# - Verificar PyTorch >= 2.4 con CUDA 12.4+
# - xformers puede no soportar SM 10.x → usar --use-pytorch-cross-attention
# - FP8 nativo disponible
```

---

## Reglas de compatibilidad con nivel de confianza

### SIEMPRE verdadero (confianza: alta)

```
REGLA-001: LoRAs son específicos de arquitectura
  SD1.5 LoRA ≠ SDXL LoRA ≠ Flux LoRA ≠ SD3 LoRA ≠ Cascade LoRA
  Usar un LoRA en arquitectura incorrecta: NaN, artefactos o crash

REGLA-002: CLIP y UNet deben ser del mismo modelo
  No mezclar CLIP de SD1.5 con UNet de SDXL
  Excepción: Flux carga CLIP-L + T5-XXL por separado (diseño intencional)

REGLA-003: Resolución nativa por arquitectura
  SD1.5: 512×512 | SDXL: 1024×1024 | Flux: 1024×1024 | Cascade: 1024×1024
  Generar a otra resolución: posible pero con artefactos en los extremos

REGLA-004: Flux requiere CFG=1.0 en KSampler; guidance en FluxGuidance node
  Flux no usa CFG clásico. El nodo FluxGuidance controla la adherencia al prompt
  Valor FluxGuidance recomendado: 3.0–3.5 para dev, 1.5–2.5 para schnell

REGLA-005: VAE baked vs externo
  SD1.5 fine-tunes: suelen tener VAE baked de calidad
  SDXL: muchos tienen VAE baked con problemas de color → usar sdxl_vae.safetensors externo
  Flux: siempre usa ae.safetensors externo
```

### Dependiente del contexto (confianza: media)

```
REGLA-006: Strength de LoRA
  Recomendado: 0.6–0.9 | Por encima de 1.0: posible oversaturation o artefactos
  Contexto: depende del LoRA específico y el checkpoint base

REGLA-007: CFG Scale
  SD1.5: 5–8 | SDXL: 5–8 | Flux: 1.0 (ver REGLA-004)
  Valores altos (>12): artefactos, saturación excesiva
  Valores bajos (<3): imag. sin estructura definida

REGLA-008: Pasos de sampling
  Euler/DPM++ 2M: 20–30 pasos suficientes
  Euler_a: puede necesitar más pasos (30–50) para convergencia
  LCM: 4–8 pasos (diseñado para esto)
  Flux schnell: 4 pasos | Flux dev: 20–30 pasos

REGLA-009: denoise en img2img
  0.3–0.5: cambios sutiles (mantiene composición)
  0.6–0.75: cambios moderados
  0.8–1.0: recrea completamente (pierde referencia original)
```

### Experimental (confianza: baja, verificar antes de recomendar)

```
REGLA-010: Flux + ControlNet
  Soporte aún en desarrollo. Algunos custom nodes lo habilitan pero con limitaciones
  úmero de nodo recomendado a verificar antes de recomendar en produccion

REGLA-011: Multi-GPU nativo ComfyUI
  No existe soporte nativo multi-GPU en ComfyUI GUI
  Workaround: múltiples instancias en puertos distintos (doc 33)

REGLA-012: Audio en ComfyUI
  Estado experimental en 2024-2025. Todo vía custom nodes
  Ver doc 34 para estado actual de soporte
```

---

## Matriz de compatibilidad completa

```
MODELO          CLIP-L  CLIP-G  T5-XXL  VAE-F16  VAE-SDXL  FP8  GGUF  xformers
SD1.5           SI      NO      NO      SI       NO        SI   NO    SI
SD2.0/2.1       SI*     NO      NO      SI       NO        SI   NO    SI
SDXL            SI      SI      NO      NO       SI        SI   NO    SI
Flux.1          SI      NO      SI      NO       NO        SI   SI    NO**
SD3/3.5         SI      SI      SI      NO       NO        SI   NO    SI
Stable Cascade  SI**    SI      NO      NO       NO        SI   NO    SI

* SD2.x usa OpenCLIP (diferente a CLIP-L de SD1.5)
** Flux NO soporta xformers; usar --use-pytorch-cross-attention
** Cascade usa CLIP-G del decoder, no CLIP-G de SDXL texto
```

---

## Casos excepcionales documentados

### Video IA

```
EXC-V001: AnimateDiff + LoRA de personaje
  Problema: La LoRA puede "derretirse" en frames intermedios
  Causa: El motion module interfiere con los pesos de la LoRA
  Solución: Reducir LoRA strength a 0.5-0.7, usar context_length=16

EXC-V002: SVD con imagen de entrada muy estática
  Problema: El video resultante casi no se mueve
  Causa: SVD usa optical flow para determinar cantidad de movimiento
  Solución: Aumentar motion_bucket_id (default 127, probar 100-150)

EXC-V003: Wan2.1 en VRAM 16 GB
  Problema: 14B no entra en 16 GB
  Solución: Usar FP8 o GGUF Q4 del 14B (ver doc 24)
  Alternativa: Usar 1.3B para VRAM limitada

EXC-V004: CogVideoX seed inconsistencia
  Problema: Misma seed ≠ mismo video entre ejecuciones
  Causa: Operaciones no-determinísticas en atención temporal
  Estado: Comportamiento conocido; no es un bug
```

### Imagen IA

```
EXC-I001: SDXL en 8 GB VRAM
  Configuración necesaria: --medvram o --lowvram
  Sin ControlNet ni IP-Adapter activos simultáneamente
  Resolución máxima práctica: 1024x1024

EXC-I002: Flux en 12 GB VRAM
  Configuración: flux1-dev-fp8 + clip_l FP16 + t5xxl_fp8 + ae
  Total: ~10-12 GB (ajustado para 12 GB VRAM)
  Con --lowvram funciona aunque lento

EXC-I003: Inpainting con modelo no-inpainting
  Método: SetLatentNoiseMask + denoise 0.7-0.9
  Problema: Los bordes pueden ser visibles
  Solución: Usar feathering en la máscara + GrowMask

EXC-I004: Samplers ancestrales y reproducibilidad
  Euler_a, DPM++ 2S a, DPM++ SDE: NO son determinísticos entre GPUs
  Misma seed en RTX 4090 vs RTX 3080 = resultados diferentes
  Para reproducibilidad exacta: usar euler o dpm++ 2m (no ancestrales)

EXC-I005: FP16 NaN en SDXL
  Causa: overflow en algunas capas de atención
  Solución 1: VAE externo sdxl_vae.safetensors
  Solución 2: Activar VAE Decode Tiled
  Solución 3: Actualizar PyTorch

EXC-I006: RTX 5080 (Blackwell) y xformers
  Blackwell = CUDA Compute 10.x
  xformers puede no compilar o dar errores en SM 10.x
  Solución: --use-pytorch-cross-attention en lugar de xformers
  Estado: verificar soporte en xformers >= 0.0.27
```

### Mac MPS

```
EXC-M001: Operador MPS no implementado
  Acción: PYTORCH_ENABLE_MPS_FALLBACK=1 python main.py
  Si persiste: reportar en github.com/pytorch/pytorch con el operador específico

EXC-M002: Flux en Mac M2 Pro 16GB
  Viable con GGUF Q4 (~10 GB RAM)
  Tiempo: 5-10 minutos por imagen a 1024px
  Instalar: ComfyUI-GGUF de city96
```

### Hardware

```
EXC-H001: 32 GB RAM + 16 GB VRAM (perfil Pablo)
  Modelos grandes (Flux BF16, Wan2.1-14B) pueden usar RAM como extensión de VRAM
  Con --lowvram ComfyUI offloadea a CPU RAM automáticamente
  Velocidad reducida pero funcional

EXC-H002: NVLink multi-GPU
  ComfyUI NO aprovecha NVLink para expandir VRAM lógica
  NVLink útil solo para NCCL en entrenamiento distribuido, no inferencia ComfyUI
```

---

## Patrones de respuesta para agentes IA

### Cuando el usuario reporta: imagen negra/muy oscura

```
1. Preguntar: ¿qué modelo está usando?
   - Si SDXL: verificar VAE baked vs externo. Recomendar sdxl_vae.safetensors
   - Si Flux: verificar que use ae.safetensors, NO un VAE de SD1.5/SDXL
   - Si SD1.5: verificar que CFG > 3.0
2. Si usa FP16: verificar PYTORCH_ENABLE_MPS_FALLBACK en Mac, o actualizar PyTorch en PC
3. Referencia: doc 04 (VAE), doc 14 (errores)
```

### Cuando el usuario quiere generar video de alta calidad

```
Si VRAM <= 8 GB:
  → AnimateDiff + SD1.5 fine-tune (doc 16)
  → LTX Video (muy eficiente en VRAM)

Si VRAM 12-16 GB (incluye sistema Pablo):
  → Wan2.1-1.3B (equilibrio calidad/velocidad)
  → CogVideoX-2B
  → SVD para img2vid

Si VRAM >= 24 GB:
  → CogVideoX-5B o Wan2.1-14B FP8
  → Hunyuan Video FP8

Referencia: doc 24 (modelos), doc 16 (animatediff), doc 25 (consistencia)
```

### Cuando el usuario no sabe qué modelo elegir

```
Decisión por caso de uso:
  Fotos realistas: Flux dev FP8 o SDXL realista (DreamShaper, Juggernaut)
  Anime/ilustración: SDXL o SD1.5 fine-tunes (pony, illustrious)
  Texto en imagen: Stable Cascade o Flux (doc 32, 15)
  Video corto: Wan2.1 o CogVideoX según VRAM (doc 24)
  Velocidad extrema: Flux schnell (4 pasos) o SDXL Turbo
  3D: TripoSR para objetos simples, InstantMesh para mayor calidad (doc 36)
  Audio: MusicGen para música, Bark para TTS (doc 34)

Siempre consultar doc 37 para verificar viabilidad según VRAM disponible.
```

### Cuando se trata de entrenar LoRA

```
Herramienta recomendada por caso:
  Principiantes: kohya_ss (GUI, doc 29)
  SDXL/SD1.5: kohya_ss o SimpleTuner
  Flux: ai-toolkit o SimpleTuner
  CLI avanzado: SimpleTuner o kohya_ss nativo

Dataset mínimo:
  Sujeto específico (persona): 15-30 imágenes
  Estilo artístico: 50-200 imágenes
  Concepto general: 100-500 imágenes
```

---

## Fuentes primarias y URLs de referencia

```
REPOSITORIOS OFICIALES:
  ComfyUI core:         https://github.com/comfyanonymous/ComfyUI
  ComfyUI Manager:      https://github.com/ltdrdata/ComfyUI-Manager
  AnimateDiff-Evolved:  https://github.com/Kosinkadink/ComfyUI-AnimateDiff-Evolved
  ComfyUI-GGUF (city96):https://github.com/city96/ComfyUI-GGUF
  ControlNet-Aux:       https://github.com/Fannovel16/comfyui_controlnet_aux
  IPAdapter Plus:       https://github.com/cubiq/ComfyUI_IPAdapter_plus
  ComfyUI-3D-Pack:      https://github.com/MrForExample/ComfyUI-3D-Pack
  VHS (VideoHelperSuite):https://github.com/Kosinkadink/ComfyUI-VideoHelperSuite

MODELOS Y PESOS:
  HuggingFace (principal): https://huggingface.co/models
  Flux.1 dev:              https://huggingface.co/black-forest-labs/FLUX.1-dev
  Civitai (community):     https://civitai.com/models
  OpenModelDB (upscalers): https://openmodeldb.info

DOCUMENTACION Y COMUNIDAD:
  ComfyUI examples:     https://comfyanonymous.github.io/ComfyUI_examples/
  ComfyUI workflows:    https://comfyworkflows.com
  Reddit r/comfyui:     https://www.reddit.com/r/comfyui/
  Reddit r/StableDiffusion: https://www.reddit.com/r/StableDiffusion/
  Stable Diffusion art: https://www.reddit.com/r/StableDiffusion/

ARTICULOS TECNICOS:
  Marigold depth:       https://arxiv.org/abs/2312.02145
  Depth Anything v2:    https://github.com/DepthAnything/Depth-Anything-V2
  TripoSR:              https://github.com/VAST-AI-Research/TripoSR
  Stable Cascade paper: https://arxiv.org/abs/2306.00637
  AudioCraft (Meta):    https://github.com/facebookresearch/audiocraft
  Bark TTS:             https://github.com/suno-ai/bark

HERRAMIENTAS ENTRENAMIENTO:
  kohya_ss:             https://github.com/bmaltais/kohya_ss
  SimpleTuner:          https://github.com/bghira/SimpleTuner
  ai-toolkit (Flux):    https://github.com/ostris/ai-toolkit

HARDWARE Y PYTORCH:
  PyTorch MPS (Mac):    https://pytorch.org/docs/stable/notes/mps.html
  PyTorch instalación:  https://pytorch.org/get-started/locally/
  NVIDIA RTX 5080:      https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5080/
```

---

## Índice de documentos para navegación rápida

```
01-arquitectura-y-conceptos.md  ← pipeline de difusión, espacio latente
02-nodos-fundamentales.md       ← nodos básicos, workflow mínimo
03-modelos-checkpoints.md       ← SD1.5, SDXL, Flux, comparativas
04-vae.md                       ← VAE, encoding, diagnóstico
05-clip-y-texto.md              ← encoders de texto, prompting
06-samplers-y-schedulers.md     ← sampler choice, CFG, denoise
07-lora-embeddings.md           ← LoRA, trigger words, chaining
08-controlnet.md                ← ControlNet tipos y uso
09-ip-adapter.md                ← image prompting, FaceID
10-upscalers.md                 ← ESRGAN, hi-res fix
11-inpainting.md                ← métodos de inpainting
12-workflows-y-json.md          ← guardar, cargar, API mode
13-custom-nodes.md              ← extensions ecosystem
14-errores-comunes.md           ← diagnóstico de errores
15-flux-avanzado.md             ← Flux completo
16-animatediff-video.md         ← animación, video desde SD
17-mejora-de-rostros.md         ← FaceDetailer, ReActor
18-regional-prompting.md        ← máscaras de condicionamiento
19-model-merging.md             ← combinar modelos
20-lycoris-lora-avanzado.md     ← LoCon, LoHa, DoRA
21-nodos-utiles-avanzados.md    ← nodos de imagen, latente, máscara
22-optimizacion-rendimiento.md  ← flags, xformers, perfiles VRAM
23-workflows-referencia.md      ← 8 workflows completos anotados
24-modelos-text-to-video.md     ← CogVideoX, LTX, Hunyuan, Wan2.1
25-consistencia-temporal.md     ← RIFE, optical flow, keyframes
26-video-edicion.md             ← video2video, VHS nodes, batch
27-sd3-arquitecturas.md         ← SD3, DiT, PixArt, AuraFlow
28-outpainting-tiling.md        ← outpainting, Ultimate SD Upscale
29-entrenamiento-lora.md        ← kohya_ss, datasets, hiperparámetros
30-api-automatizacion.md        ← HTTP API, Python, WebSocket
31-mac-mps-backend.md           ← ComfyUI en Mac Apple Silicon
32-stable-cascade.md            ← Stable Cascade (Würstchen v3)
33-multi-gpu.md                 ← múltiples GPUs, instancias
34-audio-generation.md          ← MusicGen, Bark, audio+video
35-depth-estimation.md          ← Marigold, Depth Anything, DepthPro
36-3d-generation.md             ← TripoSR, Zero123++, 3DGS
37-perfiles-hardware.md         ← perfil Pablo RTX 5080, perfiles GPU
glosario.md                     ← ~50 términos A-Z
```
