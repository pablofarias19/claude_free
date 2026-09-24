# 04 — VAE (Variational Autoencoder)

El VAE es el componente que traduce entre el espacio latente (donde trabaja el sampler) y el espacio de píxeles (lo que tú ves).

---

## ¿Qué hace exactamente?

```
Imagen real (píxeles)  ─── VAE Encode ───►  Latente (tensor 4D comprimido)
                                                         │
                                                  [KSampler trabaja aquí]
                                                         │
Imagen final (píxeles) ◄─── VAE Decode ─────────────
```

- **Encode**: imagen → latente (necesario para img2img e inpainting)
- **Decode**: latente → imagen (necesario al final de todo workflow)

---

## VAE incluido vs VAE externo

Todo checkpoint incluye un VAE internamente. Sin embargo, muchos checkpoints fine-tuned (especialmente de SD1.5) incluyen un VAE de baja calidad que produce:
- Colores lavados o grisáceos
- Pérdida de detalle en texturas finas
- Artefactos en piel y cabello

### Solución: cargar un VAE externo

Usa el nodo **Load VAE** y conectálo al VAE Decode (y VAE Encode) en lugar del VAE del checkpoint.

---

## VAEs Recomendados

### Para SD1.5
- **vae-ft-mse-840000-ema-pruned.safetensors** — el estándar de facto, mejor que la mayoría de VAEs incluidos
- **kl-f8-anime2.ckpt** — específico para estilos anime

### Para SDXL
- **sdxl_vae.safetensors** (oficial de StabilityAI) — el que viene en el checkpoint oficial es bueno
- **sdxl-vae-fp16-fix.safetensors** — versión estabilizada para evitar NaN en FP16

### Para Flux
- El VAE de Flux es diferente y viene incluido en el checkpoint. No se necesita externo.

---

## Dónde colocar los archivos VAE

```
ComfyUI/models/vae/
```

---

## Nodo: Load VAE

**Función**: Carga un VAE externo para reemplazar el del checkpoint.

**Salidas**: `VAE` → conectar al VAE Decode y/o VAE Encode

**Flujo con VAE externo**:
```
[Load Checkpoint] ─── MODEL, CLIP ─── [al KSampler y CLIP Text Encode]
[Load VAE]        ─── VAE ───────── [al VAE Decode] ← reemplaza el del checkpoint
```

---

## Diagnóstico: el VAE es el problema si…

| Síntoma | Causa probable |
|---------|----------------|
| Imágenes en escala de grises o muy desaturadas | VAE incorrecto o de baja calidad |
| Artefactos de cuadrícula o pixelado extremo | VAE corrupto o incompatible |
| Colores flácidos en SD1.5 | VAE interno deficiente, usar `vae-ft-mse-840000` |
| NaN/negro en SDXL con FP16 | Usar `sdxl-vae-fp16-fix` |
| Error de dimensiones al cargar | VAE de una arquitectura diferente |

---

## Nota sobre TAESD (Tiny Autoencoder)

TAESD es un VAE ultra-rápido y pequeño para **previsualización en tiempo real** (live preview). Baja calidad pero instantáneo. Algunos workflows de animación lo usan para previews. **No** usar para output final.

---

*[← Modelos](03-modelos-checkpoints.md) | [Siguiente: CLIP y Texto →](05-clip-y-texto.md)*
