# 01 — Arquitectura y Conceptos Fundamentales

> Antes de tocar un solo nodo, es crucial entender **qué está pasando por dentro**. ComfyUI no es magia: cada paso tiene una razón matemática.

---

## El pipeline básico de generación

Cualquier workflow de ComfyUI, por complejo que sea, sigue este flujo central:

```
Texto (prompt)
    ↓ CLIP Encode
Conditioning (tensor)
    ↓
    ├── Modelo (UNet)
    ↓         ↓
Latent Noise → KSampler (denoising iterativo)
                    ↓
              Latent Image
                    ↓ VAE Decode
              Imagen Final (píxeles)
```

---

## ¿Qué es el Espacio Latente?

El espacio latente es una **representación comprimida** de las imágenes. En lugar de trabajar con millones de píxeles, el modelo trabaja con tensores mucho más pequeños.

### Relación de compresión
- Una imagen de 512×512 píxeles → latente de 64×64×4 (channels)
- Una imagen de 1024×1024 píxeles → latente de 128×128×4
- **Factor de compresión: 8x en cada dimensión espacial**

### ¿Por qué importa esto?
- Si generas a 512×512, el modelo "piensa" en 64×64
- Resoluciones muy diferentes a la nativa del modelo generan artefactos
- El upscaling en espacio latente es más rápido que en espacio de píxeles

---

## Los tres componentes de un Checkpoint

Cuando cargas un `.safetensors`, estás cargando tres cosas a la vez:

### 1. UNet
- El corazón del modelo. Red neuronal que aprende a predecir y eliminar ruido.
- Es quien "entiende" el conditioning y genera la imagen.
- El componente más grande y más pesado en VRAM.

### 2. CLIP (Text Encoder)
- Convierte tu texto en vectores numéricos.
- El modelo no lee palabras: lee vectores de embeddings.
- SD1.5 tiene 1 CLIP; SDXL tiene 2; Flux tiene CLIP + T5.

### 3. VAE
- Codifica/decodifica entre espacio latente y espacio de píxeles.
- Influye directamente en la calidad de colores y detalles finos.
- Se puede reemplazar con un VAE externo para mejores resultados.

---

## El proceso de Denoising paso a paso

La generación funciona así:

1. **Se crea un tensor de ruido puro** (completamente aleatorio, determinado por el seed)
2. **El UNet predice cuánto ruido hay** dado el conditioning (prompt)
3. **Se elimina una fracción del ruido** según el scheduler
4. **Se repite N veces** (N = número de steps)
5. **Al final**, el latente casi sin ruido se decodifica con el VAE

Por eso más steps = más iteraciones de refinamiento (hasta un límite donde ya no mejora).

---

## CFG Scale: la tensión creativa

El CFG (Classifier-Free Guidance) controla **cuánto se fuerza** al modelo a seguir el prompt:

- **CFG = 1**: el modelo ignora casi el prompt, genera libremente
- **CFG = 7**: balance estándar — sigues el prompt sin sobreforjar
- **CFG = 15+**: el modelo sigue el prompt de forma extrema, produciendo colores saturados y artefactos

### Fórmula interna (simplificada)
```
resultado = sin_guia + CFG × (con_guia - sin_guia)
```
Por eso con CFG alto el modelo "amplifica" demasiado las diferencias.

---

## Tipos de datos en ComfyUI

Los cables de colores representan tipos de datos incompatibles entre sí:

| Color | Tipo | Descripción |
|-------|------|-------------|
| Amarillo/Marrón | MODEL | El checkpoint cargado |
| Morado | CONDITIONING | Texto codificado |
| Rojo/Naranja | LATENT | Imagen en espacio latente |
| Azul | IMAGE | Imagen en píxeles (tensor) |
| Verde | VAE | Componente VAE |
| Rosado | CLIP | Componente CLIP |
| Celeste | CONTROL_NET | Modelo ControlNet |
| Verde oscuro | MASK | Máscara en blanco/negro |

No puedes conectar sockets de distinto tipo (excepto con nodos conversores).

---

## Resoluciones nativas recomendadas

| Modelo | Resolución base | Máximo práctico |
|--------|-----------------|-----------------|
| SD 1.5 | 512×512 | 768×768 |
| SD 2.1 | 768×768 | 1024×1024 |
| SDXL | 1024×1024 | 1536×1536 |
| Flux.1 | 1024×1024 | 2048×2048 |
| SD 3 | 1024×1024 | 2048×2048 |

Generar **muy por encima** de la resolución nativa sin hi-res fix produce imágenes con dos cabezas, objetos duplicados y composiciones rotas.

---

*[← README](../README.md) | [Siguiente: Nodos Fundamentales →](02-nodos-fundamentales.md)*
