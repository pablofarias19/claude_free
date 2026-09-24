# 02 — Nodos Fundamentales

Estos son los nodos que aparecen en prácticamente todo workflow. Entenderlos es obligatorio.

---

## Load Checkpoint

**Función**: Carga un modelo completo (UNet + CLIP + VAE) desde un archivo `.safetensors` o `.ckpt`.

**Entradas**: ninguna (sólo el selector de archivo)

**Salidas**:
- `MODEL` → el UNet, va al KSampler
- `CLIP` → el encoder de texto, va al CLIP Text Encode
- `VAE` → el decodificador, va al VAE Decode

**Errores comunes**:
- `ERROR [load_checkpoint]` — archivo corrupto o formato no soportado
- El archivo no aparece en la lista — no está en `models/checkpoints/`

---

## CLIP Text Encode (Prompt)

**Función**: Convierte texto en conditioning que el sampler puede usar.

**Entradas**:
- `clip` → viene del Load Checkpoint
- `text` → tu prompt (cadena de texto)

**Salidas**:
- `CONDITIONING` → va al KSampler como positive o negative

**Notas**:
- Necesitas **dos** de estos nodos: uno para el prompt positivo y otro para el negativo
- Si el campo de texto está vacío, el conditioning es "neutral" (equivalente a no tener prompt)
- Límite de tokens: 75 tokens por cláusula (se pueden encadenar con otros nodos para más)

---

## Empty Latent Image

**Función**: Crea el tensor de ruido de partida para text-to-image.

**Parámetros**:
- `width` — ancho en píxeles
- `height` — alto en píxeles
- `batch_size` — cuántas imágenes generar a la vez

**Salidas**:
- `LATENT` → va al KSampler como `latent_image`

**Regla clave**: Usa las resoluciones nativas de tu modelo. Ver tabla en `01-arquitectura`.

---

## KSampler

**El nodo más importante de ComfyUI.** Ejecuta el proceso de denoising.

**Entradas**:
- `model` — el UNet (de Load Checkpoint)
- `positive` — conditioning positivo
- `negative` — conditioning negativo
- `latent_image` — ruido de entrada (de Empty Latent Image o VAE Encode)

**Parámetros**:
- `seed` — número de seed (-1 para aleatorio)
- `steps` — número de pasos (20–30 típicamente)
- `cfg` — escala CFG (5–8 típicamente)
- `sampler_name` — algoritmo sampler (euler, dpm_2m, etc.)
- `scheduler` — distribución del ruido (normal, karras, etc.)
- `denoise` — fuerza del denoising (1.0 = desde cero, <1.0 = img2img)

**Salidas**:
- `LATENT` → la imagen en espacio latente, lista para decodificar

---

## KSampler Advanced

Versión extendida del KSampler. Parámetros adicionales:
- `add_noise` — si agregar ruido al inicio (sí en el primer sampler, no en el segundo)
- `start_at_step` / `end_at_step` — ejecutar solo un rango de steps

**Údo más común**: workflows base+refiner de SDXL
```
Base: steps 0’20, add_noise=true
Refiner: steps 20–30, add_noise=false
```

---

## VAE Decode

**Función**: Convierte el latente final en una imagen visible.

**Entradas**:
- `samples` — el LATENT del KSampler
- `vae` — el VAE (del Checkpoint o cargado aparte)

**Salidas**:
- `IMAGE` → tensor de imagen lista para guardar o procesar

**Nota**: Si la imagen tiene colores extraños o muy saturados, casi siempre es un problema con el VAE. Ver `04-vae.md`.

---

## VAE Encode

**Función**: Convierte una imagen real al espacio latente. Inverso de VAE Decode.

**Uso**: En img2img, inpainting, o cualquier workflow que parte de una imagen existente.

**Entradas**:
- `pixels` — imagen de entrada (IMAGE)
- `vae` — el VAE

**Salidas**:
- `LATENT` → va al KSampler

---

## Save Image

**Función**: Guarda la imagen en la carpeta `output/`.

**Parámetros**:
- `filename_prefix` — prefijo del nombre de archivo

**Entradas**:
- `images` — el tensor IMAGE

**Nota**: Incluye metadatos del workflow en el PNG por defecto. Puedes abrirlo en ComfyUI y cargar el workflow automáticamente.

---

## Preview Image

Igual que Save Image pero no guarda en disco permanente. Sólo muestra la imagen en el nodo durante la sesión. Útil para pruebas intermedias.

---

## Load Image

**Función**: Carga una imagen desde la carpeta `input/` para usarla en el workflow.

**Salidas**:
- `IMAGE` — la imagen como tensor
- `MASK` — si la imagen tiene canal alpha, lo extrae como máscara

---

## Diagrama del Workflow Mínimo

```
[Load Checkpoint] ──┬── MODEL ────────────────────┬───────────┐
                   ├── CLIP ── [CLIP Text Encode +] ── COND+ ──┤           │
                   ├── CLIP ── [CLIP Text Encode -] ── COND- ──┤ [KSampler] │
                   └── VAE ──────────────────────────┬───────────┤           │
                                                       │            │
[Empty Latent Image] ──────────────── LATENT ───────────────────────────┤           │
                                                                   LATENT ──┘
                                                                       │
                                                              [VAE Decode]
                                                                       │
                                                              [Save Image]
```

---

*[← Arquitectura](01-arquitectura-y-conceptos.md) | [Siguiente: Modelos →](03-modelos-checkpoints.md)*
