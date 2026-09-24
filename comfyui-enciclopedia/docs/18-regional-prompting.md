# 18 — Regional Prompting y Conditioning Avanzado

Cómo aplicar diferentes prompts a diferentes zonas de la imagen.

---

## ¿Por qué Regional Prompting?

Con un solo prompt, el modelo toma decisiones globales. Si pides "a man on the left and a woman on the right", el modelo puede mezclarlos o ignorar la distribución espacial. Con regional prompting, asignas condicionamientos distintos a zonas específicas del latente.

---

## Método 1: Attention Couple

Custom node: `ComfyUI-Attention-Couple`

Divide la imagen en regiones y aplica diferentes condicionamientos con atención separada.

### Nodo: `AttentionCouple`
- Define regiones como porcentajes del ancho/alto
- Cada región recibe su propio conditioning positivo
- El conditioning negativo puede ser global

```
Región izquierda (0–50% ancho): "a man in a blue suit"
Región derecha (50–100% ancho): "a woman in a red dress"
Negativo global: "ugly, blurry"
```

---

## Método 2: Latent Couple

Custom node: `ComfyUI-Latent-Couple`

Opera directamente en el espacio latente, dividiendo el latente en regiones antes del sampler.

### Nodo: `LatentCouple`
- Divide el latente según divisiones especificadas
- Cada región se samplea con su propio conditioning
- Más explícito que Attention Couple pero menos suave en los bordes

---

## Método 3: Condicionamiento con Máscaras (SetConditioningAreaMask)

Nodo nativo de ComfyUI. Aplica un conditioning solo en el área definida por una máscara.

### Flujo
```
[CLIP Text Encode "cielo azul"] → CONDITIONING
        ↓
[ConditioningSetMask]
    mask: máscara de la zona superior (cielo)
    strength: 1.0
        ↓
 CONDITIONING con área → combinar con [ConditioningCombine]
```

### Nodos involucrados
- `ConditioningSetMask` — asigna una máscara a un conditioning
- `ConditioningCombine` — combina múltiples conditionings con áreas
- `ConditioningSetTimestepRange` — limita el conditioning a ciertos steps

---

## Método 4: SDXL Regional Conditioning

SDXL tiene soporte nativo para prompts con coordenadas de área:

### Nodo: `CLIPTextEncodeSDXL`
Acepta parámetros de crop y target para indicar al modelo qué parte de la imagen representa el prompt.

### Nodo: `ConditioningSetArea`
Asigna coordenadas (x, y, width, height) a un conditioning:
```
ConditioningSetArea:
  conditioning: "a blue sky"
  x: 0, y: 0
  width: 1024, height: 400   ← zona superior
  strength: 1.0
```

---

## Método 5: Gligen (Spatial Grounding)

Gligen es una extensión de SD1.5 que permite especificar **qué objeto va en qué coordenada**.

### Nodo: `GLIGENTextBoxApply`
- `text`: qué poner
- `x`, `y`, `width`, `height`: dónde (en píxeles)
- Compatible con workflows estándar de SD1.5

Requiere modelo Gligen (`gligen_sd14_textbox_pruned.safetensors` en `models/gligen/`).

---

## ConditioningCombine vs ConditioningConcat

| Nodo | Comportamiento |
|------|----------------|
| `ConditioningCombine` | Combina conditionings con promediado de pesos |
| `ConditioningConcat` | Concatena los vectores de conditioning |
| `ConditioningSetTimestepRange` | Solo activa el conditioning en ciertos steps |

**Truco avanzado**: usar `ConditioningSetTimestepRange` para que el prompt de composición actúe solo en los primeros steps (0.0–0.5) y el prompt de detalles solo en los últimos (0.5–1.0).

---

## Pesos en el Prompt (Syntax)

### Aumentar peso de un término
```
(beautiful eyes:1.4)    ← 1.4x más énfasis
((very detailed))       ← cada paréntesis ≈ ×1.1
```

### Reducir peso de un término
```
(blurry:0.5)            ← 0.5x menos énfasis
[style_word]            ← ligeramente reducido
```

### AND syntax (BREAK)
Separa el prompt en secciones independientes que el modelo procesa por separado:
```
a beautiful sunset over the ocean BREAK
high quality, photorealistic, detailed
```

---

## Prompt Scheduling por Step

Algunos custom nodes permiten cambiar el prompt durante los steps:

```
Step 0–10:  "a rough landscape sketch"
Step 10–20: "a detailed photorealistic landscape"
```

Útil para guiar la composición inicial y luego el detalle. Disponible en nodos de CR Animation.

---

*[← Mejora de Rostros](17-mejora-de-rostros.md) | [Siguiente: Model Merging →](19-model-merging.md)*
