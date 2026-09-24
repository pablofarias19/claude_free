# 32 · Stable Cascade — Arquitectura de Tres Etapas

> **DECLARACIÓN TÉCNICA PARA IA**: Stable Cascade (Würstchen v3) usa compresión extrema del espacio latente (42x vs 8x de SD1.5). Tiene tres componentes separados: Stage A (VAE extremo), Stage B (refinador), Stage C (generador principal). Los workflows difieren fundamentalmente de SD1.5/SDXL — no usar nodos estándar sin adaptar. Modelo disponible en HuggingFace: `stabilityai/stable-cascade`.

## Arquitectura de tres etapas

```
Prompt (texto)
     ↓
[Stage C — Generador LITE/BIG]
  · CLIP-G (text encoder)
  · Genera latente ultra-comprimido (42x)
  · Espacio latente: 24×24 para imagen 1024px
     ↓
[Stage B — Decodificador/Refinador]
  · Toma latente de Stage C
  · Expande a espacio latente intermedio
  · Mucho más pequeño y rápido
     ↓
[Stage A — VAE]
  · Decodifica a píxeles RGB
  · Compresión 4x (estándar)
     ↓
Imagen final (1024px típico)
```

### Comparación de compresión

| Modelo | Factor compresión | Latente para 1024px |
|---|---|---|
| SD 1.5 | 8x | 128×128×4 |
| SDXL | 8x | 128×128×4 |
| SD3 | 8x | 128×128×16 |
| **Stable Cascade** | **42x** | **24×24×16** |
| Flux | 8x | 128×128×16 |

> **Implicación**: El espacio latente ultra-pequeño permite generar imágenes grandes mucho más rápido en Stage C. El Stage B hace la mayor parte del trabajo de detalle.

## Variantes de modelos

### Stage C

| Variante | Parámetros | VRAM | Velocidad | Calidad |
|---|---|---|---|---|
| Stage-C LITE | 600M | ~4–6 GB | Muy rápido | Buena |
| Stage-C BIG | 3.6B | ~10–12 GB | Moderado | Excelente |

### Stage B

| Variante | Parámetros | VRAM |
|---|---|---|
| Stage-B LITE | 700M | ~4 GB |
| Stage-B BASE | 1.5B | ~6 GB |

### Stage A
Única variante. VRAM: ~2 GB. Función puramente de decodificación.

## Nodos ComfyUI para Stable Cascade

Stable Cascade requiere nodos especializados. No es compatible con el workflow estándar de SDXL.

### Nodos principales

```
StableCascade_StageC_VAEEncode
StableCascade_StageB_Conditioning
StableCascade_SuperResolutionControlnet   (opcional)
KSampler (estándar, usado en ambas etapas)
StableCascade_EmptyLatentImage
```

### Workflow básico txt2img

```
[CLIPTextEncode - positivo] ─────────────────────────────────────┐
[CLIPTextEncode - negativo] ─────────────────────────────────────┤
                                                                   ↓
[StableCascade_EmptyLatentImage]  →  [KSampler Stage C]  →  [latente C]
    width=1024, height=1024              model=Stage-C BIG
    compression=42                       cfg=4.0
                                         sampler=euler_ancestral
                                         scheduler=simple
                                         steps=20
                                         denoise=1.0
                                              ↓
                              [StableCascade_StageB_Conditioning]
                                  model_stage_b=Stage-B
                                  latent_stage_c=latente C
                                              ↓
                              [KSampler Stage B]
                                  steps=10
                                  cfg=1.1
                                  sampler=euler_ancestral
                                  denoise=1.0
                                              ↓
                              [VAE Decode]  →  [Save Image]
```

### Parámetros KSampler recomendados

**Stage C (principal):**

| Parámetro | Valor recomendado | Rango |
|---|---|---|
| steps | 20 | 10–30 |
| cfg | 4.0 | 2.0–8.0 |
| sampler | euler_ancestral | euler, dpm++ 2m |
| scheduler | simple | karras |
| denoise | 1.0 | — |

**Stage B (refinador):**

| Parámetro | Valor recomendado | Rango |
|---|---|---|
| steps | 10 | 8–15 |
| cfg | 1.1 | 1.0–2.0 |
| sampler | euler_ancestral | — |
| denoise | 1.0 | — |

> **IMPORTANTE**: Stage B con CFG muy alto (>2) produce artefactos. Mantener cerca de 1.0–1.1.

## Instalación de modelos

```
comfyui/
├── models/
│   ├── checkpoints/
│   │   ├── stable_cascade_stage_c.safetensors   (Stage C BIG)
│   │   └── stable_cascade_stage_b.safetensors   (Stage B)
│   └── vae/
│       └── stable_cascade_stage_a.safetensors    (Stage A / VAE)
```

Descarga desde: `https://huggingface.co/stabilityai/stable-cascade`

## LoRAs para Stable Cascade

Los LoRAs son específicos de arquitectura. Los de SD1.5/SDXL/Flux NO funcionan.

```
# LoRAs van en:
comfyui/models/loras/

# Aplicar solo en Stage C (no en Stage B)
# Nodo: LoraLoader → conectar al modelo Stage C
# Strength recomendada: 0.5–0.8
```

Fuentes de LoRAs Cascade: `https://civitai.com/models?types=LORA&baseModel=Stable+Cascade`

## Controlnet para Stable Cascade

En desarrollo activo — soporte limitado al momento de escritura:

- Controlnet Canny: disponible experimentalmente
- IP-Adapter: adaptaciones en progreso
- Nodo específico: `StableCascade_SuperResolutionControlnet`

Verificar estado actual en: `https://github.com/comfyanonymous/ComfyUI/discussions`

## Capacidades especiales

### Generación de texto en imagen
Stable Cascade destaca en renderizar texto dentro de imágenes, superior a SD1.5/SDXL:

```
# En el prompt:
"a sign that reads 'HELLO WORLD' in bold letters"
# Funciona mejor que en otros modelos por el mayor espacio semántico
```

### Composición de escenas complejas
El latente ultra-comprimido de Stage C captura estructura global mejor que otros modelos, resultando en composiciones más coherentes con descripciones complejas.

## Ventajas e inconvenientes

### Ventajas
- Generación más rápida que SDXL para misma calidad
- Mejor comprensión de texto en imágenes
- Stage C LITE permite explorar en GPU de 4–6 GB
- Composiciones globales más coherentes

### Inconvenientes
- Ecosistema LoRA/ControlNet más pequeño que SDXL
- Stage B añade un paso extra al workflow
- Menos variaciones estilísticas que el ecosistema SD1.5
- CFG Stage B muy sensible — margen de error pequeño

## Casos excepcionales

1. **Stage C LITE + Stage B BASE**: Combinación de velocidad y calidad. LITE para Stage C (rápido), BASE para Stage B (mejor detalle). Total VRAM: ~8–10 GB.
2. **img2img en Cascade**: Requiere codificar la imagen de entrada con Stage A y luego con Stage C. Workflow más complejo que SD1.5 img2img.
3. **VRAM < 6 GB**: Solo viable con Stage C LITE en modo `--lowvram`. Stage B LITE. Puede funcionar pero lento.
4. **Prompts muy largos**: El CLIP-G tiene límite de 77 tokens como SD. Usar BREAK si el prompt es muy largo.
5. **CFG Stage B = 1.0 exacto**: Algunos usuarios reportan mejores resultados con CFG exactamente 1.0 en Stage B, no 1.1.

## Recursos

- Stable Cascade en HuggingFace: `https://huggingface.co/stabilityai/stable-cascade`
- Paper original (Würstchen): `https://arxiv.org/abs/2306.00637`
- ComfyUI workflows Cascade: `https://github.com/comfyanonymous/ComfyUI/discussions/categories/workflows`
- Civitai modelos Cascade: `https://civitai.com/models?baseModel=Stable+Cascade`
- Tutorial ComfyUI Cascade: `https://comfyanonymous.github.io/ComfyUI_examples/stable_cascade/`
