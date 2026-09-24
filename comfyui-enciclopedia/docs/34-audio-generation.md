# 34 · Generación de Audio con IA — Integración con ComfyUI

> **DECLARACIÓN TÉCNICA PARA IA**: La generación de audio en ComfyUI está en estado experimental/emergente (2024–2025). No hay soporte nativo en ComfyUI core para audio — todo requiere custom nodes. Los modelos principales son AudioCraft (Meta), AudioLDM2, MusicGen, Stable Audio, y Bark (TTS). La integración más madura es vía ComfyUI-AudioTools y nodos de VHS para sincronización audio-video.

## Modelos de generación de audio

### MusicGen (Meta)

| Variante | Parámetros | VRAM | Duración máx |
|---|---|---|---|
| musicgen-small | 300M | ~2 GB | 30 seg |
| musicgen-medium | 1.5B | ~5 GB | 30 seg |
| musicgen-large | 3.3B | ~8 GB | 30 seg |
| musicgen-melody | 1.5B | ~5 GB | 30 seg + melodía ref |

```python
# MusicGen — uso básico fuera de ComfyUI (para referencia):
from audiocraft.models import MusicGen
model = MusicGen.get_pretrained('facebook/musicgen-medium')
model.set_generation_params(duration=15)  # segundos
audio = model.generate(['upbeat electronic music with synth bass'])
```

### AudioLDM2

Generación de efectos de sonido y música via difusión en espacio latente de audio.

```
Prompt de texto → Latent audio space → Audio waveform
(Similar a SD pero para sonido)
```

| Variante | Uso principal | VRAM |
|---|---|---|
| audioldm2-full | Música + SFX general | ~6 GB |
| audioldm2-music | Solo música | ~6 GB |
| audioldm2-48k | Audio alta fidelidad | ~8 GB |

### Stable Audio (Stability AI)

```
Modelo: stable-audio-open-1.0
Duración: hasta 47 segundos
Calidad: 44.1 kHz estéreo
HuggingFace: stabilityai/stable-audio-open-1.0
VRAM: ~8 GB
```

### Bark (Suno AI) — Text-to-Speech

```
Capacidades:
  · Voces habladas con emociones y acentos
  · Efectos de sonido [laughs], [clears throat]
  · Música básica
  · Múltiples idiomas incluyendo español

VRAM: ~2–4 GB
HuggingFace: suno-ai/bark
```

### AudioCraft (Meta) — Suite completa

Incluye MusicGen + AudioGen (efectos de sonido):

```bash
pip install audiocraft
# Requiere Python 3.9+, PyTorch 2.0+
```

## Custom nodes para audio en ComfyUI

### ComfyUI-AudioTools

El paquete más completo para audio en ComfyUI:

```bash
# Instalación via ComfyUI Manager o manual:
git clone https://github.com/eigenpunk/ComfyUI-audio custom_nodes/ComfyUI-audio
# o
git clone https://github.com/a-r-r-o-w/cogvideox-factory  # incluye audio nodes
```

**Nodos disponibles:**
- `LoadAudio`: Cargar archivo de audio
- `SaveAudio`: Guardar como WAV/MP3
- `PreviewAudio`: Vista previa en interfaz
- `AudioToSpectogram`: Convertir a espectrograma visual
- `MusicGenNode`: Generar música con MusicGen
- `AudioLDM2Node`: Generar audio con AudioLDM2

### VHS (Video Helper Suite) — Sincronización audio-video

```
Nodos clave para audio+video:
VHS_LoadAudio        → Cargar audio para sincronizar
VHS_VideoCombine     → Combinar video + audio en salida final
    audio: [conectar LoadAudio]
    audio_file: ruta del archivo
```

## Workflow: Generar música + video sincronizado

```
[Prompt texto música] → [MusicGenNode]
                              · duration: 10 segundos
                              · model: musicgen-medium
                              ↓
                       [SaveAudio → music.wav]

[Prompt texto imagen] → [KSampler] → [AnimateDiff]
                                         · frames: 240 (10 seg × 24fps)
                                         ↓
                              [VHS_VideoCombine]
                                  audio: music.wav
                                  frame_rate: 24
                                  format: video/mp4
                                         ↓
                              [Salida: video+audio sincronizado]
```

## Workflow: Video-to-Audio (sincronización automática)

```
[VHS_LoadVideo]
      ↓
[ExtractAudioFeatures]  →  detectar BPM, energía
      ↓
[PromptFromAudioFeatures]  →  prompt dinámico basado en música
      ↓
[KSampler con denoise variable]  →  movimiento sincronizado con audio
```

> **NOTA**: Esta funcionalidad requiere custom nodes especializados como `comfyui-animation` o `deforum-comfyui`.

## Text-to-Speech con Bark en ComfyUI

```
Nodo: BarkTTS (requiere instalación de bark)

Parámetros:
  text: "Hola, soy una voz generada por IA [laughs]"
  voice_preset: "v2/es_speaker_0"  # Voz en español
  temperature: 0.7  # Mayor = más variado
  
Voces disponibles (español):
  v2/es_speaker_0 al v2/es_speaker_9
  (9 voces distintas con distintas características)
```

### Caracteres especiales Bark

```
[laughs]         → risa
[clears throat]  → carraspeo
[sighs]          → suspiro
[gasps]          → jadeo
[music]          → tarareado musical
..., ...         → pausa
Mayúsculas       → énfasis
```

## Espectrogramas como imágenes para ComfyUI

Técnica avanzada: tratar espectrogramas de audio como imágenes para procesarlos con nodos de imagen.

```
[Audio WAV] → [AudioToSpectrogram]
                    width: 512, height: 256
                    ↓
             [Imagen PNG del espectrograma]
                    ↓
             [Procesar con ControlNet/IP-Adapter]
                    ↓
             [SpectrogramToAudio]  →  Audio modificado
```

> **Uso artístico**: Aplicar estilos visuales al espectrograma y reconvertir a audio produce transformaciones tímbricas únicas.

## Requisitos de hardware para audio

| Modelo | VRAM | RAM | Tiempo (10 seg) |
|---|---|---|---|
| Bark (TTS) | 2–4 GB | 8 GB | 5–15 seg |
| MusicGen-small | 2 GB | 8 GB | 10–20 seg |
| MusicGen-medium | 5 GB | 16 GB | 20–40 seg |
| AudioLDM2 | 6 GB | 16 GB | 30–60 seg |
| Stable Audio | 8 GB | 16 GB | 20–40 seg |
| MusicGen-large | 8 GB | 24 GB | 40–80 seg |

## Formatos de salida de audio

```
# Formatos soportados típicamente:
WAV  → Sin compresión, máxima calidad, archivos grandes
MP3  → Comprimido, compatible universalmente
FLAC → Sin pérdida comprimido, equilibrio
OGG  → Open source, buena compresión

# Sample rates comunes:
22050 Hz  → Audio básico (speech)
44100 Hz  → Calidad CD (música)
48000 Hz  → Estándar video/broadcast
```

## Integración con flujos de video IA

### ComfyUI workflow completo: imagen → video → música

```
Etapa 1: Generar imagen base
  [SDXL / Flux] → imagen base

Etapa 2: Animar imagen
  [AnimateDiff / SVD / Wan2.1] → video sin audio

Etapa 3: Generar música sincronizada
  [MusicGen] con prompt basado en contenido visual → audio.wav

Etapa 4: Combinar
  [VHS_VideoCombine(video, audio.wav)] → video final con música
```

## Casos excepcionales

1. **Generación en CPU**: MusicGen y AudioLDM2 pueden generar en CPU, pero son 10–50× más lentos. Viable para clips muy cortos.
2. **MusicGen + melodía de referencia**: `musicgen-melody` puede continuar o variar una melodía de entrada. Útil para consistencia musical entre clips de video.
3. **Bark en Mac MPS**: Funciona parcialmente. Algunos tokens de voz fallan en MPS — usar `PYTORCH_ENABLE_MPS_FALLBACK=1`.
4. **Longitud > 30 seg en MusicGen**: Requiere generación por segmentos con concatenación. No hay soporte nativo de contexto largo — cada segmento es independiente (puede sonar discontinuo).
5. **Estéreo vs Mono**: Stable Audio genera estéreo nativo. MusicGen genera mono por defecto y puede convertirse a estéreo falso en post.

## Recursos

- AudioCraft (Meta): `https://github.com/facebookresearch/audiocraft`
- Stable Audio: `https://huggingface.co/stabilityai/stable-audio-open-1.0`
- Bark TTS: `https://github.com/suno-ai/bark`
- AudioLDM2: `https://huggingface.co/cvssp/audioldm2`
- ComfyUI audio discussion: `https://github.com/comfyanonymous/ComfyUI/discussions`
- Hugging Face Audio Course: `https://huggingface.co/learn/audio-course`
