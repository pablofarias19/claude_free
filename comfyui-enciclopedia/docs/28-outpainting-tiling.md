# 28 — Outpainting y Tiled Generation

Extender imágenes más allá de sus bordes y generar imágenes muy grandes en tiles.

---

## Outpainting

Outpainting genera contenido nuevo alrededor de una imagen existente, extendiendo la escena.

### Método 1: ImagePadForOutpainting

**Nodo nativo de ComfyUI**:
```
[LoadImage] ── IMAGE
        ↓
[ImagePadForOutpainting]
  left:   pixels a agregar a la izquierda
  top:    pixels a agregar arriba
  right:  pixels a agregar a la derecha
  bottom: pixels a agregar abajo
  feathering: suavizado del borde (0 = abrupto, 40 = suave)
        ↓
  IMAGE extendida (con negro en zonas nuevas)
  MASK   (blanco en zonas nuevas, negro en original)
        ↓
[VAEEncode] → [SetLatentNoiseMask] → [KSampler denoise=1.0]
```

### Método 2: Outpainting con VAE Encode for Inpainting

Mejor coherencia con el contexto original:
```
[ImagePadForOutpainting] ── IMAGE, MASK
        ↓
[VAE Encode for Inpainting (grow_mask_by=8)] ── LATENT
        ↓
[KSampler modelo inpainting, denoise=1.0]
```

---

## Configuración para Outpainting de Calidad

**Errores comunes**:
- Borde visible entre original y generado → aumentar `feathering` (30–60px)
- Contenido generado incoherente con original → agregar descripción de la zona en el prompt
- La imagen original cambia → verificar que la máscara esté correcta (solo blanco en zonas nuevas)

**Prompt para outpainting**: describir lo que DEBERÍA verse en la extensión, no la imagen original.

---

## Outpainting Progresivo

Para extensiones muy grandes, hacerlo en múltiples pasos:

```
Imagen original 512px
↓
Extender 256px derecha → imagen 768px
↓
Extender 256px derecha → imagen 1024px
↓
Extender 256px arriba → imagen 1024x1280px
```

Cada paso usa la imagen previa como base. Más coherente que extender todo de una vez.

---

## Tiled Generation

Generar imágenes muy grandes dividiéndolas en tiles que se procesan por separado.

### ¿Cuándo usar?
- Imagen final mayor a 2048px en cualquier dimensión
- VRAM insuficiente para generar la imagen completa
- Upscaling de imágenes con añadido de detalle

---

## Ultimate SD Upscale (Tiled)

El método de tiling más popular. Ver [Upscalers](10-upscalers.md) para base.

### Parámetros clave
- `tile_width` / `tile_height`: tamaño de cada tile (512 para SD1.5, 1024 para SDXL)
- `mask_blur`: suavizado de bordes entre tiles (8–16)
- `tile_padding`: overlap entre tiles para evitar costuras (32–64)
- `seam_fix_mode`: método para corregir costuras (Band Pass, Half Tile, etc.)
- `force_uniform_tiles`: fuerza tiles del mismo tamaño

### Modos de seam fix
| Modo | Descripción |
|------|------------|
| None | Sin corrección (más rápido) |
| Band Pass | Reprocesa solo la banda entre tiles |
| Half Tile | Reprocesa medio tile en los bordes |
| Half Tile + Intersections | Mejor calidad, más lento |

---

## ControlNet Tile para Tiling

ControlNet Tile es específico para generación en tiles. Añade detalle sin cambiar la composición:

```
[Imagen upscalada (bicubic)] → [ControlNet Tile]
        ↓
[KSampler denoise=0.35–0.5]
```

El modelo ControlNet `control_v11f1e_sd15_tile.pth` ve el tile como referencia y añade detalle preservando la estructura.

---

## Texturas Seamless (Sin Costuras)

Generar texturas que se repiten sin bordes visibles:

### Método 1: Modo Tiling del checkpoint
`CheckpointLoaderSimple` + nodo `ModelSamplingDiscrete` con `tiling=True` (disponible en algunos forks).

### Método 2: Circular padding
Algunos custom nodes permiten padding circular durante la generación:
- La imagen se trata como si fuera cilíndrica/tórica
- Los bordes se conectan con el lado opuesto
- Resultado: textura perfectamente tileable

Buscar: `ComfyUI-SeamlessTiling` o `comfyui_seamless_tiling`.

### Verificar el resultado
Duplicar la imagen 2×2 y revisar si se ven costuras en los bordes.

---

## Workflow: Imagen 4K desde 1024px

```
1. Generar imagen base 1024×1024
        ↓
2. [Upscale Image Using Model ESRGAN x4] → 4096×4096
        ↓
3. [Ultimate SD Upscale]
     tile_size: 1024
     denoise: 0.3
     seam_fix: Half Tile + Intersections
        ↓
4. [SaveImage] → imagen 4K con detalle
```

---

*[← SD3 y Arquitecturas](27-sd3-arquitecturas.md) | [Siguiente: Entrenamiento de LoRA →](29-entrenamiento-lora.md)*
