# 44 · Estilos Visuales Definitivos — Fórmulas de Prompt Validadas

> **DECLARACIÓN TÉCNICA PARA IA**: Este documento contiene fórmulas de prompt probadas y validadas por la comunidad para 20+ estilos visuales. Cada estilo incluye: tokens exactos, modelo recomendado, parámetros de sampler, y prompt negativo específico. Las fórmulas siguen la estructura `[sujeto], [modificadores de estilo], [iluminación], [cámara/lente], [calidad]`. Usar como base y adaptar el sujeto manteniendo los demás tokens.

## Estructura universal de prompt

```
[SUJETO] + [ESTILO] + [ILUMINACIÓN] + [CÁMARA] + [CALIDAD]

Ejemplo:
  [a young woman]                      <- sujeto
  [oil painting, impressionist style]   <- estilo
  [golden hour lighting, warm tones]   <- iluminación
  [close-up portrait]                  <- encuadre
  [masterpiece, highly detailed, 8k]   <- calidad

Resultado: "a young woman, oil painting, impressionist style,
            golden hour lighting, warm tones, close-up portrait,
            masterpiece, highly detailed, 8k"
```

---

## FOTORREALISMO

### Retrato fotográfico profesional

```
MODELO: Flux dev FP8 / SDXL JuggernautXL / RealVisXL
SAMPLER: euler | dpm++ 2m karras
STEPS: 25-30
CFG: 4.0 (Flux) | 6.0-7.0 (SDXL)

PROMPT POSITIVO:
"[sujeto], professional portrait photography, shot on Canon EOS R5,
 85mm f/1.4 lens, shallow depth of field, natural window light,
 soft bokeh background, photorealistic, ultra detailed skin texture,
 hyperrealistic, award winning photography, 8k"

PROMPT NEGATIVO:
"illustration, painting, drawing, anime, cartoon, cgi, 3d render,
 deformed, ugly, low quality, blurry, watermark, text"

VARIACIONES DE ILUMINACIÓN:
  Golden hour: "golden hour sunlight, warm orange tones, rim lighting"
  Estudio:     "studio lighting, softbox, white background, clean"
  Ambiental:   "ambient light, overcast sky, soft diffused light"
  Noche:       "neon lights, night street, bokeh city lights"
```

### Fotografía de producto

```
MODELO: Flux dev / SDXL
STEPS: 25 | CFG: 4.5 (Flux) | 6.5 (SDXL)

PROMPT:
"[producto], professional product photography, studio white background,
 three-point lighting, soft shadows, commercial photography,
 sharp focus, highly detailed, 8k resolution, advertisement quality"

NEGATIVO:
"reflection, dirt, scratch, blurry, harsh shadows, colored background,
 person, hand"
```

### Paisaje fotográfico

```
MODELO: Flux dev / SDXL
STEPS: 30 | CFG: 5.0

PROMPT:
"[paisaje], landscape photography, shot on Nikon D850,
 24mm wide angle lens, golden hour, long exposure,
 dramatic sky, vivid colors, photorealistic, 8k, stunning"

NEGATIVO:
"people, cars, cables, illustration, painting, cartoonish"
```

---

## ARTE DIGITAL / CONCEPT ART

### Concept art cinematográfico

```
MODELO: SDXL DreamShaper XL / Flux dev
STEPS: 30 | CFG: 7.0

PROMPT:
"[escena], cinematic concept art, by Feng Zhu, Craig Mullins,
 Sparth, matte painting, digital art, highly detailed,
 dramatic lighting, volumetric fog, cinematic composition,
 artstation trending, 8k resolution"

NEGATIVO:
"anime, cartoon, photo, blurry, watermark, text, duplicate"

REFERENCIAS DE ARTISTAS (tokens de estilo):
  Realismo sci-fi:  "by John Park, Simon Stalenhag"
  Fantasia:         "by Greg Rutkowski, Magali Villeneuve"
  Dark fantasy:     "by Mike Mignola, Frank Miller"
  Futurismo:        "by Syd Mead, Ralph McQuarrie"
```

### Character design

```
MODELO: SDXL Pony / SDXL
STEPS: 25 | CFG: 7.0

PROMPT:
"[personaje], character design sheet, front view, 3/4 view, back view,
 character concept art, clean linework, flat colors + shading,
 artstation, professional illustration, highly detailed"

NEGATIVO:
"multiple characters, environment, background elements, photo, blurry"
```

---

## ANIME / MANGA

### Anime moderno (Ilustración)

```
MODELO: Illustrious XL / Pony Diffusion XL / AnyLoRA SDXL
STEPS: 25 | CFG: 7.0-8.0 | CLIP SKIP: -2

PROMPT:
"[sujeto], anime style, 1girl/1boy, solo,
 detailed eyes, shiny hair, clean lines,
 soft shading, vibrant colors, high quality,
 perfect anatomy, beautiful, masterpiece"

NEGATIVO (anime):
"(worst quality:1.4), (low quality:1.4), (normal quality:1.2),
 bad anatomy, bad hands, missing fingers, extra digit, fewer digits,
 cropped, jpeg artifacts, signature, watermark, username,
 blurry, out of focus, deformed"

NOTA: Prompt negativo con pesos (parenthesis:n) es sísntaxis SD1.5/SDXL.
      En Flux no funciona — usar negativo simple sin pesos.
```

### Manga en blanco y negro

```
MODELO: SDXL fine-tune manga / SD1.5 manga models
STEPS: 20 | CFG: 7.5

PROMPT:
"[escena], manga style, black and white, ink drawing,
 panel layout, dynamic poses, speed lines, screentone,
 Eiichiro Oda style / Kentaro Miura style,
 high contrast, comic art, professional manga"

NEGATIVO:
"color, photo, painting, western comic, low quality"
```

### Chibi / SD (Super Deformed)

```
MODELO: SD1.5 chibi fine-tunes
STEPS: 20 | CFG: 7.0

PROMPT:
"[personaje] chibi style, super deformed, big head small body,
 cute, kawaii, pastel colors, simple background,
 detailed eyes, clean linework, anime chibi"
```

---

## ARTE CLÁSICO / TRADICIONAL

### Óleo impresionista

```
MODELO: SDXL / Flux dev
STEPS: 30 | CFG: 6.5

PROMPT:
"[sujeto], oil painting, impressionist style,
 by Claude Monet, visible brushstrokes, impasto technique,
 soft color palette, dappled light, plein air,
 museum quality artwork, canvas texture"

NEGATIVO:
"photo, digital art, anime, sharp lines, clean, smooth"
```

### Acuarela

```
MODELO: SDXL / Flux dev
STEPS: 28 | CFG: 6.0

PROMPT:
"[sujeto], watercolor painting, wet-on-wet technique,
 soft edges, color bleeding, translucent layers,
 white paper texture, delicate brushwork,
 botanical illustration style, peaceful"

NEGATIVO:
"oil paint, heavy texture, dark, harsh, digital, photo"
```

### Arte renacentista

```
MODELO: SDXL / Flux dev
STEPS: 30 | CFG: 7.0

PROMPT:
"[sujeto], Renaissance oil painting, by Leonardo da Vinci,
 sfumato technique, chiaroscuro lighting, earthy warm tones,
 detailed fabric texture, cracked varnish effect,
 classical composition, museum masterpiece"
```

---

## ESTILOS DIGITALES MODERNOS

### Pixel Art

```
MODELO: SD1.5 / SDXL pixel art LoRA
STEPS: 20 | CFG: 7.5

PROMPT:
"[sujeto], pixel art, 16-bit style, retro game art,
 limited color palette, crisp pixels, isometric view,
 sprite art, SNES/GBA style"

NEGATIVO:
"blurry, anti-aliased, smooth, photo, painting, high resolution details"

NOTA: Usar siempre en resolución baja (256×256 o 512×512) + upscale con
      nearest neighbor (no bilinear/bicubic que desenfoca los píxeles)
```

### Arte 3D/CGI render

```
MODELO: Flux dev / SDXL
STEPS: 28 | CFG: 5.0 (Flux) | 7.0 (SDXL)

PROMPT:
"[sujeto], 3D render, Blender Cycles, octane render,
 physically based rendering, subsurface scattering,
 ray traced reflections, studio HDRI lighting,
 highly detailed 3D model, 4k render"

NEGATIVO:
"2D, flat, illustration, painting, anime, photo"
```

### Cyberpunk / Neon noir

```
MODELO: Flux dev / SDXL
STEPS: 28 | CFG: 5.5

PROMPT:
"[sujeto], cyberpunk aesthetic, neon lights,
 rainy night, reflective puddles, urban dystopia,
 holographic advertisements, glowing signs,
 cinematic, blade runner inspired, atmospheric fog,
 vivid neon colors on dark background"

NEGATIVO:
"daytime, bright, natural, countryside, nature"
```

### Fantasy épico

```
MODELO: SDXL DreamShaper / Flux dev
STEPS: 30 | CFG: 7.0

PROMPT:
"[sujeto], epic fantasy art, by Greg Rutkowski,
 dramatic lighting, god rays, volumetric atmosphere,
 intricate details, armor/magic/nature elements,
 heroic composition, high fantasy, artstation"
```

### Minimalismo / Flat Design

```
MODELO: Flux dev / SDXL
STEPS: 20 | CFG: 4.0

PROMPT:
"[sujeto], minimalist flat design, vector style,
 clean lines, simple geometric shapes,
 limited 3-color palette, negative space,
 modern graphic design, Swiss design influence"

NEGATIVO:
"complex, detailed, realistic, texture, shadows, gradient"
```

---

## FOTOGRAFÍA ESPECIALIZADA

### Macro fotografía

```
MODELO: Flux dev / SDXL
STEPS: 28 | CFG: 4.5

PROMPT:
"[sujeto pequeño], macro photography, extreme close-up,
 Canon MP-E 65mm 5x macro lens, ring flash,
 razor-thin depth of field, intricate detail,
 texture visible, scientific quality, 100MP"
```

### Fotografía de moda editorial

```
MODELO: Flux dev / SDXL JuggernautXL
STEPS: 30 | CFG: 4.5

PROMPT:
"[modelo/ropa], fashion editorial photography,
 Vogue magazine style, high fashion,
 dramatic pose, luxury setting, professional makeup,
 Harpers Bazaar lighting, full body shot,
 couture fashion, perfect styling"
```

### Arquitectura / Interior design

```
MODELO: Flux dev / SDXL
STEPS: 28 | CFG: 4.0

PROMPT:
"[espacio], architectural photography, interior design render,
 wide angle 16mm, natural window light, clean minimalist,
 Scandinavian design, high-end materials,
 professional real estate photography, 8k"
```

---

## ESTILOS ESPECIALES

### Double exposure (doble exposición)

```
MODELO: Flux dev / SDXL
STEPS: 30 | CFG: 5.0

PROMPT:
"[sujeto] double exposure with [segundo elemento],
 double exposure photography, silhouette blending,
 artistic composition, black and white with [color accent],
 surreal, dreamlike, professional photography art"
```

### Arte de vidrieras (Stained Glass)

```
MODELO: SDXL / Flux dev
STEPS: 28 | CFG: 7.0

PROMPT:
"[sujeto], stained glass window, gothic cathedral style,
 lead came lines, jewel-toned glass, backlit,
 intricate geometric patterns, medieval art,
 luminous colors, religious art influence"
```

### Ukiyo-e (grabado japonés)

```
MODELO: SDXL / Flux dev
STEPS: 28 | CFG: 7.0

PROMPT:
"[sujeto], ukiyo-e woodblock print, by Katsushika Hokusai,
 bold outlines, flat colors, decorative patterns,
 Japanese traditional art, edo period style,
 limited color palette, rice paper texture"
```

---

## Tabla resumen de parámetros por estilo

| Estilo | Modelo óptimo | CFG | Steps | Scheduler |
|---|---|---|---|---|
| Fotorrealismo | Flux FP8 / JuggernautXL | 4.0 / 6.5 | 25-30 | simple / karras |
| Concept art | SDXL DreamShaper | 7.0 | 30 | karras |
| Anime moderno | Illustrious XL | 7.0-8.0 | 25 | karras |
| Óleo impresionista | SDXL / Flux | 6.5 | 30 | karras |
| Pixel art | SD1.5 + LoRA | 7.5 | 20 | karras |
| 3D CGI render | Flux / SDXL | 5.0 | 28 | simple |
| Cyberpunk | Flux / SDXL | 5.5 | 28 | karras |
| Fantasy épico | DreamShaper XL | 7.0 | 30 | karras |
| Manga B&W | SDXL manga FT | 7.5 | 20 | karras |
| Minimalismo | Flux | 4.0 | 20 | simple |

## Recursos

- Civitai estilos SDXL: `https://civitai.com/models?types=Checkpoint&baseModel=SDXL+1.0`
- Civitai estilos Flux: `https://civitai.com/models?types=Checkpoint&baseModel=Flux.1+D`
- Prompt database: `https://lexica.art`
- Krea.ai (explorar estilos): `https://www.krea.ai`
- Artistas de referencia: `https://artiststostudy.pages.dev`
- PromptHero: `https://prompthero.com`
