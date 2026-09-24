# 05 — CLIP y Procesamiento de Texto

El modelo no lee palabras. Lee vectores numéricos. CLIP es el traductor.

---

## ¿Qué es CLIP?

CLIP (Contrastive Language-Image Pretraining) es una red neuronal entrenada para relacionar imágenes con texto. Transforma tu prompt en un vector de alta dimensión que el UNet puede interpretar.

```
"a cat sitting on a red sofa"
            ↓ CLIP
[0.23, -0.87, 0.44, ..., 0.12]  ← vector de 768 o 1280 dimensiones
```

---

## CLIP por arquitectura de modelo

| Modelo | Encoders | Dimensión |
|--------|----------|----------|
| SD 1.5 | CLIP-L (ViT-L/14) | 768 |
| SD 2.1 | OpenCLIP ViT-H/14 | 1024 |
| SDXL | CLIP-L + CLIP-G | 768 + 1280 |
| Flux.1 | CLIP-L + T5-XXL | 768 + 4096 |
| SD 3.5 | CLIP-L + CLIP-G + T5-XXL | triple |

**Los encoders no son intercambiables entre arquitecturas.**

---

## Tokenización y el límite de 77 tokens

CLIP divide el texto en **tokens** (unidades de significado, no exactamente palabras):
- "photography" = 1 token
- "photorealistic" = 1 token  
- "supercalifragilistic" = varios tokens

**Límite de SD1.5 y SDXL**: 77 tokens por cláusula.

Si tu prompt es más largo, el texto se corta o se divide automáticamente según el nodo que uses.

**Solución para prompts largos**: usar nodos como `CLIPTextEncodeSDXL` (SDXL) o extensiones como WAS Node Suite que manejan prompts extendidos.

---

## CLIP Skip

CLIP tiene múltiples capas. El "skip" define cuántas capas finales se omiten al extraer el embedding:

- **Skip 1** (default): se usa la última capa. Más fidelidad al prompt literal.
- **Skip 2**: se omite la última capa. Estilo más abstracto/estético.

**Cuándo usar Skip 2**: la mayoría de modelos de anime fine-tuned (NovelAI, Anything, etc.) fueron entrenados con CLIP Skip 2. Usar Skip 1 con ellos da resultados menos óptimos.

En ComfyUI: nodo **CLIP Set Last Layer** con `stop_at_clip_layer = -2` (equivale a skip 2).

```
[Load Checkpoint] ── CLIP ── [CLIP Set Last Layer (stop=-2)] ── [CLIP Text Encode]
```

---

## T5-XXL (para Flux y SD3)

T5-XXL es un modelo de lenguaje muchísimo más capaz que CLIP. Diferencias clave:

| Característica | CLIP | T5-XXL |
|----------------|------|--------|
| Tamaño | ~250 MB | ~10 GB |
| Comprensión | Palabras clave | Oraciones completas |
| Límite tokens | 77 | 512+ |
| Prompts largos | Pierde coherencia | Funciona bien |

Con Flux puedes escribir prompts en **lenguaje natural** completo: "A photograph of a woman reading a book in a sunlit café in Paris, taken with a 50mm lens" funciona mejor que keywords concatenadas.

---

## Dual CLIP Loader (SDXL)

En SDXL, en lugar de `Load Checkpoint` → CLIP, puedes usar **Dual CLIP Loader** para cargar CLIP-L y CLIP-G por separado. Útil cuando:
- Usas modelos en formato separado (UNet + VAE + CLIP por separado)
- Quieres experimentar con diferentes combinaciones

---

## Nodo: CLIPTextEncodeSDXL

Variante específica para SDXL. Permite escribir prompts distintos para cada encoder:
- `text_g` — prompt para CLIP-G (conceptos de alto nivel, composición)
- `text_l` — prompt para CLIP-L (detalles, estilo)

En la práctica, muchos usuarios ponen el mismo texto en ambos. Pero experimentar con textos diferentes puede producir resultados interesantes.

---

## Consejos de Prompting por arquitectura

### SD1.5
- Usa palabras clave separadas por comas
- Los pesos funcionan bien: `(beautiful face:1.3), detailed eyes`
- Negative prompt muy importante: `ugly, blurry, bad anatomy, watermark`

### SDXL
- Acepta frases naturales mejor que SD1.5
- Negative prompt menos crítico
- No necesita tantas palabras de calidad como "masterpiece, best quality"

### Flux
- Prompts en lenguaje natural completo
- T5 entiende instrucciones: "Make sure the text says 'Hello' clearly"
- Negative prompt tiene efecto limitado (arquitectura diferente)

---

*[← VAE](04-vae.md) | [Siguiente: Samplers y Schedulers →](06-samplers-y-schedulers.md)*
