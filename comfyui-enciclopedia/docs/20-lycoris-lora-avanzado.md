# 20 — LyCORIS y LoRA Avanzado

Más allá del LoRA estándar: familias de fine-tuning de eficiencia variable.

---

## LyCORIS (Lora beYond COnventional methods, Research Into Scale)

LyCORIS es una familia de algoritmos de fine-tuning que extienden LoRA con métodos matemáticos más sofisticados. Todos producen archivos compatibles con el nodo `Load LoRA` de ComfyUI.

---

## Algoritmos de LyCORIS

### LoRA estándar
Descomposición de baja dimensión: `W = W0 + BA` donde B y A son matrices pequeñas.
- Rápido de entrenar y cargar
- Bueno para estilos y conceptos generales

### LoCon (LoRA con convolucionales)
Extiende LoRA para cubrir también las capas convolucionales (no solo attention).
- Mejor para texturas y patrones repetitivos
- Tamaño ligeramente mayor

### LoHa (LoRA con Hadamard product)
Usa el producto Hadamard en lugar de multiplicación matricial estándar.
- Mayor capacidad de expresión con similar número de parámetros
- Mejor para estilos complejos y mezclas

### LoKR (LoRA con producto Kronecker)
Usa producto de Kronecker. Más eficiente que LoHa en algunos escenarios.
- Mejor ratio calidad/tamaño
- Menos popular pero efectivo

### Full (DyLoRA)
Entrena todos los rangos a la vez, adaptando el rango óptimo dinámicamente.

### IA3
Modifica solo vectores de escala en lugar de matrices completas.
- Ultra-pequeño (~KB)
- Para adaptaciones muy ligeras

### DoRA (Weight-Decomposed LoRA)
Descompone el peso en magnitud y dirección, entrenando cada componente por separado.
- Mejor fidelidad que LoRA estándar
- Especialmente bueno para personajes con identidad específica
- Archivo similar tamaño a LoRA

---

## Comparación Rápida

| Algoritmo | Tamaño | Calidad | Velocidad entreno | Mejor para |
|-----------|--------|---------|-------------------|----------|
| LoRA | Pequeño | Buena | Rápido | Estilos, conceptos |
| LoCon | Mediano | Mejor | Moderado | Texturas |
| LoHa | Mediano | Mejor | Lento | Estilos complejos |
| LoKR | Pequeño | Buena | Moderado | Eficiencia |
| DoRA | Pequeño | Excelente | Lento | Personajes |

---

## Uso en ComfyUI

Todos los formatos LyCORIS se usan **igual** que LoRA:

```
[Load LoRA]
  lora_name: mi_lycoris.safetensors
  strength_model: 0.8
  strength_clip: 0.8
```

ComfyUI detecta automáticamente el tipo de algoritmo dentro del archivo.

---

## Conceptos Avanzados de LoRA

### Rank (rango)
El parámetro más importante al entrenar:
- `rank 4–8`: LoRAs ligeros, estilos generales
- `rank 16–32`: balance estándar
- `rank 64–128`: alta capacidad, mayor tamaño
- Mayor rank = más parámetros = más capacidad pero riesgo de sobreajuste

### Alpha
Escala aplicada al LoRA durante la inferencia: `scale = alpha / rank`
- Si `alpha == rank`: sin cambio de escala (typical)
- Si `alpha < rank`: el LoRA tiene efecto reducido

### Conv Dim / Conv Alpha
Parámetros equivalentes para las capas convolucionales (LoCon).

---

## LoRA Stacking Inteligente

Apilar múltiples LoRAs requiere cuidado:

**Regla del 1.0**: la suma de todos los strengths no debería superar ~1.5 para evitar artefactos:
```
LoRA estilo: 0.7
LoRA personaje: 0.6
LoRA detalle: 0.3
Total: 1.6  ← en el límite, posibles artefactos
```

**Orden de carga**: los LoRAs se aplican en orden. El primero modifica el modelo base, el segundo modifica el ya modificado. El orden puede afectar el resultado.

---

## LoRA Block Weight

Algunos custom nodes permiten controlar el strength por bloque del UNet (equivalente a ModelMergeBlocks pero para LoRA):

- Custom node: `LoRA Block Weight` de Comfyroll o similar
- Permite que un LoRA afecte solo ciertos bloques
- Ejemplo: aplicar el LoRA de estilo solo en output blocks para no afectar anatomía

---

*[← Model Merging](19-model-merging.md) | [Siguiente: Nodos útiles avanzados →](21-nodos-utiles-avanzados.md)*
