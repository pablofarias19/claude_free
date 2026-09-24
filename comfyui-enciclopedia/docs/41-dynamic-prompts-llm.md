# 41 · Prompts Dinámicos, Wildcards e Integración con LLMs

> **DECLARACIÓN TÉCNICA PARA IA**: Los prompts dinámicos en ComfyUI permiten variación automática de texto en cada generación. El método principal son los wildcards (listas de valores). La integración con LLMs (Ollama, GPT, Claude) permite generar prompts completos desde descripciones simples. Custom node clave: `comfyui-inspire-pack` para wildcards, y `comfyui-llm-party` o `comfyui-ollama` para integrar modelos de lenguaje locales.

## Wildcards — Variación automática

### Concepto

```
Sin wildcards: prompt fijo → misma imagen (misma seed)

Con wildcards:
  "a __color__ cat" → genera con color aleatorio en cada run
  __color__.txt contiene: red, blue, orange, striped, tabby
  → cada ejecución elige uno distinto
```

### Sintaxis de wildcards

```
# Wildcard desde archivo:
__nombre_del_archivo__
Ejemplo: __animals__  → lee de wildcards/animals.txt

# Elección inline (sin archivo):
{opción1|opción2|opción3}
Ejemplo: {red|blue|green} car

# Anidado:
{a {big|small} {dog|cat}|a {tall|short} person}

# Con pesos:
{3::rojo|1::azul|2::verde}  → rojo 50%, azul 16.7%, verde 33.3%

# Repetir N elementos:
{2$$uno|dos|tres}  → elige 2 de las opciones
{2-4$$uno|dos|tres|cuatro|cinco}  → elige entre 2 y 4
```

### Estructura de archivos wildcards

```
comfyui/
└── custom_nodes/
    └── comfyui-inspire-pack/ (u otro node de wildcards)
        └── wildcards/
            ├── animals.txt
            ├── colors.txt
            ├── styles.txt
            ├── lighting.txt
            ├── locations/
            │   ├── cities.txt
            │   └── nature.txt
            └── moods.txt

# Ejemplo colors.txt:
red
deep red
crimson
coral
orange
golden
...

# Referencia subcarpeta:
__locations/cities__  → lee de wildcards/locations/cities.txt
```

## Custom nodes para wildcards

### Inspire Pack (más completo)

```bash
git clone https://github.com/ltdrdata/ComfyUI-Inspire-Pack custom_nodes/ComfyUI-Inspire-Pack
```

**Nodo principal:**
```
WildcardPrompt
  text: "a photo of a __animals__ in __locations/nature__"
  mode: fixed | incremental | random
  Select to add wildcard: [lista desplegable de wildcards disponibles]
  
  Salida: texto con wildcards reemplazados + condicionamiento CLIP
```

### Dynamic Prompts (alternativa)

```bash
git clone https://github.com/adieyal/comfyui-dynamicprompts custom_nodes/comfyui-dynamicprompts
```

**Nodo:**
```
DPRandomGenerator
  template: "a {beautiful|stunning|gorgeous} {sunset|sunrise} over {mountains|ocean|city}"
  → genera texto aleatorio en cada ejecución

DPCombinedSampler
  → genera todas las combinaciones posibles
```

## Nodo Primitive + Reroute para prompts modulares

```
# Construir prompt modular sin wildcards:
[Primitive: "a photo of"]  ────────────────┐
[Primitive: "a woman"]     ────────────────┤
[Primitive: "in Paris"]    ────────────────┤  [StringConcat]
[Primitive: "sunset light"]────────────────┘     ↓
                                          [CLIPTextEncode]
# Permite modificar cada parte independientemente
```

## Integración con LLMs locales (Ollama)

```
Ollama: servidor de LLMs locales (Llama 3, Mistral, Phi-3, Gemma, etc.)
Permite generar prompts completos desde instrucciones simples
No requiere API key ni internet
```

### Instalación Ollama

```bash
# En Linux/Mac:
curl -fsSL https://ollama.com/install.sh | sh

# En Windows: descargar de ollama.com

# Descargar un modelo:
ollama pull llama3.2  # 2 GB aprox, buen equilibrio
ollama pull phi3.5    # 2.4 GB, muy eficiente
ollama pull mistral   # 4 GB, mejor para creative writing

# Iniciar servidor:
ollama serve  # Puerto 11434 por defecto
```

### Custom node ComfyUI-Ollama

```bash
git clone https://github.com/rogeriochaves/ComfyUI-Ollama custom_nodes/ComfyUI-Ollama
```

**Workflow texto → prompt → imagen:**

```
[Primitive: "a cat sitting on a sofa"]
      ↓
[OllamaGenerate]
  model: llama3.2
  prompt: "Convert this concept to a detailed Stable Diffusion prompt. \
           Include artistic style, lighting, details, and quality tags. \
           Concept: {input}"
  keep_alive: -1  # mantener modelo en memoria
  temperature: 0.7
      ↓
  [texto del prompt expandido y detallado]
      ↓
[CLIPTextEncode] → [KSampler]
```

### Prompting efectivo para generación de prompts SD

```
System prompt para Ollama (mejorar resultados):
"You are a Stable Diffusion prompt engineer. 
Convert user descriptions to detailed, comma-separated prompts.
Always include: subject, style, lighting, camera, quality tags.
Format: [subject description], [art style], [lighting], [camera/lens], 
        [quality tags like 'masterpiece, highly detailed, 8k'].
Respond ONLY with the prompt, no explanation."
```

## ComfyUI-LLM-Party — Suite completa de LLM

```bash
git clone https://github.com/heshengtao/comfyui_LLM_party custom_nodes/comfyui_LLM_party
```

**Capacidades:**
- Integrar Ollama, OpenAI, Claude API, Gemini
- Cadenas de LLM (output de uno → input de otro)
- RAG (buscar en documentos antes de generar)
- Agentes con herramientas
- Generar prompts, analizar imágenes, describir resultados

```
Nodos incluidos:
  LLM_Image_Description   → describir imagen con LLM vision
  LLM_Prompt_Generator    → generar prompt desde concepto
  LLM_Style_Transfer_Text → cambiar estilo del prompt
  ChatGLM / Qwen nodes    → modelos chinos optimizados
```

## Workflow avanzado: LLM describe + genera variaciones

```
Etapa 1: Describir imagen existente
[Load Image] → [LLM Vision Describe]
  model: llava o bakllava (modelos vision en Ollama)
  prompt: "Describe this image in detail for Stable Diffusion. \
           Include style, lighting, colors, composition."
  → [texto descriptivo detallado]

Etapa 2: Modificar la descripción
[texto] → [OllamaGenerate]
  prompt: "Change the style of this prompt to cyberpunk anime: {texto}"
  → [prompt modificado]

Etapa 3: Generar con el nuevo prompt
[prompt modificado] → [CLIPTextEncode] → [KSampler]
  → imagen con misma composición, nuevo estilo
```

## Programación de prompts / Prompt scheduling

```
Cambiar el prompt entre diferentes steps de sampling:

[ConditioningSetTimestepRange]
  conditioning: [prompt1]
  start: 0.0
  end: 0.5    → prompt1 activo en steps 0-50%

[ConditioningSetTimestepRange]
  conditioning: [prompt2]
  start: 0.5
  end: 1.0    → prompt2 activo en steps 50-100%

[ConditioningCombine] → combinar ambos → [KSampler]

# Efecto: la imagen empieza con la estructura del prompt1
# y se refina con los detalles del prompt2
```

## Batch generation con prompts variables

```
# Generar N imágenes con prompts distintos en un solo run:

[LoadTextFile]
  file: prompts_list.txt  # Un prompt por línea
      ↓
[TextListToString] o [SplitStringByLineBreak]
      ↓
[ForEach (Impact Pack o Loop node)]
      ↓
  [CLIPTextEncode] → [KSampler] → [Save Image]

# Genera una imagen por cada línea del archivo
```

## Casos excepcionales

1. **Ollama y ComfyUI en el mismo PC (sistema Pablo)**: Ollama usa ~2-4 GB RAM para LLMs pequeños. Con 32 GB RAM no hay conflicto con ComfyUI. El LLM puede estar en CPU mientras la GPU genera imágenes simultáneamente.
2. **Wildcards con caracteres especiales**: Los wildcards en español con tildes o ñ pueden fallar según la codificación del archivo. Usar UTF-8 sin BOM para los archivos .txt.
3. **Seed y wildcards**: Con seed fija + wildcard, el wildcard es determinista (misma seed = misma elección). Para variar el wildcard manteniendo seed, usar `mode: incremental` o cambiar la seed solo del wildcard.
4. **LLM latencia**: Ollama con Llama 3 en CPU tarda 10-30 segundos en generar un prompt. En CPU i9-10900, latencia aceptable para workflows no-tiempo-real. Para workflows batch, la latencia suma.
5. **Prompts demasiado largos del LLM**: Los LLMs pueden generar prompts de 500+ tokens. CLIP solo acepta 77 tokens. Agregar al system prompt: "Keep the prompt under 75 words." o usar LongCLIP.

## Recursos

- Ollama: `https://ollama.com`
- ComfyUI-Ollama node: `https://github.com/rogeriochaves/ComfyUI-Ollama`
- ComfyUI-LLM-Party: `https://github.com/heshengtao/comfyui_LLM_party`
- Inspire Pack (wildcards): `https://github.com/ltdrdata/ComfyUI-Inspire-Pack`
- Dynamic Prompts: `https://github.com/adieyal/comfyui-dynamicprompts`
- Ollama modelos disponibles: `https://ollama.com/library`
- LLaVA (vision LLM): `https://ollama.com/library/llava`
