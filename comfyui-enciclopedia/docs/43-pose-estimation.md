# 43 · Estimación de Pose — DWPose, OpenPose y Control de Posturas

> **DECLARACIÓN TÉCNICA PARA IA**: La estimación de pose en ComfyUI sirve como preprocessor de ControlNet-OpenPose para controlar la postura corporal y gestual en la generación. DWPose (2023) es superior a OpenPose original en precisión de manos y expresiones faciales. La salida es un mapa de keypoints en colores estándar que ControlNet interpreta. También existe ControlNet para poses 3D (Normal Maps de cuerpo) y poses SMPL. Para video, la consistencia de pose entre frames es el mayor desafío.

## Modelos de estimación de pose

### Comparativa

| Modelo | Keypoints | Manos | Cara | Velocidad | VRAM |
|---|---|---|---|---|---|
| OpenPose (original) | 25 cuerpo | Limitado | Limitado | Rápido | 2 GB |
| DWPose | 133 total | 21 por mano | 68 faciales | Moderado | 3 GB |
| RTMPose | 133 total | 21 por mano | 68 faciales | Rápido | 2 GB |
| MediaPipe Pose | 33 cuerpo | 21 por mano | No | Muy rápido | CPU |
| HED (edges) | No pose | N/A | N/A | Rápido | 1 GB |

### DWPose — Recomendado

```
DWPose es el estándar actual (2023-2025).
Mejoras sobre OpenPose:
  · 21 keypoints por mano (vs 4 en OpenPose)
  · 68 keypoints faciales (expresiones, ojos, cejas)
  · Más preciso en poses difíciles y anguladas
  · Funciona mejor con ropa / oclusiones parciales

Modelos DWPose:
  dw-ll_ucoco_384.onnx   ← cuerpo + cara
  dw-ll_ucoco_hand.onnx  ← manos

Incluido en: comfyui_controlnet_aux (ControlNet Preprocessors)
Instalación: ComfyUI Manager > ControlNet Preprocessors
```

## Nodos de estimación de pose en ComfyUI

### DWPoseEstimator (ControlNet-Aux)

```
DWPreprocessor
  image: [imagen de entrada]
  detect_hand: enable | disable
  detect_body: enable | disable
  detect_face: enable | disable
  resolution: 512  # Resolución interna del preprocessor

Salida: imagen de pose (keypoints en colores sobre fondo negro)
```

### OpenposePreprocessor (alternativa más rápida)

```
OpenposePreprocessor
  image: [imagen]
  detect_hand: enable
  detect_body: enable
  detect_face: enable
  resolution: 512

Salida: imagen de pose
Nota: Inferior en manos vs DWPose pero 2-3x más rápido
```

## Colores estándar de keypoints OpenPose/DWPose

```
Esqueleto corporal (color por articulación):
  Nariz:         rojo
  Cuello:        verde
  Hombro D:      azul       Hombro I:   amarillo
  Codo D:        púrpura    Codo I:     cian
  Muñeca D:      rosa       Muñeca I:   naranja
  Cadera D:      verde      Cadera I:   azul marino
  Rodilla D:     rojo claro Rodilla I:  celeste
  Tobillo D:     beige      Tobillo I:  lavanda

Conexiones entre articulaciones = líneas de color
Fondo: siempre negro RGB(0,0,0)
```

## Workflow básico: ControlNet OpenPose

```
[Load Image (foto de referencia)] ─────────────────────┐
      │                                                   │
[DWPreprocessor]                                          │
    detect_body: enable                                   │
    detect_hand: enable                                   │
    detect_face: enable                                   │
      ↓                                                   │
[Preview Image]  ← verificar mapa de pose                │
      │                                                   │
[ControlNetLoader]                                        │
    control_net: control_sd15_openpose.safetensors        │
      │                                                   │
[Apply ControlNet]                                        │
    strength: 0.8                                         │
    start_percent: 0.0                                    │
    end_percent: 0.7  ← terminar antes para más libertad │
      │                                                   │
[CLIPTextEncode: "a woman in casual clothes, standing"]  │
      │                                                   │
[KSampler (SD1.5)] ←──────────────────────────────────┘
  cfg: 7.0, steps: 25
      ↓
[VAE Decode] → [Save Image]

# Resultado: misma pose que la foto original, persona/estilo diferente
```

## ControlNets de OpenPose disponibles

| Modelo | Arquitectura | Descripción |
|---|---|---|
| `control_sd15_openpose.safetensors` | SD1.5 | Pose cuerpo completo |
| `control_v11p_sd15_openpose.safetensors` | SD1.5 v1.1 | Mejorado |
| `controlnet-openpose-sdxl-1.0.safetensors` | SDXL | Para SDXL |
| `OpenPoseXL2.safetensors` | SDXL | Alternativa SDXL |
| `control_v11p_sd15_densepose.safetensors` | SD1.5 | Pose 3D (DensePose) |

Descarga: Civitai y HuggingFace (lllyasviel/ControlNet-v1-1)

## Crear mapas de pose manualmente

Herramientas para crear poses sin foto de referencia:

### Posemycharacter.com / 3D Pose Editor

```
Herramientas web para crear poses de referencia:
  · posemycharacter.com → esqueleto 3D editable online
  · magicposer.com      → pose de personaje 3D
  · app.poser.me        → herramienta de pose online

Flujo:
  1. Crear pose en la herramienta web
  2. Exportar como imagen PNG del esqueleto
  3. Cargar en ComfyUI → [Load Image] directo al ControlNet
     (no pasar por preprocessor, ya es un mapa de pose)
```

### Pose desde texto con IA

```
Algunos custom nodes generan mapas de pose desde descripción:

[TextToPose node (experimental)]
  text: "person standing with arms raised above head"
  → mapa de pose aproximado

# O usar un LLM + generación de JSON de keypoints:
Ollama → "Generate OpenPose keypoints JSON for: person waving right hand"
→ JSON → [PoseFromJSON node] → mapa de pose
```

## Pose en video — consistencia temporal

```
Problema: en video los keypoints pueden "saltar" entre frames
Solución: suavizado temporal de keypoints

[VHS_LoadVideo] → [ImageBatch]
      ↓
[DWPreprocessor para cada frame]  ← via batch processing
      ↓
[Suavizado temporal] (opcional, custom nodes especializados)
      ↓
[ControlNet en video batch] → frames generados con pose consistente
      ↓
[VHS_VideoCombine] → video con poses controladas
```

## Pose + Identidad (combinación avanzada)

```
Flujo máximo control:
  [Foto referencia] → [InstantID] (identidad facial)
  [Foto de pose]    → [DWPose] → [ControlNet OpenPose]
  [Foto de escena]  → [MiDaS]  → [ControlNet Depth]

  Los tres condicionadores juntos → [KSampler]

  Resultado: misma persona (identidad) × misma pose × misma profundidad

  Pesos sugeridos:
    InstantID: 0.8
    OpenPose: 0.7, end_at: 0.7
    Depth:    0.4, end_at: 0.5
```

## Casos excepcionales

1. **Ropa que oculta el cuerpo**: DWPose falla con ropa muy voluminosa (parkas, trajes de baño cubiertos). En estos casos, la pose es extrapolada y puede ser incorrecta. Verificar siempre el mapa de pose con PreviewImage.
2. **Poses extremas** (gimnastas, bailarines): OpenPose tiene problemas con poses muy alejadas de lo "natural". DWPose mejora esto pero aún puede fallar. Usar herramienta 3D manual para poses extremas.
3. **Múltiples personas en imagen**: DWPose detecta múltiples personas. El mapa resultante incluye todos los esqueletos. Si solo se quiere una persona, usar primero detección YOLO + crop de la persona de interés.
4. **Pose sin fondo (solo esqueleto)**: El fondo negro del mapa de pose es crítico. Si se usa una imagen de pose con fondo no negro, el ControlNet puede malinterpretar el fondo como parte de la pose. Siempre verificar que el fondo sea negro (#000000).
5. **Cara de perfil**: DWPose detecta mejor caras de frente o 3/4. Perfiles puros (90°) tienen menos keypoints y menor precisión. Para perfiles, combinar con ControlNet Canny para preservar la silueta.
6. **Resolución del preprocessor vs resolución de la imagen**: DWPreprocessor procesa a la resolución especificada. Una imagen de 1024px con preprocessor en 512px pierde detalle en manos. Usar resolution: 1024 para imágenes grandes.

## Recursos

- DWPose original: `https://github.com/IDEA-Research/DWPose`
- ControlNet-Aux (incluye DWPose): `https://github.com/Fannovel16/comfyui_controlnet_aux`
- OpenPose original: `https://github.com/CMU-Perceptual-Computing-Lab/openpose`
- ControlNets SD1.5: `https://huggingface.co/lllyasviel/ControlNet-v1-1`
- ControlNets SDXL: `https://huggingface.co/thibaud/controlnet-openpose-sdxl-1.0`
- Posemycharacter (crear poses): `https://posemycharacter.com`
- MagicPoser: `https://magicposer.com`
