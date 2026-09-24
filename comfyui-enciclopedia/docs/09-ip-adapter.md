# 09 — IP-Adapter (Image Prompt Adapter)

IP-Adapter permite usar **una imagen como prompt visual**: transfiere el estilo, colores o identidad de una imagen de referencia a la generación.

---

## ¿Qué hace exactamente?

Donde CLIP convierte texto en vectores, IP-Adapter convierte **imágenes** en vectores que el UNet puede usar como conditioning adicional. Te permite decir: "genera algo que se parezca a esto" sin escribirlo.

---

## Casos de Uso

- **Consistencia de personaje**: mantener el mismo rostro o apariencia en diferentes escenas
- **Transferencia de estilo**: aplicar la paleta y estilo de una imagen de referencia
- **Composición de referencia**: combinar elementos de varias imágenes
- **Face ID**: variante especializada en preservar identidad facial

---

## Modelos Necesarios

IP-Adapter requiere dos archivos:

1. **Modelo IP-Adapter** (`models/ipadapter/`)
   - `ip-adapter_sd15.safetensors` — para SD1.5
   - `ip-adapter-plus_sd15.safetensors` — versión plus (mejor calidad)
   - `ip-adapter_sdxl.safetensors` — para SDXL
   - `ip-adapter-faceid_sd15.safetensors` — Face ID SD1.5

2. **Image Encoder CLIP Vision** (`models/clip_vision/`)
   - `clip-vit-h-14-laion2B` — para SD1.5
   - `clip-vit-bigG-14-laion2B` — para SDXL

---

## Instalación del Custom Node

Se necesita el custom node **IPAdapter Plus** (también conocido como ComfyUI_IPAdapter_plus):

1. Instalar vía ComfyUI Manager: buscar "IPAdapter Plus"
2. O manualmente en `custom_nodes/`

---

## Nodos Principales

### IPAdapterModelLoader
Carga el modelo IP-Adapter.

### CLIPVisionLoader
Carga el encoder visual CLIP Vision.

### IPAdapter (nodo de aplicación)
**Entradas**:
- `model` — el MODEL del checkpoint
- `ipadapter` — del IPAdapterModelLoader
- `clip_vision` — del CLIPVisionLoader
- `image` — imagen de referencia
- `weight` — intensidad del efecto
- `start_at` / `end_at` — en qué porcentaje de steps aplica

**Salida**: `MODEL` modificado → al KSampler

---

## Weight Types

El parámetro `weight_type` define cómo se combina el conditioning de IP-Adapter:

| Weight Type | Efecto |
|-------------|--------|
| `original` | Estilo original, transferencia de concepto general |
| `linear` | Interpolación lineal simple |
| `channel penalty` | Penaliza canales dominantes, más sutil |
| `style transfer` | Enfocado en estilo (colores, texturas) sin identidad |
| `composition` | Enfocado en composición y estructura |
| `faceid` | Optimizado para preservar identidad facial |

---

## Valores de Weight

| Valor | Efecto |
|-------|--------|
| 0.2–0.4 | Influencia sutil, inspiración |
| 0.5–0.7 | Balance entre referencia y prompt de texto |
| 0.8–1.0 | Alta fidelidad a la imagen de referencia |
| >1.0 | Domina sobre el texto, puede artefactar |

---

## Combinación con ControlNet

IP-Adapter y ControlNet son complementarios:
- **IP-Adapter**: define el "qué" se parece (estilo, identidad)
- **ControlNet Pose**: define cómo se posiciona

```
[Imagen de estilo] ── IPAdapter ── MODEL modificado
[Imagen de pose]   ── ControlNet OpenPose ── conditioning modificado
Ambos → KSampler
```

---

## IP-Adapter FaceID

Variante especializada en preservar la identidad de un rostro:
- Requiere `ip-adapter-faceid` + `ip-adapter-faceid-portrait` (según el nodo)
- Algunos nodos requieren InsightFace para extraer el face embedding
- Mucho más preciso que el IP-Adapter genérico para rostros

---

*[← ControlNet](08-controlnet.md) | [Siguiente: Upscalers →](10-upscalers.md)*
