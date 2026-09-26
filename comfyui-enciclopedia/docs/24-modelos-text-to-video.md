# 24 — Modelos Text-to-Video Modernos

El ecosistema de video IA creció enormemente en 2024–2025. Esta sección cubre los modelos principales disponibles en ComfyUI.

---

## Panorama General

```
Generación de video IA
├── Text-to-Video (t2v): texto → video
├── Image-to-Video (i2v): imagen → video
└── Video-to-Video (v2v): video existente → video modificado
```

---

## CogVideoX

**Origen**: Zhipu AI (Universidad de Tsinghua)

### Variantes
| Modelo | Resoluc. | FPS | Duración | VRAM |
|--------|---------|-----|---------|------|
| CogVideoX-2b | 480p | 8 fps | 6s | 12 GB |
| CogVideoX-5b | 480p | 8 fps | 6s | 24 GB |
| CogVideoX1.5-5b | 720p | 16 fps | 6–10s | 24 GB |
| CogVideoX-5b-I2V | 480p | 8 fps | 6s | 24 GB |

### Características
- Arquitectura basada en Diffusion Transformer (DiT)
- Texto procesado con T5-XXL
- Excelente seguimiento de instrucciones complejas
- Soporte para prompts en chino e inglés

### Custom Node
`ComfyUI-CogVideoX` o `ComfyUI_CogVideoX_Fun`

### Flujo básico CogVideoX
```
[CogVideoXModelLoader] → MODEL
[T5TextEncode] → CONDITIONING+
[EmptyMediaPipeSkeleton o EmptyLatentVideo] → LATENT
[CogVideoXSampler] → LATENT
[VAEDecode] → IMAGE batch
[VHS_VideoCombine] → video.mp4
```

---

## LTX Video

**Origen**: Lightricks

### Ventajas principales
- **Extremadamente rápido**: genera video en segundos en lugar de minutos
- Latent space de alta compresión (1:192 vs 1:8 de SD)
- Primero genera estructura global, luego detalla
- Soporta t2v e i2v

### Modelos
- `ltx-video-2b.safetensors` — 2B parámetros
- `ltx-video-2b-0.9.5.safetensors` — versión mejorada

### Parámetros clave
- Resolución: 704×480 o 768×512
- Frames: 25, 49, 97
- Steps: 25–50
- CFG: 3–5

### Custom Node
`ComfyUI-LTXVideo`

---

## Mochi-1

**Origen**: Genmo AI

### Características
- Arquitectura AsymmDiT (Asymmetric Diffusion Transformer)
- Movimiento flüido y natural, física convincente
- 480p, hasta 5.4 segundos
- VRAM: 28 GB (completo) / 16 GB (optimizado)

### Custom Node
`ComfyUI_mochi`

---

## Hunyuan Video

**Origen**: Tencent

### Por qué destaca
- Una de las mejores calidades de movimiento y cohesión
- Diseñado con dual-stream transformer (similar a Flux)
- Soporte t2v e i2v

### Requisitos
| Formato | Tamaño | VRAM |
|---------|--------|------|
| FP16 completo | ~60 GB | 80 GB |
| FP8 quantizado | ~20 GB | 24 GB |
| GGUF Q4 | ~10 GB | 16 GB |

### Custom Node
`ComfyUI-HunyuanVideoWrapper`

---

## Wan2.1

**Origen**: Alibaba DAMO Academy

### Variantes
| Modelo | Tipo | Resolución | VRAM |
|--------|------|-----------|------|
| Wan2.1-T2V-1.3B | Text-to-video | 480p | 8 GB |
| Wan2.1-T2V-14B | Text-to-video | 720p | 24 GB |
| Wan2.1-I2V-14B-480P | Image-to-video | 480p | 24 GB |
| Wan2.1-I2V-14B-720P | Image-to-video | 720p | 32 GB |

### Puntos fuertes
- Excelente para movimiento realista
- Soporte multilenguaje
- LoRAs disponibles en la comunidad

### Custom Node
`ComfyUI-WanVideoWrapper`

---

## Stable Video Diffusion (SVD)

Ver [AnimateDiff y Video](16-animatediff-video.md) para detalle completo.

Resumen rápido:
- Image-to-video (no text-to-video)
- 14 o 25 frames
- VRAM: 14–16 GB
- El más maduro para integración en workflows complejos

---

## Comparación de Modelos

| Modelo | Calidad | Velocidad | VRAM mín | t2v | i2v | Óptimo para |
|--------|---------|-----------|----------|-----|-----|------------|
| AnimateDiff | Media | Moderada | 8 GB | ✅ | ✅ | SD1.5, muchos LoRAs |
| SVD | Media-alta | Moderada | 14 GB | ❌ | ✅ | Movimiento cámara |
| CogVideoX-2b | Alta | Lenta | 12 GB | ✅ | ✅ | Instrucciones complejas |
| LTX Video | Media | Muy rápida | 12 GB | ✅ | ✅ | Prototipado rápido |
| Wan2.1-1.3B | Media | Moderada | 8 GB | ✅ | ❌ | Hardware consumer |
| Wan2.1-14B | Alta | Lenta | 24 GB | ✅ | ✅ | Calidad profesional |
| Hunyuan Video | Muy alta | Muy lenta | 24 GB | ✅ | ✅ | Máxima calidad |
| Mochi-1 | Alta | Lenta | 16 GB | ✅ | ❌ | Física y movimiento |

---

## LoRAs para Modelos de Video

Al igual que SD, los modelos de video admiten LoRAs:

- **Wan2.1 LoRAs**: estilos, movimientos específicos, personajes
- **CogVideoX LoRAs**: disponibles en Hugging Face y Civitai
- **AnimateDiff Motion LoRAs**: ver sección 16

La comunidad aún está creciendo; buscar en Hugging Face por `{modelo}-lora`.

---

## Flujo Recomendado para Hardware Consumer (8–16 GB)

1. **Prototipado rápido**: LTX Video o Wan2.1-1.3B
2. **Calidad media**: CogVideoX-2b o AnimateDiff con SD1.5
3. **Calidad alta**: Wan2.1-14B en FP8 o Hunyuan Video GGUF

---

*[← Workflows de Referencia](23-workflows-referencia.md) | [Siguiente: Consistencia Temporal →](25-consistencia-temporal.md)*
