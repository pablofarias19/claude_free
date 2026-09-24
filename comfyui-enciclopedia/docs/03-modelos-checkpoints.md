# 03 — Modelos y Checkpoints

Un checkpoint es el cerebro del sistema. Contiene todo el conocimiento visual aprendido en millones de imágenes.

---

## Familias de Modelos

### Stable Diffusion 1.5 (SD1.5)
- **Resolución nativa**: 512×512
- **VRAM mínima**: 4 GB (con optimizaciones), 6 GB cómodo
- **Formato latente**: 4 canales, factor 8x
- **CLIP**: 1 encoder (ViT-L/14)
- **Ventajas**: Gran ecosistema de LoRAs y modelos fine-tuned, rápido
- **Desventajas**: Calidad inferior a SDXL y Flux en faces y detalles
- **Modelos populares**: Realistic Vision, DreamShaper, Anything V5, RevAnimated

### Stable Diffusion 2.1 (SD2.1)
- **Resolución nativa**: 768×768
- **CLIP**: OpenCLIP (diferente a SD1.5, **incompatible** con LoRAs de SD1.5)
- **Uso**: Casi abandonado por la comunidad. Evitar salvo necesidad específica.

### SDXL (Stable Diffusion XL)
- **Resolución nativa**: 1024×1024
- **VRAM mínima**: 8 GB, cómodo con 12 GB
- **CLIP**: 2 encoders (CLIP-L + CLIP-G)
- **Arquitectura**: Base model + Refiner (opcional pero recomendado)
- **Ventajas**: Mayor calidad, mejor comprensión de prompts, colores más ricos
- **Desventajas**: Más lento, más VRAM
- **Modelos populares**: Juggernaut XL, RealVisXL, Animagine XL, SDXL Base 1.0

### Flux.1 (Black Forest Labs, 2024)
- **Resolución nativa**: 1024×1024 (flexible)
- **VRAM mínima**: 12 GB (quantizado), 24 GB (completo)
- **Arquitectura**: Transformer de doble flujo (no UNet clásico)
- **CLIP**: CLIP-L + T5-XXL
- **Variantes**:
  - `Flux.1-dev`: Alta calidad, uso personal/investigación
  - `Flux.1-schnell`: Más rápido (4–8 steps), licencia Apache
- **Ventajas**: Mejor calidad fotorrealista, composición superior, texto en imagen
- **Desventajas**: Muy pesado, requiere hardware potente

### Stable Diffusion 3 (SD3)
- **Arquitectura**: Multimodal Diffusion Transformer (MMDiT)
- **Encoders**: CLIP-L + CLIP-G + T5-XXL
- **Variantes**: SD3-medium, SD3.5-large, SD3.5-large-turbo
- **Ventajas**: Excelente con texto y tipografía en imagen

---

## Tipos de Checkpoints

### Base
Modelo general entrenado desde cero. Punto de partida para todo.

### Fine-tuned / Merge
Modelo base ajustado con datos específicos (fotorrealismo, anime, pintura, etc.). Son los más usados en la práctica.

### Inpainting
Variante especial del modelo entrenada específicamente para inpainting. Acepta 9 canales de entrada en lugar de 4. **No** es intercambiable con el modelo base para generación normal.

### Refiner (SDXL)
Modelo especializado en los últimos pasos del denoising. Solo compatible con SDXL base.

---

## Dónde conseguir modelos

- **Civitai** (civitai.com) — mayor repositorio de la comunidad
- **Hugging Face** (huggingface.co) — modelos oficiales y de investigación

### Dónde colocarlos
```
ComfyUI/models/checkpoints/   ← aquí
```

---

## Formatos de Archivo

| Formato | Seguridad | Velocidad de carga | Recomendado |
|---------|-----------|-------------------|-------------|
| `.safetensors` | ✅ Seguro | Rápida | Sí |
| `.ckpt` | ⚠️ Puede ejecutar código | Lenta | No |

**Siempre prefiere `.safetensors`** si tienes opción.

---

## Quantización (para modelos grandes)

Los modelos Flux y SD3 son muy grandes. La quantización reduce su tamaño a cambio de pequeña pérdida de calidad:

| Precisión | Tamaño aproximado | VRAM necesaria |
|-----------|------------------|----------------|
| FP32 | ~24 GB | ~24 GB |
| FP16/BF16 | ~12 GB | ~12 GB |
| FP8 | ~6 GB | ~8 GB |
| NF4/GGUF Q4 | ~4 GB | ~6 GB |

Para Flux con 8–12 GB de VRAM: usa versiones FP8 o GGUF.

---

## Identificar el tipo de modelo

Si no sabes qué arquitectura es un checkpoint:
1. Mira el nombre del archivo — casi siempre lo indica
2. Tamaño: SD1.5 ≈2GB, SDXL ≈6GB, Flux ≈12–24GB
3. En ComfyUI: si cargarlo da error sobre canales o tamaños incompatibles, estamos usando el nodo equivocado para esa arquitectura

---

*[← Nodos Fundamentales](02-nodos-fundamentales.md) | [Siguiente: VAE →](04-vae.md)*
