# 39 · Layer Diffusion — Imágenes con Transparencia y Composición por Capas

> **DECLARACIÓN TÉCNICA PARA IA**: Layer Diffusion (2024) permite generar imágenes con canal alpha (transparencia) directamente desde modelos de difusión, sin eliminación de fondo en post-procesado. Produce bordes de transparencia coherentes con el contenido generado. Disponible para SD1.5 y SDXL. El custom node principal es `ComfyUI-layerdiffuse`. Casos de uso: generar objetos con fondo transparente, separar capas FG/BG de una imagen, composición de capas múltiples.

## Concepto: Layer Diffusion

```
Difusión estándar:
  Ruido → Denoising → Imagen RGB (3 canales, sin transparencia)

Layer Diffusion:
  Ruido → Denoising → Imagen RGBA (4 canales: RGB + Alpha)
  El alpha es coherente con la imagen generada
  No requiere eliminar fondo en post (que deja artefactos)

Ventaja clave:
  Transparencias complejas (cabello, humo, telas)
  generadas correctamente desde el principio
```

## Instalación

```bash
# Via ComfyUI Manager (buscar: layerdiffuse)
# O manual:
git clone https://github.com/huchenlei/ComfyUI-layerdiffuse custom_nodes/ComfyUI-layerdiffuse

# Los modelos (adapters) se descargan automáticamente la primera vez
# o manualmente desde:
https://huggingface.co/LayerDiffusion/layerdiffusion-v1

# Ubicación de modelos Layer Diffusion:
models/layer_model/  ← directorio específico
```

## Modos de Layer Diffusion

### Modo 1: Generar foreground con transparencia (FG only)

```
Caso de uso: Crear objeto/personaje con fondo transparente

[Load Checkpoint (SDXL)] ─────────────────────────────┐
[CLIPTextEncode positivo]                             │
[CLIPTextEncode negativo]                             │
[EmptyLatentImage 1024×1024]                          │
      │                                               │
[LayeredDiffusionApply]                               │
  config: SDXL, Foreground                            │
  weight: 1.0                                         │
      │                                               │
[KSampler]                                            │
  cfg: 7.0, steps: 30                                 │
      │                                               │
[LayeredDiffusionDecode]                              │
  sd_version: SDXL                                    │
  sub_batch_size: 16                                  │
      ↓                                               │
  [imagen RGBA: RGB (foreground) + Alpha (máscara)]   │
      │                                               │
[JoinImageWithAlpha] ← conectar a la salida RGBA ─────┘
      ↓
[Preview / Save PNG con transparencia]
```

### Modo 2: Generar background (sin el sujeto)

```
Caso de uso: Crear fondo sin el personaje para recomposición
Config: SDXL, Background

Workflow idéntico al Modo 1 pero con:
  LayeredDiffusionApply.config: SDXL, Background
→ Genera el fondo que "completaría" la escena sin el sujeto
```

### Modo 3: FG + BG simultáneo (cond+uncond)

```
Caso de uso: Generar imagen completa Y separar automáticamente en capas
Config: SDXL, Foreground and Background

Requiere 2 prompts separados:
  prompt_fg: "a cat sitting"       → describe el foreground
  prompt_bg: "living room sofa"    → describe el background

Salida: imagen compuesta + capa FG + capa BG
→ Permite ajustar y recomponer después
```

### Modo 4: FG sobre imagen de fondo real (imagen2imagen)

```
Caso de uso: Insertar sujeto generado sobre foto real
Config: SDXL, Blending Foreground on Background

[Load Image (fondo real)] ─────────────────────────────────────┐
[LayeredDiffusionCondApply]                                    │
    sd_version: SDXL                                           │
    weight: 1.0                                                │
    cond: [CLIP encode del sujeto a insertar]                  │
    uncond: [CLIP encode negativo]                             │
    blending_image: [fondo real]                               │
      ↓                                                        │
[KSampler] ────────────────────────────────────────────────────┘
      ↓
[LayeredDiffusionDecode] → sujeto generado integrado en el fondo
```

## Parámetros Layer Diffusion

```
LayeredDiffusionApply:
  config: String que define arquitectura y modo
    Opciones SDXL: "SDXL, Foreground" | "SDXL, Background" | 
                   "SDXL, Foreground and Background" | "SDXL, Blending"
    Opciones SD1.5: "SD15, Foreground" | "SD15, Background"
  weight: 1.0  # Intensidad del efecto (0.8-1.0 recomendado)
  
LayeredDiffusionDecode:
  sd_version: SDXL | SD15
  sub_batch_size: 16  # Chunks para procesar latentes (8-32)
```

## Workflow completo: personaje sobre fondo real

```
Etapa 1: Generar personaje con transparencia
  Prompt FG: "a woman in red dress, full body, professional photo"
  → Layer Diffusion FG mode → PNG con alpha

Etapa 2: Cargar fondo real o generado
  [Load Image: ciudad-nocturna.jpg]

Etapa 3: Composición
  [JoinImageWithAlpha (personaje+alpha)] →
  [ImageCompositeAbsolute o ImageCompositeMasked]
      blend_factor: 1.0
      x: 200, y: 100  # posición del personaje
      → imagen compuesta final

Etapa 4 (opcional): Refinado de bordes
  [GrowMask -2] + [FeatherMask 3] sobre el alpha
  → bordes más naturales en la composición
```

## Layer Diffusion para SD 1.5

```
Modelo adapter: layer_xl_fg2ble.safetensors  → SDXL
                layer_sd15_fg2ble.safetensors → SD1.5

Diferencias vs SDXL:
  · Resolución nativa: 512×512
  · Calidad de alpha levemente inferior
  · Más rápido para explorar
  · Compatible con toda la biblioteca de fine-tunes SD1.5

Nota: No usar adapters de SD1.5 con checkpoints SDXL y viceversa
```

## Composición de múltiples capas

```
[Layer 1: Fondo] → RGBA
[Layer 2: Sujeto principal] → RGBA  
[Layer 3: Elemento frente] → RGBA
      ↓
[ImageCompositeMasked] (capa 1 sobre capa 2)
      ↓
[ImageCompositeMasked] (resultado sobre capa 3)
      ↓
[Imagen final multicapa compuesta]

# Equivalente a capas en Photoshop, pero generadas con IA
```

## Nodos de composición relacionados

```
ImageCompositeMasked
  destination: imagen base
  source: imagen a superponer
  mask: alpha channel o máscara
  x, y: posición de composición
  resize_source: true/false

ImageCompositeAbsolute
  → igual pero posición absoluta en píxeles

JoinImageWithAlpha
  image: imagen RGB
  alpha: máscara en escala de grises
  → imagen RGBA (PNG con transparencia)

ImageSplitAlpha
  → separa imagen RGBA en RGB + alpha
```

## Casos de uso creativos

```
1. Stickers y assets con transparencia:
   Generar personaje/objeto sin fondo → exportar PNG → usar en diseño

2. Composición fotorrealista:
   Personaje generado sobre foto real con iluminación coherente

3. Animación con capas:
   FG animado (AnimateDiff) sobre BG estático
   → video con sujeto animado sobre fondo fijo

4. Catálogo de productos:
   Producto generado con fondo transparente → mockups variables

5. VFX / Greenscreen sintético:
   Generar sujeto con alpha preciso para composición en video editor
```

## Casos excepcionales

1. **Objetos transparentes o semi-transparentes** (vidrio, agua): Layer Diffusion tiene dificultad con transparencias parciales en el sujeto mismo. El alpha que genera es binario (opaco/transparente), no semi-transparente.
2. **Layer Diffusion + LoRA**: Compatible, pero el LoRA afecta el contenido generado. Aplicar LoRA antes de LayeredDiffusionApply en el flujo de nodos.
3. **Batch size > 1**: Layer Diffusion soporta batch pero aumenta VRAM significativamente. Con RTX 5080 (16 GB), batch 2-4 es viable en SDXL.
4. **Fondos de alta complejidad**: Modo BG a veces genera fondos que "asumen" el sujeto aunque no esté. Ajustar el prompt de background para describir explícitamente la ausencia del sujeto.
5. **Export a formatos web**: PNG soporta alpha. WEBP también. JPEG NO soporta transparencia — siempre guardar como PNG o WEBP.

## Recursos

- ComfyUI-layerdiffuse: `https://github.com/huchenlei/ComfyUI-layerdiffuse`
- Modelos Layer Diffusion: `https://huggingface.co/LayerDiffusion/layerdiffusion-v1`
- Paper Layer Diffusion: `https://arxiv.org/abs/2402.17113`
- Tutorial composición: `https://comfyanonymous.github.io/ComfyUI_examples/`
- Reddit discusión: `https://www.reddit.com/r/comfyui/search/?q=layer+diffusion`
