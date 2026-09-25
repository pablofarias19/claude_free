# Instrucciones de despliegue en Hostinger

## Estructura de archivos

```
public_html/
├── index.html              ← Sitio web público (sucesionlegal.com.ar)
└── mailgenius/             ← Sistema admin MailGenius Pro
    ├── index.php           ← Dashboard principal
    ├── login.php           ← Acceso al sistema
    ├── setup.php           ← Configuración inicial (primera vez)
    ├── config/
    │   ├── db.php          ← CREAR ESTE ARCHIVO (ver paso 2)
    │   ├── db.php.example  ← Plantilla
    │   └── config.php
    ├── database/
    │   ├── schema.sql      ← Estructura de la base de datos
    │   └── seed_sucesion.sql ← Respuestas del bot
    └── ...
```

---

## Pasos para instalar

### 1. Subir archivos
- Entrá al **Administrador de archivos** de Hostinger
- Abrí `public_html`
- Subí el `index.html` (sitio público)
- Subí la carpeta `mailgenius/` completa

### 2. Crear config/db.php
- Dentro de `public_html/mailgenius/config/`
- Copiá `db.php.example` → renombralo a `db.php`
- Editalo con los datos de tu base de datos:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'u580580751_sucesion');
  define('DB_USER', 'u580580751_sucesion1');
  define('DB_PASS', 'TU_CONTRASEÑA');
  ```

### 3. Importar la base de datos
- Entrá a **phpMyAdmin** en Hostinger
- Seleccioná la base de datos `u580580751_sucesion`
- Importá primero: `database/schema.sql`
- Importá después: `database/seed_sucesion.sql`

### 4. Primer acceso
- Abrí: `sucesionlegal.com.ar/mailgenius/`
- Usuario: `admin@example.com`
- La primera vez abrí `/mailgenius/setup.php` para configurar

---

## URLs del sistema

| URL | Función |
|-----|---------|
| `sucesionlegal.com.ar` | Sitio público con bot de chat |
| `sucesionlegal.com.ar/mailgenius/` | Dashboard admin |
| `sucesionlegal.com.ar/mailgenius/content_manager.php` | Gestión de respuestas del bot |
| `sucesionlegal.com.ar/mailgenius/settings.php` | Configuración SMTP, etc. |

---

## Notas importantes
- El archivo `config/db.php` **nunca** se sube a GitHub (está en .gitignore)
- Las imágenes del sitio público se pueden agregar en `public_html/img/`
- El bot de chat usa la base de datos para sus respuestas
