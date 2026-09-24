# 📚 Enciclopedia ComfyUI en Español

Guía completa y diccionario de referencia para ComfyUI: nodos, modelos, samplers, flujos de trabajo, errores y todo el ecosistema explicado en español.

---

## ¿Qué es ComfyUI?

ComfyUI es una interfaz gráfica basada en **nodos** para ejecutar modelos de generación de imágenes con IA (Stable Diffusion, SDXL, Flux, SD3, etc.). En lugar de una pantalla con botones, trabajas conectando bloques llamados **nodos** que procesan y pasan datos entre sí.

Cada nodo hace una cosa específica: cargar un modelo, codificar texto, generar ruido, decodificar una imagen. La combinación de nodos forma un **workflow** (flujo de trabajo).

---

## 🗂️ Índice Completo

### Fundamentos
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

### Avanzado
| # | Archivo | Contenido |
|---|---------|----------|
| 15 | [Flux Avanzado](docs/15-flux-avanzado.md) | Arquitectura DiT, formatos FP8/GGUF, guidance, LoRA Flux |
| 16 | [AnimateDiff y Video](docs/16-animatediff-video.md) | Animación, SVD, Wan2.1, RIFE, exportar video |
| 17 | [Mejora de Rostros](docs/17-mejora-de-rostros.md) | FaceDetailer, ReActor, Face ID, manos |
| 18 | [Regional Prompting](docs/18-regional-prompting.md) | Attention Couple, Latent Couple, Gligen, pesos en prompt |
| 19 | [Model Merging](docs/19-model-merging.md) | Weighted sum, Add Difference, block merge |
| 20 | [LyCORIS y LoRA Avanzado](docs/20-lycoris-lora-avanzado.md) | LoCon, LoHa, DoRA, block weight |
| 21 | [Nodos Útiles Avanzados](docs/21-nodos-utiles-avanzados.md) | Imágenes, máscaras, latentes, conditioning, debug |
| 22 | [Optimización y Rendimiento](docs/22-optimizacion-rendimiento.md) | Flags, xformers, Flash Attn, SDXL Turbo/Lightning |
| 23 | [Workflows de Referencia](docs/23-workflows-referencia.md) | 8 workflows completos comentados |

### Referencia
| Archivo | Contenido |
|---------|----------|
| [Glosario](glosario.md) | Diccionario alfabético de ~50 términos |

---

## 🚀 Cómo usar esta guía

- **Principiantes**: empieza por `01-arquitectura-y-conceptos.md` y `02-nodos-fundamentales.md`
- **Buscas un término específico**: ve directo al [Glosario](glosario.md)
- **Tienes un error**: ve a [Errores Comunes](docs/14-errores-comunes.md)
- **Quieres instalar extensiones**: ve a [Custom Nodes](docs/13-custom-nodes.md)
- **Usas Flux**: ve a [Flux Avanzado](docs/15-flux-avanzado.md)
- **Quieres hacer videos**: ve a [AnimateDiff y Video](docs/16-animatediff-video.md)
- **Los rostros salen mal**: ve a [Mejora de Rostros](docs/17-mejora-de-rostros.md)
- **Quieres optimizar velocidad**: ve a [Optimización y Rendimiento](docs/22-optimizacion-rendimiento.md)
- **Ejemplos prácticos**: ve a [Workflows de Referencia](docs/23-workflows-referencia.md)

---

## Estructura de archivos de ComfyUI

```
ComfyUI/
├── models/
│   ├── checkpoints/         ← modelos principales (.safetensors, .ckpt)
│   ├── vae/                 ← modelos VAE
│   ├── loras/               ← archivos LoRA y LyCORIS
│   ├── embeddings/          ← textual inversions
│   ├── controlnet/          ← modelos ControlNet
│   ├── ipadapter/           ← modelos IP-Adapter
│   ├── upscale_models/      ← modelos de upscaling (ESRGAN, etc.)
│   ├── clip/                ← modelos CLIP/T5 separados
│   ├── unet/                ← UNets separados (Flux, etc.)
│   ├── clip_vision/         ← CLIP Vision para IP-Adapter
│   ├── ultralytics/         ← detectores (FaceDetailer)
│   ├── sams/                ← SAM models
│   ├── animatediff_models/  ← motion modules
│   └── gligen/              ← modelos Gligen
├── custom_nodes/            ← extensiones instaladas
├── input/                   ← imágenes de entrada
├── output/                  ← imágenes generadas
└── user/                    ← workflows guardados y configuración
```

---

*Última actualización: 2026 · Mantenido por la comunidad hispanohablante de ComfyUI*
