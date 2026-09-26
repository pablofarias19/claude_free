# 11 — Inpainting

Editar solo una zona específica de una imagen preservando el resto.

---

## Concepto

Inpainting usa una **máscara** para indicar qué parte regenerar:
- **Blanco** = zona a regenerar
- **Negro** = zona a preservar

---

## Métodos de Inpainting

### Método 1: Inpainting básico (con cualquier modelo)

Usa un modelo normal con `Set Latent Noise Mask`:

```
[Load Image] ── IMAGE ── [VAE Encode] ── LATENT
[Load Image Mask] ── MASK ── [Set Latent Noise Mask] ── LATENT con máscara
                                       ↓
                              [KSampler] con denoise < 1.0
```

### Método 2: Modelo de inpainting dedicado

Algunos checkpoints tienen una versión `-inpainting` entrenada específicamente. Aceptan 9 canales de entrada (4 imagen + 1 máscara + 4 imagen original).

**Nodo especial**: `VAE Encode for Inpainting`

```
[Load Image] ───────────┬── [VAE Encode for Inpainting] ── LATENT
[Load Mask]  ───────────┤
[Load Checkpoint inpainting] ── MODEL ── [KSampler denoise=1.0]
```

### Método 3: Inpainting con ControlNet Inpaint

El más potente. Usa un ControlNet entrenado para inpainting que da más coherencia con el contexto alrededor de la máscara.

---

## Nodos de Máscara

### Load Image (con máscara)
Si la imagen tiene canal alpha, se extrae automáticamente como MASK.

### Image to Mask
Convierte canales de una imagen (R, G, B, A) en máscara.

### Invert Mask
Invierte blanco y negro de la máscara.

### Grow Mask
Expande el área de la máscara por N píxeles. Útil para suavizar bordes.

### Set Latent Noise Mask
Aplica la máscara al latente para que el KSampler solo modifique esa zona.

---

## Nodo de Edición Visual de Máscaras

ComfyUI incluye un editor visual de máscaras integrado. Hacer clic derecho en un nodo Load Image → **"Open in MaskEditor"** para pintar la máscara directamente sobre la imagen.

---

## Parámetros Clave

### denoise
- `0.3–0.5`: Cambios sutiles que respetan el contexto
- `0.7–0.85`: Cambios moderados
- `1.0`: Regeneración completa (ignorar la imagen original en esa zona)

### padding / grow_mask
Ampliar ligeramente la máscara ayuda a la coherencia entre la zona editada y la zona preservada. Recomendado: 10–32 píxeles de expansión.

---

## Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| El borde de la zona editada es visible | Máscara muy exacta | Usar Grow Mask |
| La zona editada no respeta el estilo | denoise muy alto | Bajar a 0.5–0.7 |
| Error de dimensiones | VAE Encode for Inpainting con modelo no-inpainting | Usar Set Latent Noise Mask en su lugar |

---

*[← Upscalers](10-upscalers.md) | [Siguiente: Workflows y JSON →](12-workflows-y-json.md)*
