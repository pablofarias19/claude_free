# 📚 Enciclopedia ComfyUI en Español

Guía completa y diccionario de referencia para ComfyUI: nodos, modelos, samplers, flujos de trabajo, errores y todo el ecosistema explicado en español.

---

## ¿Qué es ComfyUI?

ComfyUI es una interfaz gráfica basada en **nodos** para ejecutar modelos de generación de imágenes con IA (Stable Diffusion, SDXL, Flux, SD3, etc.). En lugar de una pantalla con botones, trabajas conectando bloques llamados **nodos** que procesan y pasan datos entre sí.

Cada nodo hace una cosa específica: cargar un modelo, codificar texto, generar ruido, decodificar una imagen. La combinación de nodos forma un **workflow** (flujo de trabajo).

---

## 🗂️ Índice de la Enciclopedia

| # | Archivo | Contenido |
|---|---------|----------|
| 1 | [Arquitectura y Conceptos](docs/01-arquitectura-y-conceptos.md) | Cómo funciona ComfyUI por dentro, espacio latente, pipeline |
| 2 | [Nodos Fundamentales](docs/02-nodos-fundamentales.md) | Los nodos base que usa todo workflow |
| 3 | [Modelos y Checkpoints](docs/03-modelos-checkpoints.md) | SD1.5, SDXL, Flux, SD3 — diferencias y uso |
| 4 | [VAE](docs/04-vae.md) | Qué es, cuándo usarlo, modelos comunes |
| 5 | [CLIP y Texto](docs/05-clip-y-texto.md) | Cómo se procesan los prompts |
| 6 | [Samplers y Schedulers](docs/06-samplers-y-schedulers.md) | Euler, DPM++, karras, steps, CFG |
| 7 | [LoRA y Embeddings](docs/07-lora-embeddings.md) | Fine-tuning ligero, textual inversion |
| 8 | [ControlNet](docs/08-controlnet.md) | Control de composición y pose |
| 9 | [IP-Adapter](docs/09-ip-adapter.md) | Prompts de imagen |
| 10 | [Upscalers](docs/10-upscalers.md) | Ampliar resolución |
| 11 | [Inpainting](docs/11-inpainting.md) | Edición de zonas específicas |
| 12 | [Workflows y JSON](docs/12-workflows-y-json.md) | Guardar, cargar y entender workflows |
| 13 | [Custom Nodes](docs/13-custom-nodes.md) | Extensiones populares |
| 14 | [Errores Comunes](docs/14-errores-comunes.md) | Diagnóstico y soluciones |
| — | [Glosario](glosario.md) | Diccionario alfabético de todos los términos |

---

## 🚀 Cómo usar esta guía

- **Principiantes**: empieza por `01-arquitectura-y-conceptos.md` y `02-nodos-fundamentales.md`
- **Buscas un término específico**: ve directo al [Glosario](glosario.md)
- **Tienes un error**: ve a [Errores Comunes](docs/14-errores-comunes.md)
- **Quieres instalar extensiones**: ve a [Custom Nodes](docs/13-custom-nodes.md)

---

## Estructura de archivos de ComfyUI

```
ComfyUI/
├── models/
│   ├── checkpoints/     ← modelos principales (.safetensors, .ckpt)
│   ├── vae/             ← modelos VAE
│   ├── loras/           ← archivos LoRA
│   ├── embeddings/      ← textual inversions
│   ├── controlnet/      ← modelos ControlNet
│   ├── ipadapter/       ← modelos IP-Adapter
│   ├── upscale_models/  ← modelos de upscaling
│   └── clip/            ← modelos CLIP/T5
├── custom_nodes/        ← extensiones instaladas
├── input/               ← imágenes de entrada
├── output/              ← imágenes generadas
└── workflows/           ← workflows guardados (.json)
```

---

*Última actualización: 2026 · Mantenido por la comunidad hispanohablante de ComfyUI*
