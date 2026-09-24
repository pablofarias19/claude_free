# 06 — Samplers y Schedulers

El sampler y el scheduler determinan **cómo** se elimina el ruido en cada paso. Son de los parámetros que más afectan calidad y velocidad.

---

## ¿Qué es un Sampler?

El sampler es el **algoritmo matemático** que decide cuánto ruido eliminar en cada step y cómo moverse por el espacio latente. Es como el método de resolución de una ecuación diferencial.

---

## Samplers Disponibles y Cuándo Usarlos

### euler
- El más simple. Método de Euler para ecuaciones diferenciales.
- Bueno para experimentar: comportamiento predecible.
- Necesita más steps para calidad óptima (25–30).

### euler_ancestral (euler_a)
- Agrega ruido estocástico en cada step.
- **Nunca converge** aunque aumente los steps: cada generación es diferente aunque el seed sea igual con distintos steps.
- Produce resultados créativos y variados. Muy popular en SD1.5.

### dpm_2
- Solver de segundo orden. Más preciso que Euler pero más costoso por step.

### dpm_2_ancestral
- Variante estocástica de DPM2.

### dpmpp_2m (DPM++ 2M)
- **Uno de los mejores para uso general.** Solver de segundo orden mejorado.
- Converge rápido: buen balance calidad/velocidad con 20 steps.
- Recomendado con scheduler `karras`.

### dpmpp_2m_sde
- Variante estocástica de DPM++ 2M. Más variedad, un poco menos estable.

### dpmpp_3m_sde
- Solver de tercer orden estocástico. Alta calidad pero más costoso.

### ddim
- Uno de los originales. Determinístico. Muy bueno para inpainting.
- Converge bien, útil cuando necesitas consistencia exacta.

### ddpm
- El sampler original de difusión. Lento, requiere muchos steps. Poco usado hoy.

### unipc
- Solver de alta precisión. Buena calidad con pocos steps (15–20).

### lcm
- Latent Consistency Model sampler. Diseñado para 4–8 steps con modelos LCM o LoRAs LCM.

### deis
- Solver eficiente para pocos steps (10–15). Buena calidad.

---

## ¿Qué es un Scheduler?

El scheduler define el **cronograma de ruido**: cuánto ruido hay al inicio y cómo disminuye step a step. Dos schedulers con el mismo sampler producen trayectorias de denoising distintas.

### normal
- Distribución lineal de ruido. El comportamiento base.

### karras
- Distribución no lineal: los primeros steps eliminan más ruido y los últimos refinan detalles finos.
- **Generalmente mejora la calidad** sin costo adicional.
- Uso recomendado: `dpmpp_2m` + `karras`.

### exponential
- Curva exponencial. Buenos resultados con ciertos modelos.

### sgm_uniform
- Usado en SDXL oficial. Distribución uniforme en escala SGM.

### simple
- Equivalente a normal para la mayoría de casos.

### beta
- Scheduler basado en distribución beta. Experimental.

---

## Parámetros del KSampler explicados

### steps
Número de iteraciones del sampler. Más no siempre es mejor:

| Rango | Resultado |
|-------|----------|
| 1–8 | Solo con LCM/SDXL-Turbo. Baja calidad en samplers normales. |
| 15–20 | Buena calidad con DPM++ + karras. Óptimo para velocidad. |
| 25–30 | Calidad máxima para la mayoría de modelos. |
| 40+ | Rendimientos decrecientes. Casi nunca vale la pena. |

### cfg (CFG Scale)

| Valor | Efecto |
|-------|--------|
| 1–3 | Casi sin guía. Creativo pero incoherente con el prompt. |
| 4–6 | Balance. Bueno para Flux y modelos modernos. |
| 7–9 | Estándar para SD1.5 y SDXL. |
| 10–12 | Alta fidelidad al prompt. Riesgo de colores extremos. |
| 15+ | Saturación y artefactos en la mayoría de modelos. |

**Flux recomienda CFG entre 2.5 y 4.5** (arquitectura diferente).

### denoise
- `1.0`: Generación completa desde ruido (text-to-image).
- `0.5–0.8`: img2img típico. Modifica bastante la imagen de entrada.
- `0.2–0.4`: Variaciones sutiles. Preserva la composición original.
- `0.0`: Sin cambios. La imagen de entrada pasa sin modificar.

---

## Combinaciones Recomendadas

| Objetivo | Sampler | Scheduler | Steps | CFG |
|----------|---------|-----------|-------|-----|
| SD1.5 general | dpmpp_2m | karras | 20 | 7 |
| SD1.5 variado | euler_a | normal | 25 | 7 |
| SDXL calidad | dpmpp_2m | karras | 25 | 7 |
| SDXL rápido | dpmpp_2m_sde | karras | 18 | 6 |
| Flux.1-dev | euler | normal | 20 | 3.5 |
| Flux.1-schnell | euler | normal | 4 | 1 |
| LCM LoRA | lcm | sgm_uniform | 6 | 1.5 |
| Inpainting | ddim | normal | 30 | 7 |

---

*[← CLIP y Texto](05-clip-y-texto.md) | [Siguiente: LoRA y Embeddings →](07-lora-embeddings.md)*
