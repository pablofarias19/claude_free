# 29 — Entrenamiento de LoRA Propio

Crea tus propios LoRAs para personajes, estilos u objetos específicos.

---

## ¿Cuándo entrenar un LoRA?

**Sí tiene sentido entrenar cuando**:
- Necesitas un personaje o cara específica que no existe en modelos públicos
- Quieres un estilo único y consistente entre generaciones
- Tienes 15–100 imágenes de referencia de buena calidad

**No tiene sentido cuando**:
- Hay un LoRA público que ya hace lo que necesitas
- Solo tienes 1–5 imágenes (resultado será sobreajustado)
- No tienes GPU (entrenamiento en CPU = días)

---

## Herramientas de Entrenamiento

### kohya_ss (la más popular)
- Repositorio: `bmaltais/kohya_ss`
- Interfaz web gradio
- Soporta SD1.5, SDXL, Flux, SD3
- Todos los algoritmos: LoRA, LyCORIS (LoCon, LoHa, DoRA)

### SimpleTuner
- Repositorio: `bghira/SimpleTuner`
- Más moderna, mejor soporte para Flux y SD3
- CLI con archivos de configuración YAML

### AI Toolkit
- Repositorio: `ostris/ai-toolkit`
- Muy popular para Flux LoRA
- Simple y efectiva

---

## Preparación del Dataset

### Cantidad de imágenes recomendada
| Tipo de LoRA | Imágenes mínimo | Óptimo |
|--------------|----------------|-------|
| Personaje/cara | 15 | 30–50 |
| Estilo artístico | 20 | 50–100 |
| Objeto/concepto | 10 | 20–40 |
| Pose específica | 5 | 15–25 |

### Calidad del dataset
- Resolución: mínimo 512px, preferiblemente 768px o 1024px
- Variedad: diferentes ángulos, iluminaciones, fondos
- Sin marcas de agua, texto superpuesto ni artefactos
- Coherencia: todas las imágenes del mismo concepto

### Captioning (texto descriptivo)
Cada imagen necesita un archivo `.txt` con descripción:
```
imágenes/foto1.jpg
imágenes/foto1.txt  ← "a woman with blue eyes and curly hair, smiling"
```

**Herramientas de captioning automático**:
- WD14 Tagger (para anime/ilustraciones)
- BLIP2 / Florence2 (para fotorrealismo)
- LLaVA (descripción natural detallada)
- Disponibles dentro de kohya_ss o como custom nodes en ComfyUI

---

## Parámetros Clave de Entrenamiento

### Learning Rate
- Demasiado alto: sobreajuste rápido, el modelo "olvida" el base
- Demasiado bajo: el LoRA no aprende el concepto
- Valores típicos: `1e-4` para LoRA SD1.5, `1e-4` para SDXL, `1e-4` para Flux

### Epochs y Steps
- `epoch`: una pasada completa por todas las imágenes del dataset
- `steps`: epoch × n_imágenes / batch_size
- Típicamente: 1000–2000 steps para personaje, 2000–4000 para estilo

### Network Rank (dim)
- Ver [LyCORIS y LoRA Avanzado](20-lycoris-lora-avanzado.md)
- Para personajes: rank 32–64 es habitual
- Para estilos: rank 16–32

### Regularization Images
Imágenes generadas del mismo clase sin el concepto específico. Evitan que el LoRA degrade la capacidad general del modelo. Recomendadas para LoRAs de cara/personaje.

---

## Workflow de Entrenamiento (kohya_ss)

```
1. Preparar dataset:
   dataset/
   ├── 10_nombre_clase/   ← "10" = repeats por imagen
   │   ├── imagen1.jpg
   │   ├── imagen1.txt
   │   └── ...
   └── reg/               ← regularization images

2. Configurar en kohya_ss:
   - Seleccionar modelo base
   - Apuntar al dataset
   - Configurar network rank, LR, steps

3. Entrenar (30 min–2 horas según GPU)

4. Probar el LoRA resultante en ComfyUI
   - Usar trigger word si se configuró
   - Ajustar strength (0.7–1.0)
```

---

## Diagnóstico de LoRAs Entrenados

| Problema | Causa probable | Solución |
|----------|----------------|----------|
| El concepto no aparece | Pocos steps / LR bajo | Aumentar steps o LR |
| El modelo base se degrada | Sobreentrenamiento | Reducir steps o usar reg images |
| Solo funciona con un fondo | Dataset sin variedad | Diversificar backgrounds |
| Los detalles no son precisos | Rank muy bajo | Aumentar a 32–64 |
| Artefactos extremos | LR demasiado alto | Reducir LR a la mitad |

---

## Recursos de Entrenamiento

- **kohya_ss**: https://github.com/bmaltais/kohya_ss
- **SimpleTuner**: https://github.com/bghira/SimpleTuner
- **AI Toolkit (Flux)**: https://github.com/ostris/ai-toolkit
- **Guía de dataset**: https://civitai.com/articles/107 (comunidad)
- **Evit: LoRA training guide**: https://rentry.org/lora_train

---

*[← Outpainting y Tiling](28-outpainting-tiling.md) | [Siguiente: API y Automatización →](30-api-automatizacion.md)*
