# 37 · Perfiles de Hardware y Configuraciones Recomendadas

> **DECLARACIÓN TÉCNICA PARA IA**: Este documento define perfiles de hardware específicos con configuraciones optimizadas para ComfyUI. Incluye el perfil del sistema de referencia **Pablo** (RTX 5080 / i9-10900 / 32 GB RAM / Windows 11). Al diagnosticar problemas o recomendar configuraciones, consultar primero el perfil que más se aproxime al hardware del usuario.

## Sistema de referencia: Pablo

```
USUARIO: Pablo Farias (pablofarias19@gmail.com)
SISTEMA: Equipo personal principal

GPU: NVIDIA GeForce RTX 5080 (Blackwell GB203)
  VRAM dedicada: 16303 MB GDDR7
  CUDA cores: 10752
  Reloj gráfico: 2655 MHz
  Ancho de banda de memoria: 960 GB/s
  Interfaz de memoria: 256-bit
  Arquitectura: Blackwell (5a generación DLSS)

CPU: Intel Core i9-10900 @ 2.80GHz (10 núcleos / 20 hilos)
RAM: 32 GB (usable del sistema: ~16 GB con OS + drivers)
SO: Windows 11 Pro (22621)
Driver NVIDIA: 596.49 (Game Ready, mayo 2026)
DirectX: 12
Almacenamiento: SSD 447 GB (rápido) + HDD 2.7 TB (almacenamiento)
Pantalla: 1920x1080 @ 60 Hz
```

### Configuración óptima para el sistema Pablo

```bash
# Arranque de ComfyUI recomendado:
python main.py
# (Sin flags especiales — la RTX 5080 gestiona todo automáticamente)

# Con TensorFloat-32 habilitado (Blackwell lo soporta):
python main.py --use-pytorch-cross-attention

# Si hay problemas con modelos muy grandes:
python main.py --lowvram  # raramente necesario con 16 GB VRAM
```

### Capacidades del sistema Pablo

| Tarea | Viabilidad | Tiempo estimado | Notas |
|---|---|---|---|
| SD 1.5 (512px, 20 steps) | ✅ Excelente | ~2–3 seg | Sin límites |
| SDXL (1024px, 20 steps) | ✅ Excelente | ~5–8 seg | Fluido |
| Flux dev (1024px, 20 steps) | ✅ Muy buena | ~15–25 seg | FP8 recomendado |
| Flux dev FP8 (1024px) | ✅ Excelente | ~10–18 seg | Configuración óptima |
| Flux schnell (1024px, 4 steps) | ✅ Excelente | ~3–5 seg | Ultra rápido |
| AnimateDiff SD1.5 (16 frames) | ✅ Muy buena | ~30–45 seg | — |
| SVD (14 frames) | ✅ Muy buena | ~1–2 min | — |
| Wan2.1-1.3B (81 frames) | ✅ Buena | ~3–5 min | — |
| Wan2.1-14B (81 frames) | ⚠️ Posible | ~10–15 min | Necesita FP8/GGUF |
| CogVideoX-2B (49 frames) | ✅ Buena | ~4–6 min | — |
| CogVideoX-5B (49 frames) | ⚠️ Posible | ~8–12 min | FP8 o --lowvram |
| LTX Video (97 frames) | ✅ Buena | ~2–4 min | Muy eficiente |
| Hunyuan Video (97 frames) | ⚠️ Parcial | ~20–40 min | Necesita GGUF Q4 |
| TripoSR (3D) | ✅ Buena | ~10–20 seg | — |
| SD3.5 Large | ✅ Buena | ~20–35 seg | FP8 recomendado |

### Configuración Flux en el sistema Pablo

```
Opción 1 — FP8 (recomendada):
  UnetLoader: flux1-dev-fp8.safetensors (~8 GB VRAM)
  DualCLIPLoader: clip_l.safetensors + t5xxl_fp8_e4m3fn.safetensors
  VAELoader: ae.safetensors
  KSampler: steps=20, cfg=1.0, sampler=euler, scheduler=simple
  FluxGuidance: guidance=3.5

Opción 2 — BF16 completo (máxima calidad):
  UnetLoader: flux1-dev.safetensors (~24 GB — requiere offload a RAM)
  Agregar: --lowvram
  Tiempo: ~30–45 seg por imagen

Opción 3 — GGUF Q8 (equilibrio):
  flux1-dev-Q8_0.gguf (~16 GB)
  Precisión casi idéntica a BF16, cabe en VRAM
```

> **NOTA IMPORTANTE RTX 5080**: La RTX 5080 es arquitectura Blackwell. Requiere driver >=560 y PyTorch >=2.4 para aprovechar FP8 nativo y TensorFloat-32. Verificar que la instalación de PyTorch sea CUDA 12.4+.

### Verificación de entorno para Pablo

```bash
# Ejecutar en CMD o PowerShell con venv activado:
python -c "
import torch
print('PyTorch:', torch.__version__)
print('CUDA disponible:', torch.cuda.is_available())
print('CUDA version:', torch.version.cuda)
print('GPU:', torch.cuda.get_device_name(0))
print('VRAM total:', torch.cuda.get_device_properties(0).total_memory // (1024**3), 'GB')
print('Compute capability:', torch.cuda.get_device_capability(0))
"

# Salida esperada:
# PyTorch: 2.4.x o superior
# CUDA disponible: True
# CUDA version: 12.4
# GPU: NVIDIA GeForce RTX 5080
# VRAM total: 15 GB (aprox.)
# Compute capability: (10, 0)  # Blackwell = 10.x
```

## Perfiles de hardware generales

### Perfil BAJO: 4–6 GB VRAM

```
GPUs típicas: GTX 1060 6GB, RTX 3050, RX 580
Modelos viables: SD 1.5, SDXL Turbo/Lightning
Flags: --lowvram --force-fp16

Limitaciones:
  ❌ Flux dev (no alcanza la VRAM)
  ❌ SDXL base completo (marginal)
  ❌ AnimateDiff > 8 frames
  ❌ Modelos de video

Recomendaciones:
  · Usar SD 1.5 fine-tunes como base
  · SDXL Turbo (1–4 pasos, 6 GB viables)
  · LCM LoRA + SD 1.5 para velocidad
```

### Perfil MEDIO: 8–12 GB VRAM

```
GPUs típicas: RTX 3070/3080, RX 6800, RTX 4060 Ti
Modelos viables: SDXL, Flux FP8/GGUF, SD3 Medium
Flags: (ninguno extra, o --medvram para SDXL+ControlNet)

Capacidades:
  ✅ SDXL fluido
  ✅ Flux dev FP8/GGUF
  ✅ AnimateDiff hasta 16 frames
  ⚠️ Wan2.1-1.3B (lento)
  ❌ Hunyuan Video completo

Recomendaciones:
  · Flux dev FP8 como modelo principal
  · SDXL para workflows con ControlNet múltiple
  · AnimateDiff con context window (contexto limitado)
```

### Perfil ALTO: 16 GB VRAM (incluye sistema Pablo)

```
GPUs típicas: RTX 3090, RTX 4080, RTX 5080, A4000
Modelos viables: Todo excepto los más grandes sin cuantizar
Flags: (ninguno extra)

Capacidades:
  ✅ Flux dev FP8 + todos los SDXL
  ✅ Flux dev GGUF Q8 (completo en VRAM)
  ✅ AnimateDiff completo
  ✅ CogVideoX-2B cómodo
  ⚠️ CogVideoX-5B (possible con offload)
  ⚠️ Hunyuan Video (GGUF Q4 necesario)

Recomendaciones:
  · Flux dev GGUF Q8 o FP8 como principal
  · Wan2.1-14B con FP8 para video de calidad
  · Batch size 1 para video, 2–4 para imágenes
```

### Perfil PRO: 24 GB VRAM

```
GPUs típicas: RTX 3090, RTX 4090, A5000
Modelos viables: Prácticamente todo
Flags: --highvram (mantener modelos en GPU)

Capacidades:
  ✅ Flux dev BF16 completo
  ✅ CogVideoX-5B completo
  ✅ Hunyuan Video FP8
  ✅ SD3.5 Large completo
  ⚠️ Hunyuan Video BF16 (muy justo)

Recomendaciones:
  · Mantener modelos en GPU con --highvram
  · Flux BF16 para máxima calidad de imagen
  · Wan2.1-14B FP8 para video HD
```

## Windows vs Linux para ComfyUI

| Aspecto | Windows 11 | Linux (Ubuntu) |
|---|---|---|
| xformers | Disponible (compilado) | Nativo, más rápido |
| Flash Attention | Sí (con setup) | Sí |
| Velocidad relativa | Referencia | 5–15% más rápido |
| Compatibilidad custom nodes | Muy buena | Excelente |
| Gestión de VRAM | Buena | Mejor (menos overhead) |
| Instalación | Más fácil (GUI) | Más control |

> **Para el sistema Pablo (Windows 11)**: La diferencia de rendimiento Windows vs Linux es mínima con driver moderno (596.x). No es necesario migrar a Linux salvo para casos de uso muy especializados.

## Instalación ComfyUI en Windows 11 (específico para sistema Pablo)

```bash
# 1. Instalar Python 3.11 desde python.org (NO Microsoft Store)
# 2. Instalar Git para Windows
# 3. En CMD o PowerShell:

git clone https://github.com/comfyanonymous/ComfyUI.git
cd ComfyUI
python -m venv venv
venv\Scripts\activate

# Instalar PyTorch con CUDA 12.4 (para RTX 5080 Blackwell):
pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cu124

# Instalar dependencias:
pip install -r requirements.txt

# Ejecutar:
python main.py
# Abrir: http://localhost:8188
```

> **NOTA RTX 5080**: Blackwell puede necesitar CUDA 12.6+ para soporte completo. Si hay errores de CUDA, probar:
> `pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cu126`

## Modelos recomendados para comenzar (sistema de 16 GB VRAM)

```
IMPRESCINDIBLES:
  1. flux1-dev-fp8.safetensors         (imagen principal)
  2. flux1-schnell-fp8.safetensors     (prototipado rápido)
  3. ae.safetensors                    (VAE de Flux)
  4. clip_l.safetensors + t5xxl_fp8   (encoders de Flux)
  5. sdxl_base_1.0.safetensors        (SDXL para ControlNet)

RECOMENDADOS:
  6. control_lora_rank128_v11p_sd15_canny.safetensors
  7. controlnet-canny-sdxl-1.0.safetensors
  8. ip-adapter_sdxl.safetensors
  9. wan2.1-t2v-1.3B-fp8.safetensors  (video pequeño)
  10. RealESRGAN_x4plus.pth            (upscaler)

PARA ROSTROS:
  11. sam_vit_h_4b8939.pth            (segmentación)
  12. face_yolov8n.pt                 (detector caras)
  13. 4x_NMKD-Superscale_SP.pth      (upscaler rostros)
```

## Monitoreo de rendimiento en Windows

```bash
# En CMD/PowerShell:
nvidia-smi
nvidia-smi -l 1  # Actualizar cada segundo

# Ver VRAM usada:
nvidia-smi --query-gpu=memory.used,memory.free,utilization.gpu --format=csv

# Task Manager: Vista de GPU en la pestaña 'Rendimiento'
# Buscar: GPU 0 y GPU Memory (debe mostrar RTX 5080)
```

## Casos excepcionales

1. **RTX 5080 y xformers**: La versión actual de xformers puede no tener soporte nativo para Blackwell (SM 10.x). Verificar con `python -c "import xformers; print(xformers.__version__)"`. Si falla, usar `--use-pytorch-cross-attention` en lugar de xformers.
2. **32 GB RAM con 16 GB VRAM**: Ideal para offloading. ComfyUI puede usar la RAM como extensión de VRAM. Wan2.1-14B es viable con `--lowvram` — la diferencia es velocidad, no calidad.
3. **SSD de 447 GB**: Flux dev FP8 (~8 GB) + SDXL (~7 GB) + VAEs y CLIP (~5 GB) + custom nodes (~2 GB) = ~22 GB mínimos. Guardar modelos de video en HDD y mover al SSD cuando se usen para mayor velocidad de carga.
4. **HDCP y pantalla 1080p**: Sin impacto en generación. La pantalla de 1080p limita solo la previsualización, no la calidad de salida (se puede generar 4K aunque la pantalla sea 1080p).
5. **i9-10900 (10a gen, Comet Lake)**: No tiene aceleración AVX-512. Algunos modelos de CPU inference (ONNX runtime) pueden ser más lentos que en chips modernos. No afecta workflows típicos de ComfyUI donde la GPU hace el trabajo principal.

## Recursos

- ComfyUI instalación Windows: `https://github.com/comfyanonymous/ComfyUI#windows`
- Benchmarks GPU ComfyUI: `https://github.com/comfyanonymous/ComfyUI/discussions`
- RTX 5080 specs: `https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5080/`
- PyTorch CUDA compatibility: `https://pytorch.org/get-started/locally/`
- Modelos recomendados Civitai: `https://civitai.com/models`
- HuggingFace modelo principal: `https://huggingface.co/black-forest-labs/FLUX.1-dev`
