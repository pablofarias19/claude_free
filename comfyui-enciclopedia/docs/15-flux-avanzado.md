# 15 — Flux Avanzado

Flux es la arquitectura más moderna disponible en ComfyUI. Requiere un enfoque diferente al de SD1.5 y SDXL.

---

## Arquitectura: ¿Por qué es diferente?

Flux no usa la arquitectura UNet clásica de Stable Diffusion. Usa un **Multimodal Diffusion Transformer (MMDiT)** de doble flujo:

```
Texto (CLIP-L + T5-XXL)
        ↓
[Double Stream Blocks] ←→ [Single Stream Blocks]
        ↓
    Imagen generada
```

- Los **Double Stream Blocks** procesan imagen y texto en paralelo con atención cruzada entre sí
- Los **Single Stream Blocks** fusionan ambas representaciones
- Esto permite una comprensión de texto mucho más profunda que UNet

---

## Variantes de Flux

| Variante | Licencia | Steps óptimos | CFG | Velocidad |
|----------|----------|---------------|-----|----------|
| Flux.1-dev | No comercial | 20–30 | 2.5–4.5 | Lento |
| Flux.1-schnell | Apache 2.0 | 4–8 | 1.0 | Muy rápido |
| Flux.1-dev-FP8 | No comercial | 20–30 | 2.5–4.5 | Moderado |

---

## Formatos y Cuantización para Flux

Flux completo (BF16) pesa ~24 GB. Para hardware consumer se usan versiones reducidas:

### FP8
- Tamaño: ~12 GB
- VRAM necesaria: ~12–16 GB
- Calidad: casi idéntica a BF16
- Carga con nodo `UNETLoader` (no `Load Checkpoint`)

### GGUF (Q4, Q5, Q8)
- Tamaño: 4–10 GB según quantización
- VRAM necesaria: 6–12 GB
- Requiere custom node: `ComfyUI-GGUF`
- Q8 ≈ calidad FP8, Q4 con pérdida visible

### NF4
- Tamaño: ~6 GB
- Requiere `bitsandbytes` library

---

## Carga de Flux en ComfyUI

Flux se carga diferente según el formato:

### Flux en formato checkpoint unificado
```
[Load Diffusion Model] → MODEL
[DualCLIPLoader] → CLIP  (clip_l + t5xxl)
[VAELoader] → VAE  (ae.safetensors)
```

### Flux en formato separado (dev/schnell oficiales)
```
models/unet/flux1-dev.safetensors
models/clip/clip_l.safetensors
models/clip/t5xxl_fp16.safetensors
models/vae/ae.safetensors
```

**Nodo clave**: `UNETLoader` para cargar solo el UNet de Flux.

---

## Guidance en Flux

Flux-dev usa **guidance distillation**: fue entrenado con un parámetro de guidance integrado. En ComfyUI:

**Nodo**: `FluxGuidance`
- Conectar entre el conditioning y el KSampler
- Valor recomendado: 2.5–4.5
- Flux-schnell: usar 0 o 1 (no necesita guidance)

```
[CLIP Text Encode] → CONDITIONING → [FluxGuidance (3.5)] → al KSampler (positive)
```

---

## LoRA para Flux

Los LoRAs de Flux funcionan con el mismo nodo `Load LoRA` pero tienen particularidades:

- La mayoría fueron entrenados con Flux-dev
- Strength recomendado: 0.8–1.0 (Flux es más sensible que SD)
- Algunos LoRAs de Flux incluyen el CLIP, otros no
- Verificar siempre si el LoRA requiere trigger words

### LoRAs especiales para Flux
- **Style LoRAs**: transfieren estilos artísticos específicos
- **Subject LoRAs**: personajes o objetos específicos
- **Turbo LoRAs**: reducen steps necesarios (similar a LCM para SD)

---

## Prompting con Flux

Flux con T5-XXL entiende lenguaje natural completo:

### SD-style (keywords) — menos óptimo para Flux
```
beautiful woman, red dress, sitting, park, golden hour, photorealistic, 8k
```

### Natural language — óptimo para Flux
```
A beautiful woman wearing a flowing red dress sitting on a park bench during golden hour,
warm sunlight filtering through the trees, photorealistic style, highly detailed
```

Flux también puede **escribir texto** en las imágenes con mucha más fiabilidad que SD:
```
A coffee shop sign that reads "Café Aurora" in elegant cursive lettering
```

---

## Configuración KSampler para Flux

```
sampler_name: euler
scheduler: normal (o simple)
steps: 20 (dev) / 4 (schnell)
cfg: 1.0 (el FluxGuidance reemplaza al CFG del sampler)
denoise: 1.0
```

**Importante**: Con el nodo `FluxGuidance`, poner CFG en el KSampler a **1.0**. El guidance va en el nodo dedicado.

---

## Resoluciones y Aspect Ratios en Flux

Flux es más flexible con resoluciones que SD. Resoluciones recomendadas:

| Ratio | Resolución |
|-------|----------|
| 1:1 | 1024×1024 |
| 16:9 | 1360×768 |
| 9:16 | 768×1360 |
| 4:3 | 1152×896 |
| 3:4 | 896×1152 |
| 2:3 | 832×1248 |

Flux acepta resoluciones no estándar mucho mejor que SDXL.

---

*[← Custom Nodes](13-custom-nodes.md) | [Siguiente: AnimateDiff y Video →](16-animatediff-video.md)*
