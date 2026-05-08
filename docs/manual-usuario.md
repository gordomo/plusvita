# PlusVita — Manual de Usuario

> Sistema de Gestión Clínica  
> Versión 2025 — Revisión Abril 2026

---

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Acceso al Sistema](#acceso-al-sistema)
3. [Panel Principal (Dashboard)](#panel-principal-dashboard)
4. [Gestión de Pacientes](#gestión-de-pacientes)
5. [Turnos y Agenda](#turnos-y-agenda)
6. [Evoluciones Clínicas](#evoluciones-clínicas)
7. [Kardex de Medicación (Enfermería)](#kardex-de-medicación-enfermería)
8. [Signos Vitales](#signos-vitales)
9. [Habitaciones y Camas](#habitaciones-y-camas)
10. [Informes Mensuales](#informes-mensuales)
11. [Inventario](#inventario)
12. [Reclamos](#reclamos)
13. [Gestión de Personal](#gestión-de-personal)
14. [Obras Sociales](#obras-sociales)
15. [Liquidaciones](#liquidaciones)
16. [Reportes y Exportaciones](#reportes-y-exportaciones)
17. [Administración del Sistema](#administración-del-sistema)

---

## Introducción

**PlusVita** es un sistema de gestión clínica diseñado para administrar pacientes internados y ambulatorios, coordinar la agenda de profesionales, controlar la medicación de enfermería, generar documentación clínica y administrar el personal de la institución.

El sistema diferencia el acceso y las funciones disponibles según el **rol** del usuario que ingresa (médico, enfermero, administrativo, administrador, etc.).

---

## Acceso al Sistema

### Iniciar sesión

1. Ingresar a la URL del sistema desde cualquier navegador moderno.
2. Completar **usuario** (email) y **contraseña**.
3. Hacer clic en **Ingresar**.

### Recuperar contraseña

1. En la pantalla de login, hacer clic en **¿Olvidó su contraseña?**
2. Ingresar el email registrado.
3. Revisar la casilla de correo y seguir el enlace recibido.
4. Ingresar y confirmar la nueva contraseña.

### Cerrar sesión

- Hacer clic en el nombre de usuario (esquina superior derecha) y seleccionar **Cerrar sesión**.
- La sesión se cierra automáticamente después de **20 minutos de inactividad**.

---

## Panel Principal (Dashboard)

Al ingresar, el sistema muestra un panel adaptado al rol del usuario:

| Rol | Información visible |
|---|---|
| **Administrador** | Totales de pacientes, personal, camas ocupadas, accesos rápidos a todas las secciones |
| **Médico** | Pacientes a cargo, próximos turnos, evoluciones pendientes |
| **Enfermero** | Medicaciones del día, signos vitales pendientes, lista de pacientes activos |
| **Administrativo** | Ingresos/egresos recientes, turnos del día, pacientes activos |

Desde el menú lateral (sidebar) se accede a todos los módulos según los permisos del rol.

---

## Gestión de Pacientes

### Ver lista de pacientes

- Menú: **Pacientes → Listado**
- Se muestran los pacientes activos con su estado actual (internado, ambulatorio, de permiso, derivado).
- Se puede buscar por nombre, apellido, DNI o número de historia clínica.

### Crear un paciente nuevo

1. Ir a **Pacientes → Nuevo paciente**.
2. Completar los datos obligatorios:
   - Nombre y Apellido
   - DNI
   - Fecha de ingreso
   - Modalidad: **Internado** o **Ambulatorio**
   - Habitación y cama (solo si es internado)
   - Obra social y número de afiliado
   - Motivo de ingreso
3. Completar datos opcionales: teléfono, email, sistema de emergencia, patología.
4. Hacer clic en **Guardar**.

> El sistema asigna automáticamente un número de **historia clínica** único.

### Ver historia clínica de un paciente

1. Desde el listado, hacer clic en el nombre del paciente.
2. Se despliega su ficha completa con:
   - Datos personales y de ingreso
   - Estado actual
   - Medicación activa
   - Evoluciones registradas
   - Signos vitales
   - Adjuntos
   - Historial de habitaciones

### Editar datos de un paciente

1. Desde la ficha del paciente, hacer clic en **Editar**.
2. Modificar los campos necesarios.
3. Guardar los cambios.

### Derivar un paciente

1. Desde la ficha del paciente, hacer clic en **Derivar**.
2. Confirmar la fecha y motivo de derivación.
3. El paciente queda en estado **Derivado** y se registra en el historial.

### Dar permiso a un paciente

1. Desde la ficha del paciente, hacer clic en **Dar Permiso**.
2. Confirmar la operación.
3. El paciente queda en estado **De permiso** (sale temporalmente sin causar egreso).

### Pasar a ambulatorio

1. Desde la ficha de un paciente internado, hacer clic en **Pasar a Ambulatorio**.
2. El paciente deja la habitación y continúa como seguimiento ambulatorio.

### Registrar egreso

1. Desde la ficha del paciente, hacer clic en **Registrar Egreso**.
2. Completar motivo de egreso y observaciones.
3. Opcionalmente, ingresar la epicrisis.
4. Confirmar. El paciente pasa a estado **Inactivo**.

### Reingreso de paciente

1. Buscar el paciente en el listado (filtrar inactivos si es necesario).
2. Hacer clic en **Reingreso**.
3. Completar los datos del nuevo ingreso (fecha, habitación, motivo).

### Estados de un paciente

| Estado | Descripción |
|---|---|
| **Internado** | Ocupa una habitación y cama activa |
| **Ambulatorio** | Seguimiento sin internación |
| **Ambulatorio presente** | Ambulatorio que asistió en el día |
| **De permiso** | Salida temporal sin egreso definitivo |
| **Derivado** | Transferido a otra institución |
| **Inactivo** | Dado de alta o egresado |

---

## Turnos y Agenda

### Ver agenda

- Menú: **Agenda → Calendario**
- Visualización mensual/semanal/diaria de los turnos por profesional.

### Crear un turno

1. Ir a **Agenda → Nuevo turno**.
2. Seleccionar:
   - Paciente
   - Profesional (doctor)
   - Fecha y hora de inicio y fin
   - Notas opcionales
3. Guardar.

### Buscar turnos

- Menú: **Agenda → Listado**
- Filtrar por profesional, paciente o rango de fechas.

### Notas en un turno

- Desde la ficha del turno, se pueden agregar notas específicas sobre la consulta.

---

## Evoluciones Clínicas

Las evoluciones son el registro cronológico del estado clínico del paciente.

### Crear una evolución

1. Ir a la ficha del paciente → sección **Evoluciones**.
2. Hacer clic en **Nueva evolución**.
3. Completar:
   - Tipo de evolución
   - Descripción clínica
   - Adjuntar archivos si corresponde (estudios, imágenes)
4. **Firmar digitalmente** la evolución (requiere firma registrada).
5. Guardar.

### Ver historial de evoluciones

- En la ficha del paciente, la sección **Evoluciones** muestra todas las entradas ordenadas cronológicamente con el profesional responsable.

### Firma digital

- Para poder firmar evoluciones, el médico debe tener cargada su firma en el sistema.
- Ir a **Mi perfil → Firma digital** para registrar la firma.
- La firma queda asociada a la evolución con nombre, apellido y matrícula.

---

## Kardex de Medicación (Enfermería)

El Kardex es la herramienta principal del personal de enfermería para el control de medicación.

### Acceder al Kardex

- Menú: **Enfermería → Kardex de Medicación**
- Se listan todos los pacientes activos con sus indicaciones de medicación del día.

### Ver medicación de un paciente

1. Seleccionar el paciente en el Kardex.
2. Se despliegan todas las **indicaciones activas** con sus horarios programados.
3. Cada horario muestra si fue **administrado**, **pendiente** o si está dentro de la ventana de administración (±2 horas).

### Registrar administración de medicamento

1. Localizar el medicamento y el horario correspondiente.
2. Hacer clic en **Administrado**.
3. El sistema registra el usuario que administró, la fecha y hora exacta.
4. Opcionalmente agregar observaciones.

### Indicaciones de medicación

Las indicaciones son creadas por los médicos y contienen:
- Medicamento o procedimiento
- Frecuencia de administración
- Duración del tratamiento
- Tipo: medicamento, procedimiento o control

---

## Signos Vitales

### Registrar signos vitales

1. Ir a la ficha del paciente → sección **Signos Vitales**.
2. Seleccionar el **turno**: mañana, tarde o noche.
3. Completar las notas clínicas del turno.
4. Guardar.

> Los signos vitales se registran por turno del día y quedan asociados al enfermero que los registró.

---

## Habitaciones y Camas

### Ver estado de habitaciones

- Menú: **Habitaciones**
- Se muestran todas las habitaciones con cantidad de camas disponibles y ocupadas.

### Agregar habitación

1. Ir a **Habitaciones → Nueva**.
2. Ingresar nombre de la habitación y cantidad de camas disponibles.
3. Guardar.

### Cambiar de habitación a un paciente

1. Desde la ficha del paciente, usar la opción **Cambiar habitación**.
2. Seleccionar la nueva habitación y cama.
3. El sistema registra el movimiento en el historial del paciente.

---

## Informes Mensuales

### Generar informe mensual de un paciente

1. Ir a la ficha del paciente → **Informe Mensual**.
2. Completar los campos del período:
   - Escaras y tipo
   - Requerimientos especiales
   - Equipamiento: oxígeno, ARM/BPAP, traqueotomía
3. Seleccionar el médico responsable.
4. Guardar e imprimir si es necesario.

---

## Inventario

El módulo de inventario permite controlar equipamiento y materiales con seguimiento por código QR.

### Ver inventario

- Menú: **Inventario**
- Lista de todos los ítems con su ubicación actual y estado.

### Agregar ítem

1. Ir a **Inventario → Nuevo ítem**.
2. Completar nombre, tipo, identificador y ubicación inicial.
3. El sistema genera automáticamente un **código QR** único.
4. Opcionalmente subir imagen del ítem.
5. Guardar.

### Registrar movimiento

1. Desde la ficha del ítem, hacer clic en **Registrar Movimiento**.
2. Seleccionar la nueva ubicación.
3. Guardar. El historial queda registrado.

### Escanear QR

- Desde dispositivos móviles se puede escanear el código QR de un ítem para acceder directamente a su ficha.

---

## Reclamos

### Ver reclamos

- Menú: **Reclamos**
- Lista de todos los reclamos con estado (abierto, en gestión, resuelto, cerrado).

### Crear reclamo

1. Ir a **Reclamos → Nuevo**.
2. Asociar al paciente.
3. Describir el reclamo.
4. Guardar.

### Gestionar un reclamo

1. Desde la ficha del reclamo, se pueden agregar **comentarios** de seguimiento.
2. Cambiar el estado según la gestión realizada.

---

## Gestión de Personal

### Ver lista de usuarios (staff)

- Menú: **Personal → Usuarios**
- Lista de todo el personal con rol, estado y datos de contacto.

### Crear usuario

1. Ir a **Personal → Nuevo usuario**.
2. Completar:
   - Nombre, Apellido, DNI
   - Email (será el usuario de login)
   - Teléfono, Legajo
   - Rol asignado
3. Generar contraseña inicial.
4. Guardar.

### Editar usuario

1. Desde el listado, hacer clic en el usuario.
2. Modificar los datos necesarios.
3. Guardar.

### Habilitar/Deshabilitar usuario

- Desde la ficha del usuario, usar el botón **Habilitar** o **Deshabilitar**.
- Un usuario deshabilitado no puede iniciar sesión.

### Contratos de personal

1. Desde la ficha del usuario, ir a la sección **Contrato**.
2. Registrar:
   - Tipo de contrato: directo, prestación, sin contrato
   - Fechas de inicio y vencimiento
   - CBU y concepto
   - Observaciones
3. Guardar.

### Firma digital del usuario

1. Desde la ficha del usuario → **Firma Digital**.
2. Registrar o actualizar la firma escaneada.

### Control de asistencia

- Menú: **Personal → Presentes**
- Registro diario de asistencia del personal.

---

## Obras Sociales

### Ver obras sociales

- Menú: **Obras Sociales**

### Agregar obra social

1. Ir a **Obras Sociales → Nueva**.
2. Ingresar el nombre.
3. Guardar.

Las obras sociales se asocian a los pacientes en el momento del ingreso.

---

## Liquidaciones

- Menú: **Liquidaciones**
- Generación y gestión de liquidaciones de honorarios del personal médico.

---

## Reportes y Exportaciones

### Estadísticas generales

- Menú: **Estadísticas**
- Información consolidada de pacientes, ingresos, egresos, ocupación.

### Exportar a Excel

- En los listados de pacientes, turnos o personal, buscar el botón **Exportar Excel**.
- Se descarga un archivo `.xlsx` con los datos del listado.

### Imprimir documentos

Los siguientes documentos se pueden generar en PDF:
- Historia clínica del paciente
- Informe mensual
- Prescripciones
- Evoluciones con firma digital

Desde la ficha del paciente o del documento, usar el botón **Imprimir**.

---

## Administración del Sistema

> Esta sección es exclusiva para usuarios con rol de **Administrador**.

### Roles

- Menú: **Admin → Roles**
- Un rol es un conjunto de permisos asignado a un grupo de usuarios.
- Roles predefinidos: médico, enfermero, administrativo, mantenimiento, cocina, otros.

**Crear rol:**
1. Ir a **Admin → Roles → Nuevo**.
2. Asignar nombre, descripción y categoría.
3. Seleccionar los permisos que tendrá el rol.
4. Guardar.

### Permisos

- Menú: **Admin → Permisos**
- Los permisos son acciones específicas del sistema (ej.: `patient.view`, `patient.evolve`, `agenda.manage`).
- Cada permiso puede activarse o desactivarse.

### Tipos de consumibles

- Menú: **Admin → Tipos de Consumible**
- Categorías para clasificar medicamentos y materiales.

### Tipos de ítems

- Menú: **Admin → Tipos de Ítem**
- Categorías para el inventario.

### Ubicaciones

- Menú: **Admin → Ubicaciones**
- Lugares físicos de almacenamiento para el inventario.
