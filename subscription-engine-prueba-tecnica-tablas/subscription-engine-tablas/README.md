# Subscription Engine - version alineada con las tablas solicitadas

Esta versión utiliza exactamente:

- `customers`
- `subscriptions`
- `payment_attempts`

## Backend

```bash
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Si prefieres usar el SQL entregado, ejecuta:

```text
backend/database/suscripciones.sql
```

y configura las credenciales MySQL en `.env`.

## Frontend

```bash
cd frontend
npm install
copy .env.example .env
npm run dev
```

Abre `http://localhost:5173`.

## Simulador

El motor acepta:

```json
{"result":"approved"}
{"result":"failed"}
{"result":"timeout"}
{"result":"random"}
```

`random` usa 60% aprobado, 30% rechazado y 10% timeout.

## Reintentos

`payment_attempts.retry_count` comienza en 0. Los fallos programan `next_retry_at` 24 horas después. Al tercer fallo, la suscripción pasa a `paused`.

Para simular el paso del tiempo:

```sql
UPDATE payment_attempts
SET next_retry_at = NOW() - INTERVAL 1 DAY
WHERE id = 1;
```

Luego ejecuta nuevamente `POST /api/billing/run`.

## Nota sobre el error de PaymentAttempt

No ejecutes `php app/Models/PaymentAttempt.php` directamente. Laravel carga el modelo mediante Composer. Después de descargar:

```bash
composer install
composer dump-autoload
php artisan optimize:clear
php artisan serve
```
# Subscription Engine

Motor de suscripciones desarrollado como prueba técnica. El sistema permite gestionar clientes, suscripciones y ejecutar un motor de cobros recurrentes con registro de intentos de pago, simulación de resultados y reintentos automáticos.

## 📋 Contenido

* [Tecnologías](#-tecnologías)
* [Estructura de datos](#-estructura-de-datos)
* [Requisitos](#-requisitos)
* [Instalación del Backend](#-instalación-del-backend)
* [Instalación del Frontend](#-instalación-del-frontend)
* [Ejecución del proyecto](#-ejecución-del-proyecto)
* [Funcionamiento del motor de cobros](#-funcionamiento-del-motor-de-cobros)
* [Simulador de pagos](#-simulador-de-pagos)
* [Sistema de reintentos](#-sistema-de-reintentos)
* [Simular el paso del tiempo](#-simular-el-paso-del-tiempo)
* [API principal](#-api-principal)
* [Notas sobre el modelo PaymentAttempt](#-notas-sobre-el-modelo-paymentattempt)
* [Uso de IA](#-uso-de-ia)

---

## 🛠 Tecnologías

### Backend

* PHP 8.2+
* Laravel
* MySQL
* Composer
* Laravel Eloquent ORM
* API REST

### Frontend

* React
* Vite
* JavaScript
* HTML
* CSS

---

## 🗄 Estructura de datos

El proyecto utiliza exactamente las siguientes tablas:

```text
customers
subscriptions
payment_attempts
```

### `customers`

Almacena la información de los clientes:

* `id`
* `name`
* `email`
* `document`
* `phone`
* `created_at`
* `updated_at`

### `subscriptions`

Almacena las suscripciones asociadas a los clientes:

* `id`
* `customer_id`
* `plan`
* `amount`
* `status`
* `next_billing_at`
* `created_at`
* `updated_at`

### `payment_attempts`

Registra cada intento de cobro:

* `id`
* `subscription_id`
* `amount`
* `status`
* `retry_count`
* `next_retry_at`
* `created_at`
* `updated_at`

La relación principal es:

```text
customers
    │
    └── subscriptions
            │
            └── payment_attempts
```

---

# 💻 Requisitos

Antes de ejecutar el proyecto es necesario tener instalado:

* PHP 8.2 o superior
* Composer
* MySQL
* Node.js
* npm

Para verificar las versiones:

```bash
php -v
composer -V
mysql --version
node -v
npm -v
```

> En Windows, asegúrate de que PHP y Composer estén incluidos en el `PATH` del sistema.

---

# 🚀 Instalación del Backend

Ingresar a la carpeta del backend:

```bash
cd backend
```

Instalar las dependencias:

```bash
composer install
```

Crear el archivo de configuración:

```bash
copy .env.example .env
```

Generar la clave de Laravel:

```bash
php artisan key:generate
```

## Configuración de MySQL

Crear la base de datos, por ejemplo:

```sql
CREATE DATABASE suscripciones;
```

Luego configurar las credenciales en el archivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=suscripciones
DB_USERNAME=root
DB_PASSWORD=
```

> Ajusta `DB_USERNAME` y `DB_PASSWORD` de acuerdo con la configuración local de MySQL.

---

## 🗃 Crear las tablas

El proyecto puede utilizar las migraciones de Laravel:

```bash
php artisan migrate
```

Si se desea utilizar directamente el SQL entregado con la prueba técnica, ejecutar:

```text
backend/database/suscripciones.sql
```

En ese caso, configurar previamente las credenciales de MySQL en `.env`.

---

## 🧹 Limpiar cachés y autoload

Después de instalar o realizar cambios importantes en el backend, se recomienda ejecutar:

```bash
composer dump-autoload
php artisan optimize:clear
```

---

## ▶️ Ejecutar Backend

Iniciar el servidor:

```bash
php artisan serve
```

Por defecto estará disponible en:

```text
http://127.0.0.1:8000
```

---

# ⚛️ Instalación del Frontend

Desde la raíz del proyecto:

```bash
cd frontend
```

Instalar dependencias:

```bash
npm install
```

Crear el archivo `.env`:

```bash
copy .env.example .env
```

Verificar que la URL de la API corresponda al backend.

Por ejemplo:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

Iniciar el frontend:

```bash
npm run dev
```

La aplicación estará disponible en:

```text
http://localhost:5173
```

---

# 🔄 Ejecución completa

Para trabajar con el proyecto se deben ejecutar ambos servidores.

### Terminal 1 — Backend

```bash
cd backend
php artisan serve
```

### Terminal 2 — Frontend

```bash
cd frontend
npm run dev
```

Luego abrir:

```text
http://localhost:5173
```

---

# ⚙️ Funcionamiento del motor de cobros

El flujo principal del sistema es:

```text
Cliente
   ↓
Crear suscripción
   ↓
Ejecutar motor de cobros
   ↓
Generar intento de pago
   ↓
Simular resultado
   ↓
¿Aprobado?
   ├── Sí → Cobro aprobado
   │
   └── No
        ↓
      Registrar fallo
        ↓
      Programar reintento
        ↓
      ¿Tercer fallo?
        ├── No → Nuevo reintento
        └── Sí → Suscripción pausada
```

El motor permite ejecutar el proceso mediante:

```http
POST /api/billing/run
```

---

# 🎲 Simulador de pagos

El motor de pagos permite controlar el resultado del intento mediante los siguientes valores:

```json
{
  "result": "approved"
}
```

```json
{
  "result": "failed"
}
```

```json
{
  "result": "timeout"
}
```

```json
{
  "result": "random"
}
```

### Resultados disponibles

| Resultado  | Comportamiento                    |
| ---------- | --------------------------------- |
| `approved` | El pago es aprobado               |
| `failed`   | El pago es rechazado              |
| `timeout`  | El intento termina por timeout    |
| `random`   | Selecciona un resultado aleatorio |

Cuando se utiliza:

```json
{
  "result": "random"
}
```

la distribución utilizada es:

* **60%** aprobado
* **30%** rechazado
* **10%** timeout

Esto permite probar diferentes escenarios sin depender de un proveedor de pagos real.

---

# 🔁 Sistema de reintentos

El campo:

```text
payment_attempts.retry_count
```

comienza en:

```text
0
```

Cuando un intento falla:

1. Se registra el intento de pago.
2. Se incrementa el contador de reintentos.
3. Se programa el siguiente intento.
4. `next_retry_at` queda establecido 24 horas después.

Por ejemplo:

```text
Primer fallo
retry_count = 1
next_retry_at = ahora + 24 horas
```

Si vuelve a fallar:

```text
Segundo fallo
retry_count = 2
next_retry_at = ahora + 24 horas
```

Al alcanzar el tercer fallo:

```text
Tercer fallo
retry_count = 3
subscription.status = paused
```

La suscripción queda pausada y no continúa con nuevos cobros automáticos.

---

# ⏰ Simular el paso del tiempo

Para probar los reintentos sin esperar realmente 24 horas, se puede modificar manualmente `next_retry_at`.

Por ejemplo:

```sql
UPDATE payment_attempts
SET next_retry_at = NOW() - INTERVAL 1 DAY
WHERE id = 1;
```

Esto hace que el siguiente intento sea considerado como vencido.

Después ejecutar nuevamente:

```http
POST /api/billing/run
```

El motor detectará que el reintento está disponible y procesará nuevamente el cobro.

> El `id = 1` debe reemplazarse por el ID del intento que se desea probar.

---

# 🧪 Ejemplo de flujo de prueba

Una prueba completa puede realizarse de la siguiente manera:

### 1. Crear un cliente

Crear un cliente desde la interfaz o mediante la API.

### 2. Crear una suscripción

Asignar una suscripción al cliente creado.

### 3. Ejecutar el motor

Ejecutar:

```http
POST /api/billing/run
```

### 4. Forzar un pago aprobado

Utilizar:

```json
{
  "result": "approved"
}
```

El intento debe quedar registrado como aprobado.

### 5. Forzar un fallo

Utilizar:

```json
{
  "result": "failed"
}
```

El sistema registra el fallo y programa el siguiente reintento para 24 horas después.

### 6. Simular las 24 horas

Ejecutar:

```sql
UPDATE payment_attempts
SET next_retry_at = NOW() - INTERVAL 1 DAY
WHERE id = 1;
```

### 7. Ejecutar nuevamente el motor

```http
POST /api/billing/run
```

El sistema procesa el reintento.

### 8. Probar tres fallos

Forzar `failed` tres veces y verificar que la suscripción termine en:

```text
paused
```

---

# 🔌 API principal

La aplicación expone una API REST para la comunicación entre React y Laravel.

Entre las operaciones principales se encuentran:

### Customers

```text
GET    /api/customers
POST   /api/customers
GET    /api/customers/{id}
PUT    /api/customers/{id}
DELETE /api/customers/{id}
```

### Subscriptions

```text
GET    /api/subscriptions
POST   /api/subscriptions
GET    /api/subscriptions/{id}
PUT    /api/subscriptions/{id}
DELETE /api/subscriptions/{id}
```

### Billing

```text
POST /api/billing/run
```

La interfaz React consume estos endpoints para permitir la operación completa del motor desde el navegador.

---

# ⚠️ Notas sobre el modelo `PaymentAttempt`

No se debe ejecutar directamente el archivo:

```text
backend/app/Models/PaymentAttempt.php
```

Por ejemplo, **no** ejecutar:

```bash
php app/Models/PaymentAttempt.php
```

Los modelos de Laravel dependen del autoload de Composer y del framework.

Si aparece un error como:

```text
Class "Illuminate\Database\Eloquent\Model" not found
```

se recomienda ejecutar desde `backend`:

```bash
composer install
```

y posteriormente:

```bash
composer dump-autoload
```

Luego:

```bash
php artisan optimize:clear
```

Finalmente:

```bash
php artisan serve
```

Laravel se encargará de cargar los modelos mediante Composer.

---

# 🤖 Uso de IA

Durante el desarrollo se utilizaron herramientas de Inteligencia Artificial como apoyo al proceso de construcción del proyecto.

La IA se utilizó principalmente para:

* Consultar y validar alternativas de implementación.
* Resolver dudas relacionadas con Laravel, PHP, React y MySQL.
* Identificar posibles causas de errores de configuración.
* Revisar estructura y organización del código.
* Generar ideas para mejorar la implementación.
* Apoyar la documentación técnica.
* Revisar casos de prueba y escenarios del motor de cobros.

La IA fue utilizada como **herramienta de apoyo**, no como sustituto del proceso de desarrollo.

La integración entre frontend y backend, configuración del proyecto, estructura de las entidades, lógica del motor de cobros, manejo de reintentos, pruebas y validación del funcionamiento fueron revisados y ajustados durante el desarrollo.

---

# 📌 Resumen de comandos

## Backend

```bash
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
composer dump-autoload
php artisan optimize:clear
php artisan serve
```

## Frontend

```bash
cd frontend
npm install
copy .env.example .env
npm run dev
```

## URLs

Frontend:

```text
http://localhost:5173
```

Backend:

```text
http://127.0.0.1:8000
```

---

# ✅ Resultado esperado

Al finalizar la instalación, el sistema debe permitir:

* Crear, consultar, actualizar y eliminar clientes.
* Crear y administrar suscripciones asociadas a clientes.
* Ejecutar el motor de cobros.
* Registrar los intentos de pago.
* Simular pagos aprobados, rechazados y timeout.
* Utilizar resultados aleatorios.
* Programar reintentos después de un fallo.
* Consultar el historial de intentos.
* Pausar automáticamente una suscripción después del tercer fallo.
* Simular el paso de 24 horas para probar los reintentos.
* Operar el flujo completo desde la interfaz React consumiendo la API Laravel.
