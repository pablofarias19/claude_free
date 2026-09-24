# 📖 Glosario ComfyUI — Diccionario Alfabético

Referencia rápida de todos los términos del ecosistema ComfyUI ordenados alfabéticamente.

---

## A

**Attn (Attention)** — Mecanismo interno de los transformers que determina qué partes del prompt o imagen son más relevantes al generar cada pixel/token.

**AutoEncoderKL** — Nombre técnico del VAE usado en Stable Diffusion. El "KL" viene de la divergencia Kullback-Leibler usada en su entrenamiento.

## B

**Base model** — Modelo checkpoint principal. En SDXL hay un modelo base + un refiner. El base genera la imagen, el refiner la mejora en los últimos pasos.

**Batch size** — Cuántas imágenes se generan simultáneamente en una sola pasada. Aumenta el uso de VRAM proporcionalmente.

**BF16 (Brain Float 16)** — Formato de número de punto flotante de 16 bits. Más eficiente en memoria que FP32, usado por defecto en modelos modernos como Flux.

**Block** — En el contexto de ComfyUI, un nodo. En el contexto de modelos, una sección interna de la red neuronal (por ej. bloque de atención, bloque residual).

## C

**CFG Scale (Classifier-Free Guidance)** — Controla cuánto sigue el modelo al prompt. Valores bajos (1–5): más creatividad, menos fidelidad. Valores altos (10+): más fidelidad al prompt, pero puede saturar/artefactar. Rango típico: 5–8 para la mayoría de modelos.

**Checkpoint** — Archivo que contiene los pesos (parámetros) de un modelo completo. Formatos: `.safetensors` (seguro, recomendado) o `.ckpt` (legado). Tamaño típico: 2–7 GB.

**CLIP (Contrastive Language-Image Pretraining)** — Red neuronal que convierte texto en vectores numéricos que el modelo puede entender. SD1.5 usa CLIP-L; SDXL usa CLIP-L + CLIP-G; Flux usa CLIP-L + T5-XXL.

**CLIP Skip** — Cuántas capas finales del encoder CLIP se omiten. Skip 1 = comportamiento normal. Skip 2 = estilo más "anime", muy usado con modelos entrenados con Novel AI. En nodos: parámetro `clip_skip` en algunos loaders.

**ComfyUI Manager** — Extensión de terceros que agrega una interfaz para instalar, actualizar y gestionar custom nodes. Casi obligatoria.

**Conditioning** — El resultado de codificar texto con CLIP. Es un tensor que el sampler usa como guía. En los nodos aparece como tipo de dato `CONDITIONING`.

**ControlNet** — Red adicional que condiciona la generación basándose en una imagen de control (mapa de profundidad, bordes, pose, etc.).

**Custom Node** — Extensión de terceros que agrega nuevos nodos a ComfyUI. Se instalan en la carpeta `custom_nodes/`.

## D

**Denoising** — El proceso central de generación: eliminar ruido progresivamente de un tensor hasta obtener una imagen coherente.

**Denoising Strength** — En img2img e inpainting, qué tanto se modifica la imagen de entrada. 0 = sin cambios, 1 = generación completa desde ruido.

**Depth Map** — Imagen en escala de grises donde el brillo indica la distancia al espectador. Se usa como control en ControlNet.

**DPMPP / DPM++** — Familia de samplers más eficientes. DPM++ 2M Karras es uno de los más populares por calidad/velocidad.

**dtype** — Tipo de dato de los tensores. Los principales: `fp32` (32 bits, preciso, lento), `fp16` (16 bits, rápido, menos preciso), `bf16` (16 bits alternativo, más estable).

## E

**Embedding** — Ver *Textual Inversion*.

**Empty Latent Image** — Nodo que crea un tensor de ruido puro del tamaño especificado. Es el punto de partida de cualquier generación text-to-image.

**Encode** — Convertir una imagen real al espacio latente mediante el VAE. El proceso inverso es Decode.

**Euler / Euler a** — Samplers clásicos. Euler a (ancestral) agrega estocasticidad en cada paso, produciendo variación con cada seed aunque los parámetros sean iguales.

## F

**FP16** — Punto flotante de 16 bits. Reduce el uso de VRAM a la mitad comparado con FP32, con mínima pérdida de calidad en la mayoría de casos.

**FP32** — Punto flotante de 32 bits. Máxima precisión, doble consumo de VRAM.

**Flux** — Familia de modelos de generación de imágenes de Black Forest Labs (2024). Usa una arquitectura transformer diferente a SD. Variantes: Flux.1-dev, Flux.1-schnell.

## G

**Guidance** — Ver *CFG Scale*.

## H

**Hi-res fix** — Técnica para generar a baja resolución y luego escalar + refinar. Evita artefactos de coherencia que aparecen al generar directo a alta resolución. En ComfyUI se implementa manualmente con nodos de upscale + KSampler con denoising bajo.

**Hypernetwork** — Método de fine-tuning más antiguo, casi reemplazado por LoRA. Modifica las capas de atención del modelo.

## I

**img2img** — Generación condicionada por una imagen de entrada + prompt. La imagen se codifica al espacio latente y se añade ruido según el denoising strength.

**Inpainting** — Editar solo una zona de una imagen usando una máscara. Fuera de la máscara, la imagen se preserva.

**IP-Adapter (Image Prompt Adapter)** — Extensión que permite usar una imagen como "prompt visual", transfiriendo estilo o identidad a la generación.

## K

**Karras** — Scheduler de ruido que distribuye los pasos de forma no lineal, concentrando más detalles en los pasos finales. Generalmente mejora calidad con el mismo número de steps.

**KSampler** — El nodo central de ComfyUI. Ejecuta el proceso de denoising completo dado un modelo, conditioning, latente y parámetros.

**KSampler Advanced** — Versión del KSampler con control de `start_at_step` y `end_at_step`, útil para workflows de base+refiner.

## L

**Latent** — Representación comprimida de una imagen en el "espacio latente". Tiene mucho menos resolución que la imagen final (típicamente 1/8). Los samplers trabajan en este espacio.

**Latent Space** — El espacio matemático donde el VAE comprime las imágenes. Es donde ocurre todo el proceso de denoising.

**LoRA (Low-Rank Adaptation)** — Archivo pequeño (10–300 MB) que modifica un checkpoint para aprender un estilo, personaje u objeto específico. Se aplica sobre el modelo base.

**LCM (Latent Consistency Model)** — Técnica que permite generar imágenes de calidad con muy pocos steps (4–8). Se usa como LoRA sobre modelos existentes.

## M

**Mask** — Imagen en blanco y negro que define qué zona procesar en inpainting. Blanco = zona a modificar, Negro = zona a preservar.

**Model** — Ver *Checkpoint*.

**Model Merge** — Combinar los pesos de dos o más checkpoints para obtener uno nuevo con características de ambos.

## N

**NaN (Not a Number)** — Error numérico donde un cálculo produce un valor indefinido. Generalmente causa imágenes completamente negras o artefactos extremos.

**Negative Prompt** — Texto que describe lo que NO se quiere en la imagen. Se codifica como conditioning negativo.

**Node** — Bloque funcional de ComfyUI. Tiene entradas (sockets izquierdos) y salidas (sockets derechos). Se conectan arrastrando cables entre sockets compatibles.

**Noise** — Ruido gaussiano aleatorio. Es el punto de partida de la generación; el sampler va eliminándolo guiado por el conditioning.

## P

**Pipeline** — Secuencia completa de nodos desde la entrada hasta la imagen final.

**Positive Prompt** — Texto que describe lo que SÍ se quiere en la imagen.

**Preprocessor** — En ControlNet, nodo que transforma una imagen de entrada en el mapa de control apropiado (extrae bordes, profundidad, pose, etc.).

## Q

**Queue** — La cola de generaciones pendientes en ComfyUI. Se puede agregar múltiples runs y se ejecutan en orden.

## R

**Refiner** — En SDXL, modelo secundario especializado en añadir detalle a los últimos pasos del denoising. Se usa con KSampler Advanced.

**Resolution** — Tamaño de la imagen en píxeles. Cada modelo tiene resoluciones óptimas: SD1.5 = 512×512, SDXL = 1024×1024, Flux = flexible.

## S

**Safetensors** — Formato de archivo para modelos de ML, más seguro que `.ckpt` (no ejecuta código arbitrario al cargarse). Recomendado siempre.

**Sampler** — Algoritmo que ejecuta el proceso de denoising paso a paso. Ejemplos: Euler, DPM++ 2M, DDIM, UniPC.

**Scheduler** — Define cómo se distribuye el nivel de ruido a lo largo de los steps. Ejemplos: normal, karras, exponential, sgm_uniform.

**SDXL (Stable Diffusion XL)** — Versión de Stable Diffusion con mayor resolución nativa (1024px), mejor comprensión de texto y arquitectura doble (base + refiner).

**Seed** — Número que inicializa el generador de ruido aleatorio. El mismo seed con los mismos parámetros produce la misma imagen. -1 = aleatorio.

**Socket** — Punto de conexión en un nodo. Los sockets de colores iguales son compatibles: amarillo=MODEL, morado=CONDITIONING, rojo=LATENT, azul=IMAGE, etc.

**Steps** — Número de pasos del sampler. Más steps = más detalle y calidad (hasta un límite), pero más lento. Rango típico: 20–30. Con LCM: 4–8.

## T

**T5-XXL** — Modelo de lenguaje grande usado como encoder de texto en Flux y SD3. Mucho más capaz que CLIP para entender prompts complejos y largos.

**Tensor** — Arreglo multidimensional de números. Los nodos pasan tensores entre sí representando imágenes, latentes, modelos, etc.

**Textual Inversion (Embedding)** — Archivo pequeño que enseña al modelo un concepto nuevo definido como un token especial. Se activa escribiendo su nombre en el prompt.

**Tiling** — Modo donde la imagen se genera como patrón repetible (sin costuras en los bordes). Útil para texturas.

## U

**Upscaler** — Modelo o algoritmo para aumentar la resolución de una imagen. Tipos: latent upscale (dentro del espacio latente), pixel upscale (ESRGAN, etc.).

**UNet** — La red neuronal principal dentro de un checkpoint de Stable Diffusion. Es quien realiza el denoising iterativo.

## V

**VAE (Variational Autoencoder)** — Componente que codifica imágenes reales al espacio latente (Encode) y decodifica latentes a imágenes reales (Decode). Incluido en los checkpoints, pero se puede usar uno externo para mejor calidad.

**VRAM** — Memoria de la tarjeta gráfica (GPU). El recurso más limitante en ComfyUI. Los modelos grandes requieren 8–24 GB.

## W

**Workflow** — El conjunto completo de nodos y sus conexiones guardado como archivo `.json`. Representa un pipeline de generación.

**Weight** — En LoRA e IP-Adapter, el "peso" (strength) que controla cuánto influye sobre la generación. Valor típico: 0.5–1.0.

---

*[← Volver al README](../README.md)*
