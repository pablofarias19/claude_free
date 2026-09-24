# 📚 Enciclopedia ComfyUI en Español

Guía completa y diccionario de referencia para ComfyUI: nodos, modelos, samplers, flujos de trabajo, errores y todo el ecosistema explicado en español.

> 🤖 **Agentes IA**: leer primero [`PARA-IA.md`](PARA-IA.md) — contiene árboles de decisión, compatibilidades críticas, casos excepcionales y fuentes de verdad.

---

## ¿Qué es ComfyUI?

ComfyUI es una interfaz gráfica basada en **nodos** para ejecutar modelos de generación de imágenes y video con IA (Stable Diffusion, SDXL, Flux, SD3, AnimateDiff, CogVideoX, etc.).

Cada nodo hace una cosa específica. La combinación de nodos forma un **workflow**.

---

## 🗂️ Índice Completo

### Fundamentos
| # | Archivo | Contenido |
|---|---------|----------|
| 1 | [Arquitectura y Conceptos](docs/01-arquitectura-y-conceptos.md) | Espacio latente, pipeline, tipos de datos |
| 2 | [Nodos Fundamentales](docs/02-nodos-fundamentales.md) | KSampler, VAE Decode, Load Checkpoint y más |
| 3 | [Modelos y Checkpoints](docs/03-modelos-checkpoints.md) | SD1.5, SDXL, Flux, SD3 — diferencias y uso |
| 4 | [VAE](docs/04-vae.md) | Qué es, cuándo usarlo, modelos comunes |
| 5 | [CLIP y Texto](docs/05-clip-y-texto.md) | Procesamiento de prompts, T5, CLIP Skip |
| 6 | [Samplers y Schedulers](docs/06-samplers-y-schedulers.md) | Euler, DPM++, karras, CFG, steps |
| 7 | [LoRA y Embeddings](docs/07-lora-embeddings.md) | Fine-tuning ligero, textual inversion |
| 8 | [ControlNet](docs/08-controlnet.md) | Control de composición, pose, profundidad |
| 9 | [IP-Adapter](docs/09-ip-adapter.md) | Prompts visuales, FaceID |
| 10 | [Upscalers](docs/10-upscalers.md) | ESRGAN, latent upscale, hi-res fix |
| 11 | [Inpainting](docs/11-inpainting.md) | Edición de zonas específicas con máscara |
| 12 | [Workflows y JSON](docs/12-workflows-y-json.md) | Guardar, cargar, API mode |
| 13 | [Custom Nodes](docs/13-custom-nodes.md) | 10+ extensiones esenciales explicadas |
| 14 | [Errores Comunes](docs/14-errores-comunes.md) | Diagnóstico, causas, soluciones, checklist |

### Avanzado — Imagen
| # | Archivo | Contenido |
|---|---------|----------|
| 15 | [Flux Avanzado](docs/15-flux-avanzado.md) | DiT, FP8/GGUF, guidance, prompting natural |
| 17 | [Mejora de Rostros](docs/17-mejora-de-rostros.md) | FaceDetailer, detectores, ReActor, manos |
| 18 | [Regional Prompting](docs/18-regional-prompting.md) | Attention Couple, Gligen, pesos en prompt |
| 19 | [Model Merging](docs/19-model-merging.md) | Weighted sum, block merge por capa |
| 20 | [LyCORIS y LoRA Avanzado](docs/20-lycoris-lora-avanzado.md) | LoCon, LoHa, DoRA, block weight |
| 21 | [Nodos Útiles Avanzados](docs/21-nodos-utiles-avanzados.md) | 30+ nodos de imagen, máscara, latente |
| 22 | [Optimización y Rendimiento](docs/22-optimizacion-rendimiento.md) | Flags, xformers, Flash Attn, Turbo/Lightning |
| 23 | [Workflows de Referencia](docs/23-workflows-referencia.md) | 8 workflows completos comentados |
| 27 | [SD3 y Arquitecturas Modernas](docs/27-sd3-arquitecturas.md) | MMDiT, triple CLIP, PixArt, Kolors |
| 28 | [Outpainting y Tiling](docs/28-outpainting-tiling.md) | Extender imágenes, generación 4K, seamless |

### Avanzado — Video
| # | Archivo | Contenido |
|---|---------|----------|
| 16 | [AnimateDiff y Video](docs/16-animatediff-video.md) | Motion modules, SVD, RIFE, exportar video |
| 24 | [Modelos Text-to-Video](docs/24-modelos-text-to-video.md) | CogVideoX, LTX Video, Wan2.1, Hunyuan, tabla comparativa |
| 25 | [Consistencia Temporal](docs/25-consistencia-temporal.md) | Anti-flickering, RIFE, optical flow, depth guidance |
| 26 | [Video Edición y V2V](docs/26-video-edicion.md) | Video-to-video, estilización, inpainting en video |

### Herramientas y Producción
| # | Archivo | Contenido |
|---|---------|----------|
| 29 | [Entrenamiento de LoRA](docs/29-entrenamiento-lora.md) | kohya_ss, dataset, parámetros, diagnóstico |
| 30 | [API y Automatización](docs/30-api-automatizacion.md) | HTTP API, WebSocket, Python, producción |

### Referencia
| Archivo | Contenido |
|---------|----------|
| [Glosario](glosario.md) | ~50 términos ordenados A–Z |
| [PARA-IA.md](PARA-IA.md) | Árboles de decisión, compatibilidades, casos excepcionales, fuentes |

---

## 🚀 Cómo usar esta guía

| Situación | Dónde ir |
|-----------|----------|
| Soy principiante | [01-arquitectura](docs/01-arquitectura-y-conceptos.md) → [02-nodos](docs/02-nodos-fundamentales.md) |
| Tengo un error | [14-errores-comunes](docs/14-errores-comunes.md) |
| No sé qué sampler usar | [06-samplers](docs/06-samplers-y-schedulers.md) tabla de combinaciones |
| Los rostros salen mal | [17-mejora-de-rostros](docs/17-mejora-de-rostros.md) |
| Quiero hacer video | [24-text-to-video](docs/24-modelos-text-to-video.md) → tabla comparativa |
| Quiero Flux | [15-flux-avanzado](docs/15-flux-avanzado.md) |
| GPU con poca VRAM | [22-optimizacion](docs/22-optimizacion-rendimiento.md) |
| Quiero automatizar con Python | [30-api](docs/30-api-automatizacion.md) |
| Quiero entrenar mi LoRA | [29-training-lora](docs/29-entrenamiento-lora.md) |
| Busco un término | [Glosario](glosario.md) |
| Soy un agente IA | [PARA-IA.md](PARA-IA.md) PRIMERO |

---

## Estructura de Archivos de ComfyUI

```
ComfyUI/
├── models/
│   ├── checkpoints/         ← modelos principales (.safetensors, .ckpt)
│   ├── vae/                 ← modelos VAE
│   ├── loras/               ← LoRA y LyCORIS
│   ├── embeddings/          ← textual inversions
│   ├── controlnet/          ← modelos ControlNet
│   ├── ipadapter/           ← modelos IP-Adapter
│   ├── upscale_models/      ← ESRGAN y otros
│   ├── clip/                ← CLIP/T5 separados
│   ├── unet/                ← UNets separados (Flux, etc.)
│   ├── clip_vision/         ← CLIP Vision (IP-Adapter)
│   ├── ultralytics/         ← detectores (FaceDetailer)
│   ├── sams/                ← SAM models
│   ├── animatediff_models/  ← motion modules
│   ├── gligen/              ← Gligen
│   └── wan_video/           ← Wan2.1 y otros modelos de video
├── custom_nodes/            ← extensiones instaladas
├── input/                   ← imágenes / videos de entrada
├── output/                  ← resultados
└── user/                    ← workflows y configuración
```

---

*Última actualización: 2026 · 30 documentos + glosario + guía para IA*
