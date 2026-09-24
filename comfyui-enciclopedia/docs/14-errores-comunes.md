# 14 — Errores Comunes y Soluciones

Diagnóstico de los problemas más frecuentes en ComfyUI.

---

## Errores de VRAM / Memoria

### `CUDA out of memory`
**Causa**: El modelo o la operación requiere más VRAM de la disponible.

**Soluciones**:
1. Activar optimizaciones de memoria: iniciar ComfyUI con `--lowvram` o `--medvram`
2. Reducir el batch size a 1
3. Reducir la resolución
4. Usar modelos quantizados (FP8, GGUF) para Flux y SD3
5. Cerrar otras aplicaciones que usen GPU
6. En settings de ComfyUI: activar "Force Channels Last" y "CPU VAE" si el VAE da problemas

### Imagen negra / completamente oscura
**Causas posibles**:
- VAE incorrecto (especialmente en SDXL con FP16) → usar `sdxl-vae-fp16-fix`
- NSFW filter activado en el modelo → algunos fine-tunes tienen filtro interno
- NaN en el proceso (ver abajo)

---

## Errores de Modelo / Checkpoint

### `Error loading: model.safetensors`
**Causas**:
- Archivo corrupto (descarga incompleta) → re-descargar
- Archivo en ubicación incorrecta → verificar que esté en `models/checkpoints/`
- Permisos de archivo → verificar que sea legible

### El modelo no aparece en la lista
- Verificar extensión: `.safetensors` o `.ckpt`
- Reiniciar ComfyUI o hacer clic en "Refresh" en el selector de modelo
- Verificar que esté en la carpeta correcta

### `RuntimeError: Expected all tensors to be on the same device`
**Causa**: Conflicto CPU/GPU, normalmente con nodos custom que no manejan bien el device.
**Solución**: Reiniciar ComfyUI. Si persiste, desactivar el custom node problemático.

---

## Errores de NaN / Artefactos Extremos

### Imágenes con artefactos extremos, ruido total, o NaN
**Causas y soluciones**:
1. **VAE incompatible**: usar VAE correcto para la arquitectura
2. **CFG demasiado alto** (>15): bajar el CFG
3. **FP16 inestable en SDXL**: usar `sdxl-vae-fp16-fix.safetensors`
4. **LoRA incompatible**: el LoRA es de otra arquitectura, quitarlo
5. **Steps insuficientes con sampler ancestral**: Euler_a necesita más steps

---

## Errores de Custom Nodes

### `Cannot import module 'X'`
```bash
# En el directorio del custom node:
pip install -r requirements.txt
```

### `'NoneType' object has no attribute 'X'`
**Causa**: Un nodo espera recibir datos pero recibió None.
**Solución**: Verificar que todas las conexiones estén hechas correctamente.

### Nodos desaparecidos / marcados en rojo al cargar workflow
**Causa**: El custom node que los creó no está instalado.
**Solución**: ComfyUI Manager → "Install Missing Custom Nodes" (detecta automáticamente qué falta).

---

## Errores de CLIP / Texto

### El prompt parece ignorarse
**Causas**:
- El CLIP es de una arquitectura diferente al UNet → usar Load Checkpoint y no mezclar CLIPs
- CFG muy bajo → subir a 7
- Trigger word del LoRA no incluida en el prompt

### `CLIP model not found`
**Causa**: Modelo separado de CLIP no está en `models/clip/`.
**Solución**: Descargar el CLIP correspondiente (para Flux: `clip_l.safetensors` + `t5xxl_fp16.safetensors`).

---

## Errores de Resolución / Composición

### Dos cabezas, objetos duplicados, composición rota
**Causa**: Resolución muy diferente a la nativa del modelo.
**Solución**: Usar la resolución nativa + hi-res fix si necesitas más tamaño.

### Imágen estirada o deformada
**Causa**: Ratio de aspecto muy extremo (ej. 256x1024).
**Solución**: Usar ratios más comunes (1:1, 2:3, 3:4, 9:16, 16:9).

---

## Errores de Rendimiento

### Generación muy lenta
**Causas y soluciones**:
1. Modelo en CPU en lugar de GPU → verificar que CUDA esté disponible (`nvidia-smi`)
2. `--lowvram` activo innecesariamente → quitar si tienes VRAM suficiente
3. dtype FP32 en lugar de FP16 → usar modelos BF16 o FP16
4. Demasiados steps → reducir a 20

### ComfyUI se queda colgado sin generar
- Revisar la consola/terminal donde corre ComfyUI: ver el error real
- Interrumpir la cola (botón Interrupt) y reintentar
- Verificar que la GPU no esté en 0% de uso (problema de drivers)

---

## Checklist de Diagnóstico

Cuando algo falla, verificar en orden:

- [ ] ¿El modelo está en la carpeta correcta?
- [ ] ¿El modelo es compatible con la arquitectura usada (SD1.5 vs SDXL vs Flux)?
- [ ] ¿El VAE es correcto para ese modelo?
- [ ] ¿Hay errores en la consola de ComfyUI?
- [ ] ¿Todas las conexiones de nodos están hechas?
- [ ] ¿Los LoRAs son de la misma arquitectura que el checkpoint?
- [ ] ¿Hay VRAM suficiente para la resolución y modelo elegidos?
- [ ] ¿Los custom nodes necesarios están instalados?

---

*[← Custom Nodes](13-custom-nodes.md) | [Volver al README](../README.md)*
