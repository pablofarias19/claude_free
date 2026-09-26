# 22 — Optimización y Rendimiento

Cómo exprimir al máximo el hardware disponible y reducir tiempos de generación.

---

## Flags de Inicio de ComfyUI

Se pasan al ejecutar `python main.py`:

### Gestión de VRAM
```bash
--lowvram          # Usa lo mínimo de VRAM, muy lento
--medvram          # Balance para GPUs de 8 GB
--medvram-sdxl     # Específico para SDXL en 8 GB
--novram           # Todo en RAM del sistema (CPU), extremadamente lento
--gpu-only         # Fuerza todo en GPU (para GPUs con mucha VRAM)
```

### Precisión
```bash
--fp16-vae         # VAE en FP16 (más rápido, posible inestabilidad en SDXL)
--bf16-unet        # UNet en BF16
--fp8_e4m3fn-unet  # UNet en FP8 (muy rápido si la GPU lo soporta)
--fp8_e4m3fn-text-enc  # Text encoders en FP8
```

### Red y acceso
```bash
--listen           # Escucha en todas las interfaces (para acceso en red local)
--port 8188        # Puerto (default 8188)
--preview-method auto  # Método de preview en tiempo real
```

---

## Aceleración: xformers vs Flash Attention

### xformers
Biblioteca de Meta que optimiza las operaciones de atención:
```bash
--use-pytorch-cross-attention  # Desactiva xformers (por defecto usa xformers si disponible)
```
ComfyUI usa xformers automáticamente si está instalado.

**Instalación**: `pip install xformers`

### Flash Attention 2
Aternativa a xformers, generalmente más rápida en GPUs modernas (RTX 30xx/40xx):
```bash
--attention-pytorch  # Usa la implementación nativa de PyTorch (puede activar Flash Attn)
```

### Diferencias en velocidad (estimado)
| Método | Velocidad relativa | VRAM |
|--------|------------------|------|
| Sin optimizar | 1× | Alta |
| xformers | 1.5–2× | Media |
| Flash Attention 2 | 1.8–2.5× | Media |

---

## Caché de Modelos

ComfyUI mantiene los modelos cargados en VRAM entre generaciones. Si cambias de modelo frecuentemente:

- **Settings** → `GPU memory` → configurar cuántos modelos mantener en caché
- Valor 0: descarga inmediatamente (ahorra VRAM, lento al recargar)
- Valor alto: mantiene más modelos cargados (más rápido, más VRAM)

---

## Tiling de VAE

Para imágenes muy grandes, el VAE puede causar OOM. Solución: procesar en tiles.

### Nodo: VAE Decode Tiled
Equivalente a VAE Decode pero divide la imagen en tiles para el decode:
- `tile_size`: tamaño de cada tile (default 512)
- Usar cuando el VAE Decode normal da OOM en imágenes grandes

### Nodo: VAE Encode Tiled
Igual para el encode.

---

## Tome Attention (Token Merging)

Técnica que fusiona tokens similares durante la atención para reducir cómputo:
- Disponible como custom node
- Ahorra 20–40% de tiempo con pérdida mínima de calidad
- Especialmente útil para SDXL y modelos grandes

---

## Perfiles de Hardware Recomendados

### GPU de 4–6 GB (GTX 1060, RTX 2060, etc.)
```bash
--medvram --fp16-vae
```
- SD1.5 a 512px: funciona bien
- SDXL: posible con modelos pequeños y paciencia
- Flux: prácticamente inviable

### GPU de 8 GB (RTX 3070, 3060 Ti, etc.)
```bash
--medvram-sdxl
```
- SD1.5: excelente
- SDXL a 1024px: funciona, lento
- Flux FP8: muy lento pero posible

### GPU de 12 GB (RTX 3080 12GB, 4070, etc.)
- Sin flags especiales para SD1.5 y SDXL
- Flux FP8: cómodo
- Flux GGUF Q4: cómodo

### GPU de 24 GB (RTX 3090, 4090, etc.)
- Sin limitaciones prácticas
- Flux BF16 completo: funciona

---

## Generación Rápida sin Sacrificar Calidad

### SDXL Turbo / Lightning
Variantes de SDXL destiladas para generar en 1–4 steps:

| Modelo | Steps | CFG | Sampler |
|--------|-------|-----|--------|
| SDXL-Turbo | 1–4 | 0–1 | euler_a |
| SDXL-Lightning (4-step) | 4 | 1 | euler |
| SDXL-Lightning (8-step) | 8 | 1 | euler |
| Hyper-SD (8-step) | 8 | 1–2 | dpmpp_2m |

### LCM LoRA
Aplicar un LoRA LCM sobre cualquier modelo:
- Sampler: `lcm`
- Scheduler: `sgm_uniform`
- Steps: 4–8
- CFG: 1.5–2.0

---

## Monitorización de Recursos

```bash
# Ver uso de GPU en tiempo real
nvidia-smi -l 1

# Ver detalles de memoria
nvidia-smi --query-gpu=memory.used,memory.free,utilization.gpu --format=csv
```

En la consola de ComfyUI también se imprime información de memoria al cargar modelos.

---

*[← Nodos Útiles Avanzados](21-nodos-utiles-avanzados.md) | [Siguiente: Workflows de Referencia →](23-workflows-referencia.md)*
