# ConstrucKIT · API de equipos, combustible y mantenciones

API REST en **Laravel 13 (PHP 8.4) + MySQL 8.4** para que una empresa registre sus equipos (camiones, excavadoras, generadores), las cargas de combustible y las mantenciones de cada uno, con reportes de gasto y rendimiento.

Incluye autenticación con Sanctum, Docker Compose, seeders con datos de ejemplo, tests automatizados, una colección de Postman y una vista web simple que consume el reporte.

---

## 1. Cómo levantar el proyecto

**Requisito:** solo [Docker Desktop](https://www.docker.com/products/docker-desktop/). PHP, Composer y MySQL corren dentro de contenedores.

```bash
# 1. Clonar el repositorio
git clone https://github.com/gabozizou/construckit.git
cd construckit

# 2. Crear el archivo de entorno (ya viene configurado para el MySQL de Docker)
cp .env.example .env

# 3. Construir la imagen e instalar dependencias de PHP
docker compose build
docker compose run --rm app composer install

# 4. Generar la clave de la aplicación
docker compose run --rm app php artisan key:generate

# 5. Levantar la app y MySQL
docker compose up -d

# 6. Crear las tablas y cargar los datos de ejemplo
docker compose exec app php artisan migrate --seed
```

Listo:

| Qué | Dónde |
|---|---|
| API | http://localhost:8000/api |
| Vista web del reporte | http://localhost:8000/reporte |
| MySQL (desde tu equipo, p. ej. DBeaver o TablePlus) | `localhost:3307` · usuario `construckit` · clave `secret` |

> Para volver a dejar los datos de ejemplo desde cero: `docker compose exec app php artisan migrate:fresh --seed`

---

## 2. Cómo probar

### Usuario de prueba (creado por el seeder)

| Correo | Contraseña |
|---|---|
| `demo@construckit.cl` | `password` |

### Datos de ejemplo

El seeder crea 3 equipos con 8 cargas cada uno (octubre de 2026). Los datos son fijos, así que los resultados siempre son los mismos:

| Equipo | Escenario | Qué deberías ver |
|---|---|---|
| **CAM-101** (camión) | preventiva reciente | reporte de octubre: 736 L, 280 h, **2,3 L/h**. No necesita preventiva (80 h) |
| **EXC-201** (excavadora) | última preventiva hace 315 h | aparece en "necesitan mantención" |
| **GEN-301** (generador) | nunca tuvo preventiva, 330 h | aparece primero en "necesitan mantención" |

### Colección de Postman

Importa `postman/ConstrucKIT.postman_collection.json` en Postman (también funciona en Insomnia, Bruno o Apidog).

1. Ejecuta **Auth → Login**. El token se guarda solo en la variable `token`.
2. Todas las demás peticiones lo usan automáticamente.

Incluye las 20 peticiones de la API, con ejemplos de error (horómetro menor, validaciones, sin token, rango de fechas invertido).

### Con curl

```bash
# Login: guarda el token en una variable
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@construckit.cl","password":"password"}' | sed -E 's/.*"token":"([^"]+)".*/\1/')

# Reporte de CAM-101 en octubre
curl "http://localhost:8000/api/equipment/1/report?from=2026-10-01&to=2026-10-31" \
  -H "Authorization: Bearer $TOKEN"
```

### Tests automatizados

```bash
docker compose exec app php artisan test
```

14 tests (55 verificaciones) que cubren la regla del horómetro (punto 2) y el reporte (punto 3). Corren contra una base MySQL separada (`construckit_test`), que Docker crea automáticamente, así que no tocan los datos de ejemplo.

---

## 3. Endpoints

Todas las rutas, salvo `login`, requieren el header `Authorization: Bearer <token>`.

| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/api/login` | Entrega un token (pública) |
| `POST` | `/api/logout` | Revoca el token actual |
| `GET` | `/api/user` | Usuario autenticado |
| `GET` | `/api/equipment` | Lista paginada. Filtros opcionales: `?status=`, `?type=` |
| `POST` | `/api/equipment` | Crea un equipo |
| `GET` | `/api/equipment/{id}` | Ver un equipo |
| `PUT/PATCH` | `/api/equipment/{id}` | Editar (se pueden enviar solo los campos que cambian) |
| `DELETE` | `/api/equipment/{id}` | Eliminar (soft delete) |
| `GET` | `/api/equipment/{id}/fuel-loads` | Cargas de un equipo |
| `POST` | `/api/equipment/{id}/fuel-loads` | Registrar carga (aplica la regla del horómetro) |
| `GET` | `/api/equipment/{id}/maintenances` | Mantenciones de un equipo |
| `POST` | `/api/equipment/{id}/maintenances` | Registrar mantención (aplica la regla del horómetro) |
| `GET` | `/api/equipment/{id}/report?from=AAAA-MM-DD&to=AAAA-MM-DD` | Reporte del período |
| `GET` | `/api/equipment/maintenance-due` | Equipos que necesitan mantención preventiva |

**Códigos de respuesta:** `200` OK · `201` creado · `204` sin contenido · `401` no autenticado · `404` no encontrado · `422` error de validación (con `message` y `errors` por campo, en español).

---

## 4. Decisiones técnicas

### Estructura del código

Cada pieza tiene una sola responsabilidad, para que el código sea fácil de seguir y de modificar:

| Pieza | Responsabilidad | Ejemplo |
|---|---|---|
| **Form Request** | validar la entrada | `StoreFuelLoadRequest` |
| **Controller** | recibir la petición y delegar (queda delgado) | `FuelLoadController` |
| **Service** | lógica de negocio | `HourmeterService`, `EquipmentReportService` |
| **Resource** | definir el JSON de salida | `EquipmentResource` |
| **Scope del modelo** | consultas reutilizables | `Equipment::needsPreventiveMaintenance()` |

Los mensajes de validación en español viven en una clase base (`ApiRequest`) que heredan todas las validaciones, para no repetirlos.

### Nombres en inglés, mensajes en español

Tablas, campos y código están en inglés: siguen las convenciones de Laravel (pluralización, relaciones) sin configuración extra. Todo lo que lee el usuario está en español.

| Español | En el código |
|---|---|
| equipo | `equipment` (incontable: la tabla no lleva "s") |
| carga de combustible | `fuel_loads` |
| mantención | `maintenances` |
| horómetro | `hourmeter` / `current_hourmeter` |
| código interno / patente | `code` / `plate` |

### Base de datos

- **Soft delete en equipos:** eliminar un equipo no borra su historial. El gasto del año pasado sigue siendo real.
- **Claves foráneas con `restrictOnDelete`:** MySQL impide borrar físicamente un equipo que tenga cargas o mantenciones.
- **Montos en CLP como enteros sin signo:** el peso chileno no usa decimales.
- **Horómetro como `decimal(10,1)`:** los horómetros marcan décimas de hora.
- **Índices** en `(equipment_id, loaded_at)` y `(equipment_id, performed_on)` para el reporte, y en `(equipment_id, type, hourmeter)` para encontrar la última preventiva.
- **Fechas en UTC:** la base queda en una zona horaria neutra, y cada cliente la convierte a su hora local.

### Regla del horómetro

| Horómetro recibido | Resultado |
|---|---|
| Mayor al actual | Se registra y se actualiza el horómetro del equipo |
| **Igual** al actual | Se registra, y el equipo no cambia. *Decisión propia:* dos cargas sin uso entre medio (por ejemplo, el mismo día) es un caso real |
| Menor al actual | Se rechaza con `422` y un mensaje claro |

- **Lógica en un solo lugar:** cargas y mantenciones usan el mismo `HourmeterService`, así que la regla no se duplica.
- **Transacción con bloqueo (`lockForUpdate`):** si llegan dos registros del mismo equipo al mismo tiempo, el segundo espera al primero. Sin esto, ambos podrían leer el horómetro antiguo y pasar la validación.
- **El horómetro no se edita directamente:** solo se fija al crear el equipo (valor inicial). Después lo actualizan las cargas y mantenciones. Editarlo a mano permitiría bajarlo y romper la regla.

### Rendimiento en litros por hora

Se usa el método **"estanque lleno a estanque lleno"**, el estándar en gestión de flotas: los litros de una carga reponen lo que se gastó **desde la carga anterior**. Por eso, para el rendimiento, **no se cuentan los litros de la primera carga del período**: reponen consumo de antes del rango.

**Ejemplo:**

| Carga | Horómetro | Litros | ¿Cuenta para el rendimiento? |
|---|---|---|---|
| 1ª | 1000 | 100 | No: repone consumo anterior al período |
| 2ª | 1050 | 120 | Sí: gastados entre 1000 y 1050 h |
| 3ª | 1100 | 110 | Sí: gastados entre 1050 y 1100 h |

- Horas de uso: 1100 − 1000 = **100 h**
- Rendimiento: (120 + 110) ÷ 100 = **2,3 L/h**
- Sumando también la primera carga daría 3,3 L/h: un 43 % más que el consumo real.

**Litros totales y gasto sí incluyen todas las cargas** (330 L en el ejemplo): es lo que efectivamente se cargó y se pagó.

- **Casos borde:** con menos de 2 cargas, o si el horómetro no avanzó, el rendimiento es `null` y se explica en `note`. Nunca se divide por cero.
- **Supuesto:** cada carga llena el estanque. Si alguna es parcial, esa carga se desvía, pero en períodos largos el error se compensa (todo lo cargado termina gastándose).
- **Rango de fechas:** el día final se incluye completo (hasta las 23:59:59).
- **Consultas eficientes:** las sumas las hace MySQL (`SUM`) en una consulta, sin traer las filas a PHP.

### Mantención preventiva

- Horas desde la última preventiva = horómetro actual − horómetro de la última preventiva (o 0 si nunca tuvo una). Aparecen los equipos con **más de 250 h**, del más urgente al menos urgente.
- Solo cuentan las **preventivas**: una reparación correctiva no reemplaza el mantenimiento programado.
- La última preventiva se obtiene con `MAX(hourmeter)`: como el horómetro nunca baja, la de mayor horómetro es la más reciente. Todo se resuelve en una sola consulta SQL.
- El intervalo de 250 h está en una constante (`Equipment::PREVENTIVE_INTERVAL_HOURS`), no repetido por el código.

### Autenticación

- **Sanctum con tokens:** cada dispositivo (app móvil, Postman, web) recibe su propio token, y el logout revoca solo el de esa sesión.
- **Sin registro público:** en una plataforma B2B, los usuarios los crea un administrador. Para la prueba, el usuario demo viene del seeder.
- El login responde el mismo mensaje si el correo no existe o si la contraseña es incorrecta, para no revelar qué correos están registrados.

### Errores

- Todos los errores de `/api/*` responden en JSON, aunque el cliente no envíe `Accept: application/json`.
- `404` y `401` con mensajes en español, sin exponer clases ni rutas internas.

### Tests

- Corren contra **MySQL** y no contra SQLite: el código usa funciones de MySQL (`lockForUpdate`, `HAVING` sin `GROUP BY`), y los tests deben probar lo mismo que corre en producción.
- Usan una base separada (`construckit_test`) para no borrar los datos de ejemplo.

### Vista web

- `/reporte` es una página Blade con HTML, CSS y JavaScript puro, sin npm ni compilación. Consume la misma API que usaría la app móvil, así que no duplica lógica.
- El token se guarda solo en memoria (al recargar hay que volver a entrar), y los datos se insertan con `textContent` para evitar XSS.

---

## 5. Qué dejé fuera y por qué

Prioricé que lo pedido funcionara bien y estuviera probado, por sobre agregar funcionalidades:

| Qué | Por qué quedó fuera |
|---|---|
| Roles y permisos (supervisor, administrador) | El enunciado pide autenticación simple. Se agregaría con Policies de Laravel |
| Multi-empresa | Cada empresa debería ver solo sus equipos (`company_id` + un scope global). Fuera del alcance de la prueba |
| Editar o eliminar cargas y mantenciones | Implica recalcular el horómetro del equipo y validar contra los registros vecinos. Es lógica delicada, y preferí no hacerla a medias |
| Campo "estanque lleno: sí/no" | Permitiría calcular el rendimiento solo entre cargas completas, más preciso con cargas parciales |
| Intervalo de preventiva por tipo de equipo | Hoy son 250 h para todos. Podría vivir en una tabla de configuración por tipo |
| Token de la vista web en cookie `httpOnly` | Para producción usaría el modo SPA de Sanctum. Para la demo bastaba con mantenerlo en memoria |

---

## 6. Parte 2 · Preguntas cortas

### 1. Despliegue en Google Cloud

Mi experiencia de despliegue es en AWS: desplegaba el backend manualmente en EC2 y usaba S3 para archivos. En Google Cloud he trabajado con OAuth2, Firebase y la publicación de apps Android. Con esa base, propondría:

- **App:** Cloud Run, que ejecuta el contenedor del `Dockerfile` sin administrar servidores y escala solo.
- **Base de datos:** Cloud SQL para MySQL, administrada y con conexión privada a la app.
- **Credenciales:** Secret Manager. El `.env` nunca va al repositorio ni dentro de la imagen, y `APP_DEBUG=false` en producción.
- **Respaldos:** los automáticos diarios de Cloud SQL, con restauración a un punto en el tiempo. Y probaría restaurarlos cada cierto tiempo: un respaldo que nunca se probó no se sabe si sirve.

### 2. Incidente del lunes

1. **Aviso** a los supervisores y equipo que lo estoy revisando y cuándo les doy novedades.
2. **Replico** el error para saber si es la app móvil o el backend.
3. **Reviso si la API responde** (`/health` o `/status`) y leo los logs de Laravel.
4. **Pregunto qué cambió el fin de semana:** un deploy, un certificado SSL vencido, espacio en disco, alguna credencial vencida.
5. **Reviso la base de datos:** conexión y espacio.
6. Si vino de un deploy, **hago rollback** primero. El hotfix desde `main` va después, con calma.
7. **Aviso que está resuelto** y anoto la causa para que no se repita.

Si el error es solo en algunos dispositivos, uso Crashlytics: así encontré, en una app para Argentina, una librería incompatible con una impresora y una versión de Android que no tenia como debuggear.

### 3. Rendimiento del reporte

Primero mediría antes de cambiar nada:

- **`EXPLAIN`** de la consulta, para ver si usa los índices `(equipment_id, fecha)` o recorre toda la tabla. Un error típico que los anula es filtrar con funciones sobre la columna, como `DATE(loaded_at)`.
- Revisaría que las sumas las haga MySQL (`SUM`) y no PHP, cargando miles de filas en memoria.

Si con eso no basta:

- **Una tabla de resumen mensual por equipo:** dos años serían 24 filas en vez de miles.
- **Caché** para períodos cerrados, que ya no cambian.
- Como último recurso, generar el reporte en una cola y avisar al usuario cuando esté listo.

### 4. Soporte TI

Priorizo según **a quién bloquea cada problema y cuánto me toma resolverlo**:

1. **Correo:** la persona no puede trabajar y es un bloqueante. *"Podria ser cambio de password solamente."*
2. **Notebook:** cargador, batería, reinicio forzado. Si es hardware, le consigo un equipo de reemplazo y envío el suyo a servicio técnico. *"Lo reviso apenas termine con el correo; si es grave, comunico a supervisores para conseguir otro equipo."*
3. **Desarrollo:** no bloquea a nadie hoy, pero tiene fecha. Lo trabajo en bloques de tiempo protegidos y aviso **temprano** si hay riesgo de no alcanzar: *"Explico que estuve ocupado con urgencias en la mañana; te confirmo a las 15:00 si mantengo la fecha, de lo contrario postergo la fecha de entrega del desarrollo"*

---

## 7. Uso de IA

Usé un asistente de IA (Claude) como apoyo durante todo el desarrollo: configuración del entorno con Docker, diseño del modelo de datos, programación, depuración de errores, tests y documentación. También me ayudó a documentar la creacion de collection de postman en los cuales tardaria mucho tiempo.

No trabajaba con Laravel desde 2020, así que usé la IA para retomarlo rápido, apoyándome en conceptos que ya manejo (APIs REST, validaciones, ORM, autenticación por tokens). Las decisiones técnicas las revisé y tomé yo, y construí y probé cada parte paso a paso en mi entorno.
