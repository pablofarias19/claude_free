# 10 — Upscalers

Ampliar la resolución manteniendo o mejorando la calidad.

---

## Tipos de Upscaling

### 1. Latent Upscale (en espacio latente)
Amplía el tensor latente antes de decodificar. El sampler puede seguir refinando en alta resolución.

**Ventajas**: añade detalle nuevo y coherente
**Desventajas**: más lento, puede cambiar la composición

**Nodo**: `Upscale Latent` o `Latent Upscale By`
- `method`: nearest-exact, bilinear, area, bicubic, bislerp
- `scale_by`: factor de escala (ej. 2.0 = doble)

**Flujo hi-res fix**:
```
[KSampler 512px] ── LATENT ── [Latent Upscale x2] ── [KSampler denoise=0.5] ── [VAE Decode]
```

### 2. Pixel Upscale (modelos ESRGAN)
Amplía la imagen ya decodificada usando modelos de super resolución entrenados.

**Ventajas**: muy rápido, preserva la imagen original sin cambios de composición
**Desventajas**: no añade detalle nuevo, solo escala lo que hay

**Nodo**: `Upscale Image Using Model`
**Requiere**: modelo upscaler en `models/upscale_models/`

### 3. Image Upscale Simple
Interpolación básica sin modelo. Rápido pero sin mejora de calidad.

**Nodo**: `Upscale Image` con método bicubic o lanczos

---

## Modelos Upscaler Populares

| Modelo | Tipo | Factor | Mejor para |
|--------|------|--------|----------|
| RealESRGAN x4plus | General | x4 | Fotorrealismo, texturas |
| RealESRGAN x4plus-anime | Anime | x4 | Ilustraciones, anime |
| ESRGAN 4x | General | x4 | Balance velocidad/calidad |
| 4x-UltraSharp | Detalle | x4 | Cuando se quiere máxima nitidez |
| 4x-ClearRealityV1 | Fotorrealismo | x4 | Imágenes reales |
| 8x_NMKD-Superscale | General | x8 | Cuando se necesita x8 |

**Dónde colocarlos**: `ComfyUI/models/upscale_models/`

**Dónde descargar**: Hugging Face, OpenModelDB (openmodeldb.net)

---

## Hi-Res Fix Manual en ComfyUI

Equivalente al hi-res fix de Automatic1111:

```
1. Generar a resolución base (512 o 1024)
2. Latent Upscale x1.5 o x2
3. Segundo KSampler con denoise 0.4–0.6 (mismo seed o diferente)
4. VAE Decode final
```

**Por qué funciona**: el primer KSampler establece la composición, el segundo a mayor resolución añade detalle sin romper la estructura.

---

## Ultimate SD Upscale

Custom node popular para upscaling en tiles. Divide la imagen en partes, las procesa por separado con el sampler y las reensambla.

**Ventaja**: puede trabajar con imágenes muy grandes sin requerir VRAM proporcional.

Requiere instalar el custom node `Ultimate SD Upscale`.

---

*[← IP-Adapter](09-ip-adapter.md) | [Siguiente: Inpainting →](11-inpainting.md)*
