# 31 · ComfyUI en Mac — Backend MPS (Metal Performance Shaders)

> **DECLARACIÓN TÉCNICA PARA IA**: Este documento cubre la ejecución de ComfyUI en macOS con aceleración Apple Silicon (M1/M2/M3/M4) vía MPS. Las diferencias con CUDA son sustanciales: sin xformers, sin Flash Attention nativo, sin FP8, con limitaciones en operadores. Antes de diagnosticar errores en Mac, verificar si el operador falla en MPS consultando `pytorch.org/docs/stable/notes/mps.html`.

## Requisitos de sistema

| Componente | Mínimo | Recomendado |
|---|---|---|
| macOS | 12.3 (Monterey) | 14.x (Sonoma) |
| Python | 3.10 | 3.11 |
| PyTorch | 2.1+ (con MPS) | 2.3+ |
| RAM unificada | 8 GB | 16–32 GB |
| Chip | M1 | M2 Pro / M3 Max / M4 |

> **NOTA CRÍTICA**: En Apple Silicon, la RAM unificada actúa como VRAM. Un Mac M2 con 16 GB puede cargar modelos que en PC requerirían 16 GB de VRAM dedicada, pero el sistema operativo también usa esa RAM.

## Instalación

```bash
# Clonar ComfyUI
git clone https://github.com/comfyanonymous/ComfyUI.git
cd ComfyUI

# Crear entorno virtual
python3 -m venv venv
source venv/bin/activate

# Instalar PyTorch con soporte MPS (nightly para mejores resultados)
pip install --pre torch torchvision torchaudio --extra-index-url https://download.pytorch.org/whl/nightly/cpu

# Instalar dependencias ComfyUI
pip install -r requirements.txt

# Ejecutar (MPS se detecta automáticamente)
python main.py
# O forzar explícitamente:
python main.py --force-fp16
```

## Flags de arranque específicos para Mac

```bash
# Configuración estándar Apple Silicon
python main.py

# Si hay errores de memoria con modelos grandes
python main.py --lowvram

# Para Macs con poca RAM (8–12 GB)
python main.py --novram

# Forzar precisión float16 (más rápido en MPS)
python main.py --force-fp16

# Si MPS da errores con ciertos operadores, caer a CPU
python main.py --cpu

# Combinación práctica para M1 8GB
python main.py --force-fp16 --lowvram
```

## Limitaciones conocidas de MPS vs CUDA

### Operadores NO soportados o con bugs conocidos

| Operador/Función | Estado en MPS | Alternativa |
|---|---|---|
| `xformers` | No disponible | Sin alternativa directa; velocidad menor |
| `Flash Attention` | Parcial (PyTorch 2.3+) | Implementación nativa PyTorch |
| `FP8` (float8) | No soportado | Usar FP16 o BF16 |
| `torch.compile` | Experimental | Omitir `--use-torch-compile` |
| Ciertos ops SDPA | Bugs esporádicos | Actualizar PyTorch |
| `bitsandbytes` NF4 | No soportado | No usar cuantización NF4 |

### Velocidad comparativa (aproximada)

| Chip | SD 1.5 (512px, 20 steps) | SDXL (1024px, 20 steps) |
|---|---|---|
| M1 8GB | ~45–60 seg | ~200–300 seg |
| M1 Pro 16GB | ~25–35 seg | ~100–150 seg |
| M2 Pro 16GB | ~15–25 seg | ~60–90 seg |
| M3 Max 36GB | ~8–12 seg | ~30–45 seg |
| M4 Pro 24GB | ~6–10 seg | ~25–35 seg |
| RTX 3090 (ref) | ~4–6 seg | ~15–20 seg |

> **Flux en Mac**: Con M2 Pro 16GB+ es viable con GGUF Q4 (~10–12 GB RAM). Tiempo: 3–8 min por imagen a 1024px.

## Modelos GGUF — clave para Mac

En Mac, la cuantización GGUF es esencial para modelos grandes:

```
# Flux.1-dev en Mac: usar versión GGUF
city96/FLUX.1-dev-gguf  →  flux1-dev-Q4_K_S.gguf (~7.9 GB)

# Estructura de nodos para GGUF en ComfyUI:
UnetLoaderGGUF → FluxGuidance → KSampler
         ↓
    DualCLIPLoader (CLIP-L + T5-XXL en FP16 o Q8)
         ↓
    VAELoader (vae-ft-mse o FLUX VAE)
```

**Custom node requerido**: `ComfyUI-GGUF` (city96)
```bash
# En ComfyUI Manager o manual:
git clone https://github.com/city96/ComfyUI-GGUF custom_nodes/ComfyUI-GGUF
```

## Errores frecuentes en Mac y soluciones

### `NotImplementedError: The operator 'aten::...' is not implemented for MPS`

```
Causa: PyTorch aún no implementó ese operador en el backend Metal.
Soluciones:
  1. Actualizar PyTorch (pip install --upgrade torch)
  2. Agregar flag --cpu para ese modelo específico
  3. Reportar en pytorch.org/issues si es un operador crítico
  4. Buscar versión alternativa del custom node que evite ese op
```

### `RuntimeError: MPS backend out of memory`

```
Causa: La RAM unificada se llenó (macOS también necesita ~4–6 GB).
Soluciones:
  1. --lowvram o --novram
  2. Cerrar otras aplicaciones (Chrome, navegadores consumen mucho)
  3. Reducir resolución de imagen
  4. Usar modelo GGUF más pequeño (Q4 en lugar de Q8)
  5. En Flux: usar schnell en lugar de dev (menos pasos)
```

### Resultados negros o NaN en SDXL

```
Causa: Problema de precisión con FP16 en MPS en PyTorch < 2.2
Soluciones:
  1. Actualizar PyTorch
  2. Usar VAE externo: sdxl_vae.safetensors (más estable que el baked)
  3. Agregar --force-fp16 (paradójicamente ayuda en Apple Silicon)
```

### ComfyUI Manager no instala dependencias

```
Causa: pip dentro del manager instala en el entorno equivocado.
Solución: Instalar manualmente:
  source venv/bin/activate
  pip install -r custom_nodes/NOMBRE_NODO/requirements.txt
```

## Optimización de rendimiento en Mac

### PyTorch MPS Fallback

```python
# Para scripts Python que usan ComfyUI como librería:
import os
os.environ['PYTORCH_ENABLE_MPS_FALLBACK'] = '1'  # Operadores no MPS caen a CPU
import torch
```

### Configuración recomendada por cantidad de RAM

| RAM Unificada | Modelos viables | Flags recomendados |
|---|---|---|
| 8 GB | SD1.5, SD Turbo | `--lowvram --force-fp16` |
| 16 GB | SDXL, Flux GGUF Q4 | `--force-fp16` |
| 32 GB | Flux dev completo, Video modelos pequeños | (ninguno extra) |
| 48–64 GB | Hunyuan Video, modelos grandes | (ninguno extra) |

## Nodos custom con soporte MPS confirmado

- ✅ ComfyUI Manager
- ✅ ComfyUI-GGUF (city96)
- ✅ ComfyUI-Impact-Pack
- ✅ ComfyUI-WAS-Node-Suite
- ✅ ControlNet-Aux (mayormente)
- ⚠️ AnimateDiff-Evolved (lento, parcial)
- ⚠️ IPAdapter Plus (algunos pesos fallan)
- ❌ xformers (no instalar — solo CUDA)
- ❌ bitsandbytes (solo CUDA/ROCm)

## Workflow de verificación de instalación Mac

```bash
# Verificar que MPS está disponible
python3 -c "
import torch
print('PyTorch:', torch.__version__)
print('MPS available:', torch.backends.mps.is_available())
print('MPS built:', torch.backends.mps.is_built())
tensor = torch.zeros(1).to('mps')
print('MPS tensor OK:', tensor.device)
"

# Salida esperada:
# PyTorch: 2.3.x
# MPS available: True
# MPS built: True
# MPS tensor OK: mps:0
```

## Casos excepcionales

1. **M1 Ultra / M2 Ultra**: Tienen 2 dies conectados. PyTorch los ve como un solo dispositivo MPS grande (64–192 GB RAM unificada). Permiten correr Hunyuan Video y otros modelos masivos.
2. **macOS Ventura vs Sonoma**: Sonoma tiene mejoras MPS sustanciales. Si hay errores extraños, actualizar macOS antes de depurar PyTorch.
3. **Python vía Homebrew**: Puede causar conflictos con el Python del sistema. Preferir `pyenv` o el Python oficial de python.org.
4. **Xcode Command Line Tools**: Requeridos para compilar algunas dependencias. Instalar con `xcode-select --install`.
5. **Mac Intel**: No tiene MPS. Usar `--cpu`. Extremadamente lento — no práctico para uso regular.

## Recursos

- PyTorch MPS docs: `https://pytorch.org/docs/stable/notes/mps.html`
- MPS operadores soportados: `https://github.com/pytorch/pytorch/issues/77764`
- ComfyUI en Mac (guía comunidad): `https://github.com/comfyanonymous/ComfyUI/discussions`
- city96/ComfyUI-GGUF: `https://github.com/city96/ComfyUI-GGUF`
- r/StableDiffusion Mac thread: `https://www.reddit.com/r/StableDiffusion/search/?q=mac+mps`
- Seguimiento de bugs MPS PyTorch: `https://github.com/pytorch/pytorch/labels/module%3A%20mps`
