# 🐔 SIGA — Sistema Inteligente de Gestión Avícola
> Versión 2.0 · PHP + MySQL + XAMPP

---

## 📦 Estructura del Proyecto

```
siga-avicola/
├── index.html              ← Interfaz principal (frontend)
├── database.sql            ← Script de base de datos MySQL
├── README.md               ← Este archivo
├── includes/
│   └── db.php              ← Conexión PDO a MySQL
└── api/
    ├── dashboard.php       ← KPIs, resumen general
    ├── galpones.php        ← CRUD Galpones
    ├── lotes.php           ← CRUD Lotes
    ├── sensores.php        ← CRUD Sensores + lecturas
    ├── alertas.php         ← Gestión de alertas
    └── tipos_sensor.php    ← Catálogo de tipos de sensor
```

---

## 🚀 Instalación paso a paso en XAMPP

### 1. Copiar el proyecto
Copia la carpeta `siga-avicola` dentro de:
```
C:\xampp\htdocs\siga-avicola\
```

### 2. Crear la base de datos
1. Abre XAMPP y **inicia Apache + MySQL**
2. Abre el navegador en: **http://localhost/phpmyadmin**
3. Haz clic en **"Nueva"** en el panel izquierdo
4. Escribe el nombre: `siga_avicola` y clic en **Crear**
5. Selecciona la base de datos creada → pestaña **SQL**
6. Pega el contenido del archivo `database.sql` y clic en **Ejecutar**

   *O bien usa la línea de comandos:*
   ```bash
   mysql -u root -p < database.sql
   ```

### 3. Configurar la conexión (si cambias el password)
Edita `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Tu usuario MySQL
define('DB_PASS', '');           // Tu contraseña (vacía en XAMPP por defecto)
define('DB_NAME', 'siga_avicola');
```

### 4. Abrir el sistema
Navega a: **http://localhost/siga-avicola/**

---

## 🌐 API REST Endpoints

| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/dashboard.php` | KPIs, alertas, consumos |
| GET | `/api/galpones.php` | Listar galpones |
| POST | `/api/galpones.php` | Crear galpón |
| PUT | `/api/galpones.php?id=1` | Editar galpón |
| DELETE | `/api/galpones.php?id=1` | Eliminar galpón |
| GET | `/api/lotes.php` | Listar lotes |
| POST | `/api/lotes.php` | Crear lote |
| PUT | `/api/lotes.php?id=1` | Actualizar lote |
| GET | `/api/sensores.php` | Listar sensores |
| POST | `/api/sensores.php` | Crear sensor |
| POST | `/api/sensores.php?lectura=1` | Registrar lectura de sensor |
| GET | `/api/alertas.php` | Listar alertas |
| PUT | `/api/alertas.php?all=1` | Marcar todas leídas |
| GET | `/api/tipos_sensor.php` | Catálogo de tipos de sensor |

---

## 💡 Modo Demo

Si el sistema **no puede conectar con la API**, funciona automáticamente en **modo demo** con datos de ejemplo. Ideal para mostrar el sistema sin instalar nada.

---

## 🤖 Asistente IA

El módulo de IA usa la API de **Claude (Anthropic)**. Funciona directamente desde el navegador. No requiere configuración adicional.

---

## 📊 Módulos del Sistema

| Módulo | Descripción |
|--------|-------------|
| **Dashboard** | KPIs en tiempo real: temperatura, amoníaco, aves, alimento, agua |
| **Alertas** | Notificaciones automáticas por valores críticos |
| **Galpones** | CRUD completo de galpones con estado |
| **Lotes** | Registro de lotes con seguimiento de mortalidad |
| **Sensores** | Monitoreo de sensores con umbrales configurados |
| **Asistente IA** | Chat inteligente especializado en avicultura |

---

## 🔴🟡🟢 Sistema de Alertas Automáticas

| Sensor | ⚠️ Advertencia | 🔴 Crítico |
|--------|---------------|-----------|
| Temperatura | ≥ 30°C | ≥ 35°C |
| Amoníaco | ≥ 25 ppm | ≥ 35 ppm |
| Humedad | ≥ 75% | ≥ 85% |
| CO₂ | ≥ 1000 ppm | ≥ 1500 ppm |

Las alertas se generan automáticamente al registrar lecturas de sensores desde la API.

---

## 🛠️ Tecnologías

- **Frontend**: HTML5 + CSS3 + JavaScript Vanilla
- **Backend**: PHP 8+ (sin frameworks)
- **Base de datos**: MySQL 5.7+ / MariaDB
- **Servidor local**: XAMPP
- **IA**: Claude API (Anthropic)
- **Fuentes**: Sora + JetBrains Mono (Google Fonts)
