# 27 — SD3, SD3.5 y Arquitecturas Modernas

Stable Diffusion 3 introdujo una arquitectura radicalmente diferente: Multimodal Diffusion Transformer (MMDiT).

---

## ¿Qué cambia en SD3?

SD3 abandona el UNet de SD1.5/SDXL y adopta una arquitectura transformer pura:

```
SD1.5/SDXL:
  UNet (convoluciones + atención) + CLIP(s)

SD3/SD3.5:
  MMDiT (Multimodal Diffusion Transformer) + CLIP-L + CLIP-G + T5-XXL
```

---

## MMDiT: Cómo Funciona

El Multimodal Diffusion Transformer procesa imagen y texto en **streams separados** que se cruzan mediante atención bidireccional:

```
[Texto → T5-XXL + CLIP] ──┬── Atención cruzada ──┬── [Texto refinado]
[Imagen latente]         ──┤                        └── [Imagen refinada]
```

Ambo streams se influyen mutuamente en cada bloque. Esto permite una comprensión de texto superior.

---

## Triple Text Encoder

SD3 usa **tres** encoders de texto simultáneamente:

| Encoder | Tamaño | Especialidad |
|---------|--------|-------------|
| CLIP-L | ~250 MB | Conceptos visuales rápidos |
| CLIP-G | ~700 MB | Relaciones y composición |
| T5-XXL | ~10 GB | Comprensión de lenguaje natural compleja |

Los tres se combinan y se pasan al MMDiT. Puedes omitir T5 para ahorrar VRAM (con algo de perdida en instrucciones complejas).

---

## Variantes de SD3

| Modelo | Parámetros | VRAM mín | Notas |
|--------|-----------|----------|-------|
| SD3-medium | 2B | 8 GB | Primer modelo público |
| SD3.5-large | 8B | 16 GB | Mejor calidad |
| SD3.5-large-turbo | 8B | 16 GB | 4 steps, más rápido |
| SD3.5-medium | 2.5B | 8 GB | Balance calidad/velocidad |

---

## Carga en ComfyUI

SD3 se carga diferente a SD1.5/SDXL:

```
[CheckpointLoaderSimple] ← funciona si el .safetensors es el modelo unificado

# O por partes:
[UNETLoader: sd3_medium.safetensors] → MODEL
[CLIPLoader: clip_l/clip_g/t5xxl] → CLIP
[VAELoader: sd3_vae.safetensors] → VAE
```

**Nodo especial**: `TripleCLIPLoader` para cargar los tres encoders juntos.

### Omitir T5 para ahorrar VRAM
Con el `TripleCLIPLoader`, puedes dejar `t5xxl` vacío. El modelo funciona con solo CLIP-L + CLIP-G pero pierde capacidad de seguir instrucciones largas.

---

## KSampler para SD3

Configuración recomendada:

```
sampler: euler o dpmpp_2m
scheduler: sgm_uniform o simple
steps: 28 (SD3) / 4 (SD3.5 Turbo)
cfg: 4–7 (SD3) / 1 (Turbo)
```

---

## Diferencias de Prompting

### SD3 vs SD1.5
- SD3 entiende oraciones completas, no solo keywords
- Mejor con descripciones detalladas del estilo
- No necesita palabras como "masterpiece, best quality"
- El prompt negativo tiene menos impacto (arquitectura diferente)

### SD3 vs Flux
- Muy similares en filosofía de prompting
- SD3 tiende a mejor adherencia al prompt
- Flux tiende a mejor calidad fotográfica

---

## VAE de SD3

SD3 usa un VAE diferente al de SD1.5/SDXL:
- Factor de compresión: 8x (igual que los anteriores)
- **16 canales** en el espacio latente (vs 4 de SD1.5)
- NO es compatible con el VAE de SD1.5 ni SDXL

---

## LoRAs para SD3

Los LoRAs de SD3 son incompatibles con SD1.5/SDXL. La comunidad está creciendo:
- Buscar en Civitai/Hugging Face por “SD3.5”
- Entrenamiento con kohya_ss con soporte SD3
- Los LoRAs de SDXL **no funcionan** en SD3

---

## Playground v2.5 y otros DiT

Otros modelos que usan arquitectura DiT (Diffusion Transformer) compatible con ComfyUI:

| Modelo | Origen | Especialidad |
|--------|--------|-------------|
| Playground v2.5 | Playground AI | Estetica y colores |
| AuraFlow | Fal.ai | Open source, liviano |
| PixArt-Σ | Huawei Noah | Texto en imagen |
| Kolors | Kuaishou | Chino/Inglés, fotorrealismo |

Todos se cargan con nodos similares a SD3 (`UNETLoader` + `CLIPLoader` o `CheckpointLoader`).

---

*[← Video Edición](26-video-edicion.md) | [Siguiente: Outpainting y Tiling →](28-outpainting-tiling.md)*
