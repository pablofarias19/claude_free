# 23 — Workflows de Referencia

Estructuras de workflows completos para los casos de uso más comunes.

---

## 1. Text-to-Image Mínimo (SD1.5)

```
[CheckpointLoaderSimple]
  ├─ MODEL ──────────────────────────────┬─[KSampler]
  ├─ CLIP ── [CLIPTextEncode+] ── COND+ ──┤
  ├─ CLIP ── [CLIPTextEncode-] ── COND- ──┤
  └─ VAE ─────────────────────────┬[VAEDecode]
[EmptyLatentImage] ── LATENT ─────────────┘
                                          └─[SaveImage]

KSampler: euler / karras / 20 steps / CFG 7 / denoise 1.0
```

---

## 2. Text-to-Image con Hi-Res Fix

```
[Checkpoint] ── [KSampler 512px, denoise=1.0] ── LATENT
                                                      ↓
                                          [LatentUpscaleBy x1.5]
                                                      ↓
                                     [KSampler 768px, denoise=0.5]
                                                      ↓
                                               [VAEDecode]
                                                      ↓
                                               [SaveImage]

Segundo KSampler: mismos conditioning y modelo, mismo o diferente seed.
```

---

## 3. SDXL Base + Refiner

```
[CheckpointLoaderSimple: sdxl_base]
  └─ MODEL/CLIP/VAE

[CheckpointLoaderSimple: sdxl_refiner]
  └─ MODEL (solo MODEL, no CLIP ni VAE del refiner)

Base:
[KSamplerAdvanced]
  add_noise: true
  start_at_step: 0
  end_at_step: 20
  return_with_leftover_noise: true

Refiner:
[KSamplerAdvanced]
  add_noise: false
  start_at_step: 20
  end_at_step: 30
  model: el MODEL del refiner
  conditioning: del CLIP del BASE (no del refiner)

Sal: VAEDecode con el VAE del BASE
```

---

## 4. Img2Img básico

```
[LoadImage] ── IMAGE ── [VAEEncode] ── LATENT
                              ↑
                          VAE del checkpoint
                              ↓
                       [KSampler denoise=0.6]
                              ↓
                          [VAEDecode]
                              ↓
                          [SaveImage]
```

---

## 5. Inpainting con Máscara

```
[LoadImage] ───────────── IMAGE ── [VAEEncode] ── LATENT
                                              ↑
[LoadImage mask=true] ── MASK ── [SetLatentNoiseMask]
                                              ↓
                                    [KSampler denoise=0.85]
                                              ↓
                                          [VAEDecode]
                                              ↓
                                          [SaveImage]
```

---

## 6. Con ControlNet (Canny)

```
[LoadImage] ── [CannyEdgePreprocessor] ── imagen de control
                                               ↓
[ControlNetLoader] ── [ApplyControlNet strength=0.8] ── CONDITIONING modificado
                          ↑                         ↓
                    COND+ del texto              al KSampler positive
```

---

## 7. Flux Text-to-Image

```
[UNETLoader: flux1-dev-fp8] ── MODEL
[DualCLIPLoader: clip_l + t5xxl] ── CLIP
[VAELoader: ae.safetensors] ── VAE

[CLIPTextEncode] ── COND ── [FluxGuidance: 3.5] ── COND+ modificado
[CLIPTextEncode] ── COND- (puede ser vacío o misma frase)

[EmptyLatentImage 1024x1024]

[KSampler]
  sampler: euler
  scheduler: normal
  steps: 20
  cfg: 1.0   ← el guidance va en FluxGuidance, no aquí
  denoise: 1.0

[VAEDecode] → [SaveImage]
```

---

## 8. FaceDetailer Post-Proceso

```
[Cualquier workflow] ── IMAGE
        ↓
[FaceDetailer]
  guide_size: 512
  denoise: 0.45
  bbox_detector: face_yolov8m.pt
  feather: 20
        ↓
   IMAGE mejorada
        ↓
   [SaveImage]
```

---

## Consejos para Organizar Workflows Complejos

1. **Usar Groups**: agrupar nodos por función (carga de modelos, generación, post-proceso)
2. **Usar colores de nodo**: clic derecho → Color para identificar visualmente cada sección
3. **Usar Notes**: documentar parámetros no obvios directamente en el canvas
4. **Nombrar outputs**: usar nodos con `filename_prefix` descriptivos
5. **Guardar versiones**: guardar el workflow como JSON con versiones incrementales

---

*[← Optimización](22-optimizacion-rendimiento.md) | [Volver al README](../README.md)*
