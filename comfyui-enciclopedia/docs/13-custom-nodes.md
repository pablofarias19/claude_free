# 13 — Custom Nodes

Extensiones de terceros que agregan nuevos nodos y capacidades a ComfyUI.

---

## Cómo Instalar Custom Nodes

### Método 1: ComfyUI Manager (recomendado)
1. Instalar primero ComfyUI Manager
2. Menú → **Manager** → **Install Custom Nodes**
3. Buscar por nombre e instalar
4. Reiniciar ComfyUI

### Método 2: Manual
```bash
cd ComfyUI/custom_nodes/
git clone https://github.com/autor/nombre-del-nodo
cd nombre-del-nodo
pip install -r requirements.txt  # si existe
```
Reiniciar ComfyUI.

---

## Custom Nodes Esenciales

### ComfyUI Manager
**Repositorio**: `ltdrdata/ComfyUI-Manager`
**Qué hace**: Gestor de custom nodes. Permite instalar, actualizar, desactivar y desinstalar otros nodos desde la interfaz. **Casi obligatorio**.

---

### ComfyUI Impact Pack
**Repositorio**: `ltdrdata/ComfyUI-Impact-Pack`
**Qué hace**:
- Detección y segmentación de objetos (SAM + bbox detector)
- Inpainting automático de rostros (ADetailer equivalente)
- Pipes para simplificar workflows complejos

**Nodos clave**: `FaceDetailer`, `SAMLoader`, `BboxDetectorSEGS`, `ImpactSimpleDetectorSEGS`

---

### WAS Node Suite
**Repositorio**: `WASasquatch/was-node-suite-comfyui`
**Qué hace**: Más de 100 nodos extra:
- Procesamiento avanzado de imágenes y máscaras
- Carga de imágenes por lote
- Nodos de texto y prompts dinámicos
- Latent interpolation
- Film grain, efectos varios

---

### Efficiency Nodes
**Repositorio**: `jags111/efficiency-nodes-comfyui`
**Qué hace**: Simplifica workflows con nodos que combinan múltiples pasos:
- `Efficient Loader` — carga checkpoint + LoRAs + CLIP en un solo nodo
- `KSampler (Efficient)` — KSampler con más opciones y preview en tiempo real
- `XY Plot` — genera gráficas comparativas de parámetros

---

### ComfyUI ControlNet Aux
**Repositorio**: `Fannovel16/comfyui_controlnet_aux`
**Qué hace**: Todos los preprocessors de ControlNet:
- Canny, Depth (MiDaS, Zoe, DPT), OpenPose, DW Pose
- Lineart, Scribble, Segmentation, Normal map
- HED, MLSD, y muchos más

**Indispensable** si usas ControlNet.

---

### IPAdapter Plus
**Repositorio**: `cubiq/ComfyUI_IPAdapter_plus`
**Qué hace**: Implementación completa de IP-Adapter:
- Todos los tipos de IP-Adapter (style, composition, face)
- Soporte para Face ID
- Attention masking

---

### ComfyUI-AnimateDiff-Evolved
**Repositorio**: `Kosinkadink/ComfyUI-AnimateDiff-Evolved`
**Qué hace**: Generación de videos/animaciones con AnimateDiff.
- Genera secuencias de frames coherentes
- Compatible con LoRAs de movimiento
- Control de cámara y motion LoRAs

---

### ComfyUI-VideoHelperSuite
**Repositorio**: `Kosinkadink/ComfyUI-VideoHelperSuite`
**Qué hace**: Carga y exporta videos.
- Cargar video frame a frame
- Exportar secuencia de frames como video
- Herramientas de combinación de frames

---

### CR Animation Nodes (ComfyUI Reactor)
**Repositorio**: `Suzie1/ComfyUI_Comfyroll_CustomNodes`
**Qué hace**: Nodos para animación y prompts dinámicos:
- Schedulers de prompts por frame
- Control de cámara
- Interpolación de valores

---

### ReActor (Face Swap)
**Repositorio**: `Gourieff/comfyui-reactor-node`
**Qué hace**: Face swap (intercambio de caras) usando InsightFace.
**Nota**: Uso ético — solo usar con personas que han dado su consentimiento.

---

### UltimateSDUpscale
**Repositorio**: `ssitu/ComfyUI_UltimateSDUpscale`
**Qué hace**: Upscaling en tiles para imágenes de alta resolución sin explotar VRAM. Ver sección 10.

---

## Troubleshooting de Custom Nodes

| Problema | Solución |
|----------|----------|
| Nodo aparece en rojo | Falta dependencia Python. Ver consola, instalar con pip |
| Nodo no aparece | El custom node no se instaló bien o ComfyUI no se reinició |
| Conflicto entre nodos | Dos nodos definen el mismo tipo. Desactivar uno desde el Manager |
| Error al importar | Revisa `custom_nodes/nombre/requirements.txt` y ejecuta pip |

---

*[← Workflows](12-workflows-y-json.md) | [Siguiente: Errores Comunes →](14-errores-comunes.md)*
