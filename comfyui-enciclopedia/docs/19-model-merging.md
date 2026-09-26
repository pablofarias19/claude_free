# 19 — Model Merging (Fusión de Modelos)

Combinar los pesos de dos o más checkpoints para obtener uno nuevo con características de ambos.

---

## ¿Por qué hacer un merge?

- Combinar la anatomía de un modelo con el estilo de otro
- Suavizar el sobreajuste de un modelo muy específico
- Crear modelos personalizados sin reentrenar
- Recuperar capacidades perdidas al hacer fine-tuning

---

## Métodos de Merge

### Weighted Sum (suma ponderada)
La forma más simple. Interpola linealmente los pesos:

```
resultado = (1 - alpha) × modelo_A + alpha × modelo_B
```

- `alpha = 0.0`: 100% modelo A
- `alpha = 0.5`: 50%/50%
- `alpha = 1.0`: 100% modelo B

### Add Difference
Agrega la diferencia de un fine-tune sobre un base model a otro:

```
resultado = modelo_A + alpha × (modelo_B - modelo_C)
```

Donde C es el modelo base del que B fue fine-tuneado.

**Uso típico**: transferir el estilo de un LoRA bakeado (B-C) a otro modelo base (A).

### No Reset
Similar a Weighted Sum pero con tratamiento especial de los bloques sin entrenar.

---

## Nodos de Merge en ComfyUI

### ModelMergeSimple
Merge básico de dos modelos:
- `model1`, `model2`: los modelos a fusionar
- `ratio`: alpha (0–1)

### ModelMergeBlocks
Permite controlar el ratio **por bloque** del UNet:

```
input_blocks.0  = 0.3
input_blocks.1  = 0.5
mid_block       = 0.7
output_blocks.0 = 0.4
...
```

Los bloques del UNet tienen funciones diferentes:
- **Input blocks**: capturan información de bajo nivel (texturas, bordes)
- **Middle block**: semántica de alto nivel
- **Output blocks**: reconstrucción de la imagen

### CLIPMergeSimple
Merge del componente CLIP por separado.

### ModelMergeAdd / ModelMergeSubtract
Suma o resta los pesos directamente.

---

## Block Merge Avanzado

Trasferir características específicas:

| Objetivo | Qué hacer |
|----------|----------|
| Transferir solo el estilo | Mayor ratio de B en output_blocks |
| Transferir solo la anatomía | Mayor ratio de B en input_blocks |
| Transferir composición | Mayor ratio de B en mid_block |
| Transferir prompt following | Mayor ratio de B en el CLIP |

---

## Model Surgery: extraer y transferir LoRAs bakeados

Algunos modelos tienen LoRAs "horneados" dentro del checkpoint. Se pueden extraer:

**Proceso**: `diff = modelo_finetuned - modelo_base`

Esto produce los cambios que el fine-tuning introdujo, que luego pueden aplicarse como LoRA a otro base model con `Add Difference`.

Herramientas externas como **sd-scripts** o **kohya** facilitan este proceso.

---

## Guardar el Modelo Mergeado

### Nodo: Save Checkpoint / ModelSave
Guarda el resultado del merge como un nuevo `.safetensors`:

```
[ModelMergeBlocks] → MODEL
        ↓
[Save Checkpoint]
    filename_prefix: "mi_merge_v1"
```

Se guarda en `output/` por defecto. Mover manualmente a `models/checkpoints/` para usarlo.

---

## Precauciones

- Solo fusionar modelos de **la misma arquitectura** (SD1.5 con SD1.5, SDXL con SDXL)
- Verificar que los modelos usen el mismo CLIP (SD2.1 tiene CLIP incompatible con SD1.5)
- Hacer merges incrementales y probar en cada paso
- Ratio muy extremo (>0.8) hacia un modelo puede destruir las capacidades del otro

---

*[← Regional Prompting](18-regional-prompting.md) | [Siguiente: LyCORIS y LoRA Avanzado →](20-lycoris-lora-avanzado.md)*
