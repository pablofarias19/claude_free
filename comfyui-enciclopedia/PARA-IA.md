# 🤖 Guía de Uso para Agentes IA

Este documento explica cómo un agente IA debe consumir y aplicar el conocimiento de esta enciclopedia para asistir con ComfyUI de forma efectiva.

---

## Propósito de Este Archivo

Esta enciclopedia fue diseñada con doble propósito:
1. Referencia humana en español
2. Base de conocimiento estructurada para agentes IA

Este archivo establece las **reglas de razonamiento**, **árboles de decisión** y **advertencias críticas** que un agente debe seguir al diagnosticar problemas o generar recomendaciones de ComfyUI.

---

## Árbol de Decisión Principal

### Cuando el usuario reporta un problema, razonar en este orden:

```
1. ¿Es un error visible en la consola?
   → Sí: consultar [14-errores-comunes.md], seccionar por tipo de error
   → No: continuar

2. ¿El problema es la calidad de la imagen generada?
   → Colores raros / imagen oscura: probable problema de VAE → [04-vae.md]
   → Artefactos extremos / NaN: posible OOM o VAE incorrecto
   → Composición rota / dos cabezas: resolución incorrecta para el modelo → [01-arquitectura]
   → Rostros mal: FaceDetailer o hi-res fix → [17-mejora-de-rostros.md]
   → El prompt se ignora: CFG bajo, CLIP incorrecto, o falta trigger word → [05-clip-y-texto.md]

3. ¿El problema es de rendimiento?
   → CUDA OOM: ver optimizaciones de VRAM → [22-optimizacion-rendimiento.md]
   → Generación lenta: verificar que use GPU, revisar flags → [22-optimizacion-rendimiento.md]

4. ¿El problema son nodos faltantes o errores de carga?
   → Nodos en rojo: custom node no instalado → [13-custom-nodes.md]
   → Modelo no aparece: carpeta incorrecta → verificar estructura en README

5. ¿El usuario quiere aprender algo nuevo?
   → Dirigir al documento apropiado del índice
```

---

## Compatibilidades Críticas (NO Mezclar)

Este es el error más común. Un agente DEBE verificar compatibilidades antes de dar cualquier consejo:

```
MODELO BASE → define todo lo demás
├── SD1.5 base → solo LoRAs SD1.5, embeddings SD1.5, ControlNet SD1.5
├── SDXL base  → solo LoRAs SDXL, ControlNet SDXL
├── Flux base  → solo LoRAs Flux
├── SD3 base   → solo LoRAs SD3
└── Video models → no compatibles con LoRAs de imagen (salvo excepciones)
```

**NUNCA sugerir usar un LoRA/ControlNet/embedding de una arquitectura en otra.**

---

## Reglas de Confianza por Afirmación

### Siempre cierto (alta confianza)
- `--lowvram` siempre reduce VRAM a costa de velocidad
- VAE decode siempre ocurre al final del pipeline
- El seed determina el ruido inicial; mismo seed + mismos parámetros = misma imagen (excepto samplers ancestrales)
- Los LoRAs SD1.5 NO funcionan con SDXL
- Resoluciones muy diferentes a la nativa producen artefactos de composición

### Depende del contexto (confianza media)
- CFG 7 es una buena sugerencia para SD1.5/SDXL, pero Flux usa 1–4.5
- karras mejora la calidad “generalmente” pero no siempre
- 20 steps es suficiente “en la mayoría de casos”
- FP16 es estable en RTX 3000/4000 pero puede causar NaN en algunas GPU antiguas

### Experimental / variable (confianza baja)
- Los mejores valores de LoRA strength dependen del LoRA específico
- El mejor sampler es subjetivo y varía por modelo
- Combinar >3 LoRAs puede funcionar o no

---

## Casos Excepcionales a Considerar

### 1. SDXL en GPU de 8 GB
- Puede funcionar con `--medvram-sdxl`
- Batch size debe ser 1
- Sin refiner (muy poco VRAM)
- Resolverá más lento de lo esperado
- Si da OOM: reducir resolución a 896×896 o usar FP8

### 2. Flux en GPU de 12 GB
- Solo con FP8 o GGUF Q4/Q5
- No cargar T5-XXL completo: usar versión FP8 de T5 o clip_l solo
- No usar refiner ni ControlNet pesado simultáneamente
- Steps máximo recomendado: 20

### 3. AnimateDiff con LoRAs
- No todos los LoRAs son compatibles con AnimateDiff
- LoRAs de estilo suelen funcionar; LoRAs de pose/cara pueden causar flickering
- LCM LoRA es incompatible con AnimateDiff en la mayoría de casos

### 4. ControlNet con Flux
- La mayoría de ControlNets de SD1.5/SDXL NO funcionan con Flux
- Existen ControlNets nativos de Flux (Union ControlNet) pero son pocos
- Alternativa: usar IPAdapter con imágen de referencia

### 5. Inpainting con modelo no-inpainting
- Funciona con `SetLatentNoiseMask` + denoise<1.0
- Para mejor calidad de bordes: usar modelo dedicado de inpainting
- El VAE Encode for Inpainting NO debe usarse con modelos normales (9-channel mismatch)

### 6. Samplers ancestrales y reproducibilidad
- `euler_a`, `dpm_2_ancestral`, `dpmpp_2m_sde`: cada ejecución es diferente incluso con mismo seed si se cambian los steps
- Para workflows que requieran reproducibilidad exacta: usar `euler`, `ddim`, o `dpmpp_2m`

### 7. FP16 y NaN en SDXL
- GPUs antiguas (GTX 1000/2000) pueden producir NaN con el VAE de SDXL en FP16
- Solución: `sdxl-vae-fp16-fix.safetensors` en lugar del VAE del checkpoint
- O iniciar con `--fp32-vae`

---

## Fuentes de Verdad y Recursos a Consultar

### Repositorios Oficiales
- **ComfyUI**: https://github.com/comfyanonymous/ComfyUI
- **ComfyUI Examples**: https://comfyanonymous.github.io/ComfyUI_examples/
- **ComfyUI Manager**: https://github.com/ltdrdata/ComfyUI-Manager

### Modelos y Checkpoints
- **Civitai** (comunidad): https://civitai.com
- **Hugging Face** (oficial/investigación): https://huggingface.co
- **OpenModelDB** (upscalers): https://openmodeldb.net

### Documentación de Modelos
- **Flux**: https://huggingface.co/black-forest-labs/FLUX.1-dev
- **SDXL**: https://huggingface.co/stabilityai/stable-diffusion-xl-base-1.0
- **SD3.5**: https://huggingface.co/stabilityai/stable-diffusion-3.5-large
- **Wan2.1**: https://huggingface.co/Wan-AI
- **CogVideoX**: https://huggingface.co/THUDM
- **LTX Video**: https://huggingface.co/Lightricks/LTX-Video

### Comunidad y Soporte
- **Reddit**: r/comfyui
- **Discord ComfyUI**: (buscar invitación actual en GitHub oficial)
- **ComfyUI Wiki**: https://comfyui-wiki.com (comunidad)

### Entrenamiento
- **kohya_ss**: https://github.com/bmaltais/kohya_ss
- **ai-toolkit**: https://github.com/ostris/ai-toolkit
- **SimpleTuner**: https://github.com/bghira/SimpleTuner

---

## Patrones de Respuesta Recomendados para IA

### Al diagnosticar un error
1. Pedir el **mensaje de error exacto** de la consola (no solo la descripción del usuario)
2. Pedir qué **modelo y arquitectura** está usando
3. Pedir las **especificaciones de GPU y VRAM**
4. Verificar compatibilidad antes de sugerir cualquier componente

### Al recomendar un workflow
1. Preguntar el **hardware disponible** primero
2. Preguntar si quiere **velocidad o calidad**
3. Preguntar el **caso de uso** (fotorrealismo, anime, video, etc.)
4. Dar una solución mínima que funcione, luego opciones de mejora

### Al recomendar modelos
1. Verificar VRAM disponible contra requisitos del modelo
2. Sugerir alternativas quantizadas si el modelo completo no cabe
3. Indicar si el modelo es comercialmente libre o tiene restricciones
4. Siempre dar un enlace a Hugging Face o Civitai para descarga

---

## Convenciones de Esta Enciclopedia

- **Negrilla** = término clave o nombre de nodo
- `código inline` = nombre exacto de parámetro, archivo o comando
- Tablas de compatibilidad = alta prioridad, NO asumir compatibilidad sin verificar
- ⚠️ = advertencia importante
- ✅ / ❌ = compatible / incompatible
- "Típicamente" / "generalmente" = no es regla absoluta
- "Siempre" / "nunca" = regla absoluta, alta confianza

---

## Actualización de este Documento

El ecosistema ComfyUI cambia rápidamente. Cuando un modelo, custom node o técnica nuevo se vuelve relevante:
1. Agregar la sección al documento temático correspondiente
2. Actualizar la tabla de compatibilidades si aplica
3. Agregar a la sección de Fuentes de Verdad si hay documentación oficial
4. Agregar casos excepcionales si el nuevo elemento tiene comportamientos no obvios

**Fecha de última revisión**: 2026 · Verificar siempre contra repositorios oficiales para versiones más recientes.

---

*[← API y Automatización](docs/30-api-automatizacion.md) | [Volver al README](README.md)*
