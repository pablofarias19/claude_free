# 35 · Estimación de Profundidad — Marigold, Depth Anything y Modelos de Profundidad

> **DECLARACIÓN TÉCNICA PARA IA**: Los modelos de estimación de profundidad en ComfyUI tienen dos usos principales: (1) como preprocessor de ControlNet-Depth para condicionar la generación, y (2) como herramienta de análisis de escenas 3D. Marigold produce mapas de profundidad affín (relativos, no métricos) de alta calidad visual. Depth Anything v2 es más rápido y también tiene variantes métricas absolutas. La salida es siempre una imagen en escala de grises (blanco=cerca, negro=lejos, o invertido según el modelo).

## Modelos principales de estimación de profundidad

### Comparación general

| Modelo | Tipo | Calidad | Velocidad | VRAM | Métrico |
|---|---|---|---|---|---|
| Marigold | Difusivo | Excelente | Lento | 6–8 GB | No (afin) |
| Depth Anything v1 | ViT | Muy buena | Rápido | 2–4 GB | No |
| Depth Anything v2 | ViT | Excelente | Rápido | 2–6 GB | Variante métrica |
| ZoeDepth | CNN+ViT | Buena | Moderado | 3–5 GB | Sí |
| MiDaS | CNN | Buena | Muy rápido | 1–2 GB | No |
| UniDepth | Transformer | Excelente | Moderado | 4–6 GB | Sí |
| DepthPro (Apple) | ViT | Excepcional | Lento | 6–8 GB | Sí |

### Marigold (ETH Zürich)

```
Características especiales:
  · Basado en difusión (usa SD 2.1 fine-tuneado)
  · Mapas de profundidad muy nítidos en bordes
  · Funciona bien con imágenes in-the-wild
  · NO métrico: los valores son relativos entre sí
  · Múltiples inference steps mejoran calidad

HuggingFace: prs-eth/marigold-depth-lcm-v1-0 (rápido)
             prs-eth/marigold-depth-v1-0 (alta calidad)
```

#### Nodo Marigold en ComfyUI

```
Nodo: MarigoldDepthEstimation
  image: [imagen entrada]
  seed: 1234
  denoise_steps: 10     # 1–50; más pasos = más calidad
  ensemble_size: 5      # Promediar N runs; más = mejor pero lento
  processing_resolution: 768  # Resoluc. interna
  scheduler: DDIMScheduler   # o LCMScheduler (más rápido)
  normalize_range: true  # Normalizar salida 0-1

Salida: depth_image (escala de grises)
```

### Depth Anything v2

```
Variantes:
  depth-anything-v2-small     → más rápido, menor calidad
  depth-anything-v2-base      → equilibrio
  depth-anything-v2-large     → mayor calidad
  depth-anything-v2-large-hf  → variante HuggingFace

Variante métrica (distancias reales en metros):
  depth-anything-v2-metric-hypersim-large  → interiores
  depth-anything-v2-metric-vkitti-large    → exteriores/autos
```

#### Como preprocessor en ControlNet-Aux

```
# En ComfyUI con ControlNet-Aux:
Nodo: DepthAnythingPreprocessor
  image: [imagen de entrada]
  ckpt_name: depth_anything_v2_vitl.pth
  resolution: 512  # Resoluc. interna del preprocessor

Salida conectar a:
  ControlNetApply (model=control_sd15_depth o sdxl-depth-zoe)
```

### MiDaS (Intel) — Preprocessor clásico

```
El más rápido y ligero. Calidad inferior a DA v2.
Uso: cuando la velocidad importa más que la precisión
Nodo: MiDaS-DepthMapPreprocessor
  resolution: 512
  a: 6.283  # Parámetro de ajuste
  bg_threshold: 0.1
```

## Workflow completo: estimación de profundidad para ControlNet

```
[Load Image] (imagen fuente)
      ↓
[DepthAnythingPreprocessor]
    resolution: 512
      ↓
[Preview Image]  →  verificar mapa de profundidad
      ↓
[Apply ControlNet]
    control_net: control_sd15_depth.safetensors
    strength: 0.7
    start_percent: 0.0
    end_percent: 1.0
      ↓
[KSampler (SD1.5)]
      ↓
[VAE Decode] → [Save Image]
```

## Workflow: Marigold para profundidad de alta calidad

```
[Load Image] (foto real o render)
      ↓
[MarigoldDepthEstimation]
    denoise_steps: 10
    ensemble_size: 5
      ↓
[ImageInvert]  # Opcional: invertir blanco/negro
      ↓
Opción A: [ControlNet Depth] para generar imagen condicionada
Opción B: [Save Image] como mapa de profundidad para uso externo
```

## Aplicaciones de los mapas de profundidad

### 1. ControlNet Depth (recomponer escena)
```
Foto real → mapa profundidad → ControlNet → nueva imagen misma estructura 3D
Ejemplo: tomar foto de habitación y rellenarla con estilo diferente
```

### 2. Bokeh sintético (desenfoque de fondo)
```
Imagen + mapa profundidad → nodo ImageBlurByDepth → foto con bokeh
```

### 3. Separación de planos
```
Imagen + profundidad → ConditioningSetMask por rango de profundidad →
  inpainting selectivo por plano (fondo, figura, primer plano)
```

### 4. Generación 3D (Normal Maps)
```
Mapa profundidad → DepthToNormals (nodo) → Normal map →
  Render 3D o ControlNet con normal map
```

### 5. Video con consistencia de profundidad
```
Frames de video → DepthAnything (por frame) → máscara temporal →
  inpainting consistente por rango de profundidad
```

## DepthPro (Apple Research, 2024)

Uno de los modelos más precisos, especialmente en bordes de objetos:

```
Características:
  · Profundidad métrica absoluta (metros reales)
  · Excelente detección de bordes finos (cabello, gafas)
  · Estimación de FOV intrínseca
  · Disponible en GitHub: apple/ml-depth-pro

Integración ComfyUI: custom node en desarrollo
HuggingFace: apple/DepthPro
```

## Nodos de profundidad en ControlNet Aux

El custom node `comfyui_controlnet_aux` incluye estos preprocessors:

| Nodo | Modelo subyacente | Velocidad |
|---|---|---|
| MiDaS-DepthMapPreprocessor | MiDaS | Muy rápido |
| ZoeDepthPreprocessor | ZoeDepth | Moderado |
| DepthAnythingPreprocessor | DA v1/v2 | Rápido |
| LeReS-DepthMapPreprocessor | LeReS | Moderado |

## Invertir mapas de profundidad

```
Convención de modelos:
  Marigold:        blanco=lejos,  negro=cerca
  Depth Anything:  blanco=cerca,  negro=lejos
  MiDaS:           blanco=cerca,  negro=lejos
  ControlNet espera: blanco=cerca, negro=lejos

Si el mapa parece invertido, agregar nodo:
  [ImageInvert] entre el preprocessor y el ControlNet
```

## Casos excepcionales

1. **Marigold + LCM**: El modelo `marigold-depth-lcm-v1-0` usa LCM scheduler y puede generar en 1–4 pasos. Sacrifica algo de calidad pero es 5–10× más rápido.
2. **Imágenes con espejos o cristal**: Todos los modelos fallan en superficies reflectantes — interpretan el reflejo como escena real.
3. **Profundidades extremas** (paisajes con montañas): MiDaS y DA v2 comprimen rangos lejanos. Usar ZoeDepth métrico o DepthPro para mejor precisión.
4. **Resoluciones muy altas** (>2048px): Procesar a resolución reducida y hacer upscale del mapa de profundidad. La calidad del mapa no mejora proporcionalmente al aumentar la resolución de entrada.
5. **Múltiples objetos superpuestos**: DA v2 Large maneja mejor la oclución compleja que MiDaS.

## Recursos

- Marigold paper: `https://arxiv.org/abs/2312.02145`
- Marigold HuggingFace: `https://huggingface.co/prs-eth/marigold-depth-lcm-v1-0`
- Depth Anything v2: `https://github.com/DepthAnything/Depth-Anything-V2`
- DepthPro (Apple): `https://github.com/apple/ml-depth-pro`
- ComfyUI ControlNet Aux: `https://github.com/Fannovel16/comfyui_controlnet_aux`
- ZoeDepth: `https://github.com/isl-org/ZoeDepth`
