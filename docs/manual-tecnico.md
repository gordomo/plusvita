# PlusVita — Manual Técnico

> Documentación para el equipo de desarrollo  
> Versión 2025 — Revisión Abril 2026

---

## Tabla de Contenidos

1. [Visión General del Sistema](#visión-general-del-sistema)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Estructura del Proyecto](#estructura-del-proyecto)
4. [Configuración del Entorno](#configuración-del-entorno)
5. [Base de Datos y Entidades](#base-de-datos-y-entidades)
6. [Controladores y Rutas](#controladores-y-rutas)
7. [Servicios](#servicios)
8. [Sistema de Seguridad y Autenticación](#sistema-de-seguridad-y-autenticación)
9. [Sistema de Roles y Permisos (RBAC)](#sistema-de-roles-y-permisos-rbac)
10. [Formularios](#formularios)
11. [Templates (Vistas)](#templates-vistas)
12. [Generación de Documentos PDF y Excel](#generación-de-documentos-pdf-y-excel)
13. [Envío de Correos](#envío-de-correos)
14. [Uploads y Archivos](#uploads-y-archivos)
15. [Comandos CLI](#comandos-cli)
16. [Migraciones de Base de Datos](#migraciones-de-base-de-datos)
17. [Docker y Despliegue](#docker-y-despliegue)
18. [Flujos de Negocio Clave](#flujos-de-negocio-clave)
19. [Convenciones y Patrones del Proyecto](#convenciones-y-patrones-del-proyecto)
20. [Puntos de Atención para Nuevos Desarrolladores](#puntos-de-atención-para-nuevos-desarrolladores)

---

## Visión General del Sistema

PlusVita es una aplicación web de gestión clínica construida sobre **Symfony 5.1**. Gestiona:

- Pacientes internados y ambulatorios
- Agenda de turnos y profesionales
- Kardex de medicación de enfermería
- Signos vitales por turno
- Historia clínica, evoluciones y documentos
- Control de personal, contratos y asistencia
- Inventario de equipos con trazabilidad QR
- Liquidaciones de honorarios
- Sistema RBAC granular de roles y permisos

---

## Stack Tecnológico

| Componente | Tecnología | Versión |
|---|---|---|
| Lenguaje | PHP | ^7.2.5 |
| Framework | Symfony | 5.1.* |
| ORM | Doctrine ORM | ^2 |
| Motor de BD | MySQL | 8.0+ |
| Motor de plantillas | Twig | ^3 |
| CSS Framework | Bootstrap | 4.5 |
| Tablas interactivas | DataTables | — |
| Calendario | FullCalendar | — |
| PDF | DomPDF / KnpSnappy | ^2.0 / ^1.8 |
| Excel | PHPOffice/PHPSpreadsheet | ^1.17 |
| QR Codes | endroid/qr-code | ^4.3 |
| Mailer | Symfony Mailer (SendGrid/Google) | 5.1.* |
| Contenedor | Docker + Docker Compose | — |

---

## Estructura del Proyecto

```
plusvita/
├── bin/                        # Ejecutables CLI (bin/console)
├── config/                     # Configuración Symfony
│   ├── packages/               # Config de bundles (doctrine, security, mailer...)
│   ├── routes/                 # Definición de rutas adicionales
│   ├── services.yaml           # Inyección de dependencias y parámetros globales
│   └── security.yaml           # Firewalls, proveedores, access control
├── docs/                       # Documentación del proyecto
├── migrations/                 # Migraciones Doctrine (19 versiones)
├── public/                     # Web root
│   ├── index.php               # Front controller
│   └── uploads/                # Archivos subidos por usuarios
│       ├── firmas/             # Firmas digitales de profesionales
│       ├── adjuntos/pacientes/ # Adjuntos de pacientes
│       ├── adjuntos/staff/     # Adjuntos de personal
│       └── items/              # Imágenes y QRs de inventario
├── src/
│   ├── Command/                # Comandos CLI personalizados
│   ├── Controller/             # 38 controladores HTTP
│   ├── Entity/                 # 44 entidades Doctrine
│   ├── Form/                   # 28 Form Types
│   ├── Repository/             # Repositorios Doctrine
│   ├── Security/               # Autenticadores, Voters, Handlers
│   ├── Service/                # Lógica de negocio reutilizable
│   ├── Twig/                   # Extensiones Twig custom
│   └── Validator/              # Validadores custom
├── templates/                  # Vistas Twig (41 módulos)
├── var/                        # Cache y logs (gitignored)
├── vendor/                     # Dependencias Composer (gitignored)
├── composer.json
├── docker-compose.yml
├── Dockerfile
└── symfony.lock
```

---

## Configuración del Entorno

### Variables de entorno (.env)

```dotenv
APP_ENV=prod                    # dev | prod
APP_SECRET=<secret>
DATABASE_URL="mysql://user:pass@127.0.0.1:3306/plusvita"
MAILER_DSN=sendgrid://KEY@default
# o bien:
# MAILER_DSN=gmail://user:pass@default
```

### Parámetros globales (config/services.yaml)

```yaml
parameters:
    firmas_directory: '%kernel.project_dir%/public/uploads/firmas'
    adjuntos_pacientes_directory: '%kernel.project_dir%/public/uploads/adjuntos/pacientes'
    adjuntos_staff_directory: '%kernel.project_dir%/public/uploads/adjuntos/staff'
    items_imagenes_directory: '%kernel.project_dir%/public/uploads/items/imagenes'
    items_qr_directory: '%kernel.project_dir%/public/uploads/items/qr'
    session_max_idle_time: 1200   # segundos (20 minutos)
```

### Instalación inicial

```bash
composer install
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
# Crear usuario admin inicial:
php bin/console app:create-admin  # (si existe el comando)
```

---

## Base de Datos y Entidades

### Diagrama de dominio simplificado

```
User (staff) ─── Role ─── Permission
     │
     ├── Booking (turnos) ─── Cliente (paciente)
     │                              │
     ├── Evolucion                  ├── HistoriaPaciente
     │                              ├── SignosVitales
     ├── UserContract               ├── ConsumiblesClientes ─── HorarioToma
     ├── UserFirma                  │        └── Consumible
     └── PresentesDoctores          ├── Prescripcion
                                    ├── Reclamo ─── ComentarioReclamo
                                    ├── InformeMensual
                                    ├── AdjuntosPacientes
                                    ├── HistoriaIngreso / HistoriaEgreso
                                    └── HistoriaHabitaciones ─── Habitacion

Item ─── TipoItem ─── Ubicacion ─── Movimiento
     └── NotaItem
```

### Entidades principales

#### User (`src/Entity/User.php`)
Representa a todo el personal del sistema. Implementa `UserInterface` de Symfony.

```php
// Campos clave
id, email (unique), username, password (hash)
nombre, apellido, dni, telefono, legajo
habilitado (bool)
modalidad (JSON array)

// Relaciones
roles         → ManyToMany(Role)
bookings      → OneToMany(Booking) [como creador]
doctorBookings → OneToMany(Booking) [como profesional asignado]
firma         → OneToOne(UserFirma)
prescripciones → OneToMany(Prescripcion)
```

> **Nota histórica:** Existe también la entidad `Doctor` (legacy) que fue migrada a `User` en la versión de Nov 2025. No crear nuevos doctores con la entidad `Doctor`; usar `User` con el rol correspondiente.

#### Cliente (`src/Entity/Cliente.php`)
Entidad central del dominio. Representa al paciente.

```php
// Campos clave
id, hClinica (unique — historia clínica)
nombre, apellido, dni, email, telefono
fIngreso (datetime), fEgreso (datetime nullable)
activo (bool)

// Estado de internación
modalidad: 1=ambulatorio, 2=internado
habitacion (string), cama (int)
ambulatorio (bool), derivado (bool)
de_permiso (bool), ambulatorio_presente (bool)

// Relaciones
docReferente     → ManyToMany(User)
historiaClinica  → OneToOne(HistoriaPaciente)
evoluciones      → OneToMany(Evolucion)
signosVitales    → OneToMany(SignosVitales)
medicaciones     → OneToMany(ConsumiblesClientes)
prescripciones   → OneToMany(Prescripcion)
reclamos         → OneToMany(Reclamo)
```

#### ConsumiblesClientes (`src/Entity/ConsumiblesClientes.php`)
Prescripción de medicación a un paciente.

```php
consumibleId, clienteId
fecha (datetime)
tipoIndicacion: "medicamento" | "procedimiento" | "control"
activo (bool)
horarios → OneToMany(HorarioToma)
```

#### HorarioToma (`src/Entity/HorarioToma.php`)
Cada instancia es un horario programado de medicación.

```php
fecha (date), horario (time)
administrado (bool)
administradoPorUserId (int)
observaciones (text nullable)
indicacion → ManyToOne(ConsumiblesClientes)
```

> **Regla de negocio:** La ventana de administración válida es ±2 horas respecto al `horario` programado. El Kardex lo evalúa para mostrar si un turno está "pendiente", "en ventana" o "vencido".

#### SignosVitales (`src/Entity/SignosVitales.php`)
Incorporada en diciembre 2025.

```php
paciente_id (int)
fecha (date)
turno: "mañana" | "tarde" | "noche"
notas (text)
registradoPorUserId (int)
completado (bool)
fechaHoraRegistro (datetime)
```

#### Role y Permission (`src/Entity/Role.php`, `src/Entity/Permission.php`)
Modelo RBAC. Ver sección [Sistema de Roles y Permisos](#sistema-de-roles-y-permisos-rbac).

### Convención de repositorios

Cada entidad tiene un repositorio en `src/Repository/`. Los métodos de consulta complejos deben ir en el repositorio, no en el controlador. Ejemplo:

```php
// src/Repository/ClienteRepository.php
public function findActivosInternados(): array
{
    return $this->createQueryBuilder('c')
        ->where('c.activo = true')
        ->andWhere('c.modalidad = 2')
        ->orderBy('c.apellido', 'ASC')
        ->getQuery()
        ->getResult();
}
```

---

## Controladores y Rutas

### Listado de controladores

| Controlador | Ruta base | Responsabilidad |
|---|---|---|
| `ClienteController` | `/pacientes` | CRUD pacientes, ingresos, egresos, derivaciones, permisos, ambulatorio |
| `BookingController` | `/booking` | CRUD turnos, calendario, filtrado |
| `DashboardController` | `/dashboard` | Panel principal por rol |
| `EvolucionController` | `/evolucion` | Evoluciones clínicas, adjuntos, firma |
| `ConsumibleController` | `/consumible` | CRUD medicamentos y materiales |
| `MedicacionEnfermeriaController` | `/medicacion-enfermeria` | Kardex — listado y registro de tomas |
| `KardexController` | `/kardex` | Acceso al Kardex |
| `UserController` | `/user` | CRUD personal |
| `UserManagementController` | — | Administración avanzada de usuarios |
| `UserContractController` | `/user/contrato` | Contratos de personal |
| `UserFirmaController` | `/user/firma` | Gestión de firmas digitales |
| `UserPresenteController` | `/user/presente` | Control de asistencia |
| `RoleController` | `/admin/roles` | CRUD roles |
| `PermissionController` | `/permission` | CRUD permisos |
| `HabitacionController` | `/habitacion` | CRUD habitaciones y camas |
| `ItemController` | `/item` | CRUD inventario |
| `ImprimirController` | `/imprimir` | Generación PDFs |
| `InformeMensualController` | `/informe-mensual` | Informes mensuales |
| `StatsController` | `/estadisticas` | Estadísticas y reportes |
| `PrescripcionController` | `/prescripcion` | Prescripciones médicas |
| `ReclamoController` | `/reclamo` | Reclamos de pacientes |
| `ObraSocialController` | `/obra-social` | CRUD obras sociales |
| `NurseController` | `/nurse` | CRUD enfermeros (legacy) |
| `DoctorController` | `/doctor` | CRUD doctores (legacy) |
| `LiquidacionesController` | `/liquidaciones` | Liquidaciones de honorarios |
| `SecurityController` | `/login`, `/logout` | Autenticación |
| `ResetPassController` | `/reset-pass` | Recuperación de contraseña |
| `AdjuntosPacientesController` | `/adjuntos/pacientes` | Archivos de pacientes |
| `AdjuntosStaffController` | `/adjuntos/staff` | Archivos de personal |
| `ExportToExcel` | — | Exportaciones Excel |
| `QrConstrollerController` | `/qr` | Generación QR |
| `TipoConsumibleController` | `/tipo-consumible` | Tipos de consumibles |
| `TipoItemController` | `/tipo-item` | Tipos de ítems |
| `UbicacionController` | `/ubicacion` | Ubicaciones de almacenamiento |
| `NovedadUbicacionController` | `/novedad-ubicacion` | Novedades de ubicaciones |
| `NotasHistoriaClinicaController` | `/notas-historia-clinica` | Notas clínicas |
| `NotasTurnoController` | `/notas-turno` | Notas de turnos |

### Rutas clave

```
GET  /pacientes/                    → listado de pacientes
GET  /pacientes/new                 → formulario nuevo paciente
POST /pacientes/new                 → crear paciente
GET  /pacientes/{id}/show           → ficha del paciente
GET  /pacientes/{id}/edit           → editar paciente
POST /pacientes/{id}/derivar        → derivar paciente
POST /pacientes/{id}/darpermiso     → dar permiso
POST /pacientes/{id}/ambulatorio    → pasar a ambulatorio
POST /pacientes/{id}/egreso         → registrar egreso
GET  /booking/calendar              → calendario interactivo
POST /booking/new                   → crear turno
GET  /medicacion-enfermeria/        → kardex principal
POST /evolucion/new/{clienteId}     → nueva evolución
GET  /admin/roles/                  → gestión de roles
```

---

## Servicios

Los servicios encapsulan la lógica de negocio compleja que no pertenece a un controlador único.

### PatientStateService (`src/Service/PatientStateService.php`)
Centraliza todas las transiciones de estado de un paciente.

Métodos principales:
- `internar(Cliente, Habitacion, int $cama)` — internación
- `darEgreso(Cliente, string $motivo)` — egreso
- `derivar(Cliente, \DateTime $fecha)` — derivación
- `darPermiso(Cliente)` — permiso temporal
- `pasarAmbulatorio(Cliente)` — cambio a ambulatorio

> Siempre usar este servicio para cambiar el estado de un paciente. No modificar los campos directamente desde el controlador.

### HorarioTomaCalculatorService (`src/Service/HorarioTomaCalculatorService.php`)
Calcula los horarios de toma según la frecuencia indicada en `ConsumiblesClientes`.

### AuthorizationService (`src/Service/AuthorizationService.php`)
Asignación y verificación de permisos a usuarios. Coordina con el `PermissionVoter`.

### HabitacionService (`src/Service/HabitacionService.php`)
Verifica disponibilidad de camas y gestiona asignaciones.

### TurnoService (`src/Service/TurnoService.php`)
Creación y validación de turnos. Detecta conflictos de horario.

### UserEvolutionService (`src/Service/UserEvolutionService.php`)
Determina si un usuario tiene permiso para crear/editar evoluciones de un paciente específico.

### ResetPasswordMailerService (`src/Service/ResetPasswordMailerService.php`)
Genera token de reset y envía el correo de recuperación de contraseña.

---

## Sistema de Seguridad y Autenticación

### Archivos relevantes

```
src/Security/
├── LoginFormAuthenticator.php     # Guard authenticator con formulario
├── MultiEntityUserProvider.php    # Proveedor que resuelve User, Doctor, Nurse
├── SessionIdleHandler.php         # Cierre por inactividad
├── AccessDeniedHandler.php        # Redirección en 403
└── Voter/
    └── PermissionVoter.php        # Voter de permisos granulares
```

### Proveedor multi-entidad

`MultiEntityUserProvider` permite que el sistema autentique con tres entidades distintas: `User`, `Doctor` (legacy), y `Nurse`. Al hacer login, busca el email en las tres tablas en orden.

> En nuevas funcionalidades, asumir que todos los usuarios son `User`. `Doctor` y `Nurse` son entidades legacy mantenidas por compatibilidad.

### Sesión por inactividad

`SessionIdleHandler` implementa `KernelEvents::REQUEST` y cierra la sesión si el tiempo desde la última actividad supera `session_max_idle_time` (1200 segundos = 20 minutos).

### Voter de permisos

`PermissionVoter` evalúa atributos del tipo `permission.nombre` contra los permisos asignados al rol del usuario. Uso en controladores:

```php
$this->denyAccessUnlessGranted('permission.patient.evolve');
// o bien:
if (!$this->isGranted('permission.agenda.manage')) { ... }
```

### Security.yaml (resumen)

```yaml
security:
    firewalls:
        main:
            guard:
                authenticators: [App\Security\LoginFormAuthenticator]
            logout:
                path: app_logout
            remember_me: ~
    access_control:
        - { path: ^/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/, roles: ROLE_USER }
```

---

## Sistema de Roles y Permisos (RBAC)

### Modelo

```
User → tiene → Role(s) → tiene → Permission(s)
```

- Un usuario puede tener múltiples roles.
- Un rol tiene múltiples permisos.
- Los permisos tienen nombres únicos en formato `dominio.accion` (ej: `patient.view`, `agenda.manage`).

### Categorías de roles predefinidas

| Categoría | Descripción |
|---|---|
| MEDICAL | Médicos y profesionales de salud |
| NURSING | Enfermeros y técnicos |
| ADMINISTRATIVE | Personal administrativo |
| MAINTENANCE | Mantenimiento |
| KITCHEN | Cocina |
| OTHER | Otros roles |

### Roles de sistema

Los roles marcados como `isSystem = true` no pueden eliminarse. Lo mismo aplica a los permisos de sistema.

### Agregar un nuevo permiso

1. Crear el permiso en la base de datos (desde la UI o por migración).
2. Asignarlo al/los rol(es) correspondientes.
3. En el controlador/servicio, proteger la acción:
   ```php
   $this->denyAccessUnlessGranted('permission.nuevo.permiso');
   ```
4. En Twig, proteger elementos de la UI:
   ```twig
   {% if is_granted('permission.nuevo.permiso') %}
       <a href="...">Acción</a>
   {% endif %}
   ```

---

## Formularios

Los Form Types están en `src/Form/`. Siguen la convención de Symfony con `buildForm()` y `configureOptions()`.

### Formularios principales

| Form Type | Entidad | Uso |
|---|---|---|
| `ClienteType` | Cliente | Creación/edición de pacientes |
| `BookingType` | Booking | Turnos |
| `EvolucionType` | Evolucion | Evoluciones clínicas |
| `ConsumibleType` | Consumible | Medicamentos |
| `UserType` | User | Personal |
| `RoleType` | Role | Roles RBAC |
| `PermissionType` | Permission | Permisos RBAC |
| `UserContractType` | UserContract | Contratos |
| `InformeMensualType` | InformeMensual | Informes mensuales |
| `ReingresoType` | Cliente | Reingreso de pacientes |

### Convención

Todos los formularios usan `data_class` mapeado a la entidad. Los campos con lógica de transformación usan `DataTransformer` o el evento `PRE_SET_DATA`.

---

## Templates (Vistas)

```
templates/
├── base.html.twig             # Layout principal: navbar + sidebar + content block
├── navbar.html.twig           # Barra superior: usuario, logout
├── sidebar.html.twig          # Menú lateral: ítems según rol
├── dashboard/
├── cliente/                   # Vistas de pacientes
│   ├── index.html.twig        # Listado
│   ├── historia.html.twig     # Ficha completa del paciente
│   ├── historico.html.twig    # Historial
│   └── ...modales, parciales
├── booking/
├── evolucion/
├── medicacion_enfermeria/     # Kardex
├── habitacion/
├── item/                      # Inventario
├── user/
├── role/
├── permission/
├── imprimir/                  # Templates para PDFs (sin navbar/sidebar)
└── ...
```

### Convenciones de templates

- Heredan de `base.html.twig` con `{% extends 'base.html.twig' %}`.
- Bloque principal: `{% block body %}`.
- Las vistas de impresión/PDF tienen su propio layout sin assets de UI.
- Las acciones peligrosas (eliminar, derivar, egresar) usan modales de confirmación Bootstrap.

---

## Generación de Documentos PDF y Excel

### PDF con DomPDF

```php
// Uso en controlador (ej: ImprimirController)
use Dompdf\Dompdf;

$dompdf = new Dompdf();
$html = $this->renderView('imprimir/historia.html.twig', ['cliente' => $cliente]);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('historia-clinica.pdf', ['Attachment' => false]);
exit;
```

### PDF con KnpSnappy (wkhtmltopdf)

Alternativa para documentos más complejos. Requiere que `wkhtmltopdf` esté instalado (el `.deb` está en la raíz del proyecto).

```php
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;

return new PdfResponse(
    $this->knpSnappy->getOutputFromHtml($html),
    'documento.pdf'
);
```

### Excel con PHPSpreadsheet

```php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Nombre');
// ... llenar datos

$writer = new Xlsx($spreadsheet);
$response = new StreamedResponse(function() use ($writer) {
    $writer->save('php://output');
});
$response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$response->headers->set('Content-Disposition', 'attachment;filename="export.xlsx"');
return $response;
```

---

## Envío de Correos

El sistema usa Symfony Mailer con soporte para SendGrid y Gmail.

```php
// src/Service/ResetPasswordMailerService.php
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

$email = (new Email())
    ->from('noreply@plusvita.com')
    ->to($user->getEmail())
    ->subject('Recuperación de contraseña')
    ->html($this->twig->render('emails/reset_password.html.twig', [...]));

$this->mailer->send($email);
```

Configurar el DSN en `.env`:
```
MAILER_DSN=sendgrid://API_KEY@default
# o:
MAILER_DSN=gmail://user%40gmail.com:password@default
```

---

## Uploads y Archivos

Los archivos se guardan en `public/uploads/` con subdirectorios por tipo. Los paths se configuran como parámetros en `services.yaml` (ver sección de configuración).

### Patrón de upload en controlador

```php
$file = $form->get('firma')->getData();
if ($file) {
    $filename = md5(uniqid()) . '.' . $file->guessExtension();
    $file->move($this->getParameter('firmas_directory'), $filename);
    $entity->setFirmaPath($filename);
}
```

### Acceso a archivos en templates

```twig
<img src="{{ asset('uploads/firmas/' ~ firma.path) }}">
```

---

## Comandos CLI

Los comandos están en `src/Command/`. Se ejecutan con `php bin/console <nombre>`.

```bash
php bin/console app:migrate-doctors   # Migrar doctores legacy a User
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
php bin/console cache:warmup
```

---

## Migraciones de Base de Datos

### Historial de migraciones (resumen)

| Versión | Descripción |
|---|---|
| 20250101000000 | Schema inicial |
| 20250822104737 | Setup base del sistema |
| 20250828152759 | Cambios estructurales iniciales |
| 20250905151028 | Sistema de permisos y roles |
| 20251002182626 | Actualizaciones de dominio |
| 20251118182815 | **Migración Doctor → User**, campo DNI en User |
| 20251118191634 | Cambios en user_contract |
| 20251128173640 | Epicrisis nullable en HistoriaPaciente |
| 20251215102959 | **Nueva tabla signos_vitales** |

### Crear nueva migración

```bash
# Generar desde cambios en entidades:
php bin/console doctrine:migrations:diff

# Ejecutar migraciones pendientes:
php bin/console doctrine:migrations:migrate

# Ver estado:
php bin/console doctrine:migrations:status
```

> Nunca editar una migración ya ejecutada en producción. Crear siempre una nueva.

---

## Docker y Despliegue

### docker-compose.yml (estructura)

```yaml
services:
  app:
    build: .
    ports: ["80:80"]
    volumes: [".:/var/www/html"]
    depends_on: [db]
  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: plusvita
      MYSQL_USER: user
      MYSQL_PASSWORD: pass
```

### Dockerfile

Basado en PHP + Apache. Incluye:
- Extensiones PHP: pdo_mysql, gd, zip, mbstring, intl
- Composer
- wkhtmltopdf (`.deb` en raíz del proyecto)
- Entrypoint en `entrypoint.sh`

### Despliegue en producción

```bash
docker-compose up -d
docker-compose exec app composer install --no-dev --optimize-autoloader
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker-compose exec app php bin/console cache:warmup
```

### Carpeta conf/

Contiene configuración de servidor web (Apache/Nginx). El archivo `symfony.conf` define el VirtualHost.

---

## Flujos de Negocio Clave

### 1. Ingreso de paciente

```
Admin crea Cliente
  → selecciona modalidad (internado/ambulatorio)
  → si internado: asigna habitacion+cama via HabitacionService
  → PatientStateService.internar()
  → se crea HistoriaPaciente
  → se registra HistoriaIngreso
  → paciente queda activo con estado internado
```

### 2. Ciclo de medicación (Kardex)

```
Médico crea ConsumiblesClientes para un paciente
  → HorarioTomaCalculatorService genera HorarioToma(s)
  → Enfermero accede al Kardex
  → Para cada HorarioToma en ventana (±2h): puede marcar "administrado"
  → Se registra administradoPorUserId + timestamp
```

### 3. Derivación de paciente

```
Admin ejecuta derivar(Cliente)
  → PatientStateService.derivar()
  → Cliente.derivado = true, derivacion_fecha = ahora
  → HistoriaPaciente.derivado = true
  → paciente sigue activo pero en estado derivado
```

### 4. Egreso de paciente

```
Admin ejecuta egreso(Cliente)
  → PatientStateService.darEgreso()
  → Cliente.activo = false, fEgreso = ahora
  → se libera habitacion/cama
  → se registra HistoriaEgreso
  → medicaciones activas se desactivan
```

### 5. Autorización granular

```
Request → Symfony Firewall → LoginFormAuthenticator
  → usuario autenticado con ROLE_USER
  → controlador llama denyAccessUnlessGranted('permission.X')
  → PermissionVoter verifica User→Role→Permission
  → si no tiene: AccessDeniedHandler redirige con mensaje
```

---

## Convenciones y Patrones del Proyecto

### Nomenclatura

- **Entidades:** PascalCase singular (`Cliente`, `ConsumiblesClientes`)
- **Controladores:** `NombreController.php`
- **Servicios:** `NombreService.php`
- **Repositorios:** `NombreRepository.php`
- **Form Types:** `NombreType.php`
- **Templates:** snake_case en carpetas, kebab-case en archivos (`historia_clinica.html.twig`)

### Persistencia

Siempre usar el EntityManager inyectado, no la clase estática:

```php
// Correcto
public function __construct(EntityManagerInterface $em) { $this->em = $em; }
$this->em->persist($entity);
$this->em->flush();

// Incorrecto
EntityManager::getInstance()->persist($entity);
```

### Respuestas JSON en controladores

Para respuestas AJAX usar `JsonResponse`:

```php
return $this->json(['success' => true, 'data' => $result]);
```

### Flash messages

```php
$this->addFlash('success', 'Paciente creado correctamente.');
$this->addFlash('error', 'No se pudo completar la operación.');
```

En template:
```twig
{% for message in app.flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}
```

---

## Puntos de Atención para Nuevos Desarrolladores

### Entidades legacy

- `Doctor` y `Nurse` existen por compatibilidad con datos históricos. Todo nuevo personal debe crearse como `User`.
- La migración de doctores a usuarios se realizó en Nov 2025. Puede haber datos en ambas tablas.

### PatientStateService es obligatorio

No modificar directamente los campos de estado de `Cliente` (activo, derivado, de_permiso, modalidad, habitacion, cama). Siempre pasar por `PatientStateService` para garantizar consistencia y registro de historial.

### Ventana de administración de medicación

La ventana de ±2 horas en `HorarioToma` es una regla de negocio crítica. El Kardex la usa para determinar si un turno puede registrarse. No alterarla sin consultar con los responsables clínicos.

### Carga de archivos en producción

Los uploads van a `public/uploads/`. En Docker, este directorio debe estar montado como volumen persistente para que sobreviva redeployments.

### Roles de sistema

Los roles y permisos con `isSystem = true` están hardcodeados como base del sistema. No eliminarlos ni modificarlos por migración sin análisis previo de impacto.

### Sesión y autenticación multi-entidad

`MultiEntityUserProvider` busca en 3 tablas al autenticar. Si hay emails duplicados entre `user`, `doctor` y `nurse`, puede haber comportamiento inesperado. Al crear usuarios, validar unicidad cross-tabla.

### wkhtmltopdf en Docker

El binario `.deb` de wkhtmltopdf está en la raíz del proyecto y se instala en el Dockerfile. Si hay problemas con la generación de PDFs, verificar que esté instalado correctamente en el contenedor:
```bash
docker-compose exec app wkhtmltopdf --version
```

### Migraciones con datos

Algunas migraciones (ej: 20251118182815) incluyen lógica de migración de datos además de schema. Revisar siempre el contenido de la migración antes de ejecutar en producción.

### Bootstrap 4 (no 5)

El proyecto usa Bootstrap **4.5**. No usar clases de Bootstrap 5 que no existen en 4 (ej: `g-3` → usar `row` + margen manual).
