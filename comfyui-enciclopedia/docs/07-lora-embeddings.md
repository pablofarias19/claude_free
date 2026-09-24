# 07 — LoRA y Embeddings

Herramientas para personalizar y extender los modelos base sin reentrenarlos por completo.

---

## LoRA (Low-Rank Adaptation)

### ¿Qué es?
LoRA es un método de fine-tuning eficiente que entrena solo una pequeña fracción del modelo (matrices de baja dimensión). El resultado es un archivo separado (10–300 MB) que **modifica** el checkpoint base.

### ¿Para qué se usa?
- Personajes específicos (actores, personajes animados)
- Estilos artísticos (pintura al óleo, anime, fotorrealismo extremo)
- Conceptos específicos (ropa, objetos, poses)
- Mejoras de calidad general

### Compatibilidad
- LoRAs de SD1.5 → solo con modelos SD1.5
- LoRAs de SDXL → solo con modelos SDXL
- LoRAs de Flux → solo con Flux
- **No son intercambiables entre arquitecturas**

### Dónde colocarlos
```
ComfyUI/models/loras/
```

---

## Nodo: Load LoRA

**Función**: Aplica un LoRA sobre el modelo y el CLIP.

**Entradas**:
- `model` — el MODEL del checkpoint
- `clip` — el CLIP del checkpoint

**Parámetros**:
- `lora_name` — archivo LoRA a usar
- `strength_model` — intensidad sobre el UNet (0.0–1.5)
- `strength_clip` — intensidad sobre el CLIP (0.0–1.5)

**Salidas**:
- `MODEL` modificado → al KSampler
- `CLIP` modificado → al CLIP Text Encode

### Cómo encadenar múltiples LoRAs
```
[Load Checkpoint] ── MODEL, CLIP ── [Load LoRA 1] ── MODEL, CLIP ── [Load LoRA 2] ── ...
```
Cada Load LoRA toma el MODEL y CLIP del anterior y pasa los modificados al siguiente.

---

## Strength (Peso del LoRA)

| Valor | Efecto |
|-------|--------|
| 0.0 | El LoRA no tiene efecto |
| 0.5 | Influencia moderada, mezcla sutil |
| 0.8–1.0 | Influencia estándar recomendada |
| 1.2–1.5 | Influencia intensa (puede artefactar) |
| >1.5 | Generalmente produce resultados rotos |

El valor óptimo depende del LoRA específico. Siempre revisar la página del LoRA en Civitai para la recomendación del creador.

---

## Trigger Words (Palabras Activadoras)

Muchos LoRAs requieren una **trigger word** específica en el prompt para activarse. Sin ella, el LoRA puede no funcionar aunque esté cargado.

Ejemplo: Un LoRA de estilo 80s puede requerir `retro80s` en el prompt.

Siempre verificar en la página del LoRA cuáles son sus trigger words.

---

## Textual Inversion (Embeddings)

### ¿Qué es?
Un embedding es un archivo pequeño (≈10–100 KB) que añade un nuevo **token** al vocabulario del CLIP. Al escribir ese token en el prompt, el modelo "recuerda" el concepto.

### Tipos de uso
- **Positivos**: añadir estilos, personajes, mejoras de calidad
- **Negativos**: términos negativos potentes como `EasyNegative`, `BadDream`

### Dónde colocarlos
```
ComfyUI/models/embeddings/
```

### Cómo usarlos en el prompt
```
(embedding:EasyNegative:1.0)   ← en el prompt negativo
(embedding:GoodQuality:1.2)    ← en el prompt positivo
```
O en algunos casos simplemente escribir el nombre del archivo sin extensión.

### Diferencia con LoRA
| Aspecto | LoRA | Embedding |
|---------|------|----------|
| Tamaño | 10–300 MB | 10–100 KB |
| Modifica | UNet + CLIP | Solo CLIP |
| Efecto | Más potente | Más sutil |
| Velocidad de carga | Más lento | Instantáneo |

---

## Hypernetworks (legado)

Método anterior a LoRA. Modifica las capas de atención del UNet. Casi completamente reemplazado por LoRA. Si encuentras un `.pt` de hypernetwork:
```
ComfyUI/models/hypernetworks/
```
No hay soporte nativo directo en ComfyUI — requiere extensiones.

---

## LCM LoRA

Tipo especial de LoRA que permite generar con muy pocos steps (4–8) a costa de algo de calidad. Se aplica igual que cualquier LoRA pero:
- Usar sampler `lcm`
- CFG muy bajo (1.0–2.0)
- 4–8 steps

**Advertencia**: incompatible con ControlNet en muchos casos.

---

*[← CLIP y Texto](05-clip-y-texto.md) | [Siguiente: ControlNet →](08-controlnet.md)*
