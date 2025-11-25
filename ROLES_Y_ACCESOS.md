# Sistema de Roles y Accesos Web

## Definición de Roles del Sistema

El sistema implementa una estructura jerárquica de roles basada en IDs numéricos que definen los permisos y accesos de cada usuario dentro de la plataforma web.

### Tabla de Roles

| ID | Rol                              | Descripción | Tipo de Acceso |
|----|----------------------------------|-------------|----------------|
| 1  | Administrador del Sistema (ROOT) | Acceso completo y absoluto a todas las funcionalidades del sistema | Superadministrador |
| 2  | Administrador Académico          | Gestión académica completa, usuarios y configuración general | Administrador |
| 3  | Jefe de Departamento             | Administración de su departamento específico | Gestor |
| 4  | Coordinador de Carrera           | Administración de sus carreras asignadas | Gestor |
| 5  | Profesor (Docente)               | Gestión de clases, asistencias y funciones académicas básicas | Docente |
| 6  | Estudiante                       | Consulta de catálogo de aulas y registro de asistencias | Estudiante |
| 7  | Invitado                         | Acceso de solo lectura a funcionalidades limitadas | Invitado (sin acceso web) |

---

## Detalle por Rol

### 🔴 1. Administrador del Sistema (ROOT)

**Descripción:** Superadministrador con acceso total a todas las funcionalidades del sistema sin restricciones.

**Acceso a Rutas Web:**
- ✅ `/dashboard` - Panel principal
- ✅ `/dashboard/catalogo` - Catálogo de aulas
- ✅ `/dashboard/disponibilidad` - Disponibilidad de aulas
- ✅ `/dashboard/horarios` - Gestión de horarios
- ✅ `/dashboard/tipos-recursos` - Tipos de recursos
- ✅ `/dashboard/sesiones-clase` - Sesiones de clase
- ✅ `/dashboard/grupos` - Gestión de grupos
- ✅ `/dashboard/consultar-disponibilidad` - Consulta disponibilidad
- ✅ `/dashboard/mi-historial-aulas` - Historial de aulas
- ✅ `/dashboard/departamentos` - Gestión de departamentos
- ✅ `/dashboard/docentes` - Gestión de docentes
- ✅ `/dashboard/estudiantes` - Gestión de estudiantes
- ✅ `/dashboard/solicitudes-inscripcion` - Solicitudes de inscripción
- ✅ `/dashboard/roles` - **EXCLUSIVO:** Gestión de roles del sistema
- ✅ `/dashboard/materias` - Gestión de materias
- ✅ `/dashboard/informes` - Gestión de reportes
- ✅ `/dashboard/notificaciones` - Centro de notificaciones
- ✅ `/dashboard/sugerencia-aula` - Sugerencias de aulas
- ✅ `/dashboard/mantenimientos` - Gestión de mantenimientos
- ✅ `/dashboard/incidencias` - Gestión de incidencias

**Funcionalidades Clave:**
- Configuración global del sistema
- Gestión de roles y permisos
- Acceso a todos los reportes y estadísticas
- Administración completa de usuarios y recursos

---

### 🟠 2. Administrador Académico

**Descripción:** Administrador con control completo sobre la gestión académica y recursos del sistema.

**Acceso a Rutas Web:**
- ✅ `/dashboard` - Panel principal
- ✅ `/dashboard/disponibilidad` - Disponibilidad de aulas
- ✅ `/dashboard/horarios` - Gestión de horarios
- ✅ `/dashboard/tipos-recursos` - Tipos de recursos
- ✅ `/dashboard/sesiones-clase` - Sesiones de clase
- ✅ `/dashboard/grupos` - Gestión de grupos
- ✅ `/dashboard/departamentos` - Gestión de departamentos
- ✅ `/dashboard/docentes` - Gestión de docentes
- ✅ `/dashboard/estudiantes` - Gestión de estudiantes
- ✅ `/dashboard/solicitudes-inscripcion` - Solicitudes de inscripción
- ✅ `/dashboard/materias` - Gestión de materias
- ✅ `/dashboard/informes` - Gestión de reportes
- ✅ `/dashboard/notificaciones` - Centro de notificaciones
- ✅ `/dashboard/sugerencia-aula` - Sugerencias de aulas
- ✅ `/dashboard/mantenimientos` - Gestión de mantenimientos
- ✅ `/dashboard/incidencias` - Gestión de incidencias

**Restricciones:**
- ❌ `/dashboard/roles` - No puede gestionar roles del sistema
- ❌ `/dashboard/catalogo` - No acceso a catálogo (solo ROOT y Estudiantes)

**Funcionalidades Clave:**
- Gestión académica completa
- Administración de usuarios (docentes y estudiantes)
- Control de horarios y recursos
- Reportes académicos

---

### 🟡 3. Gestor de Departamento

**Descripción:** Administrador responsable de gestionar su departamento académico específico.

**Acceso a Rutas Web:**
- ✅ `/dashboard` - Panel principal
- ✅ `/dashboard/disponibilidad` - Disponibilidad de aulas
- ✅ `/dashboard/sesiones-clase` - Sesiones de clase
- ✅ `/dashboard/materias` - Gestión de materias
- ✅ `/dashboard/notificaciones` - Centro de notificaciones
- ✅ `/dashboard/mantenimientos` - Gestión de mantenimientos
- ✅ `/dashboard/incidencias` - Gestión de incidencias

**Restricciones:**
- ❌ `/dashboard/catalogo` - Sin acceso a catálogo
- ❌ `/dashboard/horarios` - No puede gestionar horarios
- ❌ `/dashboard/tipos-recursos` - No puede administrar tipos de recursos
- ❌ `/dashboard/grupos` - No puede gestionar grupos
- ❌ `/dashboard/departamentos` - No puede administrar departamentos
- ❌ `/dashboard/docentes` - No puede gestionar docentes
- ❌ `/dashboard/estudiantes` - No puede gestionar estudiantes
- ❌ `/dashboard/solicitudes-inscripcion` - No puede manejar inscripciones
- ❌ `/dashboard/roles` - Sin acceso a gestión de roles
- ❌ `/dashboard/informes` - No puede acceder a informes
- ❌ `/dashboard/sugerencia-aula` - No puede hacer sugerencias

**Funcionalidades Clave:**
- Gestión de materias del departamento
- Control de disponibilidad de aulas
- Gestión de mantenimientos del departamento
- Reportes de incidencias

---

### 🟢 4. Gestor de Carrera

**Descripción:** Administrador encargado de gestionar las carreras académicas asignadas.

**Acceso a Rutas Web:**
- ✅ `/dashboard` - Panel principal
- ✅ `/dashboard/disponibilidad` - Disponibilidad de aulas
- ✅ `/dashboard/sesiones-clase` - Sesiones de clase
- ✅ `/dashboard/materias` - Gestión de materias
- ✅ `/dashboard/notificaciones` - Centro de notificaciones
- ✅ `/dashboard/incidencias` - Gestión de incidencias

**Restricciones:**
- ❌ `/dashboard/catalogo` - Sin acceso a catálogo
- ❌ `/dashboard/horarios` - No puede gestionar horarios
- ❌ `/dashboard/tipos-recursos` - No puede administrar tipos de recursos
- ❌ `/dashboard/grupos` - No puede gestionar grupos
- ❌ `/dashboard/departamentos` - No puede administrar departamentos
- ❌ `/dashboard/docentes` - No puede gestionar docentes
- ❌ `/dashboard/estudiantes` - No puede gestionar estudiantes
- ❌ `/dashboard/solicitudes-inscripcion` - No puede manejar inscripciones
- ❌ `/dashboard/roles` - Sin acceso a gestión de roles
- ❌ `/dashboard/informes` - No puede acceder a informes
- ❌ `/dashboard/sugerencia-aula` - No puede hacer sugerencias
- ❌ `/dashboard/mantenimientos` - No puede gestionar mantenimientos

**Funcionalidades Clave:**
- Gestión de materias por carrera
- Consulta de disponibilidad de aulas
- Control de sesiones de clase
- Reporte de incidencias

---

### 🔵 5. Profesor (Docente)

**Descripción:** Docente con acceso a funciones académicas y gestión de sus clases.

**Acceso a Rutas Web:**
- ✅ `/dashboard` - Panel principal
- ✅ `/dashboard/sesiones-clase` - Sesiones de clase
- ✅ `/dashboard/consultar-disponibilidad` - **EXCLUSIVO:** Consultar disponibilidad de aulas
- ✅ `/dashboard/mi-historial-aulas` - **EXCLUSIVO:** Historial personal de uso de aulas
- ✅ `/dashboard/solicitudes-inscripcion` - Solicitudes de inscripción
- ✅ `/dashboard/notificaciones` - Centro de notificaciones
- ✅ `/dashboard/sugerencia-aula` - Sugerencias de aulas
- ✅ `/dashboard/incidencias` - Gestión de incidencias

**Restricciones:**
- ❌ `/dashboard/catalogo` - Sin acceso a catálogo
- ❌ `/dashboard/disponibilidad` - No puede gestionar disponibilidad (solo consultar)
- ❌ `/dashboard/horarios` - No puede gestionar horarios
- ❌ `/dashboard/tipos-recursos` - No puede administrar tipos de recursos
- ❌ `/dashboard/grupos` - No puede gestionar grupos
- ❌ `/dashboard/departamentos` - No puede administrar departamentos
- ❌ `/dashboard/docentes` - No puede gestionar otros docentes
- ❌ `/dashboard/estudiantes` - No puede gestionar estudiantes
- ❌ `/dashboard/roles` - Sin acceso a gestión de roles
- ❌ `/dashboard/materias` - No puede gestionar materias
- ❌ `/dashboard/informes` - No puede acceder a informes
- ❌ `/dashboard/mantenimientos` - No puede gestionar mantenimientos

**Funcionalidades Clave:**
- Iniciar y finalizar sesiones de clase
- Consultar disponibilidad de aulas para planificación
- Ver historial de uso de aulas personales
- Aprobar/rechazar solicitudes de inscripción /?
- Reportar incidencias en aulas

---

### 🟣 6. Estudiante

**Descripción:** Alumno con acceso limitado a funciones académicas básicas y catálogo.

**Acceso a Rutas Web:**
- ✅ `/dashboard` - Panel principal
- ✅ `/dashboard/catalogo` - **EXCLUSIVO:** Catálogo de aulas (compartido con ROOT)
- ✅ `/dashboard/notificaciones` - Centro de notificaciones

**Restricciones:**
- ❌ `/dashboard/disponibilidad` - No puede gestionar disponibilidad
- ❌ `/dashboard/horarios` - No puede gestionar horarios
- ❌ `/dashboard/tipos-recursos` - No puede administrar tipos de recursos
- ❌ `/dashboard/sesiones-clase` - No puede gestionar sesiones
- ❌ `/dashboard/grupos` - No puede gestionar grupos
- ❌ `/dashboard/departamentos` - No puede administrar departamentos
- ❌ `/dashboard/docentes` - No puede gestionar docentes
- ❌ `/dashboard/estudiantes` - No puede gestionar otros estudiantes
- ❌ `/dashboard/solicitudes-inscripcion` - No puede manejar inscripciones
- ❌ `/dashboard/roles` - Sin acceso a gestión de roles
- ❌ `/dashboard/materias` - No puede gestionar materias
- ❌ `/dashboard/informes` - No puede acceder a informes
- ❌ `/dashboard/sugerencia-aula` - No puede hacer sugerencias
- ❌ `/dashboard/mantenimientos` - No puede gestionar mantenimientos
- ❌ `/dashboard/incidencias` - No puede gestionar incidencias
- ❌ `/dashboard/consultar-disponibilidad` - No puede consultar disponibilidad
- ❌ `/dashboard/mi-historial-aulas` - No tiene historial de aulas

**Funcionalidades Clave:**
- Consultar catálogo de aulas disponibles
- Recibir notificaciones del sistema
- Registro de asistencias (vía móvil/QR)

---

### ⚪ 7. Invitado

**Descripción:** Usuario temporal con acceso muy limitado, principalmente para funciones de consulta básica.

**Acceso a Rutas Web:**
- ❌ **Sin acceso a rutas protegidas** - El rol de invitado está diseñado principalmente para la aplicación móvil o acceso temporal sin funciones web

**Nota:** El rol de invitado (7) está principalmente diseñado para acceso temporal en la aplicación móvil y no tiene acceso a las rutas web protegidas del dashboard.

---

## Matriz de Accesos Web

| Ruta Web | ROOT (1) | Admin Académico (2) | Gestor Depto (3) | Gestor Carrera (4) | Docente (5) | Estudiante (6) | Invitado (7) |
|----------|----------|---------------------|------------------|-------------------|-------------|----------------|--------------|
| `/dashboard` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| `/dashboard/catalogo` | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `/dashboard/disponibilidad` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `/dashboard/horarios` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `/dashboard/tipos-recursos` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `/dashboard/sesiones-clase` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| `/dashboard/grupos` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `/dashboard/consultar-disponibilidad` | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| `/dashboard/mi-historial-aulas` | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| `/dashboard/departamentos` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `/dashboard/docentes` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `/dashboard/estudiantes` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `/dashboard/solicitudes-inscripcion` | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| `/dashboard/roles` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `/dashboard/materias` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `/dashboard/informes` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `/dashboard/notificaciones` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| `/dashboard/sugerencia-aula` | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| `/dashboard/mantenimientos` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `/dashboard/incidencias` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |

---

## Rutas Públicas

Las siguientes rutas son accesibles sin autenticación:

- ✅ `/` - Página de bienvenida (pública)
- ✅ `/login` - Formulario de login (redirige si ya está autenticado)
- ✅ Rutas de recuperación de contraseña (`/reset-password/*`)

---

## Sistema de Navegación

El menú lateral de navegación en `MainLayoutDashboard.vue` se ajusta dinámicamente según el rol del usuario, mostrando únicamente las opciones correspondientes a sus permisos. El sistema verifica:

1. **Autenticación:** Todas las rutas del dashboard requieren autenticación Passport
2. **Role-based Access:** Middleware `role:1,2,etc.` restringe acceso por rol
3. **Menú Dinámico:** El frontend filtra opciones según `user.role_id`
4. **Redirección Inteligente:** Sistema mantiene ruta original durante login

---

## Notas Importantes

- **Middleware de Autenticación:** Todas las rutas protegidas usan `auth.passport`
- **Sistema de Roles:** Implementado mediante middleware `role` con IDs numéricos
- **Menú Contextual:** La navegación se adapta según los permisos del usuario
- **Rutas con Alias:** Existen rutas de compatibilidad que redirigen a URLs con prefijo `/dashboard/`
- **Acceso Invitado:** El rol 7 (Invitado) está diseñado principalmente para la aplicación móvil

---

**Última actualización:** Diciembre 2024
**Versión:** Sistema Web v1.0
**Framework:** Laravel 12 + Vue.js 3 + Inertia.js
