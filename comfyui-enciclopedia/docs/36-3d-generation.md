# 36 · Generación 3D con IA — Modelos y Flujos de Trabajo

> **DECLARACIÓN TÉCNICA PARA IA**: La generación 3D en ComfyUI está en estado emergente/experimental. Los flujos principales son: (1) imagen → 3D mesh con TripoSR/Zero123++/InstantMesh, (2) múltiples vistas → reconstrucción NeRF/3DGS, (3) texto → 3D (aun limitado en calidad). Los formatos de salida son OBJ, GLB, o Gaussian Splat (.ply). Ninguno produce calidad de producción sin retoque manual en 2024–2025.

## Modelos principales de generación 3D

### Comparación

| Modelo | Entrada | Salida | Calidad | VRAM | Velocidad |
|---|---|---|---|---|---|
| TripoSR | 1 imagen | Mesh OBJ/GLB | Buena | 8 GB | Rápido (~5 seg) |
| Zero123++ | 1 imagen | Múltiples vistas | Muy buena | 8 GB | Moderado |
| InstantMesh | 1 imagen | Mesh + UV | Excelente | 10 GB | Moderado |
| Wonder3D | 1 imagen | Mesh + textura | Muy buena | 8 GB | Lento |
| One-2-3-45 | 1 imagen | Mesh 3D | Buena | 8 GB | Lento |
| Shap-E (OpenAI) | Texto | Mesh 3D | Aceptable | 4 GB | Rápido |
| Point-E (OpenAI) | Texto | Nube de puntos | Limitada | 4 GB | Rápido |

## TripoSR — Más usado en ComfyUI

Desarrollado por Stability AI + TripoAI. El más integrado en ComfyUI:

```
Características:
  · Single-view reconstruction (1 imagen → malla 3D)
  · ~5 segundos de generación
  · Salida: OBJ con mapa UV de textura
  · Mejor con objetos aislados sobre fondo neutro
  · Debilidades: partes ocultas inventadas, geometría plana a veces

HuggingFace: stabilityai/TripoSR
```

### Workflow TripoSR en ComfyUI

```
[Load Image] (objeto sobre fondo blanco/neutro)
      ↓
[RemoveBackground]  # Importante: fondo transparente mejora resultados
      ↓
[TripoSR_ModelLoader]
    model: triposr
      ↓
[TripoSR_Generator]
    image: [imagen sin fondo]
    resolution: 256     # Resolución de la malla (128, 256, 512)
    threshold: 25.0     # Umbral de densidad de la malla
      ↓
[TripoSR_MeshViewer]   # Previsualizar en ComfyUI
      ↓
[TripoSR_SaveMesh]
    format: obj    # obj, glb
    filename: mi_objeto
```

**Custom node requerido**: `ComfyUI-3D-Pack` o `comfyui-triposr`

## Zero123++ — Generación de múltiples vistas

```
Flujo: 1 imagen → 6 vistas desde ángulos diferentes
  Vista frontal + vistas a 0°, 30°, 60°, 90° + cenital + posterior

Ventaja: Las 6 vistas pueden alimentar a un reconstructor más preciso
Desventaja: Requiere paso adicional de reconstrucción

HuggingFace: sudo-ai/zero123plus-v1.2
```

### Workflow Zero123++ → InstantMesh

```
[Imagen frontal del objeto]
         ↓
[Zero123PlusNode]
    num_views: 6
    steps: 75
    cfg: 4.0
         ↓
[6 imágenes de diferentes vistas]
         ↓
[InstantMeshGenerator]
    input_images: [6 vistas]
    export_format: glb
         ↓
[Mesh 3D completo con textura]
```

## InstantMesh — Mejor calidad de geometría

```
Características:
  · Toma múltiples vistas (o genera con Zero123++)
  · LRM (Large Reconstruction Model) para geometría fina
  · Salida: GLB con textura baked
  · VRAM: 10–16 GB para calidad máxima

GitHub: TencentARC/InstantMesh
```

## Gaussian Splatting (3DGS) en ComfyUI

Técnica alternativa al mesh tradicional:

```
Qué es: Representación 3D como nubes de gaussianas 3D
Ventajas: Rendering muy rápido, aspecto fotorrealista
Desventajas: No exportable fácilmente como mesh, viewers especializados
Formato: .ply (point cloud gaussiano)

Workflow típico:
  Video 360° del objeto → COLMAP (SfM) → 3DGS training → .ply
  Tiempo training: 30–60 min por escena
```

**Custom node**: `ComfyUI-3DGS-Pack` (experimental)

## ComfyUI-3D-Pack — Suite completa

El custom node más completo para 3D:

```bash
git clone https://github.com/MrForExample/ComfyUI-3D-Pack custom_nodes/ComfyUI-3D-Pack
```

**Incluye nodos para:**
- TripoSR
- Zero123++
- Wonder3D
- InstantMesh
- Gaussian Splatting
- Renderizado de mallas
- Conversión de formatos 3D

## Preparación de imagen para generación 3D

La calidad del resultado 3D depende enormemente de la imagen de entrada:

```
Buenas prácticas:
  ✅ Objeto centrado en la imagen
  ✅ Fondo simple o transparente (PNG con alpha)
  ✅ Iluminación uniforme, sin sombras duras
  ✅ Objeto completamente visible (sin cortes)
  ✅ Resolución 512x512 o 1024x1024
  ✅ Vista frontal o ängulo ligero (no extremo)

Malas prácticas:
  ❌ Fondo complejo o con múltiples objetos
  ❌ Objeto muy fino o transparente (vidrio, telas)
  ❌ Escenas (solo objetos aislados funcionan bien)
  ❌ Objetos articulados (personas, animales con poses complejas)
```

## Workflow completo: texto → 3D

```
Etapa 1: Texto → Imagen
  [SDXL / Flux] con prompt:
  "a ceramic teapot, studio lighting, white background, centered"
  → imagen PNG a 512×512

Etapa 2: Quitar fondo
  [BiRefNet / REMBG] → imagen con fondo transparente

Etapa 3: Imagen → 3D
  [TripoSR] o [Zero123++ + InstantMesh]
  → archivo .obj o .glb

Etapa 4: Abrir en Blender para retoque
  Importar OBJ/GLB → corregir geometría → renderizar
```

## Formatos 3D de salida y compatibilidad

| Formato | Compatibilidad | Textura incluida | Uso recomendado |
|---|---|---|---|
| OBJ + MTL | Universal | Archivo separado | Blender, Maya, Max |
| GLB | Web, Unity, Unreal | Embebida | Distribución, juegos |
| FBX | Industry estándar | Puede embeberse | Animación 3D |
| .ply | Pointclouds | No | Gaussian Splatting |
| USD/USDZ | Apple, Pixar | Sí | AR, pipelines VFX |

## Herramientas complementarias (fuera de ComfyUI)

```
Blender (open source):
  · Importar y retoque de mallas 3D
  · Baked texture editing
  · Plugin: BlenderNeRF para Gaussian Splatting

Meshroom (open source):
  · Fotogrametría desde fotos reales
  · Complementa modelos de IA

Gaussian Splatting viewer:
  · WebGL viewer: https://antimatter15.com/splat/
  · Gaussian Splatting: https://github.com/graphdeco-inria/gaussian-splatting
```

## Eliminación de fondo (paso crítico)

El mejor flujo para preparar imagen para 3D:

```
# Nodos de RemoveBackground en ComfyUI:
BiRefNet        → mejor calidad en cabellos y bordes finos
REMBG           → rápido y general
IS-Net          → alta precisión
BriaRMBG        → buen equilibrio calidad/velocidad

Nodo típico:
  [Load Image] → [BiRefNetUltraPreprocessor] → [imagen + máscara]
  → [JoinImageWithAlpha] → PNG transparente
```

## Casos excepcionales

1. **Objetos simmétricos**: TripoSR maneja bien objetos con simetría (tazas, botellas). La parte posterior se extrapola razonablemente.
2. **Texturas complejas**: Los modelos actuales son mejores en geometría que en textura fiel. Usar textura generada → proyección en Blender para mejorar.
3. **VRAM > 16 GB**: InstantMesh en calidad máxima (512 resolution) requiere ~16 GB VRAM. Con RTX 5080 de 16 GB es viable en el límite.
4. **Imágenes con manos/caras**: Resultados muy pobres en 2024–2025. Los modelos no tienen "semantic understanding" de anatomía humana suficiente.
5. **Escenas (no solo objetos)**: Ninguno de los modelos actuales reconstruye escenas bien. Solo objetos aislados. Para escenas: usar NeRF con fotos reales (COLMAP + Nerfstudio).

## Recursos

- TripoSR GitHub: `https://github.com/VAST-AI-Research/TripoSR`
- Zero123++: `https://github.com/SUDO-AI-3D/zero123plus`
- InstantMesh: `https://github.com/TencentARC/InstantMesh`
- ComfyUI-3D-Pack: `https://github.com/MrForExample/ComfyUI-3D-Pack`
- Gaussian Splatting original: `https://github.com/graphdeco-inria/gaussian-splatting`
- Nerfstudio: `https://github.com/nerfstudio-project/nerfstudio`
- Wonder3D: `https://github.com/xxlong0/Wonder3D`
