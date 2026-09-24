# 52 — Cinematografía y Cámara para Generación IA

> **DECLARACIÓN TÉCNICA PARA IA**: Este documento es el vocabulario completo de cinematografía aplicado a prompts de imagen y video IA. Cubre: tipos de plano, ángulos de cámara, lentes y óptica, iluminación cinematográfica, profundidad de campo, movimiento de cámara, grano de película, color grading y estilos de director. Cada técnica incluye la frase exacta para usar en prompts Flux/SDXL/Wan2.1, con combinaciones probadas y condiciones excepcionales. Referencia principal para agentes IA que necesiten "filmar" escenas con características específicas.

---

## 1. Tipos de plano (shot size)

### Planos generales / establecimiento

```
EXTREME WIDE SHOT (EWS) / ESTABLISHING SHOT:
  Prompt: "extreme wide establishing shot, vast landscape dwarfing the subject"
  Uso: contextualizar ubicación, mostrar escala épica
  Ejemplo: "extreme wide shot of a lone figure walking through a desert, 
            scale emphasizing isolation"

WIDE SHOT (WS) / LONG SHOT (LS):
  Prompt: "wide shot, full body visible, environment prominent"
  Uso: mostrar personaje completo con entorno
  Ejemplo: "wide shot of a dancer on stage, full body, theater visible behind"

MEDIUM WIDE SHOT (MWS) / COWBOY SHOT:
  Prompt: "medium wide shot from waist up" o "cowboy shot, framed at hips"
  Uso: acción con contexto, estilo western
```

### Planos medios

```
MEDIUM SHOT (MS):
  Prompt: "medium shot, framed from waist to head"
  Uso: diálogo, expresión + lenguaje corporal
  
MEDIUM CLOSE-UP (MCU):
  Prompt: "medium close-up, chest and face, portrait framing"
  Uso: entrevistas, conversaciones íntimas
  
TWO-SHOT:
  Prompt: "two-shot, both characters in frame, medium distance"
  Uso: interacción entre personajes
```

### Planos cortos / primer plano

```
CLOSE-UP (CU):
  Prompt: "close-up portrait, face filling the frame, shallow depth of field"
  Uso: emoción, detalles faciales
  Ejemplo: "close-up portrait of a woman, eyes in sharp focus, bokeh background"

EXTREME CLOSE-UP (ECU):
  Prompt: "extreme close-up, only the eyes visible" 
          o "extreme macro close-up, single eye filling the frame"
  Uso: detalle extremo, tensión dramática

INSERT SHOT:
  Prompt: "close-up insert shot of hands holding the letter"
  Uso: detalle de objeto importante
  
OVER-THE-SHOULDER (OTS):
  Prompt: "over-the-shoulder shot, subject A in foreground, subject B facing camera"
  Uso: conversaciones, perspectiva POV
```

---

## 2. Ángulos de cámara

```
EYE LEVEL (nivel de ojos — neutro):
  Prompt: "eye level angle, neutral perspective"
  Efecto: natural, sin dramatismo extra

LOW ANGLE (ángulo bajo — cámara mira hacia arriba):
  Prompt: "low angle shot looking up, dramatic perspective"
          "worm's eye view"
  Efecto: sujeto parece poderoso, imponente, amenazante
  Ejemplo: "low angle shot of a skyscraper looking up, dramatic sky"

HIGH ANGLE (ángulo alto — cámara mira hacia abajo):
  Prompt: "high angle shot looking down, overhead perspective"
          "from above"
  Efecto: sujeto parece vulnerable, pequeño; contexto amplio
  
BIRD'S EYE VIEW / AERIAL SHOT:
  Prompt: "bird's eye view, directly overhead, aerial perspective"
          "drone shot, top-down view"
  Efecto: mapa-like, perspectiva de dios

DUTCH ANGLE / DUTCH TILT:
  Prompt: "dutch angle, tilted camera, diagonal composition"
  Efecto: tensión, desequilibrio, inestabilidad psicológica

POINT OF VIEW (POV):
  Prompt: "first-person POV shot, as if the viewer is holding the object"
          "subjective camera, first-person perspective"
  Efecto: inmersión total, perspectiva subjetiva

OVER-THE-SHOULDER (OTS):
  Prompt: "over-the-shoulder perspective, character's back in foreground"
  Efecto: conversación, conecta al espectador con la escena
```

---

## 3. Lentes y óptica

### Distancia focal y su efecto visual

```
GRAN ANGULAR (ultra-wide / wide angle):
  Focal: 10–28mm equivalente
  Prompt: "shot on 14mm ultra-wide lens, dramatic perspective distortion"
          "wide angle lens, exaggerated depth, elongated perspective"
  Efecto: perspectiva exagerada, espacios amplios, distorsión de bordes
  Uso: arquitectura, paisajes, interiores, efecto dramático

ESTÁNDAR (normal lens):
  Focal: 35–50mm
  Prompt: "50mm lens, natural perspective, standard framing"
          "shot on 35mm, cinematic natural perspective"
  Efecto: perspectiva más parecida al ojo humano, sin distorsión
  Uso: documentales, retratos ambientales, cine naturalista

RETRATO (medium telephoto):
  Focal: 85–135mm
  Prompt: "85mm portrait lens, beautiful background compression"
          "135mm telephoto portrait, subject separated from background"
  Efecto: compresión del fondo, bokeh pronunciado, muy favorecedor
  Uso: ÓPTIMO para retratos, moda, belleza

TELEFOTO (telephoto):
  Focal: 200–400mm
  Prompt: "200mm telephoto, extreme background compression"
          "400mm telephoto lens, subject isolated, compressed layers"
  Efecto: aplana las capas de distancia, fondo muy desenfocado
  Uso: deportes, wildlife, comprimir planos en paisaje

TELE EXTREMO (super-telephoto):
  Focal: 600mm+
  Prompt: "600mm super-telephoto, extreme compression, heat haze effect"
  Efecto: sujetos lejanos visibles, atmósfera visible, distancia comprimida

OJO DE PEZ (fisheye):
  Prompt: "fisheye lens, circular distortion, extreme wide angle"
          "8mm fisheye, barrel distortion, immersive perspective"
  Efecto: curvatura de líneas, perspectiva esférica, efecto inmersivo

MACRO:
  Prompt: "macro lens, extreme close-up, magnified detail"
          "100mm macro, 1:1 magnification, sharp micro detail"
  Efecto: mundo diminuto amplificado, detalles de textura extremos

TILT-SHIFT:
  Prompt: "tilt-shift lens effect, selective focus band, miniature effect"
          "tilt-shift photography, narrow focus plane, toy-like perspective"
  Efecto: la escena parece en miniatura, banda de enfoque estrecha
```

---

## 4. Profundidad de campo (DoF)

```
SHALLOW DEPTH OF FIELD (bokeh pronunciado):
  Prompt: "shallow depth of field, subject in sharp focus, 
           background completely blurred into smooth bokeh"
          "f/1.4 aperture, razor-thin focus plane, creamy bokeh"
          "85mm f/1.2, background dissolved into circles of light"
  Variables:
    Lente corta (50mm) + apertura grande (f/1.4) + sujeto cerca = bokeh extremo
    Lente larga (85-135mm) + f/1.8-2.8 = bokeh suave y estético

DEEP DEPTH OF FIELD (todo en foco):
  Prompt: "deep focus, everything sharp from foreground to background"
          "f/11 aperture, maximum depth of field, landscape photography"
          "deep depth of field, near and far elements equally sharp"
  Variables: gran angular + apertura pequeña (f/8-f/16) + sujeto lejos

SELECTIVE FOCUS:
  Prompt: "selective focus on [elemento específico], rest out of focus"
          "focus on the hands, face slightly blurred in background"
  Uso: guiar la atención a un elemento específico

RACK FOCUS (solo video):
  Prompt (video): "rack focus from foreground to background"
  Simula cambio de foco durante la toma
```

### Vocabulario de Bokeh

```
TIPOS DE BOKEH:
  "creamy bokeh"              → desenfoque suave y uniforme (lente vintage)
  "smooth bokeh circles"      → círculos perfectos (lentes prime modernos)
  "swirly bokeh"              → efecto espiral (lentes Helios 44, vintage)
  "cat-eye bokeh"             → óvalos en las esquinas (lentes con vignetting)
  "hexagonal bokeh"           → hexágonos (diafragma de 6 hojas)
  "soap bubble bokeh"         → con bordo definido (lentes como Trioplan)
  "bokeh balls"               → puntos de luz fuera de foco (luces de fondo)

INTENSIDAD:
  Leve: "slight background blur, f/2.8"
  Medio: "pleasant bokeh, f/2.0 portrait"
  Extremo: "extreme bokeh, subject isolated, f/1.0 optical blur"
```

---

## 5. Iluminación cinematográfica

### Esquemas de iluminación clásicos

```
TRES PUNTOS (Three-Point Lighting):
  Prompt: "three-point lighting setup, key light from left, fill light softening 
           shadows, rim light separating subject from background"
  Componentes:
    Key light:  luz principal, un lado, crea sombras
    Fill light: rellena sombras del lado opuesto, más suave
    Rim/Hair:   luz de borde detrás, separa del fondo
  Uso: entrevistas, retratos, escenas de diálogo

ILUMINACIÓN LATERAL (Side/Rembrandt):
  Prompt: "Rembrandt lighting, dramatic side lighting, triangle of light on cheek"
          "45-degree side lighting, sculpted shadows, chiaroscuro"
  Efecto: dramático, artístico, esculpe el rostro
  
CONTRALUZ / SILHOUETTE:
  Prompt: "backlit silhouette, subject in front of bright light source"
          "contre-jour, backlight, halo effect around subject"
          "silhouette photography, bright background, dark subject"
  Efecto: dramático, misterioso, simbólico

LUZ CENITAL (Top Light):
  Prompt: "top lighting, overhead light source, deep eye socket shadows"
          "overhead single light, dramatic from above, horror style"
  Efecto: siniestro, amenazante, dramático (a menudo usado en thriller)

LUZ BAJA (Under Light):
  Prompt: "under lighting, light from below, eerie shadows going up"
          "uplight, light source below the face, dramatic upward shadows"
  Efecto: sobrenatural, inquietante, villano

LUZ NATURAL / WINDOW LIGHT:
  Prompt: "natural window light, soft directional daylight from the left"
          "Rembrandt window light, golden hour light through a window"
          "overcast diffused natural light, soft shadows"

GOLDEN HOUR:
  Prompt: "golden hour lighting, warm orange-gold sunlight, long shadows"
          "sunset lighting, warm golden tones, magic hour"
          "the golden hour before sunset, warm backlight"
  Efecto: cálido, épico, romántico, cinematográfico

BLUE HOUR:
  Prompt: "blue hour lighting, dusk, deep blue sky, warm artificial lights"
          "twilight, blue-purple ambient light, city lights beginning"
  Efecto: atmósferico, melancólico, transición día-noche
```

### Tipos de luz por calidad

```
LUZ DURA (Hard Light):
  Prompt: "hard lighting, sharp shadows, high contrast, single point source"
          "harsh directional light, deep shadows, high contrast"
  Fuentes: sol directo, flash sin difusor, spot teatral
  Efecto: dramático, duro, film noir

LUZ SUAVE (Soft Light):
  Prompt: "soft diffused lighting, gentle shadows, flattering portrait light"
          "soft box lighting, wrapped light, minimal shadows"
  Fuentes: ventana grande, softbox, día nublado, rebote
  Efecto: suave, favorecedor, editorial

AMBIENTE / PRÁCTICA:
  Prompt: "ambient lighting only, practical lights, tungsten warm glow"
          "lit only by candlelight, warm flickering shadows"
          "neon signs as only light source, colorful urban glow"
  Efecto: naturalista, inmersivo, atmosférico

VOLUMÉTRICO (God Rays):
  Prompt: "volumetric lighting, god rays through smoke/mist"
          "light shafts, dusty atmosphere, volumetric beams"
          "crepuscular rays, light cutting through fog"
  Efecto: épico, místico, cinematográfico premium
```

### Paletas de color de iluminación

```
CÁLIDO:
  "warm tungsten lighting, amber glow, 3200K color temperature"
  "warm orange-yellow light, cozy interior, incandescent"

FRÍO:
  "cool blue-white lighting, 6500K daylight, clinical feel"
  "cold moonlight, blue-tinted shadows, night scene"

TEAL AND ORANGE (Hollywood standard):
  "teal shadows and orange highlights, cinematic color grade"
  "orange warm skin tones, teal-blue background, Hollywood color grading"

NEON / CYBERPUNK:
  "neon-lit, magenta and cyan light, rain-slicked streets"
  "cyberpunk neon signs, purple and blue ambient glow"

MONOCROMÁTICO:
  "monochromatic green lighting, Matrix-style, single hue"
  "red lighting only, dramatic single color, Suspiria style"
```

---

## 6. Grano y textura de película

```
GRANO LIGERO (Fine Film Grain):
  Prompt: "subtle film grain, Kodak Portra 400, fine grain texture"
          "light cinematic grain, 35mm film look, slight grain"
  ISO equivalente: ~400-800
  Uso: look de cine sutil, sin perder nitidez

GRANO MEDIO:
  Prompt: "35mm film grain, Fuji Superia 800, visible grain texture"
          "medium grain film photography, analog texture"
  ISO equivalente: ~1600-3200
  Uso: fotografía documental, callejera, reportaje

GRANO PESADO (Heavy Film Grain):
  Prompt: "heavy film grain, ISO 3200, grainy texture, analog photography"
          "extreme grain, high-speed film, gritty texture, pushed film"
          "grainy black and white, pushed Kodak Tri-X 400 at 3200"
  ISO equivalente: 3200-12800
  Uso: documental raw, horror, urgencia dramática

TIPOS DE PELÍCULA (grain + color):
  "Kodak Portra 160"   → colores pastel suaves, piel perfecta, poco grano
  "Kodak Portra 400"   → cálido, favorecedor, grano sutil (retrato)
  "Kodak Gold 200"     → cálido, vivos, ligeramente saturados
  "Fuji Velvia 50"     → colores muy saturados, cielos azules profundos
  "Fuji Provia 100"    → equilibrado, neutro, fiel al color
  "Kodak Tri-X 400"    → B&W con grano característico (icónico)
  "Ilford HP5"         → B&W suave, flexible, clásico
  "Cinestill 800T"     → tungsten, halos rojos en luces, nocturno

VIÑETEADO:
  Prompt: "natural lens vignetting, darkened corners, analog lens"
          "strong vignette, dark corners, old lens aesthetic"
  Efecto: dirige la atención al centro, look vintage/cinematográfico

ABERRACIÓN CROMÁTICA:
  Prompt: "chromatic aberration, color fringing, vintage lens imperfection"
          "slight chromatic aberration on edges, analog lens look"
  Efecto: carácter de lente, vintage, orgánico

LENS FLARE:
  Prompt: "anamorphic lens flare, blue horizontal streaks"
          "lens flare from sun, J.J. Abrams style"
          "vintage lens flare, circular reflections, warm flare"
  Efecto: cinematográfico, real, atmosférico
```

---

## 7. Movimiento de cámara (para video / prompts descriptivos)

```
DOLLY IN / PUSH IN:
  Prompt video: "slow dolly in, camera approaching the subject"
  Efecto: intimidad creciente, tensión que se acumula

DOLLY OUT / PULL BACK:
  Prompt video: "dolly out, camera pulling back, revealing wider context"
  Efecto: revelación, distanciamiento, soledad

TRACKING SHOT / DOLLY LATERAL:
  Prompt video: "tracking shot following the subject from the side"
  Efecto: acompañamiento del movimiento, dinamismo

PAN (panorámica):
  Prompt video: "slow pan from left to right across the landscape"
               "pan reveal, starting on detail then widening"
  Efecto: descubrimiento espacial, establecer entorno

TILT (inclinación vertical):
  Prompt video: "tilt up from feet to face, slow reveal"
               "tilt down from sky to ground level"
  Efecto: revelar personaje o contexto verticalmente

CRANE / JIB:
  Prompt video: "crane shot, camera rises to reveal the city"
               "jib shot, camera descending from above"
  Efecto: grandiosidad, contexto épico, inicio o cierre de escena

STEADICAM / HANDHELD:
  "smooth Steadicam follow shot, fluid movement with the character"
  "handheld camera, slightly shaky, documentary feel, realistic motion"
  Efecto Steadicam: fluido, íntimo, sigue al personaje
  Efecto handheld: urgencia, realismo, documental

DRONE / AERIAL:
  Prompt video: "drone aerial shot, sweeping over the landscape"
               "aerial helicopter shot, descending slowly"
  Efecto: escala épica, perspectiva imposible

WHIP PAN (pan rápido):
  Prompt video: "whip pan transition, fast horizontal blur between scenes"
  Efecto: energía, transición rápida, estilo moderno

RACK FOCUS (ver DoF):
  Prompt video: "rack focus from foreground flower to background mountain"
  Efecto: redirigir la atención dramáticamente
```

---

## 8. Estilos de cinematografía por director / película

```
ROGER DEAKINS (Blade Runner 2049, 1917, Skyfall):
  "Roger Deakins cinematography style, meticulous composition, 
   moody atmospheric lighting, precise shadow control, golden landscapes"
  Características: lighting preciso, composiciones equilibradas, ambiental

GORDON WILLIS (El Padrino, Manhattan):
  "Gordon Willis cinematography, underexposed faces, 
   dark top-lit interiors, high contrast film noir style"
  Características: sombras deliberadas en rostros, alto contraste, oscuro

VITTORIO STORARO (Apocalypse Now, El último tango en París):
  "Vittorio Storaro lighting, warm-cool color contrasts, 
   symbolic lighting, orange fire tones vs cool blue shadows"
  Características: colores simbólicos, contraste cálido/frío

EMMANUEL LUBEZKI (Gravity, El Renacido, Birdman):
  "Emmanuel Lubezki natural light, long takes, 
   handheld intimate camera, golden hour, available light only"
  Características: luz natural, largas tomas, íntimo

HOYTE VAN HOYTEMA (Tenet, Interstellar, Dunkirk):
  "Hoyte van Hoytema IMAX cinematography, practical effects, 
   cool desaturated, technical precision, wide aspect ratio"

CHRISTOPHER DOYLE (In the Mood for Love, Fallen Angels):
  "Christopher Doyle Wong Kar-wai style, slow motion, 
   saturated neon colors, blurred motion, dreamy atmospheric"

NEON NOIR:
  "neo-noir cinematography, wet streets, neon reflections,
   high contrast, chiaroscuro shadows, fog atmosphere"

HORROR STYLE:
  "horror film cinematography, low key lighting, 
   single practical light source, deep shadows, tension framing"

WESTERN EPIC:
  "Sergio Leone cinematography, extreme close-ups alternating with
   extreme wide shots, golden dusty light, dramatic silence"
```

---

## 9. Color grading y look

```
TEAL AND ORANGE (dominante Hollywood):
  "cinematic teal and orange color grade, warm skin tones,
   cool shadows and backgrounds"

BLEACH BYPASS:
  "bleach bypass effect, desaturated, high contrast,
   silver retention, gritty texture"

CROSS PROCESSING:
  "cross-processed film, shifted colors, unexpected hues,
   cyan shadows, yellow highlights"

KODACHROME (vintage):
  "Kodachrome color palette, rich reds, saturated blues,
   warm greens, 1970s vintage look"

BLACK AND WHITE:
  "high contrast black and white photography"
  "low contrast B&W, film noir, soft grays"
  "dramatic B&W, split toning, silver gelatin print"

TECHNICOLOR (clásico):
  "three-strip Technicolor look, vivid primary colors,
   classic Hollywood Wizard of Oz palette"

MUTED / DESATURADO:
  "desaturated film look, muted colors, soft palette"
  "faded film color, bleached look, washed out colors"

DARK ACADEMIA:
  "dark academia aesthetic, warm browns, deep greens,
   candlelit ambiance, autumn tones"

VSCO FILM:
  "faded highlights, lifted shadows, slight cyan cast, film emulation"
```

---

## 10. Aspecto y formato

```
ACADÉMICO 4:3 (formato vintage):
  "4:3 aspect ratio, old film format, Academy ratio"

CINEMASCOPE / ANAMÓRFICO 2.39:1:
  "anamorphic widescreen, 2.39:1 aspect ratio, cinemascope"
  "anamorphic lens, letterbox format, cinematic black bars"
  Truco prompt: añadir "cinematic black bars top and bottom" para efecto

16:9 (standard HD):
  "16:9 widescreen" → proporción estándar de pantalla

IMAX 1.43:1:
  "IMAX format, tall widescreen, expansive frame"

VERTICAL 9:16 (móvil):
  "vertical format, portrait orientation, mobile-first framing"
  "shot for Instagram Stories, 9:16 vertical composition"
```

---

## 11. Combinaciones de prompt cinematográfico completo

### Ejemplo 1: Retrato dramático

```
"Close-up portrait of a weathered detective, shot on 85mm f/1.4,
shallow depth of field, Rembrandt lighting from a single window,
hard shadows sculpting the face, film noir aesthetic,
Kodak Tri-X 400 black and white grain, slight vignetting,
cinematic composition, 1950s noir atmosphere"
```

### Ejemplo 2: Paisaje épico

```
"Extreme wide establishing shot of a lone samurai standing on a cliff,
drone aerial perspective slightly elevated, 
golden hour sunlight from behind creating silhouette with rim light,
volumetric god rays through morning mist,
shot on anamorphic 35mm lens, cinematic 2.39:1 widescreen,
Roger Deakins lighting style, teal and orange color grade"
```

### Ejemplo 3: Acción urbana nocturna

```
"Medium shot tracking a woman running through rain-slicked streets,
handheld camera, slight motion blur on background,
neon signs reflecting on wet pavement,
cyan and magenta neon lighting, cyberpunk color palette,
shot on 35mm with fast lens f/1.8, visible film grain ISO 3200,
lens flare from neon signs, anamorphic horizontal streaks"
```

### Ejemplo 4: Horror íntimo

```
"Extreme close-up of hands opening a door, insert shot,
underlit from below, single candle as only light source,
dutch angle, slight camera tilt for unease,
deep shadows, heavy contrast, Dario Argento horror style,
warm orange candlelight vs deep cool shadows,
heavy grain, slight lens distortion, fog atmosphere"
```

### Ejemplo 5: Naturaleza documental

```
"Medium wide shot of a red fox in a snowy forest,
telephoto 400mm lens, extreme background compression,
layers of snow-covered trees dissolving into bokeh,
overcast soft natural light, blue hour winter palette,
wildlife photography, National Geographic style,
sharp subject isolation, muted desaturated winter tones"
```

---

## 12. Plantilla universal de prompt cinematográfico

```
[TAMAÑO DE PLANO] + [ÁNGULO] + [SUJETO] + [ACCIÓN/ESTADO] + 
[ENTORNO] + [ILUMINACIÓN] + [LENTE] + [PROFUNDIDAD DE CAMPO] + 
[TEXTURA/PELÍCULA] + [MOVIMIENTO] + [COLOR GRADE] + [ESTILO DIRECTOR]

MÍNIMO VIABLE (4 elementos):
  [PLANO] + [ILUMINACIÓN] + [LENTE] + [PELÍCULA/GRANO]
  Ejemplo: "close-up, golden hour backlight, 85mm f/1.4, Kodak Portra 400"

ESTÁNDAR (6 elementos):
  + [SUJETO] + [ÁNGULO]
  Ejemplo: "close-up portrait of a woman, slightly low angle,
            golden hour backlight, 85mm f/1.4, shallow DoF, Kodak Portra 400"

COMPLETO (todos):
  Ver ejemplos de sección anterior
```

---

## 13. Casos excepcionales y trucos

```
CE-CAM001: "cinematic" en Flux vs SDXL
  "Cinematic" en Flux activa estilos de iluminación y composición ricos
  En SDXL: añadir nombre de película o director para mejor resultado
  Más específico siempre supera al genérico

CE-CAM002: Bokeh en IA — no siempre predecible
  Los modelos generan bokeh aproximado, no físicamente exacto
  Para bokeh PRONUNCIADO: especificar f/1.2 + distancia + lente larga
  "85mm f/1.2 portrait, subject very close, background far" → máximo bokeh

CE-CAM003: Lens flare con Flux
  Flux genera anamorphic flares bien con: "anamorphic lens flare, blue streaks"
  Para flares cíclicos (sol): "lens flare from direct sun, warm circular flare"
  Los flares tipo J.J. Abrams: "cinematic lens flare, horizontal blue streaks"

CE-CAM004: Grain y nitidez son opuestos relativos
  Mucho grano + "ultra sharp" pueden contradecirse
  Para imagen granulada pero nítida: "sharp grain texture" no "heavy grain"
  Para look de película auténtico: aceptar ligera reducción de nitidez

CE-CAM005: Dutch angle — usar con moderación
  Un ángulo de 5-15° da inquietud sutil
  Más de 30°: efecto muy notorio, casi caricaturesco
  Prompt: "slight dutch angle" vs "extreme dutch tilt"

CE-CAM006: Anamorphic aspect ratio en IA
  "Anamorphic widescreen" sugiere el formato
  Para barras negras reales: añadir "letterbox, black bars top and bottom"
  En Flux: combinar con resolución 1344×576 o similar para efecto real

CE-CAM007: Color grade teal-orange con Flux
  "Teal and orange color grading" funciona bien en Flux
  Para más control: combinar con LoRA de color grade específico
  Alternativa: post-process en ComfyUI con ImageColorHSV nodes

CE-CAM008: Movimiento de cámara en imágenes estáticas
  "Slow dolly in" en imagen estática = sensación de profundidad en composición
  El modelo interpreta "movimiento" como perspectiva y composición dinámica
  Para video real: ver doc 50 (keyframing y Deforum)
```

---

## 14. Recursos y fuentes

```
REFERENCIA CINEMATOGRÁFICA:
  American Cinematographer:  https://www.theasc.com/magazine
  Kodak film stocks guide:   https://www.kodak.com/en/motion/products/capture/negative
  Film photography project:  https://filmphotographyproject.com

PROMPTS Y COMUNIDAD:
  CivitAI prompt sharing:    https://civitai.com/images
  PromptHero (visual DB):    https://prompthero.com
  Lexica (SD prompt search): https://lexica.art

TÉCNICA FOTOGRÁFICA:
  Photography Life (DoF):    https://photographylife.com/what-is-depth-of-field
  Cambridge in Colour:       https://www.cambridgeincolour.com
  
ILUMINACIÓN:
  Lighting diagrams:         https://www.diagrammr.com
  Strobox (lighting DB):     https://strobox.com

INSPIRACIÓN CINEMATOGRÁFICA:
  Cinematography.net:        https://cinematography.net
  Shot on what:              https://www.shotonwhat.com
  Roger Deakins forum:       https://www.rogerdeakins.com/forum
  Still frames DB:           https://www.movie-screencaps.com
```
