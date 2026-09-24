# 46 · Condiciones Especiales de Desarrollo — Patrones Excepcionales Reconocidos

> **DECLARACIÓN TÉCNICA PARA IA**: Este documento cataloga condiciones especiales, comportamientos no obvios, y combinaciones validadas que la comunidad ha documentado. Incluye: comportamientos contraintuitivos de samplers, efectos de interacción entre nodos, limitaciones arquitectónicas conocidas, trucos de precisión, y patrones de fallo recurrentes. Consultar antes de diagnosticar bugs que podrían ser comportamiento esperado.

---

## CONDICIONES DE SAMPLER Y SCHEDULER

### CE-S001: Samplers ancestrales y no-determinismo

```
Samplers afectados: euler_a, dpm++ 2s a, dpm++ sde, dpm++ 2m sde

Comportamiento:
  Misma seed + mismo prompt + misma GPU = DIFERENTE imagen
  La razón: estos samplers añaden ruido adicional en cada paso
  que depende del estado interno del generador aleatorio
  que varía según el hardware y CUDA implementation

Implicación práctica:
  - NO usar ancestrales si necesitas reproducibilidad exacta
  - SÍ usar si quieres variedad con la misma seed
  - Para comparar modelos: usar euler o dpm++ 2m (determinísticos)

Excepción documentada:
  En Flux, euler es determinístico en todo el hardware (testado).
  En SDXL, dpm++ 2m karras es el más consistente entre GPUs.
```

### CE-S002: Karras vs Normal scheduler — efecto en primeros pasos

```
Comportamiento:
  Normal: ruido distribuido uniformemente entre todos los pasos
  Karras: más ruido en pasos iniciales, menos en finales
  → Karras = mejor estructura global con menos pasos
  → Normal = más fiel al prompt en pasos intermedios

Cuándo usar Normal:
  - Flux (scheduler recomendado para Flux es 'simple' o 'normal')
  - Procesos de inpainting con denoise < 0.5 (Karras puede ser demasiado agresivo)

Cuándo usar Karras:
  - SD1.5 y SDXL para calidad máxima
  - Cuando se tienen pocos pasos (15-20)
```

### CE-S003: DPM++ 2M Karras — el “valor por defecto profesional”

```
Combinación: dpm++ 2m + karras

Razón de uso amplio:
  - Converge rápido (20 pasos son suficientes)
  - Determinístico (reproducible)
  - Buen balance detalle/coherencia
  - Estándar implicitly acordado por la comunidad SDXL

Límitación: En algunos fine-tunes de SD1.5 con estilos muy específicos
(como 3D render o pixel art), euler_a produce mejores resultados
a pesar del no-determinismo.
```

### CE-S004: Sigmóide SGM vs Exponential — para SDXL

```
sgm_uniform: recomendado para SDXL Turbo y modelos de consistencia
exponential: produce gradientes de color más suaves en SDXL base

Uso específico:
  SDXL Turbo: sgm_uniform + 1-4 pasos
  SDXL base:  karras (estándar) o exponential (si karras da halos)
```

---

## CONDICIONES DE PRECISIÓN Y MEMORIA

### CE-P001: BF16 vs FP16 — diferencias reales

```
FP16: float16, 5 bits exponente, 10 bits mantisa
BF16: bfloat16, 8 bits exponente, 7 bits mantisa

Implicaciones:
  BF16: menor precisión decimal PERO mayor rango dinámico
  → Menos overflow (NaN) en capas de atención con valores extremos
  FP16: mayor precisión decimal pero desborda con valores > 65504
  → Más NaN en SDXL, menos en SD1.5

Práctico para SDXL:
  Usar BF16 si hay NaN en SDXL con FP16
  RTX 5080 (Blackwell): BF16 y FP16 tienen velocidades similares
  GPUs < Ampere: no tienen BF16 nativo (A100 fue la primera mainstream)
```

### CE-P002: FP8 — cuándo degradar y cuándo no

```
FP8 (float8 e4m3fn): Sólo disponible en Hopper+ (H100) y Lovelace/Blackwell (RTX 4090, 5080)

FP8 para Flux:
  flux1-dev-fp8.safetensors = calidad ~99% del BF16 original
  Ahorro VRAM: ~50% vs BF16
  Velocidad: igual o ligeramente más rápido (tensor cores FP8)
  → Recomendado para RTX 5080 (Blackwell tiene FP8 acelerado)

FP8 para SDXL:
  Ahorro VRAM: ~50%
  Calidad: perde ligero detalle en texturas finas
  → Usar si la VRAM es la limitación; no usar si tienes margen

FP8 NO recomendado para:
  - VAE (siempre en FP32 o FP16, nunca FP8 — produce tiling)
  - CLIP encoders en generación de alta precisión
```

### CE-P003: TAESD vs VAE completo

```
TAESD: VAE ligero solo para previsualización en tiempo real

Comportamiento:
  Usar TAESD en la opción de preview del KSampler
  → Previews rápidos durante el denoising
  → Calidad de preview inferior al VAE completo

CRÍTICO:
  La imagen FINAL siempre debe decodificarse con VAE completo
  (ae.safetensors para Flux, sdxl_vae para SDXL)
  TAESD solo para el widget de preview en tiempo real
```

---

## CONDICIONES DE CLIP Y TEXTO

### CE-C001: El límite de 77 tokens no es absoluto

```
CLIP trunca a 77 tokens, pero hay workarounds:

Método 1: BREAK (nativo en ComfyUI)
  "primer segmento de 77 tokens, BREAK, segundo segmento"
  Cada segmento se codifica independientemente
  → Permite prompts de hasta 154+ tokens

Método 2: Dos nodos CLIPTextEncode + ConditioningConcat
  Prompt1 → CLIPTextEncode → Conditioning1
  Prompt2 → CLIPTextEncode → Conditioning2
  [ConditioningConcat] → Conditioning combinado

Método 3: T5-XXL (Flux)
  T5-XXL en Flux NO tiene límite de 77 tokens
  Acepta textos largos y prose natural
  → En Flux, escribir prompts como oraciones naturales
```

### CE-C002: Pesos de palabras con paréntesis

```
Sintaxis SD1.5/SDXL:
  (palabra)        = peso 1.1
  ((palabra))      = peso 1.21 (1.1 al cuadrado)
  (((palabra)))    = peso 1.33
  (palabra:1.5)    = peso exacto 1.5
  [palabra]        = peso 0.9 (reduce importancia)
  [[palabra]]      = peso 0.81
  
Únicamente en WebUI de AUTOMATIC1111 por defecto.
En ComfyUI: solo funciona con el parser específico habilitado.

En FLUX: NO funciona el sistema de pesos por paréntesis.
Usarlo en Flux no tiene efecto y puede añadir literalmente paréntesis.
→ En Flux, enfatizar repitiendo o reordenando términos.
```

### CE-C003: Orden de tokens importa (posición = peso implícito)

```
En SD1.5/SDXL:
  Tokens al inicio del prompt tienen MAYOR peso implícito
  Tokens al final tienen MENOR influencia
  → Poner el sujeto principal PRIMERO
  → Los tags de calidad (masterpiece, 8k) al FINAL

En Flux con T5-XXL:
  T5 tiene mejor comprensión semántica
  El orden importa menos (entiende relaciones de modificador-sustantivo)
  Escribir en oraciones naturales funciona
```

---

## CONDICIONES DE WORKFLOW

### CE-W001: Latent upscale con nearest-exact vs bicubic

```
LatentUpscale - métodos de interpolación:
  nearest-exact:  sin suavizado, preserva información pura del latente
  bilinear:       suavizado ligero
  bicubic:        suavizado más agresivo
  area:           downscale only

Recomendación:
  Para hi-res fix: nearest-exact
  Razón: el KSampler posterior con denoise=0.4 añade el detalle
  Bicubic pre-suaviza innecesariamente lo que se va a refinar
  
  Para outpainting: bilinear o bicubic (evita border artifacts)
```

### CE-W002: Condicionamiento — área vs mask vs timestep

```
Tres métodos de condicionamiento parcial, cada uno con comportamiento diferente:

ConditioningSetArea:
  Define un área rectangular donde aplica el prompt
  más predecible para composiciones estructuradas
  requiere ConditioningCombine con el prompt global

ConditioningSetMask:
  Usa máscara arbitraria (forma libre)
  strength define cuánto domina este prompt en el área
  set_cond_area: default | mask_bounds (important: mask_bounds más limpio)

ConditioningSetTimestepRange:
  Activa el prompt solo durante ciertos pasos del denoising
  start=0.0, end=0.5 → influye en los primeros 50% de pasos (estructura global)
  start=0.5, end=1.0 → influye en el último 50% (detalles finos)
```

### CE-W003: Batch size vs latent batch

```
Dos maneras de generar múltiples imágenes:

Opción A: batch_size en EmptyLatentImage
  Genera N imágenes de golpe
  VRAM requerida: aproximadamente N × VRAM de una imagen
  Velocidad: más eficiente por imagen que generarlas una a una

Opción B: repetir el workflow N veces (seed incrementada automáticamente)
  VRAM fija (1 imagen a la vez)
  Menos eficiente pero no requiere más VRAM
  ComfyUI Queue mode: agregar al queue y procesar secuencialmente

Para RTX 5080 (16 GB VRAM):
  SDXL 1024x1024: batch 2-3 viable
  Flux 1024x1024 FP8: batch 1-2 (3 puede dar OOM)
  SD1.5 512x512: batch 4-8 cómodo
```

### CE-W004: Seed -1 no es verdaderamente aleatoria en ComfyUI

```
Comportamiento:
  Seed -1 en ComfyUI → usa timestamp del sistema como seed
  → Dos ejecuciones en el mismo segundo PUEDEN dar la misma imagen
  (raro pero posible en sistemas rápidos o en batch)

  Para exploración más aleatoria:
  Usar el nodo RandomNoise o agregar un primitivo de seed
  conectado a un KSampler con randomize=always
```

---

## CONDICIONES DE MODELOS ESPECÍFIFCOS

### CE-M001: Flux y el double-CFG problem

```
Comportamiento documentado:
  Flux tiene un mecanismo interno de guidance.
  Si se usa CFG > 1.0 en el KSampler ADEMÁS del nodo FluxGuidance,
  se aplica doble condicionamiento → resultados inestables.

Correcto:
  KSampler CFG = 1.0
  FluxGuidance node: guidance = 3.5 (esto sí controla la aderencia)

Incorrecto:
  KSampler CFG = 7.0 (activa CFG clásico en Flux → artefactos)
```

### CE-M002: SDXL refiner — proporción óptima base/refiner

```
El pipeline SDXL Base + Refiner tiene una zona óptima de traspaso:

Recomendado:
  Base: 0.0 → 0.8 (primeros 80% de pasos)
  Refiner: 0.8 → 1.0 (20% finales)

Práctica con KSampler Advanced:
  KSamplerAdvanced (Base):
    add_noise: enable
    start_at_step: 0
    end_at_step: 20  # (de 25 totales)
    return_with_leftover_noise: enable
  
  KSamplerAdvanced (Refiner):
    add_noise: disable
    start_at_step: 20
    end_at_step: 10000  # sin límite
    return_with_leftover_noise: disable

Fuera de esa zona:
  < 0.7 para base: el refiner tiene que hacer demasiado trabajo
  > 0.9 para base: el refiner no tiene tiempo de refinar
```

### CE-M003: AnimateDiff y la tensión entre frames

```
Comportamiento:
  AnimateDiff introduce coherencia temporal PERO reduce variación entre frames
  → Videos que parecen "atascados" o con muy poco movimiento

Causas y soluciones:
  1. Motion module strength muy alta: reducir a 0.75-0.90
  2. Context length muy corto: probar 16-24 frames de contexto
  3. Motion LoRA: usar pan/zoom LoRA para añadir movimiento dirigido
  4. CFG demasiado alto: reducir a 5.0-6.0 para más variación
  5. Prompt muy específico: el modelo intenta mantener todos los detalles
```

### CE-M004: Inpainting y el VAE encoding doble

```
Error frecuente en flujos de inpainting:
  [Load Image] → [VAE Encode] → [SetLatentNoiseMask]
  → [VAE Encode OTRA VEZ] → ERROR

Correcto:
  [Load Image] → [VAE Encode for Inpainting]
  (este nodo específico maneja la máscara internamente)
  O: [SetLatentNoiseMask] con solo UN VAE Encode antes

Con modelos de inpainting dedicados (model-inpainting.safetensors):
  Requieren VAE Encode for Inpainting (6-channel latent)
  NO usar con VAE Encode estándar (4-channel) → crash o imagen incorrecta
```

---

## CONDICIONES DE HARDWARE

### CE-H001: RTX 5080 y xformers (Blackwell)

```
Problema conocido (2025-2026):
  xformers puede no tener wheels precompiladas para CUDA Compute 10.x (Blackwell)
  Si xformers falla en RTX 5080:
  
  Solución A (recomendada):
    Arrancar ComfyUI con: --use-pytorch-cross-attention
    Usa la atención nativa de PyTorch (ligeramente más lento pero estáble)
  
  Solución B:
    Instalar xformers desde nightly/source cuando salga soporte Blackwell
    pip install xformers --pre
  
  Verificar:
    python -c "import xformers; print(xformers.version.__version__)"
    Si error: usar solución A
```

### CE-H002: GDDR7 y ancho de banda en Blackwell

```
RTX 5080 tiene 960 GB/s de ancho de banda vs 576 GB/s del RTX 4090

Implicación práctica:
  - Modelos con muchas transferencias de datos GPU↔VRAM se benefician más
  - Flux en FP8 con RTX 5080 es notablemente más rápido que en RTX 4090
    (a pesar de igual CUDA cores en algunos benchmarks)
  - Generación de video (muchos frames en batch) se beneficia especialmente
```

### CE-H003: OOM vs OOM — dos tipos de error

```
Error CUDA Out of Memory durante generación:
  Causa: el modelo + la operación no caben en VRAM actualmente disponible
  Solución: --lowvram o reducir resolución o batch size

Error CUDA Out of Memory durante carga del modelo:
  Causa: el modelo en sí no cabe en VRAM ni con offloading
  Solución: usar versión más pequeña/cuantizada del modelo (FP8/GGUF)

Error CUDA Out of Memory en VAE Decode:
  Causa: imagen de alta resolución no cabe en VRAM para decodificar
  Solución: usar VAE Decode Tiled (nodo tiled_decode)
    tile_size: 512 o 1024
```

---

## TRUCOS Y PATRONES VALIDADOS

### TRUCO-01: Aumentar variedad sin cambiar seed

```
Método: cambiar el scheduler manteniendo todo lo demás igual
  Misma seed + mismo prompt + misma model
  karras vs normal vs exponential → 3 resultados distintos con misma seed

También:
  Cambiar entre euler y dpm++ 2m (deterministas) → variación de interpretación
```

### TRUCO-02: Mejorar coherencia de manos con ControlNet Depth

```
# Las manos son notoriamente difíciles en todos los modelos.
# Flujo probado que mejora resultados:

[Generar imagen] → [si manos malas]

Workflow mejorado:
  1. Estimar profundidad con MiDaS/DepthAnything
  2. Aplicar ControlNet Depth (strength 0.4-0.6)
  3. Generar con el condicionamiento de profundidad
  4. Si aún hay problemas: FaceDetailer con YOLO hand detector

Alternativa:
  Usar Flux dev (gen. de manos mucho mejor que SD1.5/SDXL)
```

### TRUCO-03: CFG Rescale para reducir oversaturation

```
Problema: CFG alto (7-9) produce colores muy saturados/artificiales

Solución: Nodo CFGGuider con rescale_cfg
  rescale_multiplier: 0.7  # reduce intensidad del CFG en los colores
  → mantiene coherencia con el prompt pero colores más naturales

O: Simplemente bajar CFG a 5.5-6.5 (solución más simple)
```

### TRUCO-04: Usar Primitive para experimentación rápida

```
# Agregar nodos Primitive a los parámetros que se quieren variar:
[Primitive] → (CFG del KSampler)
[Primitive] → (steps del KSampler)
[Primitive] → (strength del ControlNet)

# Ventaja: cambiar valores sin tener que buscar el nodo en el canvas
# Agrupar primitivos en un área del canvas = "panel de control" visual
```

### TRUCO-05: Preview condicional con Switch

```
# Para workflows de producción donde el preview ralentiza:

[KSampler]
    ↓
[CR Image Switch]  (de CR node suite)
    opción 1: [Preview Image]  <- para desarrollo
    opción 2: [Save Image]     <- para producción
    input: [Primitive 0/1]

# Con el Primitive en 0 = preview, en 1 = guardar directo
```

## Recursos

- ComfyUI issues (bugs documentados): `https://github.com/comfyanonymous/ComfyUI/issues`
- ComfyUI discussions: `https://github.com/comfyanonymous/ComfyUI/discussions`
- Comportamiento Flux confirmado: `https://github.com/black-forest-labs/flux`
- SDXL paper (arquitectura original): `https://arxiv.org/abs/2307.01952`
- AnimateDiff paper: `https://arxiv.org/abs/2307.04725`
- Comunidad r/comfyui tips: `https://www.reddit.com/r/comfyui/top/?t=all`
