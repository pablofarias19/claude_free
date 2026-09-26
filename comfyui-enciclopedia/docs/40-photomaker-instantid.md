# 40 · Preservación de Identidad — PhotoMaker, InstantID y PuLID

> **DECLARACIÓN TÉCNICA PARA IA**: Las herramientas de preservación de identidad permiten mantener los rasgos faciales de una persona real en imágenes generadas con distintos estilos, poses o escenarios. Son fundamentalmente distintas de los LoRA de personajes: no requieren entrenamiento, funcionan desde una sola foto de referencia. Las tres principales son PhotoMaker (generación desde referencia), InstantID (mayor fidelidad), y PuLID (compatible con Flux). IP-Adapter FaceID es la alternativa más ligera. Ninguno es 100% fiel — para máxima fidelidad combinar con ControlNet OpenPose.

## Comparativa de métodos de preservación de identidad

| Herramienta | Fidelidad | Velocidad | Compatibilidad | Requisitos VRAM |
|---|---|---|---|---|
| IP-Adapter FaceID | Media | Rápida | SD1.5, SDXL | 6-8 GB |
| PhotoMaker | Media-Alta | Moderada | SDXL | 8-10 GB |
| PhotoMaker v2 | Alta | Moderada | SDXL | 8-10 GB |
| InstantID | Alta | Moderada | SDXL | 10-12 GB |
| PuLID | Alta | Moderada | Flux | 12-14 GB |
| ReActor (swap) | Muy Alta* | Rápida | Cualquiera | 4-6 GB |

*ReActor hace face swap (post-proceso), no generación. Ver doc 17.

## PhotoMaker — Generación desde referencia facial

```
Desarrollador: TencentARC
Basado en: SDXL
Función: Incorpora identidad facial en el proceso de difusión
Entrada: 1 o más fotos de referencia de la persona
Salida: Imagen generada que preserva rasgos del sujeto

Modelo: photomaker-v2.bin
HuggingFace: TencentARC/PhotoMaker-V2

Palabra clave especial en prompt:
  img  (literal, dentro del prompt)
  Ejemplo: "a photo of img as a superhero"
  El token img se reemplaza por los embeddings faciales
```

### Instalación PhotoMaker

```bash
# Custom node:
git clone https://github.com/shiimizu/ComfyUI-PhotoMaker custom_nodes/ComfyUI-PhotoMaker
# o via ComfyUI Manager: buscar "PhotoMaker"

# Modelo:
# Descargar photomaker-v2.bin a models/photomaker/
```

### Workflow PhotoMaker

```
[Load Image (foto referencia)] ────────────────────────┐
      │                                                  │
[PhotoMakerLoader]                                      │
  photomaker_model_name: photomaker-v2.bin              │
      │                                                  │
[PhotoMakerEncode]                                      │
  photomaker: [model]                                   │
  image: [foto referencia]                              │
  clip: [CLIP del checkpoint SDXL]                      │
  trigger_word: "img"                                   │
  text: "a cinematic photo of img in Paris"             │
      ↓                                                  │
  [conditioning con identidad incorporada]              │
      │                                                  │
[KSampler (SDXL)] ←────────────────────────────────────┘
  cfg: 5.0, steps: 30, sampler: euler, scheduler: karras
      ↓
[VAE Decode] → [Save Image]
```

### Parámetros clave PhotoMaker

```
PhotoMakerEncode:
  style_strength_ratio: 20  # Qué tanto domina la identidad vs el estilo
                             # Rango: 10-50; mayor = más fiel al rostro

KSampler:
  cfg: 5.0-7.0  (PhotoMaker funciona mejor con CFG moderado)
  steps: 25-35
  denoise: 1.0 para txt2img
```

### Múltiples fotos de referencia (PhotoMaker)

```
# PhotoMaker acepta batch de imágenes para mejor promedio facial:
[Load Image 1] ─┐
[Load Image 2] ─┤ [ImageBatch] → [PhotoMakerEncode]
[Load Image 3] ─┘
  → Promedia los rasgos de las N fotos → mayor estabilidad
  Recomendado: 3-5 fotos con diferentes ángulos
```

## InstantID — Alta fidelidad facial

```
Desarrollador: InstantX-Team
Basado en: SDXL + ControlNet
Mejor que PhotoMaker en: fidelidad a rasgos específicos, cejas, forma de labios
Requiere adicionalmente: InsightFace (extrae embedding facial)

Modelos necesarios:
  ip-adapter.bin          → models/instantid/
  ControlNetModel/        → carpeta con config de ControlNet facial

HuggingFace: InstantX/InstantID
```

### Instalación InstantID

```bash
# Custom node:
git clone https://github.com/cubiq/ComfyUI_InstantID custom_nodes/ComfyUI_InstantID

# Dependencia adicional:
pip install insightface onnxruntime
# (onnxruntime-gpu si CUDA disponible)

# Modelo InsightFace:
# Descargar buffalo_l desde:
# https://github.com/deepinsight/insightface
# Ubicar en: models/insightface/models/buffalo_l/
```

### Workflow InstantID

```
[Load Image (foto referencia)] ──────────────────────────┐
      │                                                    │
[InstantIDFaceAnalysis]                                   │
  provider: CUDA  (o CPU si no hay CUDA)                  │
      │                                                    │
  face_embedding + keypoints                              │
      │                                                    │
[InstantIDModelLoader]                                    │
  instantid_file: ip-adapter.bin                          │
      │                                                    │
[ControlNetLoader]                                        │
  control_net_name: ControlNetModel (InstantID)           │
      │                                                    │
[ApplyInstantID]                                          │
  instantid: [model]                                      │
  insightface: [face analysis]                            │
  control_net: [ControlNet]                               │
  image: [foto referencia]                                │
  model: [SDXL checkpoint]                                │
  positive: [CLIP encode]                                 │
  negative: [CLIP encode]                                 │
  weight: 0.8       # Intensidad de la identidad          │
  start_at: 0.0     # Cuándo empieza a aplicarse          │
  end_at: 1.0       # Cuándo termina (0.7 para más estilo)│
      ↓                                                    │
[KSampler] ←─────────────────────────────────────────────┘
  cfg: 5.0, steps: 30
      ↓
[VAE Decode] → [Save Image]
```

### Parámetros InstantID recomendados

```
weight:
  0.5-0.7 → estilo artístico con rasgos preservados
  0.8-1.0 → máxima fidelidad facial (más fotorrealista)

end_at:
  0.7     → permite más creatividad en la composición
  1.0     → aplica identidad durante todo el proceso

Combinar con ControlNet OpenPose para control de pose:
  [OpenPose de foto objetivo] + [InstantID]
  → misma persona en la pose de la foto objetivo
```

## PuLID — Preservación de identidad para Flux

```
Desarrollador: ToTheBeginning
Compatible con: Flux.1 dev/schnell
Ventaja: Primero en ofrecer ID preservation de calidad en Flux

Modelo: pulid_flux_v0.9.1.safetensors
HuggingFace: guozinan/PuLID

Custom node: https://github.com/cubiq/ComfyUI_PuLID
```

### Workflow PuLID + Flux

```
[Load Image (referencia)] ──────────────────────────────┐
      │                                                    │
[PuLIDModelLoader]                                        │
  pulid_file: pulid_flux_v0.9.1.safetensors               │
      │                                                    │
[PuLIDInsightFaceLoader]                                  │
  provider: CUDA                                          │
      │                                                    │
[ApplyPulidFlux]                                          │
  model: [UNETLoader Flux]                                │
  pulid: [PuLIDModelLoader]                               │
  eva_clip: [EVAClipLoader]                               │
  face_analysis: [PuLIDInsightFaceLoader]                 │
  image: [foto referencia]                                │
  weight: 1.0                                             │
  start_at: 0.0                                           │
  end_at: 1.0                                             │
      ↓                                                    │
[KSampler Flux] ←──────────────────────────────────────────┘
  cfg: 1.0, guidance (FluxGuidance): 4.0
  steps: 20, sampler: euler
      ↓
[VAE Decode] → [Save Image]
```

## Técnica combinada: máxima fidelidad

```
Mejor resultado = InstantID + ControlNet OpenPose + ControlNet Depth:

[Foto referencia] → InstantID (identidad facial)
[Foto de pose]    → DWPose   (estructura corporal)
[Foto de pose]    → MiDaS    (profundidad de escena)
      ↓
[SDXL KSampler con 3 condicionadores]
      ↓
[Imagen: misma persona, misma pose, misma profundidad]

Peso recomendado:
  InstantID: 0.8
  OpenPose:  0.7, start=0.0, end=0.7
  Depth:     0.5, start=0.0, end=0.5
```

## Casos excepcionales

1. **Gafas en la foto de referencia**: Todos los modelos de ID preservation incluyen las gafas en el embedding. Si no se quieren en la imagen generada, agregar "without glasses" al prompt y reducir el weight del ID.
2. **Cambio de género con ID preservation**: PhotoMaker lo permite con prompts como "a photo of img as a man". InstantID tiene más dificultad — puede requerir weight < 0.6.
3. **Fotos de baja calidad**: InsightFace requiere cara claramente visible > 100×100 px. Fotos pixeladas o con cara de lado fallan en detección. Usar foto frontal de al menos 512×512.
4. **Múltiples personas en la imagen**: Los nodos procesan solo la cara principal (más grande o más prominente). Para múltiples personas, procesar en workflows separados y componer.
5. **PuLID y VRAM 16 GB**: Flux + PuLID requiere ~14 GB VRAM total con FP8. Con RTX 5080 (16 GB) es viable pero ajustado. Usar `--lowvram` si hay problemas.
6. **Consistencia de personaje en video**: Combinar InstantID frame inicial + AnimateDiff + IP-Adapter en frames intermedios para mantener identidad a lo largo del video.

## Recursos

- PhotoMaker v2: `https://github.com/TencentARC/PhotoMaker`
- InstantID: `https://github.com/InstantX-Team/InstantID`
- ComfyUI InstantID node: `https://github.com/cubiq/ComfyUI_InstantID`
- PuLID: `https://github.com/ToTheBeginning/PuLID`
- ComfyUI PuLID node: `https://github.com/cubiq/ComfyUI_PuLID`
- InsightFace: `https://github.com/deepinsight/insightface`
- IP-Adapter FaceID (alternativa ligera): `https://huggingface.co/h94/IP-Adapter-FaceID`
