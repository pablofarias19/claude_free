# 51 — Flux: Trucos y Técnicas Avanzadas

> **DECLARACIÓN TÉCNICA PARA IA**: Este documento consolida TODOS los trucos, técnicas avanzadas y conocimiento no obvio sobre Flux.1 (dev y schnell) en ComfyUI. Cubre: configuración óptima RTX 5080, variantes de modelos, control de guidance, LoRAs, prompting avanzado, workflows, limitaciones conocidas y condiciones excepcionales. Hardware de referencia: NVIDIA RTX 5080 (16 GB VRAM GDDR7, Blackwell), Windows 11, PyTorch CUDA 12.4+.

---

## 1. Fundamentos que la mayoría ignora

### Flux NO es Stable Diffusion

Flux usa arquitectura **MMDiT** (Multimodal Diffusion Transformer) — fundamentalmente diferente a UNet:

```
SD/SDXL: UNet con cross-attention (texto → imagen separados)
Flux:     MMDiT donde texto e imagen se procesan juntos en los mismos bloques

Consecuencias prácticas:
  ✅ Entiende mejor composición espacial compleja
  ✅ Texto en imagen funciona bien (no como SD1.5/SDXL)
  ✅ Prompt largo con muchos detalles → los incorpora todos
  ❌ CFG clásico no funciona (usar FluxGuidance node)
  ❌ Weights con paréntesis (palabra:1.5) NO tienen efecto
  ❌ LoRAs de SD1.5/SDXL son incompatibles
  ❌ xformers NO compatible (usar --use-pytorch-cross-attention)
```

### Los dos encoders de texto

```
CLIP-L:  77 tokens, enfocado en conceptos visuales rápidos
T5-XXL:  ~512 tokens efectivos, descripción detallada y compleja

Flux usa AMBOS simultáneamente:
  CLIP-L → guía estructural inicial
  T5-XXL → detalles finos, relaciones espaciales, texto en imagen

Truco: poner los conceptos más importantes primero en el prompt.
T5-XXL es menos sensible al orden que CLIP-L, pero los primeros
tokens siguen teniendo mayor peso implícito.
```

---

## 2. Variantes de modelos y cuándo usar cada uno

| Modelo | Tamaño | Pasos óptimos | Uso ideal |
|---|---|---|---|
| flux1-dev.safetensors | 23.8 GB BF16 | 20-30 | Calidad máxima, producción |
| flux1-dev-fp8.safetensors | ~11.9 GB | 20-30 | **Recomendado RTX 5080** — calidad ≈ BF16 |
| flux1-schnell.safetensors | 23.8 GB BF16 | 4 | Prototipado rápido, iteración |
| flux1-schnell-fp8.safetensors | ~11.9 GB | 4 | **Prototipado en RTX 5080** |
| flux1-dev-bnb-nf4.gguf | ~6.5 GB | 20-30 | VRAM muy limitada (≤8 GB) |
| flux1-dev-Q4_K_S.gguf | ~6.7 GB | 20-30 | Alternativa GGUF calidad media |
| flux1-dev-Q8_0.gguf | ~12.4 GB | 20-30 | GGUF alta calidad |

> **CE-FL001 (RTX 5080 RECOMENDACIÓN)**: Usar `flux1-dev-fp8.safetensors` + `t5xxl_fp8_e4m3fn.safetensors` + `clip_l.safetensors` + `ae.safetensors`. Total VRAM: ~13-14 GB. Deja 2-3 GB para buffers. Sin flags especiales.

---

## 3. Configuración completa de nodos para RTX 5080

### Loader correcto: DualCLIPLoader + UNETLoader

```
[DualCLIPLoader]                    [UNETLoader]
  ├── clip_name1: clip_l.safetensors   ├── unet_name: flux1-dev-fp8.safetensors
  ├── clip_name2: t5xxl_fp8_e4m3fn    └── weight_dtype: fp8_e4m3fn
  └── type: "flux"
      │                                     │
      └─────── clip ────────────────────────┤
                                            │
                                      [VAELoader]
                                        └── ae.safetensors
                                              │
                                           [VAE]

[CLIPTextEncode] (positive) ─────────── clip ──► conditioning
[CLIPTextEncode] (negative, vacío) ──── clip ──► conditioning

[FluxGuidance]
  ├── conditioning: positive
  └── guidance: 3.5               ← ESTE es el control real de adherencia
      │
      └── conditioning_guided

[EmptySD3LatentImage]             ← usar este, NO EmptyLatentImage
  ├── width: 1024
  ├── height: 1024
  └── batch_size: 1

[KSampler]
  ├── model: (UNETLoader output)
  ├── positive: conditioning_guided
  ├── negative: conditioning (negativo vacío)
  ├── latent: (EmptySD3LatentImage)
  ├── seed: (número)
  ├── steps: 20
  ├── cfg: 1.0                    ← SIEMPRE 1.0 para Flux
  ├── sampler_name: "euler"
  ├── scheduler: "simple"
  └── denoise: 1.0

[VAEDecode]
  ├── samples: (KSampler)
  └── vae: (VAELoader)
      │
[SaveImage]
```

---

## 4. FluxGuidance — el verdadero control de Flux

### Escala de valores y efecto

```
guidance = 1.0 → muy libre, creativo, puede ignorar partes del prompt
guidance = 2.0 → libertad moderada
guidance = 3.0 → buen seguimiento del prompt, variedad decente
guidance = 3.5 → RECOMENDADO para dev: balance calidad/creatividad
guidance = 4.0 → alta adherencia al prompt
guidance = 5.0 → muy literal, puede saturar o rigidizar
guidance = 7.0+ → extremo: artefactos posibles, sobre-saturación

Para Flux schnell:
guidance = 2.0-2.5 → óptimo (schnell fue entrenado con menor guidance)
```

### Truco: guidance diferencial para composición

```
# Usar dos FluxGuidance en diferentes momentos:
# 1. Guidance alto al inicio (estructura)
# 2. Guidance bajo al final (detalles)

[ConditioningSetTimestepRange]
  start: 0.0, end: 0.5
  + [FluxGuidance] guidance: 4.5

[ConditioningSetTimestepRange]
  start: 0.5, end: 1.0
  + [FluxGuidance] guidance: 2.5

→ Estructura sólida + detalles creativos
```

---

## 5. Samplers y schedulers para Flux

### Combinaciones probadas

```
CALIDAD MÁXIMA (20-28 pasos):
  sampler: euler       + scheduler: simple    ← estándar, el más estable
  sampler: euler       + scheduler: normal    ← similar, ligeramente diferente
  sampler: dpm++ 2m    + scheduler: simple    ← más nitidez en detalles

VELOCIDAD (8-15 pasos):
  sampler: euler       + scheduler: simple    ← sigue siendo el mejor

PROTOTIPADO (4 pasos, solo schnell):
  sampler: euler       + scheduler: simple
  sampler: lcm         + scheduler: lcm       ← más rápido aún

EXPERIMENTAL:
  sampler: dpm++ 2m    + scheduler: beta      ← a veces genera variaciones interesantes
  sampler: euler_cfg_pp + scheduler: simple   ← para uso con CFG > 1 (ver CE-FL002)
```

> **CE-FL002 (TRUCO AVANZADO)**: `euler_cfg_pp` permite usar CFG > 1.0 en Flux de forma más estable que euler normal. Probar cfg=1.5-2.0 con `euler_cfg_pp` + guidance=3.5 para mayor adherencia al prompt sin artefactos.

---

## 6. Prompting avanzado para Flux

### Aprovechar T5-XXL

Flux con T5-XXL puede procesar descripciones muy detalladas y relacionales:

```
# SD1.5/SDXL prompt típico (limitado a 77 tokens, lista de keywords):
"beautiful woman, long hair, blue eyes, forest, sunlight, cinematic"

# Flux prompt aprovechando T5-XXL (descripción natural y compleja):
"A woman with long auburn hair and bright blue eyes stands at the edge 
of a misty forest. Dappled morning sunlight filters through the oak 
canopy above, casting golden patches on her white linen dress. 
She looks slightly to her left with a contemplative expression. 
The background shows ancient moss-covered trees fading into soft fog."
```

### El negativo en Flux

```
# El negativo en Flux tiene MUCHO MENOS efecto que en SD1.5/SDXL
# Opciones:

OPCIÓN A — Negativo vacío (más puro):
  negative_prompt: ""
  
OPCIÓN B — Negativo mínimo (para casos específicos):
  negative_prompt: "blurry, low quality, deformed"
  
OPCIÓN C — Sin FluxGuidance alta (guidance>4.0 amplifica el negativo):
  guidance=3.0-3.5 con negativo básico

# Lo que NO hace en Flux:
# - Remover objetos específicos eficientemente
# - Controlar estilo artístico negativamente
# - Funcionar con paréntesis de énfasis (ugly:1.5) NO tiene efecto
```

### Palabras clave de calidad que SÍ funcionan en Flux

```
# Para fotografía:
"shot on Sony A7R IV, 85mm f/1.4, shallow depth of field"
"RAW photo, 8K resolution, photorealistic"
"editorial photography, professional studio lighting"

# Para arte digital:
"concept art, Artstation trending, ultra detailed"
"digital painting, cinematic lighting, by [artista]"

# Para calidad técnica general:
"masterpiece, highly detailed, sharp focus"
"4K, ultra high resolution"

# Lo que NO funciona:
"(best quality:1.4), ((masterpiece))"  ← paréntesis de peso ignorados
```

---

## 7. Resoluciones y aspect ratios

### Resoluciones nativas óptimas para Flux

```
Flux fue entrenado con resoluciones variables. Funciona bien en:

CUADRADO:
  1024×1024  ← base, siempre funciona

RETRATO:
  768×1024   ← 3:4
  832×1152   ← 2:3 aproximado
  896×1152   ← ligeramente más ancho
  1024×1344  ← retrato largo

PAISAJE:
  1024×768   ← 4:3
  1152×832   ← más panorámico
  1344×768   ← aproximado 16:9
  1536×640   ← panorámico extremo

# Truco: para 16:9 exacto en calidad máxima:
  1280×720  ← Full HD (Flux lo maneja bien)
  1920×1080 ← puede generar VRAM OOM en 16GB; preferir 1280×720 o usar VAE Tiled

# CE-FL003: Resoluciones fuera de rango
# Flux puede generar a 512×512, pero aparecen artefactos de composición
# Resolución mínima recomendada: 768×768
# Resolución máxima en RTX 5080 sin VAE Tiled: ~1536×1536
```

---

## 8. LoRAs para Flux — todo lo que necesitas saber

### Dónde encontrar LoRAs Flux

```
CivitAI: filtrar por "Flux" en tipo de modelo
  https://civitai.com/models?types=LORA&baseModel=Flux.1+D

HuggingFace: buscar "flux lora"
  https://huggingface.co/models?search=flux+lora
  
Notables:
  Flux-dev-de-distill       ← LoRA que acelera a 8-12 pasos sin perder calidad
  Flux Turbo LoRA           ← 8 pasos, calidad alta
  Flux aesthetic             ← mejora estética general
  Flux realism               ← más fotorrealista
```

### Aplicar LoRA en Flux — diferencias con SDXL

```
Flux carga LoRA con el nodo estándar [Load LoRA]:
  model_strength: 0.7–1.0  ← igual que siempre
  clip_strength: 0.7–1.0   ← controla efecto en CLIP + T5

# Particularidad:
# LoRAs de tipo "character" para Flux a menudo requieren trigger words
# que describan características específicas del personaje en lenguaje natural

# Truco: probar el LoRA con guidance=3.0 antes que 3.5
# Algunos LoRAs son más sensibles a guidance alto
```

### Flux Turbo / aceleración (TRUCO)

```
# Flux-dev-de-distill LoRA (o similar):
steps: 8-12    (en vez de 20-28)
cfg: 1.0
guidance: 3.5
sampler: euler
scheduler: simple

# En RTX 5080: ~4-6 segundos por imagen a 1024×1024
# Calidad: 85-90% del Flux dev completo
```

---

## 9. ControlNet para Flux

### Estado actual (experimental pero funcional)

```
Modelos ControlNet para Flux disponibles:
  InstantX/FLUX.1-dev-Controlnet-Canny      ← canny edges
  InstantX/FLUX.1-dev-Controlnet-Depth      ← depth map
  XLabs-AI/flux-controlnet-collections      ← conjunto completo
  
Custom nodes necesarios:
  x-flux-comfyui: https://github.com/XLabs-AI/x-flux-comfyui
  flux-controlnet: integrado en algunos packs

Nodo: FluxControlNet (distinto a Apply ControlNet estándar)
```

### Workflow ControlNet Canny para Flux

```
[Load Image]
      │
[Canny Edge] (o CannyEdgeDetector)
      ├── low_threshold: 100
      └── high_threshold: 200
          │
          └── canny_image

[FluxControlNetLoader]
  └── ckpt_name: flux-dev-controlnet-canny.safetensors
      │
      └── controlnet

[ApplyFluxControlNet]  ← NOT el Apply ControlNet estándar
  ├── positive: (conditioning con FluxGuidance)
  ├── controlnet
  ├── image: canny_image
  ├── strength: 0.7
  ├── start_percent: 0.0
  └── end_percent: 0.85
      │
[KSampler] (cfg=1.0, sampler=euler, scheduler=simple)
```

> **CE-FL004**: Usar `ApplyFluxControlNet` NO `Apply ControlNet`. Los ControlNets de SD1.5/SDXL son incompatibles con Flux. Verificar que los modelos sean específicamente entrenados para Flux.1.

---

## 10. IP-Adapter y Flux

### PuLID — el IP-Adapter nativo de Flux

```
Modelo: pulid_flux_v0.9.1.safetensors (o más reciente)
Custom node: ComfyUI-PuLID-Flux-Enhanced
  https://github.com/cubiq/ComfyUI_PuLID_Flux

Instalación:
  Manager → Search "PuLID Flux" → instalar

Nodo: ApplyPulidFlux
  ├── model (UNETLoader output)
  ├── pulid_model (PulidFluxModelLoader)
  ├── eva_clip (PulidFluxEvaClipLoader)
  ├── face_analysis (InsightFaceLoader)
  ├── image: (foto referencia)
  ├── weight: 0.9-1.0
  ├── start_at: 0.0
  ├── end_at: 1.0
  └── model_out → KSampler
```

### VRAM para PuLID + Flux en RTX 5080

```yaml
# Configuración mínima viable en 16 GB:
flux_model: flux1-dev-fp8.safetensors     (~6 GB)
t5xxl: t5xxl_fp8_e4m3fn.safetensors      (~2.5 GB)
clip_l: clip_l.safetensors               (~0.2 GB)
vae: ae.safetensors                      (~0.3 GB)
pulid: pulid_flux_v0.9.1.safetensors     (~1.2 GB)
insightface: buffalo_l                   (~0.5 GB)
Total estimado: ~11 GB → OK para RTX 5080
batch_size: 1, resolución ≤ 1024×1024
```

---

## 11. Inpainting y img2img en Flux

### Flux Fill — inpainting nativo

```
Modelo dedicado: flux1-fill-dev.safetensors
  (NO usar flux1-dev para inpainting — usar el modelo Fill específico)
  
Custom node: https://github.com/jjkramhoeft/ComfyUI-Jjk-Nodes (FluxFill nodes)

Workflow básico:
[Load Image] + [Load Mask]
      │
[FluxFillLoader]
      │
[InpaintModelConditioning]
  ├── positive: prompt de lo que debe aparecer
  ├── negative: ""
  ├── vae: ae.safetensors
  ├── pixels: imagen original
  └── mask: máscara (blanco=inpaintar)
      │
[KSampler]
  ├── steps: 20-30
  ├── cfg: 1.0
  ├── guidance en FluxGuidance: 30.0  ← NOTA: Fill usa guidance alto
  └── denoise: 1.0
```

> **CE-FL005**: Flux Fill usa un rango de guidance DIFERENTE. Probar guidance=28-35 (en vez de 3.5). Esto es correcto y esperado para el modelo Fill.

### img2img estándar en Flux dev

```
# Flux dev NO tiene modelo inpainting dedicado para img2img general
# Usar [VAE Encode] + denoise reducido:

[VAE Encode]
  └── image: imagen_referencia → latent

[KSampler]
  ├── latent: (VAE Encode)
  ├── denoise: 0.5-0.75   ← controla cuánto cambiar
  ├── cfg: 1.0
  └── guidance: 3.5
```

---

## 12. Flux Schnell — máximo aprovechamiento

### Cuándo usar schnell vs dev

```
SCHNELL (4 pasos, ~3-5 seg RTX 5080):
  ✅ Exploración de prompts (iteración rápida)
  ✅ Thumbnails y previews
  ✅ Storyboards y composición general
  ✅ Variaciones rápidas de seed
  ❌ Detalle fino de rostros
  ❌ Texturas complejas
  ❌ Alta fidelidad a prompt detallado

DEV (20-28 pasos, ~20-40 seg RTX 5080):
  ✅ Imágenes finales
  ✅ Prompt con muchos detalles específicos
  ✅ Calidad de rostros y manos
  ✅ Texturas y detalles finos

# TRUCO PRODUCCIÓN: usar schnell para encontrar el seed/composición,
# luego reproducir mismo seed en dev para calidad máxima
```

### Configuración schnell optimizada

```yaml
model: flux1-schnell-fp8.safetensors (o dev-fp8 con LoRA aceleración)
steps: 4
cfg: 1.0
guidance: 2.0-2.5  ← schnell usa guidance más bajo que dev
sampler: euler
scheduler: simple
denoise: 1.0
seed: [fijo para exploración]
```

---

## 13. Trucos de prompt avanzados

### Estructura de prompt óptima para Flux

```
[TIPO DE IMAGEN] [SUJETO PRINCIPAL] [ACCIÓN/ESTADO] [ENTORNO] [ILUMINACIÓN] [CÁMARA/TÉCNICA] [CALIDAD]

Ejemplo completo:
"Photograph of [a young woman with curly red hair] [standing in the rain] 
[on a cobblestone street in Paris at night] [neon reflections on wet pavement, 
warm golden light from café windows] [shot on 50mm f/2.0, bokeh background] 
[cinematic, photorealistic, high detail]"
```

### Describir proporciones y composición

```
# Flux entiende bien las instrucciones de composición espacial:

"The subject occupies the left third of the frame"
"centered composition, subject in foreground"
"bird's eye view looking down at..."
"extreme close-up of the hands, shallow depth of field"
"wide establishing shot, subject small in the environment"

# Regla de tercios en texto:
"the horizon line sits at the upper third of the image"
```

### Texto en imagen con Flux

```
# Flux puede renderizar texto legible (a diferencia de SD1.5/SDXL)
# Técnica:

"a sign that reads 'CAFÉ DU MONDE' in vintage brass letters"
"a whiteboard with the equation E=mc² written in chalk"
"a book cover with the title 'The Last Dawn' in serif font"

# TRUCO: poner el texto entre comillas en el prompt
# "neon sign that reads 'OPEN 24H'"  → mejor que sin comillas

# Limitaciones: texto muy largo o cursiva compleja sigue siendo difícil
# Máximo ~20 caracteres por elemento de texto para alta fidelidad
```

---

## 14. Hi-res y upscaling con Flux

### Workflow hi-res recomendado para RTX 5080

```
ETAPA 1 — Generación base:
  resolución: 1024×1024
  steps: 20, guidance: 3.5
  → latent_base

ETAPA 2 — Latent upscale:
  [LatentUpscale]
    method: nearest-exact   ← SIEMPRE nearest-exact para Flux
    width: 1536
    height: 1536
    → latent_upscaled

ETAPA 3 — Refinado (img2img):
  [KSampler]
    latent: latent_upscaled
    denoise: 0.45-0.55  ← clave: suficiente para agregar detalle
    steps: 15
    cfg: 1.0, guidance: 3.5
    → imagen final 1536×1536

# Para 2K en RTX 5080: usar VAE Decode Tiled en etapa 3
# tile_size: 512, overlap: 64
```

> **CE-FL006**: Para Flux usar `nearest-exact` en LatentUpscale, NO `bilinear` o `bicubic`. Los métodos de interpolación generan artefactos en el espacio latente de Flux por su diferente arquitectura.

---

## 15. Parámetros avanzados del KSampler

### add_noise y return_with_leftover_noise

```
add_noise: enable (default) → añade ruido inicial para generación completa
add_noise: disable → útil para refinado/upscaling donde ya hay estructura latente

return_with_leftover_noise: enable → útil para pasar latent a otro KSampler
return_with_leftover_noise: disable (default)
```

### Encadenar dos KSamplers (técnica de dos etapas)

```
KSampler 1 (estructura):
  steps: 12, denoise: 1.0, guidance: 4.5
  sampler: euler, scheduler: simple
      │
      └── latent_parcial
            │
KSampler 2 (detalle):
  steps: 10, denoise: 0.5
  guidance: 3.0 (menos)
  sampler: dpm++ 2m, scheduler: simple
      │
      └── latent_final
            │
         [VAE Decode]

# Resultado: más control sobre estructura vs detalle
```

---

## 16. Caché de modelos y velocidad

### Model caching entre generaciones

```
# ComfyUI mantiene el modelo en VRAM entre generaciones del mismo workflow
# TRUCO: no recargar el modelo si solo cambia seed/prompt

# Para máxima velocidad en batch de seeds:
# 1. Generar primer imagen
# 2. Cambiar solo el seed (Primitive Node)
# 3. Queue Again → el modelo ya está en VRAM

# Tiempo con modelo cacheado (RTX 5080, flux-dev-fp8, 1024×1024, 20 pasos):
# Primera imagen: ~25-30 seg (incluye carga de modelo)
# Siguientes:     ~15-20 seg (modelo ya en VRAM)
```

### Truco: Latent preview con TAESD

```
[KSampler]
  └── (output) → [VAEDecodeTaesd]   ← preview instantáneo durante generación
                       │
                  [PreviewImage]

# En paralelo:
[KSampler] → [VAEDecode] → [SaveImage]  ← imagen final alta calidad

# PreviewImage en ComfyUI muestra cada paso sin esperar al VAE final
# TAESD es ~50x más rápido que ae.safetensors
# CE-FL007: NUNCA usar TAESD para imagen final, solo preview
```

---

## 17. Casos excepcionales documentados

```
CE-FL001: RTX 5080 — configuración óptima
  Usar flux1-dev-fp8 + t5xxl_fp8_e4m3fn + ae.safetensors
  Sin flags especiales (--use-pytorch-cross-attention solo si xformers falla)
  Ver sección 3

CE-FL002: euler_cfg_pp permite CFG > 1.0 de forma estable
  cfg: 1.5-2.0 + sampler euler_cfg_pp + guidance: 3.5
  Más adherente al prompt que cfg=1.0

CE-FL003: Resolución mínima 768×768
  Artefactos de composición bajo 768px
  Max sin VAE Tiled: ~1536×1536 en 16 GB VRAM

CE-FL004: ControlNet Flux requiere modelos específicos
  NO usar ControlNets de SD1.5/SDXL
  Usar InstantX o XLabs controlnets for Flux

CE-FL005: Flux Fill usa guidance 28-35 (muy diferente a dev)
  Guidance 3.5 en Fill = resultado muy malo
  Guidance correcto: 28-35

CE-FL006: LatentUpscale con nearest-exact para Flux
  bilinear/bicubic generan artefactos en latent space de Flux

CE-FL007: TAESD solo para preview, nunca para imagen final

CE-FL008: Flux + LoRA de SD1.5/SDXL = crash o resultado incoherente
  Verificar siempre que el LoRA esté entrenado específicamente en Flux
  Civitai filtra por "Base Model: Flux.1 D"

CE-FL009: T5-XXL en CPU cuando hay poca VRAM
  Si 16 GB no alcanza: t5xxl puede offloadearse a CPU RAM
  --lowvram hace esto automáticamente
  Costo: ~3-5 seg extra por imagen para mover embeddings

CE-FL010: Flux schnell NO puede ser fine-tuneado para usos comerciales
  Licencia schnell: solo investigación y uso no comercial
  Flux dev: requiere acuerdo de licencia para uso comercial
  Para producción comercial: verificar términos en HuggingFace
```

---

## 18. Referencia rápida — parámetros validados RTX 5080

```yaml
# PERFIL PABLO — Flux dev, portrait calidad
modelo: flux1-dev-fp8.safetensors
clip: [clip_l.safetensors, t5xxl_fp8_e4m3fn.safetensors]
vae: ae.safetensors
steps: 20-28
cfg: 1.0
guidance: 3.5
sampler: euler
scheduler: simple
resolución: 1024×1024 (o 832×1152 para retrato)
tiempo_estimado: 20-35 seg/imagen

# PERFIL PABLO — Flux schnell prototipado
modelo: flux1-schnell-fp8.safetensors
steps: 4
guidance: 2.5
tiempo_estimado: 3-5 seg/imagen

# PERFIL PABLO — Flux dev 2K hi-res
etapa1: 1024×1024, steps=20, guidance=3.5
etapa2: latent upscale × 1.5 (nearest-exact)
etapa3: steps=15, denoise=0.5, VAE Decode Tiled
tiempo_estimado: ~60-90 seg total
```

---

## 19. Recursos y fuentes

```
MODELOS FLUX:
  Flux.1 dev oficial:        https://huggingface.co/black-forest-labs/FLUX.1-dev
  Flux.1 schnell:            https://huggingface.co/black-forest-labs/FLUX.1-schnell
  Flux.1 Fill (inpainting):  https://huggingface.co/black-forest-labs/FLUX.1-Fill-dev
  Flux FP8 (Kijai):          https://huggingface.co/Kijai/flux-fp8
  T5XXL FP8:                 https://huggingface.co/city96/t5-v1_1-xxl-encoder-gguf

CONTROLNET FLUX:
  InstantX Canny/Depth:      https://huggingface.co/InstantX/FLUX.1-dev-Controlnet-Canny
  XLabs AI collection:       https://github.com/XLabs-AI/x-flux-comfyui

LORA Y ACELERACIÓN:
  Flux LoRAs CivitAI:        https://civitai.com/models?types=LORA&baseModel=Flux.1+D
  Flux Turbo (accel LoRA):   https://huggingface.co/alimama-creative/FLUX.1-Turbo-Alpha
  ai-toolkit (entrenamiento):https://github.com/ostris/ai-toolkit

IDENTIDAD (PULID):
  PuLID Flux:                https://github.com/ToTheBeginning/PuLID
  ComfyUI PuLID enhanced:    https://github.com/cubiq/ComfyUI_PuLID_Flux

PAPER Y TÉCNICA:
  Flux paper (Black Forest):  https://arxiv.org/abs/2408.06519
  MMDiT architecture:         https://arxiv.org/abs/2403.03206

COMUNIDAD:
  Reddit r/StableDiffusion (Flux): https://www.reddit.com/r/StableDiffusion/
  ComfyUI workflows Flux:    https://comfyworkflows.com
  CivitAI Flux models:       https://civitai.com/models?baseModel=Flux.1+D
```
