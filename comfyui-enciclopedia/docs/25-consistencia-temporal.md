# 25 — Consistencia Temporal en Video

El mayor desafío de la generación de video IA: mantener coherencia entre frames sin parpadeo ni cambios abruptos.

---

## El Problema de la Inconsistencia Temporal

Cuando se procesan frames independientemente (como en video-to-video), cada frame puede tener:
- Cambios de iluminación sutiles
- Cambios de textura (flickering)
- Cambios de color
- Posiciones de objetos ligeramente distintas

El resultado: video que "tiembla" visualmente aunque el contenido sea correcto.

---

## Técnicas de Consistencia

### 1. Modelos con Atención Temporal Nativa
La solución más efectiva: usar modelos diseñados para video (AnimateDiff, CogVideoX, Wan2.1).
Estos ven múltiples frames a la vez y aprenden correlaciones temporales durante el entrenamiento.

### 2. Ventana de Contexto (Context Windows)
En AnimateDiff, el `context_length` define cuántos frames se ven juntos.
- `context_overlap`: frames compartidos entre ventanas adyacentes
- Mayor overlap = más suavidad, más VRAM

### 3. IP-Adapter para Consistencia de Personaje
Usar la misma imagen de referencia en todos los frames:
```
[Imagen de referencia] → IPAdapter weight=0.5 → todos los frames del batch
```
Mantiene estilo y apariencia general sin atar la pose.

### 4. ControlNet Temporal
Aplicar el mismo ControlNet (depth, canny) a todos los frames extrayendo el mapa de cada frame original:
```
[Video original] → [Preprocessor por frame] → ControlNet → frames consistentes
```

---

## Interpolación de Frames (RIFE)

RIFE (Real-time Intermediate Flow Estimation) genera frames intermedios entre dos frames existentes:

### Custom Node: `ComfyUI-RIFE-VFI`

**Nodo**: `RIFE VFI`
- `frames`: el batch de frames de entrada
- `multiplier`: cuántos frames intermedios generar (2 = duplica FPS, 4 = cuadruplica)
- `tta`: test-time augmentation (más preciso, más lento)
- `rife_model`: versión del modelo (4.6, 4.14, etc.)

**Flujo típico**:
```
Generación → 8 fps → [RIFE x3] → 24 fps
```

### Limitaciones de RIFE
- No crea contenido nuevo, solo interpola
- Movimientos muy rápidos o cambios abruptos producen ghosting
- No puede recuperar información perdida en frames de baja calidad

---

## Frame Blending

También llamado temporal smoothing: promediar frames adyacentes para suavizar parpadeo.

**Custom Node**: `VHS_DuplicateFrames` + blending manual con nodos de imagen.

**Tradeoff**: reduce el flickering pero también suaviza el movimiento real (motion blur no deseado).

---

## Optical Flow

Algoritmo que calcula el movimiento real entre frames. Usado por RIFE internamente.

**Aplicaciones en ComfyUI**:
- Warping de frames: mover píxeles según el flujo calculado
- Masked warping: combinar frames con warp para suavizar transiciones

Disponible en: `ComfyUI-OpticalFlowEstimation` y otros custom nodes.

---

## Depth-Guided Temporal Consistency

Usar mapas de profundidad para guía temporal:
1. Extraer depth map de cada frame del video original
2. Usar ControlNet Depth en la generación
3. La profundidad constante ancla el movimiento tridimensional

---

## Strategies por Tipo de Problema

| Problema | Solución |
|----------|----------|
| Flickering de textura | Reducir denoise + ControlNet Tile |
| Cambios de iluminación | IP-Adapter para referencia de color |
| Cambio de identidad | IPAdapter FaceID en batch |
| Movimiento entrecortado | RIFE interpolación |
| Composición variable | ControlNet Depth o Pose |
| Todo el video incoherente | Usar modelo nativo de video (AnimateDiff, CogVideoX) |

---

## Temporal Upscaling

Aplicar upscaling a todos los frames de un video:

```
[VHS_LoadVideo] → IMAGE batch
        ↓
[Upscale Image Using Model] → IMAGE batch upscalado
        ↓
[VHS_VideoCombine] → video HD
```

Para videos largos, procesar en batches de 50–100 frames para no agotar VRAM.

---

## Deforum-style Keyframing

Técnica avanzada: definir parámetros (zoom, rotación, traducción) que evolucionan frame a frame:

```
Frame 0: zoom=1.0, angle=0
Frame 30: zoom=1.05, angle=2
Frame 60: zoom=1.1, angle=0
```

En ComfyUI: disponible con nodos de CR Animation o scripts de Deforum para ComfyUI.

---

*[← Text-to-Video](24-modelos-text-to-video.md) | [Siguiente: Video Edición →](26-video-edicion.md)*
