# 47 — Differential Diffusion y StyleAligned

> **DECLARACIÓN TÉCNICA PARA IA**: Este documento cubre dos técnicas avanzadas de control fino en ComfyUI: **Differential Diffusion** (denoising variable por zona) y **StyleAligned** (coherencia de estilo en batch). Incluye workflows, parámetros críticos, casos de uso, condiciones excepcionales y recursos. Optimizado para RTX 5080 (16 GB VRAM) con SDXL y Flux.

---

## Differential Diffusion

### ¿Qué es?

Differential Diffusion permite aplicar **niveles distintos de denoising en diferentes zonas de la imagen** usando un mapa de intensidad (grayscale). Las zonas blancas reciben el máximo denoising (se regeneran completamente), las zonas negras quedan intactas, y los grises intermedios producen transiciones suaves.

```
Mapa de intensidad:
  Blanco (255) → denoise = 1.0 (regeneración total)
  Gris 50%     → denoise = 0.5 (cambio moderado)
  Negro (0)    → denoise = 0.0 (sin cambio)
```

**Diferencia clave vs Inpainting clásico:**

| Característica | Inpainting | Differential Diffusion |
|---|---|---|
| Límite | Borde duro máscara | Transición gradual |
| Zona preservada | Completamente fija | Puede recibir denoise mínimo |
| Artefactos de borde | Frecuentes | Muy reducidos |
| Control granular | Binario (sí/no) | Continuo (0.0–1.0) |
| Mejor para | Remplazo de objetos | Estilización regional, armonización |

---

### Instalación del custom node

```
Repositorio: https://github.com/exx8/differential-diffusion
Instalación vía ComfyUI Manager: buscar "Differential Diffusion"

Nodo principal: ApplyDifferentialDiffusion
Ubicación en Manager: Custom Nodes → differential-diffusion
```

---

### Workflow básico

```
[Load Checkpoint]
      │
      ├─── [CLIP Text Encode] → positive
      ├─── [CLIP Text Encode] → negative
      │
[VAE Encode] (imagen original)
      │
      ├─── latent
      │
[ApplyDifferentialDiffusion]
  ├── latent: (desde VAE Encode)
  └── differential_map: (imagen grayscale 0–255)
      │
      └── latent_out ──► [KSampler]
                           ├── model
                           ├── positive
                           ├── negative
                           ├── denoise: 1.0 ← IMPORTANTE: dejar en 1.0
                           └── ...
                              │
                           [VAE Decode] → [Save Image]
```

> **CE-DD001 (CRÍTICO)**: El denoise del KSampler debe ser **1.0** con Differential Diffusion. La variación de intensidad la controla el mapa, NO el parámetro denoise del KSampler. Si se usa denoise < 1.0 + mapa, el resultado es impredecible.

---

### Crear el mapa de intensidad

**Opción A — Con nodos de imagen en ComfyUI:**

```
[Load Image] (imagen original)
      │
[ImageToMask] → canal alpha o luminosidad
      │
[MaskToImage]
      │
[ImageBlur] ← radio 10–30 para transiciones suaves
      │
→ differential_map
```

**Opción B — Exterior (Photoshop/GIMP):**
- Pintar en escala de grises donde blanco = zona a cambiar
- Aplicar Gaussian Blur (15–30px) para evitar bordes duros
- Guardar como PNG sin alpha
- Importar con [Load Image]

**Opción C — Desde máscara de segmentación:**

```
[SAM Segmentation] o [YOLO Detector]
      │
[MaskToImage]
      │
[ImageBlur] radio=20
      │
[ImageColorHSV] ← ajustar luminosidad si necesario
      │
→ differential_map
```

---

### Casos de uso principales

**1. Cambiar fondo preservando sujeto con transición suave:**

```yaml
mapa: sujeto=negro (0), fondo=blanco (255), bordes=gradiente
prompt: "misma persona, fondo [nuevo_fondo], fotografía realista"
denoise_ksampler: 1.0
modelo: SDXL o Flux
resultado: fondo completamente regenerado, sujeto preservado, sin bordes
```

**2. Estilizar solo cara/piel sin tocar ropa:**

```yaml
mapa: cara+piel=blanco, ropa+fondo=negro, transición=gradiente 40px
prompt: "portrait, [nuevo_estilo_para_cara]"
denoise_ksampler: 1.0
cfg: 7.0 (SDXL) o FluxGuidance 3.0 (Flux)
```

**3. Armonizar composición de imagen (diferentes fuentes):**

```yaml
situación: imagen con elementos de diferentes fuentes que no cohesionan
mapa: zonas de "costura" = gris 50-70%, zonas problemáticas = blanco
prompt: descripción general unificada del resultado deseado
pasos: 15–20 (menos pasos preserva más las zonas grises)
```

**4. Actualización de detalles en generación por partes:**

```yaml
# Imagen grande generada en tiles que no cohesionan
mapa: solapamiento entre tiles = gris 40-60%
pasos: 10–15
denoise: 1.0
resultado: cohesión suave entre tiles sin regenerar todo
```

---

### Parámetros de referencia por modelo

| Modelo | Steps | CFG/Guidance | Sampler | Mapa blur | Notas |
|---|---|---|---|---|---|
| SDXL | 25 | cfg=7.0 | dpm++ 2m karras | 20–30px | Óptimo para inpainting zonal |
| Flux dev | 28 | guidance=3.5 | euler | 15–25px | Más predecible |
| SD1.5 | 20 | cfg=7.0 | euler | 10–20px | Rápido, menos detalle |
| DreamShaper XL | 25 | cfg=7.0 | dpm++ 2m | 20px | Artístico, buen rendimiento |

---

## StyleAligned

### ¿Qué es?

**StyleAligned** es una técnica que garantiza coherencia de estilo visual entre múltiples imágenes generadas en el mismo batch. Basada en el paper de Google Research (2023), funciona modificando la atención del modelo para que las imágenes "se vean entre sí" durante la difusión.

```
Sin StyleAligned: 4 imágenes con mismo prompt → 4 estilos diferentes
Con StyleAligned: 4 imágenes con mismo prompt → mismo estilo unificado
```

**Usos principales:**
- Series/colecciones consistentes
- Storyboards y cómics
- Conjuntos de productos con mismo look
- Variaciones de personaje manteniendo identidad visual

---

### Instalación

```
Repositorio: https://github.com/brianfitzgerald/style_aligned_comfy
Alternativa:  Está integrado en ComfyUI-Inspire-Pack (nodo StyleAlignedBatchAlign)

Instalación:
  Manager → Search: "style aligned" → instalar "style_aligned_comfy"
  O vía git: ComfyUI/custom_nodes/style_aligned_comfy
```

---

### Nodos disponibles

```
StyleAlignedSampleReferenceLatents   ← genera latentes de referencia
StyleAlignedBatchAlign               ← aplica alineación al batch
```

**Workflow StyleAligned básico:**

```
[Load Checkpoint]
      │
[CLIP Text Encode] × N (un prompt por imagen)
      │
[StyleAlignedSampleReferenceLatents]
  ├── model
  ├── positive: conditioning_ref (imagen de referencia de estilo)
  ├── negative
  ├── latent: (batch de latentes vacíos)
  └── share_attn: "q+k" ← controla qué se comparte
      │
[KSampler]
  ├── latent: (desde StyleAligned)
  ├── batch_size: N (número de imágenes)
  └── ...
      │
[VAE Decode]
      │
[Save Image]
```

---

### Configuración de share_attn

```
"q"     → compartir solo queries (mínima influencia)
"k"     → compartir solo keys (influencia media)
"q+k"   → compartir queries + keys (recomendado — balance coherencia/variedad)
"q+k+v" → máxima coherencia (puede reducir variedad excesivamente)
```

> **CE-SA001**: Usar `q+k` como default. `q+k+v` produce imágenes casi idénticas, perdiendo la variedad deseada. Para series con variaciones (mismo personaje, distintas poses), `q+k` es el estándar.

---

### Parámetros críticos

```yaml
# StyleAligned óptimo para series
share_attn: "q+k"
batch_size: 4-8 (más de 8 puede causar incoherencia)
cfg: 7.0-8.0 (SDXL), 1.0+FluxGuidance=3.5 (Flux)
steps: 20-28
sampler: dpm++ 2m karras (SDXL), euler (Flux)

# Referencia de estilo
# Opción A: primera imagen del batch como referencia implícita
# Opción B: cargar imagen de estilo deseado como referencia explícita
```

---

### Combinar StyleAligned + ControlNet

Para series con poses controladas (storyboard, cómic):

```
[ControlNet] (DWPose) × N poses
      │
[StyleAligned] (coherencia de estilo)
      │
[KSampler batch]
      │
→ serie coherente en estilo Y pose
```

```yaml
# Parámetros para storyboard
controlnet_type: dwpose
controlnet_strength: 0.7-0.85
share_attn: "q+k"
batch_size: 4
modelo: SDXL fine-tune o Flux
```

---

### Workflow combinado: Differential + StyleAligned

Para series de imágenes donde se modifica una zona específica pero manteniendo coherencia de estilo entre todas:

```
[Imagen base] × N
      │
[Differential Diffusion] (zona a modificar variable)
      │
[StyleAligned] (aplica ANTES del KSampler)
      │
[KSampler]
      │
→ N imágenes: zona modificada + estilo unificado
```

> **CE-DD002**: Differential Diffusion y StyleAligned son **compatibles** cuando StyleAligned se aplica al modelo/conditioning ANTES del KSampler, y Differential al latent. No interferir en la misma dimensión del pipeline.

---

### Guía rápida según caso de uso

```
CASO: Cambiar fondo de foto
→ Differential Diffusion
→ mapa: fondo blanco, sujeto negro, blur 20px
→ denoise KSampler = 1.0

CASO: Serie de producto con mismo look
→ StyleAligned, share_attn=q+k, batch=4-6
→ Mismo prompt base + variaciones en descripción del producto

CASO: Storyboard de personaje
→ StyleAligned + ControlNet OpenPose por frame
→ LoRA del personaje, strength=0.7
→ share_attn=q+k

CASO: Estilizar zona específica en múltiples imágenes
→ Differential Diffusion por imagen + StyleAligned para coherencia
→ Steps reducidos (15-20) para preservar mejor las zonas fijas

CASO: Composite con múltiples elementos de distintas fuentes
→ Differential Diffusion con gradiente en zonas de unión
→ Steps bajos (12-15), alto blur en mapa (30-40px)
→ Prompt descriptivo de la escena completa
```

---

## Recursos y fuentes

```
DIFFERENTIAL DIFFUSION:
  Repositorio:            https://github.com/exx8/differential-diffusion
  Paper (arXiv):          https://arxiv.org/abs/2306.03278
  Demo oficial:           https://differential-diffusion.github.io

STYLEALIGNED:
  Repositorio ComfyUI:    https://github.com/brianfitzgerald/style_aligned_comfy
  Paper Google Research:  https://arxiv.org/abs/2312.02133
  Proyecto original:      https://style-aligned-gen.github.io
  Inspire Pack (alternativa): https://github.com/ltdrdata/ComfyUI-Inspire-Pack

WORKFLOWS COMUNIDAD:
  CivitAI StyleAligned:   https://civitai.com/search/models?query=style+aligned
  Reddit r/comfyui:       https://www.reddit.com/r/comfyui/
  ComfyUI workflows hub:  https://comfyworkflows.com
```

---

## Casos excepcionales documentados

```
CE-DD001: KSampler denoise DEBE ser 1.0 con Differential Diffusion
  El denoise variable lo controla exclusivamente el mapa grayscale
  denoise < 1.0 en KSampler + mapa = resultado impredecible/artefactos
  Ver sección workflow básico

CE-DD002: Compatibilidad Differential + StyleAligned
  Compatibles SI se aplican en dimensiones separadas del pipeline
  Differential → modifica el latent de entrada
  StyleAligned → modifica la atención del modelo
  No se solapan; pueden usarse juntos

CE-SA001: share_attn q+k+v reduce variedad excesivamente
  Para series con variaciones de poses/composición: usar q+k
  q+k+v: útil solo cuando se necesita copia casi exacta de estilo

CE-SA002: batch_size > 8 puede romper coherencia
  Con más de 8 imágenes la atención cruzada se diluye
  Para batches grandes: dividir en grupos de 4-6 usando misma imagen de referencia

CE-SA003: StyleAligned en Flux — soporte experimental
  El paper original usa arquitectura UNet (SD/SDXL)
  En Flux (MMDiT) el custom node puede no implementar correctamente
  Verificar comportamiento antes de producción con Flux
```
