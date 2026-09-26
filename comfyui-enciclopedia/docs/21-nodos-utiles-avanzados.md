# 21 — Nodos Útiles Avanzados

Nodos menos conocidos pero muy poderosos para flujos de trabajo complejos.

---

## Nodos de Control de Flujo

### Primitive
Expone un único valor que puede conectarse a múltiples nodos. Si necesitas cambiar el seed o los steps en varios KSamplers a la vez, un solo Primitive lo controla todo.

```
[Primitive: 42] ──┬── seed del KSampler 1
                └── seed del KSampler 2
```

### Note
Nodo de texto puro, sin conexiones. Documenta el workflow.

### Reroute
Punto de redirección de cables. Solo organiza visualmente, no altera los datos.

---

## Nodos de Imagen

### ImageScale
Escala una imagen a dimensiones específicas usando interpolación:
- `nearest-exact`, `bilinear`, `area`, `bicubic`, `lanczos`
- `lanczos` = mejor calidad para imágenes fotográficas
- `nearest-exact` = preserva píxeles duros (pixel art)

### ImageCrop
Recorta una imagen a coordenadas específicas (x, y, width, height).

### ImagePadForOutpainting
Agrega padding a los bordes de una imagen para hacer outpainting (extender la imagen más allá de sus bordes).

### ImageComposite
Composita dos imágenes usando una máscara como alpha:
```
[imagen_destino] + [imagen_fuente] + [máscara] → composición
```

### JoinImageWithAlpha
Combina una imagen RGB con un canal alpha para crear imagen RGBA.

### ImageBatch
Combina múltiples imágenes en un batch (lote).

### ImageFromBatch
Extrae una imagen específica de un batch por índice.

---

## Nodos de Máscara

### MaskToImage / ImageToMask
Convierte entre tipos MASK e IMAGE.

### SolidMask
Crea una máscara de color sólido (todo blanco o todo negro).

### GrowMask
Expande el área blanca de una máscara por N píxeles. Con `expand` negativo, contrae.

### FeatherMask
Suaviza los bordes de una máscara para transiciones más naturales.

### MaskComposite
Operaciones booleanas entre máscaras: `union` (OR), `intersection` (AND), `difference` (XOR).

---

## Nodos de Latente

### LatentBlend
Interpolación entre dos latentes:
- `blend_factor`: 0 = solo latente1, 1 = solo latente2
- Útil para morphing entre imágenes en el espacio latente

### LatentFlip
Invierte el latente horizontal o verticalmente.

### LatentRotate
Rota el latente 90, 180 o 270 grados.

### LatentCrop
Recorta el latente (recuerda: coordenadas 8x menores que la imagen).

### LatentComposite
Inserta un latente dentro de otro en una posición específica. Útil para inpainting manual.

---

## Nodos de Conditioning Avanzado

### ConditioningSetTimestepRange
Activa el conditioning solo durante un rango de steps:
```
start: 0.0   end: 0.5  ← solo en los primeros 50% de steps
```

Caso de uso: usar un prompt de composición al inicio y un prompt de detalle al final.

### ConditioningZeroOut
Anula completamente un conditioning (lo convierte en tensor cero). Útil para hacer ablation o desactivar condicionamientos selectivamente.

### ConditioningSetArea y ConditioningSetAreaPercentage
Asigna coordenadas a un conditioning para regional prompting. Ver [Regional Prompting](18-regional-prompting.md).

---

## Nodos de Procesamiento de Texto

### String (de WAS Node Suite u otros)
Manipulación de cadenas de texto: concatenar, reemplazar, extraer partes.

### CR Text List (Comfyroll)
Carga una lista de prompts desde texto con separadores. Útil para batchear con prompts distintos.

### Random Line From Text (WAS)
Elige aleatoriamente una línea de un texto multilinea. Para generación aleatoria de prompts.

---

## Nodos de Matemáticas y Lógica

Disponibles en custom nodes como WAS, Comfyroll, o mtb nodes:

### Math (números)
- Operaciones: suma, resta, multiplicación, división entre valores de nodos
- Útil para calcular dimensiones dinámicamente

### Int, Float, String
Nodos que exponen un valor de tipo específico. Alternativa a Primitive con tipo forzado.

---

## Nodos de Batch Processing

### Load Image Batch (WAS)
Carga todas las imágenes de una carpeta como batch automáticamente.

### Batch Size Expander
Repite un latente N veces para procesar varias imágenes con los mismos parámetros.

### Image Batch to Image List
Convierte un batch (múltiples imágenes en un tensor) a una lista iterable.

---

## Nodos de Metadatos

### Image Save con metadatos (WAS)
Guarda la imagen con metadatos personalizados en el PNG (EXIF o chunks).

### CR Image Output (Comfyroll)
Más control sobre el guardado: carpetas dinámicas, timestamps, secuencias numeradas.

---

## Nodos de Depuración

### Preview Image
Muestra imagen intermedia sin guardar. Imprescindible para depurar workflows largos.

### CR Show Text
Muestra el valor de un string en un nodo visual. Para verificar prompts generados dinámicamente.

### CR Show Float / CR Show Int
Equivalentes para números.

---

*[← LyCORIS](20-lycoris-lora-avanzado.md) | [Siguiente: Optimización y Rendimiento →](22-optimizacion-rendimiento.md)*
