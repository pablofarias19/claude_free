# Enciclopedia ComfyUI en Español

> **AGENTES IA**: Empezar por [`PARA-IA.md`](./PARA-IA.md) — contiene árboles de decisión, matrices de compatibilidad, casos excepcionales y fuentes. El perfil de hardware del usuario está en [`docs/37-perfiles-hardware.md`](./docs/37-perfiles-hardware.md).

Referencia completa del ecosistema ComfyUI: nodos, modelos, samplers, errores, video IA, herramientas de entrenamiento, generación 3D y audio. Todos los documentos incluyen declaraciones técnicas para consumo por agentes IA, casos excepcionales y recursos.

**Total: 37 documentos + glosario + PARA-IA = 39 archivos**

---

## 📖 Índice

### Fundamentos

| # | Título | Contenido clave |
|---|---|---|
| [01](docs/01-arquitectura-y-conceptos.md) | Arquitectura y Conceptos | Espacio latente, UNet, VAE, CLIP, pipeline de difusión |
| [02](docs/02-nodos-fundamentales.md) | Nodos Fundamentales | Load Checkpoint, KSampler, VAE Decode, Save Image |
| [03](docs/03-modelos-checkpoints.md) | Modelos y Checkpoints | SD1.5, SDXL, Flux, SD3 — comparativa, formatos |
| [04](docs/04-vae.md) | VAE | Encode/decode, VAEs recomendados, diagnóstico |
| [05](docs/05-clip-y-texto.md) | CLIP y Texto | Variantes CLIP, límite tokens, prompting por arquitectura |
| [06](docs/06-samplers-y-schedulers.md) | Samplers y Schedulers | Euler, DPM++, DDIM, LCM, CFG, karras, guía completa |
| [07](docs/07-lora-embeddings.md) | LoRA y Embeddings | Load LoRA, strength, trigger words, LCM LoRA |
| [08](docs/08-controlnet.md) | ControlNet | Canny, depth, pose, tile — preprocessors, fuerza |
| [09](docs/09-ip-adapter.md) | IP-Adapter | Style transfer, FaceID, combinaciones |
| [10](docs/10-upscalers.md) | Upscalers | ESRGAN, latent upscale, hi-res fix workflow |
| [11](docs/11-inpainting.md) | Inpainting | 3 métodos, máscaras, editor visual |
| [12](docs/12-workflows-y-json.md) | Workflows y JSON | Estructura JSON, API mode, nodos de utilidad |
| [13](docs/13-custom-nodes.md) | Custom Nodes | Manager, Impact Pack, WAS Suite, AnimateDiff |
| [14](docs/14-errores-comunes.md) | Errores Comunes | VRAM, imágenes negras, NaN, diagnóstico por árbol |
| [Glosario](glosario.md) | Glosario A–Z | ~50 términos definidos |

### Avanzado — Imagen

| # | Título | Contenido clave |
|---|---|---|
| [15](docs/15-flux-avanzado.md) | Flux Avanzado | MMDiT, FP8/GGUF, FluxGuidance, LoRAs Flux |
| [17](docs/17-mejora-de-rostros.md) | Mejora de Rostros | FaceDetailer, ReActor, IP-Adapter FaceID, manos |
| [18](docs/18-regional-prompting.md) | Regional Prompting | Attention Couple, ConditioningSetMask, Gligen |
| [19](docs/19-model-merging.md) | Model Merging | Weighted Sum, Add Difference, cirugía de modelos |
| [20](docs/20-lycoris-lora-avanzado.md) | LyCORIS y LoRA Avanzado | LoCon, LoHa, LoKR, DoRA, rank, stacking |
| [21](docs/21-nodos-utiles-avanzados.md) | Nodos Avanzados | ImageBatch, LatentBlend, MaskComposite, debug |
| [22](docs/22-optimizacion-rendimiento.md) | Optimización | xformers, Flash Attention, flags, perfiles VRAM |
| [23](docs/23-workflows-referencia.md) | Workflows de Referencia | 8 workflows anotados completos |
| [27](docs/27-sd3-arquitecturas.md) | SD3 y DiT | MMDiT triple CLIP, SD3.5, PixArt, AuraFlow |
| [28](docs/28-outpainting-tiling.md) | Outpainting y Tiling | Ultimate SD Upscale, outpainting progresivo, 4K |
| [31](docs/31-mac-mps-backend.md) | Mac MPS Backend | Apple Silicon, MPS, GGUF en Mac, errores comunes |
| [32](docs/32-stable-cascade.md) | Stable Cascade | Arquitectura 3 etapas, compresión 42x, LoRAs |
| [35](docs/35-depth-estimation.md) | Estimación de Profundidad | Marigold, Depth Anything v2, DepthPro, ControlNet |
| [36](docs/36-3d-generation.md) | Generación 3D | TripoSR, Zero123++, InstantMesh, Gaussian Splatting |

### Avanzado — Video IA

| # | Título | Contenido clave |
|---|---|---|
| [16](docs/16-animatediff-video.md) | AnimateDiff | Motion modules, SVD, context windows, VHS |
| [24](docs/24-modelos-text-to-video.md) | Modelos Text-to-Video | CogVideoX, LTX, Mochi, Hunyuan, Wan2.1 |
| [25](docs/25-consistencia-temporal.md) | Consistencia Temporal | RIFE, optical flow, depth-guided, schedules |
| [26](docs/26-video-edicion.md) | Video Edición | v2v, style transfer, VHS nodes, batch VRAM |

### Herramientas y Sistemas

| # | Título | Contenido clave |
|---|---|---|
| [29](docs/29-entrenamiento-lora.md) | Entrenamiento LoRA | kohya_ss, SimpleTuner, datasets, hiperparámetros |
| [30](docs/30-api-automatizacion.md) | API y Automatización | HTTP API, Python, WebSocket, workflows dinámicos |
| [33](docs/33-multi-gpu.md) | Multi-GPU | Múltiples instancias, load balancing, monitoreo |
| [34](docs/34-audio-generation.md) | Generación de Audio | MusicGen, AudioLDM2, Bark TTS, video+audio |
| [37](docs/37-perfiles-hardware.md) | Perfiles de Hardware | **Perfil Pablo (RTX 5080)**, perfiles 4–24 GB VRAM |
| [PARA-IA](PARA-IA.md) | Guía para Agentes IA | Árboles de decisión, matrices, casos excepcionales |

---

## Estructura de carpetas ComfyUI

```
comfyui/
├── main.py
├── models/
│   ├── checkpoints/      ← .safetensors, .ckpt (modelos base)
│   ├── loras/            ← LoRA y LyCORIS (.safetensors)
│   ├── vae/              ← VAE externos (.safetensors, .pt)
│   ├── controlnet/       ← ControlNet y T2I Adapter
│   ├── clip/             ← CLIP, T5, encoders de texto
│   ├── unet/             ← UNet independientes (Flux)
│   ├── upscale_models/   ← ESRGAN, GFPGAN, RealESRGAN
│   ├── embeddings/       ← Textual Inversion (.pt, .safetensors)
│   ├── ipadapter/        ← IP-Adapter pesos
│   ├── animatediff_models/ ← Motion modules
│   └── gguf/             ← Modelos cuantizados GGUF
├── custom_nodes/         ← Extensiones instaladas
├── output/               ← Imágenes y videos generados
├── input/                ← Imágenes de entrada para workflows
├── temp/                 ← Caché temporal
└── user/
    └── default/
        └── workflows/    ← Workflows guardados (.json)
```

---

## Compatibilidad rápida LoRA × Arquitectura

| LoRA entrenado en | SD1.5 | SD2.x | SDXL | Flux | SD3 | Cascade |
|---|---|---|---|---|---|---|
| SD1.5 | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| SDXL | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Flux | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| SD3 / SD3.5 | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Cascade | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

---

## Hardware de referencia (usuario Pablo)

```
GPU: NVIDIA RTX 5080 (Blackwell) — 16 GB VRAM GDDR7
CPU: Intel i9-10900 — RAM: 32 GB — SO: Windows 11 Pro
Configuración Flux recomendada: FP8 sin flags especiales
Detalles completos: docs/37-perfiles-hardware.md
```
