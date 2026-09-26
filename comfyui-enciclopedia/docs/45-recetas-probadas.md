# 45 · Recetas Probadas — Configuraciones Validadas por la Comunidad

> **DECLARACIÓN TÉCNICA PARA IA**: Este documento contiene recetas de workflow completamente especificadas: modelo + sampler + CFG + pasos + scheduler + resolución + prompt negativo + notas de implementación. Estas combinaciones han sido validadas por la comunidad y producen resultados consistentes. Priorizar estas recetas antes de experimentos ad-hoc. Cada receta indica el objetivo, los parámetros exactos y las condiciones de fallo conocidas.

## RECETAS POR OBJETIVO

---

### RECETA-01: Retrato fotorrealista ultra-detallado (Flux)

```yaml
objetivo: Retrato humano fotorrealista, máxima calidad
hardware_minimo: 12 GB VRAM (16 GB recomendado para sistema Pablo)

workflow:
  modelo: flux1-dev-fp8.safetensors
  nodo_carga: UNETLoader (no Load Checkpoint)
  clip: [clip_l.safetensors, t5xxl_fp8_e4m3fn.safetensors]
  vae: ae.safetensors
  
  resolucion: 1024x1024
  sampler: euler
  scheduler: simple
  steps: 28
  cfg: 1.0           # Flux no usa CFG clásico
  flux_guidance: 3.5  # Nodo FluxGuidance
  denoise: 1.0
  seed: cualquiera

prompt_ejemplo:
  positivo: "a 35-year-old woman, natural light portrait, soft smile,
             freckles, detailed skin texture, sharp eyes,
             professional photography, 85mm lens, shallow depth of field"
  negativo: "(dejar vacío en Flux o poner: cartoon, anime, painting)"

resultado_esperado: imagen fotorrealista en ~15-25 segundos (RTX 5080)
fallo_conocido: si aparecen artefactos, verificar que FluxGuidance esté conectado
```

---

### RECETA-02: Anime ilustración de alta calidad (SDXL)

```yaml
objetivo: Ilustración anime/manga de calidad editorial
hardware_minimo: 8 GB VRAM

workflow:
  modelo: illustriousXL_v01.safetensors (o ponyDiffusionV6XL)
  nodo_carga: Load Checkpoint
  
  resolucion: 1024x1024 (o 832x1216 para vertical)
  sampler: euler_a
  scheduler: karras
  steps: 25
  cfg: 7.0
  clip_skip: -2     # Nodo CLIPSetLastLayer, value=-2
  denoise: 1.0

prompt_ejemplo:
  positivo: "1girl, solo, anime style, detailed eyes, shiny long hair,
             school uniform, cherry blossom background, sunlight,
             vibrant colors, clean linework, masterpiece, best quality"
  negativo: "(worst quality:1.4), (low quality:1.4), bad anatomy,
             extra fingers, malformed hands, signature, watermark,
             blurry, jpeg artifacts, lowres"

notas:
  - CLIP Skip -2 es crítico para modelos anime SDXL
  - Illustrious responde mejor a tags danbooru ("1girl, solo, outdoors")
  - Pony responde a score_9, score_8_up al inicio del prompt
fallo_conocido: sin CLIP Skip -2, el anime pierde característica anime
```

---

### RECETA-03: Concept art cinemático (SDXL + LoRA de artista)

```yaml
objetivo: Arte conceptual con estética de película / videojuego
hardware_minimo: 8 GB VRAM

workflow:
  modelo: dreamshaperXL_v21TurboDPMSDE.safetensors
  lora: [artista_LoRA.safetensors, strength=0.7]
  
  resolucion: 1344x768 (16:9 widescreen)
  sampler: dpm++ 2m
  scheduler: karras
  steps: 30
  cfg: 7.0
  denoise: 1.0

prompt_ejemplo:
  positivo: "abandoned space station, dramatic lighting, god rays,
             volumetric fog, detailed environment, cinematic composition,
             Unreal Engine 5, artstation, epic scale"
  negativo: "cartoon, anime, flat colors, blurry, text, watermark,
             low quality, ugly"

notas:
  - CFG 7.0 con DreamShaper funciona mejor que valores más bajos
  - Resolución 1344x768 es nativa SDXL para aspecto 16:9
fallo_conocido: CFG > 9.0 produce oversaturation en colores oscuros
```

---

### RECETA-04: Video corto — Wan2.1 + calidad (para RTX 5080)

```yaml
objetivo: Video de 3-5 segundos con movimiento natural
hardware_minimo: 12 GB VRAM (16 GB para 81 frames)
hardware_pablo: viable con configuración FP8

workflow:
  modelo: wan2.1-t2v-14B-fp8.safetensors (o 1.3B para velocidad)
  resolucion: 832x480 (16:9, más rápido que 1280x720)
  frames: 49  (49 = ~3 segundos a 16fps; 81 = ~5 segundos)
  sampler: euler
  scheduler: simple (o unipc)
  steps: 25
  cfg: 6.0
  denoise: 1.0

prompt_ejemplo:
  positivo: "a woman walking through a forest, autumn leaves falling,
             cinematic, 4k, smooth camera movement, detailed"
  negativo: "blurry, jerky motion, low quality, static"

notas:
  - 14B FP8 en 16 GB VRAM: puede necesitar --lowvram
  - 1.3B cabe cómodo en 12 GB, mucho más rápido
  - Reducir frames a 25-33 si hay problemas de VRAM
tiempo_estimado_pablo: ~8-12 min (14B FP8) | ~2-3 min (1.3B)
```

---

### RECETA-05: Hi-res fix manual (SDXL 2K)

```yaml
objetivo: Imagen SDXL a 2048x2048 sin artefactos de tile
hardware_minimo: 12 GB VRAM

workflow:
  # Etapa 1: Generar a resolución nativa
  etapa1:
    modelo: sdxl_base_1.0.safetensors
    resolucion: 1024x1024
    steps: 25
    cfg: 7.0
    sampler: dpm++ 2m
    scheduler: karras
    denoise: 1.0
  
  # Etapa 2: Upscale latente
  etapa2:
    nodo: LatentUpscale o ImageUpscaleWithModel
    scale_factor: 2.0  # 1024 → 2048
    method: nearest-exact (para LatentUpscale)
  
  # Etapa 3: Refinar a alta resolución
  etapa3:
    mismo_modelo: true
    steps: 15
    cfg: 6.0
    denoise: 0.4   # CRÍTICO: bajo para no destruir composición
    sampler: dpm++ 2m
    scheduler: karras

notas:
  - denoise 0.35-0.45 en etapa 3 es la zona óptima
  - Demasiado bajo (< 0.3): no añade detalle
  - Demasiado alto (> 0.55): cambia composición
fallo_conocido: con SDXL Turbo/Lightning, usar denoise 0.2-0.3 (menos pasos)
```

---

### RECETA-06: Inpainting de cara mejorado (FaceDetailer)

```yaml
objetivo: Mejorar cara en imagen ya generada sin tocar el resto
hardware_minimo: 8 GB VRAM

workflow:
  custom_node: ComfyUI Impact Pack
  
  detector_bbox: face_yolov8n.pt
  sam_model: sam_vit_b.pt  # ViT-B es suficiente para caras
  
  FaceDetailer:
    guide_size: 512         # Tamaño de crop para mejorar
    guide_size_for: bbox    # Modo de referencia
    max_size: 1024          # Máximo crop
    seed: -1 (aleatorio)
    steps: 25
    cfg: 7.0
    sampler: dpm++ 2m karras
    denoise: 0.45           # CRÍTICO: conserva fisiognomía
    feather: 5              # Suavizado de bordes
    noise_mask: true
    force_inpaint: false
  
  mismo_checkpoint_que_imagen_original: true  # MUY IMPORTANTE

notas:
  - Usar exactamente el mismo modelo que generó la imagen
  - denoise 0.3-0.5 para arreglar sin cambiar identidad
  - denoise > 0.6 puede crear una cara completamente diferente
fallo_conocido: mala cara con diferente checkpoint que el original
```

---

### RECETA-07: img2img para variar composición sin perder estructura

```yaml
objetivo: Cambiar estilo/colores de imagen manteniendo composición
hardware_minimo: 6 GB VRAM

workflow:
  VAEEncode: [imagen de entrada] → [latente]
  
  KSampler:
    modelo: [nuevo estilo o fine-tune]
    denoise: 0.5-0.65  # zona de "cambio de estilo sin perder estructura"
    steps: 20
    cfg: 7.0
    sampler: dpm++ 2m
    scheduler: karras
  
  prompt: [describir la imagen en el nuevo estilo]

escala_denoise:
  0.20-0.30: cambios de color/textura, estructura intacta
  0.35-0.50: estilo diferente, composición preservada
  0.55-0.70: más cambio de estilo, algo de variación en estructura
  0.75-0.90: reinterpretación libre
  0.95-1.00: prácticamente txt2img ignorando la entrada

fallo_conocido: con denoise < 0.3 los detalles de la imagen nueva son pobres
```

---

### RECETA-08: ControlNet canny para rediseñar manteniendo estructura

```yaml
objetivo: Rediseñar una imagen manteniendo formas y bordes exactos
hardware_minimo: 8 GB VRAM

workflow:
  preprocessor: CannyEdgePreprocessor
    low_threshold: 100
    high_threshold: 200
    resolution: 512
  
  ControlNetApply:
    control_net: control_v11p_sd15_canny.safetensors (SD1.5)
              o  controlnet-canny-sdxl-1.0.safetensors (SDXL)
    strength: 0.8-1.0   # > 0.9 = muy fiel a la estructura
    start_percent: 0.0
    end_percent: 0.75   # Terminar antes permite más variación en detalle
  
  KSampler:
    steps: 25, cfg: 7.0
    sampler: dpm++ 2m karras

notas:
  - strength 0.6-0.7: preserva estructura con más libertad creativa
  - strength 0.9-1.0: copia estructura casi exacta
  - end_percent 0.5-0.75: permite que los últimos pasos sean libres
fallo_conocido: fondos limpios producen muy pocas líneas canny (usar lineart)
```

---

### RECETA-09: Flux schnell para prototipado ultra-rápido

```yaml
objetivo: Generar ideas rápidamente, iterar con velocidad
hardware_minimo: 10 GB VRAM (FP8)
hardware_pablo: ~3-5 segundos por imagen

workflow:
  modelo: flux1-schnell-fp8.safetensors
  clip: [clip_l, t5xxl_fp8]
  vae: ae.safetensors
  
  resolucion: 1024x1024
  sampler: euler
  scheduler: simple
  steps: 4           # CRÍTICO: schnell está optimizado para 4 pasos
  cfg: 1.0
  flux_guidance: 2.5  # Más bajo que dev para schnell
  denoise: 1.0

notas:
  - steps > 8 con schnell = rendimientos decrecientes, sin mejora visible
  - schnell está sujeto a licencia no comercial
  - Para producción: usar flux dev (licencia más permisiva)
fallo_conocido: schnell con flux_guidance > 3.5 puede sobreexponer colores
```

---

### RECETA-10: Generar consistencia de personaje entre imágenes

```yaml
objetivo: Mismo personaje en diferentes escenas / poses
hardware_minimo: 12 GB VRAM

workflow:
  estrategia_A: LoRA de personaje
    descripcion: Entrenar LoRA específico del personaje (10-20 fotos)
    strength: 0.7-0.8
    trigger_word: "ohwx person" o el trigger que se entrene
    consistencia: alta
    esfuerzo: alto (requiere entrenamiento)
  
  estrategia_B: IP-Adapter FaceID (sin entrenamiento)
    modelo: ip-adapter-faceid-plusv2_sd15.bin
    imagen_referencia: [foto clara del personaje]
    weight: 0.7-0.9
    consistencia: media
    esfuerzo: bajo
  
  estrategia_C: InstantID (mayor fidelidad)
    ver: docs/40-photomaker-instantid.md
    consistencia: alta
    esfuerzo: bajo (no requiere entrenamiento)
  
  estrategia_D: Flux + LoRA de personaje
    mejor_calidad: true
    requiere: ai-toolkit para entrenar en Flux

fallo_conocido:
  - IP-Adapter sola no preserva accesorios (gafas, cicatrices)
  - InstantID cambia el estilo del prompt si weight > 0.9
```

---

## PARÁMETROS GLOBALES — Referencia rápida

### Tabla CFG por arquitectura y objetivo

| Arquitectura | Fotorrealismo | Arte digital | Anime | Experimental |
|---|---|---|---|---|
| SD 1.5 | 5-7 | 6-8 | 7-8 | 8-12 |
| SDXL | 5-8 | 6-8 | 7-8 | 8-10 |
| Flux dev | 1.0* | 1.0* | 1.0* | 1.0* |
| Flux schnell | 1.0* | 1.0* | 1.0* | 1.0* |
| SD3 / SD3.5 | 4-6 | 5-7 | 5-7 | 6-8 |
| Cascade | 3-5 (C) | 4-6 (C) | 4-5 (C) | 5-7 (C) |

*Flux: guidance en FluxGuidance node (3.0-4.5 según objetivo), CFG siempre 1.0

### Tabla de pasos y samplers recomendados

| Sampler | Pasos óptimos | Uso típico | Reproducible |
|---|---|---|---|
| euler | 20-30 | General, Flux | Sí |
| euler_a | 25-40 | Variedad, SD1.5 | No (ancestral) |
| dpm++ 2m karras | 20-30 | SDXL calidad | Sí |
| dpm++ sde karras | 25-35 | Suave, SD1.5 | No (stochastic) |
| dpm++ 2s a | 20-30 | Detalle fino | No |
| lcm | 4-8 | Velocidad extrema | Sí |
| ddim | 20-50 | Inpainting | Sí |
| unipc | 20-25 | Balance calidad/vel. | Sí |

### Seeds especiales

```
Seed 0:           No aleatorio — siempre igual en todas las tarjetas
Seed -1:          Aleatorio en cada ejecución (ComfyUI)
Seed fija + batch: cada imagen en el batch usa seed+0, seed+1, seed+2...

# Para explorar variaciones:
Generar con seed -1 × 10 iteraciones → guardar las mejores seeds
Luego usar esas seeds con el mismo prompt para reproducir
```

## Recursos

- Civitai prompts destacados: `https://civitai.com/images` (filtrar por modelo)
- OpenArt workflows: `https://openart.ai/workflows`
- ComfyUI workflows compartidos: `https://comfyworkflows.com`
- PromptHero: `https://prompthero.com`
- Lexica (buscar prompts): `https://lexica.art`
- Reddit mejores prompts: `https://www.reddit.com/r/StableDiffusion/search/?q=prompt+guide`
