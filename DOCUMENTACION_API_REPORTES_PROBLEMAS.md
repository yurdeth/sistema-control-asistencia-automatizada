# API - Reportes de Problemas en Aulas

Documentación completa de los endpoints para el sistema de reportes de problemas en aulas (recursos defectuosos, códigos QR dañados, limpieza, infraestructura, etc.).

## Tabla de Contenidos
- [Autenticación](#autenticación)
- [Categorías Disponibles](#categorías-disponibles)
- [Estados Disponibles](#estados-disponibles)
- [Endpoints](#endpoints)
  - [Catálogos y Estadísticas](#catálogos-y-estadísticas)
    - [Obtener Lista de Categorías](#1-obtener-lista-de-categorías)
    - [Obtener Lista de Estados](#2-obtener-lista-de-estados)
    - [Obtener Estadísticas de Reportes](#3-obtener-estadísticas-de-reportes)
  - [CRUD y Operaciones](#crud-y-operaciones)
    - [Crear Reporte](#4-crear-reporte)
    - [Listar Todos los Reportes](#5-listar-todos-los-reportes)
    - [Ver un Reporte Específico](#6-ver-un-reporte-específico)
    - [Obtener Reportes por Aula](#7-obtener-reportes-por-aula)
    - [Obtener Reportes por Estado](#8-obtener-reportes-por-estado)
    - [Obtener Reportes por Categoría](#9-obtener-reportes-por-categoría)
    - [Obtener Mis Reportes](#10-obtener-mis-reportes)
    - [Cambiar Estado de un Reporte](#11-cambiar-estado-de-un-reporte)
    - [Asignar Usuario a un Reporte](#12-asignar-usuario-a-un-reporte)
    - [Marcar Reporte como Resuelto](#13-marcar-reporte-como-resuelto)

---

## Autenticación

Todos los endpoints requieren autenticación mediante **Bearer Token** (Laravel Passport).

**Header requerido:**
```
Authorization: Bearer {token}
```

---

## Categorías Disponibles

| Valor | Descripción |
|-------|-------------|
| `recurso_defectuoso` | Problemas con recursos del aula (proyector, aire acondicionado, pizarra, etc.) |
| `qr_danado` | Código QR ilegible, rayado o dañado |
| `limpieza` | Problemas de limpieza en el aula |
| `infraestructura` | Daños en infraestructura (puertas, ventanas, paredes, etc.) |
| `conectividad` | Problemas de internet o conectividad |
| `otro` | Otros problemas no categorizados |

---

## Estados Disponibles

| Estado | Descripción |
|--------|-------------|
| `reportado` | Reporte recién creado (estado inicial) |
| `en_revision` | El reporte está siendo revisado por un gestor |
| `asignado` | Se asignó un responsable para resolver el problema |
| `en_proceso` | El problema está siendo atendido actualmente |
| `resuelto` | El problema fue solucionado |
| `rechazado` | El reporte fue rechazado (no procede) |
| `cerrado` | El reporte fue cerrado (finalizado sin resolución) |

---

## Endpoints

### Catálogos y Estadísticas

---

### 1. Obtener Lista de Categorías

Obtiene el catálogo completo de categorías disponibles para reportes. Útil para poblar dropdowns/selects en la interfaz.

**Endpoint:** `GET /api/classroom-reports/catalogs/categories`

**Acceso:** Todos los usuarios autenticados

#### Parámetros de Entrada

Ninguno.

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "data": [
        {
            "value": "recurso_defectuoso",
            "label": "Recurso Defectuoso",
            "descripcion": "Problemas con recursos del aula (proyector, aire acondicionado, pizarra, etc.)"
        },
        {
            "value": "qr_danado",
            "label": "Código QR Dañado",
            "descripcion": "Código QR ilegible, rayado o dañado"
        },
        {
            "value": "limpieza",
            "label": "Limpieza",
            "descripcion": "Problemas de limpieza en el aula"
        },
        {
            "value": "infraestructura",
            "label": "Infraestructura",
            "descripcion": "Daños en infraestructura (puertas, ventanas, paredes, etc.)"
        },
        {
            "value": "conectividad",
            "label": "Conectividad",
            "descripcion": "Problemas de internet o conectividad"
        },
        {
            "value": "otro",
            "label": "Otro",
            "descripcion": "Otros problemas no categorizados"
        }
    ]
}
```

#### Uso en Frontend (Ejemplo Flutter/Dart)

```dart
// Obtener categorías para poblar dropdown
final response = await http.get(
    Uri.parse('$baseUrl/api/classroom-reports/catalogs/categories'),
    headers: {'Authorization': 'Bearer $token'}
);

final categories = jsonDecode(response.body)['data'];

// Usar en DropdownButton
DropdownButton<String>(
    items: categories.map<DropdownMenuItem<String>>((category) {
        return DropdownMenuItem<String>(
            value: category['value'],
            child: Text(category['label']),
        );
    }).toList(),
    onChanged: (value) { /* ... */ },
);
```

---

### 2. Obtener Lista de Estados

Obtiene el catálogo completo de estados disponibles para reportes, incluyendo colores sugeridos para UI.

**Endpoint:** `GET /api/classroom-reports/catalogs/statuses`

**Acceso:** Todos los usuarios autenticados

#### Parámetros de Entrada

Ninguno.

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "data": [
        {
            "value": "reportado",
            "label": "Reportado",
            "descripcion": "Reporte recién creado",
            "color": "#3B82F6"
        },
        {
            "value": "en_revision",
            "label": "En Revisión",
            "descripcion": "El reporte está siendo revisado",
            "color": "#F59E0B"
        },
        {
            "value": "asignado",
            "label": "Asignado",
            "descripcion": "Se asignó un responsable",
            "color": "#8B5CF6"
        },
        {
            "value": "en_proceso",
            "label": "En Proceso",
            "descripcion": "El problema está siendo atendido",
            "color": "#06B6D4"
        },
        {
            "value": "resuelto",
            "label": "Resuelto",
            "descripcion": "El problema fue solucionado",
            "color": "#10B981"
        },
        {
            "value": "rechazado",
            "label": "Rechazado",
            "descripcion": "El reporte fue rechazado",
            "color": "#EF4444"
        },
        {
            "value": "cerrado",
            "label": "Cerrado",
            "descripcion": "El reporte fue cerrado",
            "color": "#6B7280"
        }
    ]
}
```

**Nota:** Los colores están en formato hexadecimal y son sugerencias basadas en Tailwind CSS para una UI consistente.

---

### 3. Obtener Estadísticas de Reportes

Obtiene métricas y estadísticas generales del sistema de reportes. Útil para dashboards administrativos.

**Endpoint:** `GET /api/classroom-reports/statistics`

**Acceso:** Solo Administradores y Gestores (ROOT, Administrador Académico, Jefe de Departamento, Coordinador de Carreras)

#### Parámetros de Entrada

Ninguno.

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "data": {
        "total_reportes": 127,
        "reportes_mes_actual": 23,
        "reportes_pendientes": 15,
        "reportes_resueltos": 98,
        "por_estado": {
            "reportado": 8,
            "en_revision": 3,
            "asignado": 2,
            "en_proceso": 2,
            "resuelto": 98,
            "rechazado": 5,
            "cerrado": 9
        },
        "por_categoria": {
            "recurso_defectuoso": 45,
            "qr_danado": 12,
            "limpieza": 28,
            "infraestructura": 18,
            "conectividad": 15,
            "otro": 9
        },
        "top_aulas_con_reportes": [
            {
                "aula_id": 5,
                "aula_nombre": "Aula 201 - Edificio A",
                "total_reportes": 18
            },
            {
                "aula_id": 12,
                "aula_nombre": "Laboratorio 3 - Edificio C",
                "total_reportes": 15
            },
            {
                "aula_id": 8,
                "aula_nombre": "Aula 305 - Edificio B",
                "total_reportes": 12
            },
            {
                "aula_id": 3,
                "aula_nombre": "Auditorio Principal",
                "total_reportes": 10
            },
            {
                "aula_id": 15,
                "aula_nombre": "Sala de Conferencias 1",
                "total_reportes": 9
            }
        ]
    }
}
```

#### Descripción de Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `total_reportes` | integer | Total de reportes en el sistema |
| `reportes_mes_actual` | integer | Reportes creados en el mes actual |
| `reportes_pendientes` | integer | Reportes que NO están en estado `resuelto`, `cerrado` o `rechazado` |
| `reportes_resueltos` | integer | Reportes marcados como resueltos |
| `por_estado` | object | Conteo de reportes agrupados por estado |
| `por_categoria` | object | Conteo de reportes agrupados por categoría |
| `top_aulas_con_reportes` | array | Top 5 aulas con mayor cantidad de reportes |

#### Respuestas de Error

**401 - No Autorizado**
```json
{
    "success": false,
    "message": "Acceso no autorizado"
}
```

**403 - Permisos Insuficientes**
```json
{
    "success": false,
    "message": "Acceso no autorizado"
}
```

---

### CRUD y Operaciones

---

### 4. Crear Reporte

Permite a docentes y estudiantes reportar problemas en las aulas.

**Endpoint:** `POST /api/classroom-reports/new`

**Acceso:** Todos los usuarios autenticados (Docentes, Estudiantes, Administradores, Gestores)

**Content-Type:** `multipart/form-data` (si se envía foto) o `application/json`

#### Parámetros de Entrada

| Campo | Tipo | Obligatorio | Descripción |
|-------|------|-------------|-------------|
| `aula_id` | integer | ✅ Sí | ID del aula donde ocurrió el problema. Debe existir en la tabla `aulas` |
| `categoria` | string | ✅ Sí | Categoría del problema. Valores permitidos: `recurso_defectuoso`, `qr_danado`, `limpieza`, `infraestructura`, `conectividad`, `otro` |
| `descripcion` | string | ✅ Sí | Descripción detallada del problema. Mínimo 10 caracteres, máximo 1000 caracteres |
| `foto_evidencia` | file | ❌ No | Foto que evidencia el problema. Formatos permitidos: jpeg, png, jpg. Tamaño máximo: 5MB |

#### Ejemplo de Petición (JSON)

```json
{
    "aula_id": 5,
    "categoria": "qr_danado",
    "descripcion": "El código QR del aula está completamente rayado con marcador permanente, no se puede escanear desde ninguna aplicación móvil"
}
```

#### Ejemplo de Petición (FormData con foto)

```javascript
const formData = new FormData();
formData.append('aula_id', 5);
formData.append('categoria', 'recurso_defectuoso');
formData.append('descripcion', 'El aire acondicionado no enciende, hace ruidos extraños al intentar encenderlo');
formData.append('foto_evidencia', fileInput.files[0]);

fetch('/api/classroom-reports/new', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token
    },
    body: formData
});
```

#### Respuesta Exitosa (201 Created)

```json
{
    "success": true,
    "message": "Reporte de problema creado exitosamente",
    "data": {
        "id": 1,
        "aula_id": 5,
        "usuario_reporta_id": 12,
        "categoria": "qr_danado",
        "descripcion": "El código QR del aula está completamente rayado...",
        "estado": "reportado",
        "foto_evidencia": "reportes_problemas/reporte_1699999999_abc123.jpg",
        "usuario_asignado_id": null,
        "fecha_resolucion": null,
        "notas_resolucion": null,
        "created_at": "2025-11-13T15:52:00.000000Z",
        "updated_at": "2025-11-13T15:52:00.000000Z",
        "aula": {
            "id": 5,
            "codigo": "A-201",
            "nombre": "Aula 201 - Edificio A",
            "capacidad": 40,
            "estado": "activo"
        },
        "usuarioReporta": {
            "id": 12,
            "nombre": "Juan Pérez",
            "email": "juan.perez@universidad.edu"
        }
    }
}
```

#### Respuestas de Error

**422 - Error de Validación**
```json
{
    "success": false,
    "message": "Error de validación",
    "errors": {
        "aula_id": ["El aula especificada no existe"],
        "categoria": ["La categoría debe ser: recurso_defectuoso, qr_danado, limpieza, infraestructura, conectividad u otro"],
        "descripcion": ["La descripción debe tener al menos 10 caracteres"]
    }
}
```

**401 - No Autorizado**
```json
{
    "success": false,
    "message": "Acceso no autorizado"
}
```

**500 - Error del Servidor**
```json
{
    "success": false,
    "message": "Error al crear el reporte de problema",
    "error": "Detalles del error"
}
```

---

### 5. Listar Todos los Reportes

Obtiene todos los reportes de problemas del sistema.

**Endpoint:** `GET /api/classroom-reports/get/all`

**Acceso:** Solo Administradores y Gestores (ROOT, Administrador Académico, Jefe de Departamento, Coordinador de Carreras)

#### Parámetros de Entrada

Ninguno.

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "aula_id": 5,
            "usuario_reporta_id": 12,
            "categoria": "qr_danado",
            "descripcion": "El código QR del aula está rayado...",
            "estado": "reportado",
            "foto_evidencia": "reportes_problemas/reporte_1699999999_abc123.jpg",
            "usuario_asignado_id": null,
            "fecha_resolucion": null,
            "notas_resolucion": null,
            "created_at": "2025-11-13T15:52:00.000000Z",
            "updated_at": "2025-11-13T15:52:00.000000Z",
            "aula": { ... },
            "usuarioReporta": { ... },
            "usuarioAsignado": null
        },
        {
            "id": 2,
            "aula_id": 3,
            "usuario_reporta_id": 8,
            "categoria": "recurso_defectuoso",
            "descripcion": "El proyector no enciende...",
            "estado": "resuelto",
            "fecha_resolucion": "2025-11-13T18:30:00.000000Z",
            "aula": { ... },
            "usuarioReporta": { ... },
            "usuarioAsignado": { ... }
        }
    ]
}
```

#### Respuestas de Error

**401 - No Autorizado**
```json
{
    "success": false,
    "message": "Acceso no autorizado"
}
```

**403 - Permisos Insuficientes**
```json
{
    "success": false,
    "message": "Acceso no autorizado"
}
```

**500 - Error del Servidor**
```json
{
    "success": false,
    "message": "Error al obtener los reportes",
    "error": "Detalles del error"
}
```

---

### 6. Ver un Reporte Específico

Obtiene los detalles de un reporte específico.

**Endpoint:** `GET /api/classroom-reports/get/{id}`

**Acceso:** Todos los usuarios autenticados

#### Parámetros de URL

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | integer | ID del reporte a consultar |

#### Ejemplo de Petición

```
GET /api/classroom-reports/get/1
```

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "data": {
        "id": 1,
        "aula_id": 5,
        "usuario_reporta_id": 12,
        "categoria": "qr_danado",
        "descripcion": "El código QR del aula está rayado...",
        "estado": "reportado",
        "foto_evidencia": "reportes_problemas/reporte_1699999999_abc123.jpg",
        "usuario_asignado_id": null,
        "fecha_resolucion": null,
        "notas_resolucion": null,
        "created_at": "2025-11-13T15:52:00.000000Z",
        "updated_at": "2025-11-13T15:52:00.000000Z",
        "aula": {
            "id": 5,
            "codigo": "A-201",
            "nombre": "Aula 201 - Edificio A"
        },
        "usuarioReporta": {
            "id": 12,
            "nombre": "Juan Pérez",
            "email": "juan.perez@universidad.edu"
        },
        "usuarioAsignado": null
    }
}
```

#### Respuestas de Error

**404 - No Encontrado**
```json
{
    "success": false,
    "message": "Reporte no encontrado"
}
```

---

### 7. Obtener Reportes por Aula

Obtiene todos los reportes de un aula específica.

**Endpoint:** `GET /api/classroom-reports/get/classroom/{aula_id}`

**Acceso:** Todos los usuarios autenticados

#### Parámetros de URL

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `aula_id` | integer | ID del aula |

#### Ejemplo de Petición

```
GET /api/classroom-reports/get/classroom/5
```

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "aula_id": 5,
            "categoria": "qr_danado",
            "descripcion": "El código QR está rayado...",
            "estado": "reportado",
            "usuarioReporta": { ... },
            "usuarioAsignado": null
        },
        {
            "id": 5,
            "aula_id": 5,
            "categoria": "limpieza",
            "descripcion": "El aula necesita limpieza profunda...",
            "estado": "resuelto",
            "usuarioReporta": { ... },
            "usuarioAsignado": { ... }
        }
    ]
}
```

---

### 8. Obtener Reportes por Estado

Filtra los reportes por su estado actual.

**Endpoint:** `GET /api/classroom-reports/get/status/{estado}`

**Acceso:** Todos los usuarios autenticados

#### Parámetros de URL

| Parámetro | Tipo | Valores Permitidos |
|-----------|------|-------------------|
| `estado` | string | `reportado`, `en_revision`, `asignado`, `en_proceso`, `resuelto`, `rechazado`, `cerrado` |

#### Ejemplo de Petición

```
GET /api/classroom-reports/get/status/reportado
```

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "aula_id": 5,
            "categoria": "qr_danado",
            "estado": "reportado",
            "aula": { ... },
            "usuarioReporta": { ... },
            "usuarioAsignado": null
        }
    ]
}
```

#### Respuestas de Error

**422 - Estado No Válido**
```json
{
    "success": false,
    "message": "Estado no válido"
}
```

---

### 9. Obtener Reportes por Categoría

Filtra los reportes por categoría de problema.

**Endpoint:** `GET /api/classroom-reports/get/category/{categoria}`

**Acceso:** Todos los usuarios autenticados

#### Parámetros de URL

| Parámetro | Tipo | Valores Permitidos |
|-----------|------|-------------------|
| `categoria` | string | `recurso_defectuoso`, `qr_danado`, `limpieza`, `infraestructura`, `conectividad`, `otro` |

#### Ejemplo de Petición

```
GET /api/classroom-reports/get/category/qr_danado
```

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "aula_id": 5,
            "categoria": "qr_danado",
            "descripcion": "El código QR está rayado...",
            "estado": "reportado",
            "aula": { ... },
            "usuarioReporta": { ... },
            "usuarioAsignado": null
        },
        {
            "id": 8,
            "aula_id": 12,
            "categoria": "qr_danado",
            "descripcion": "El código QR no se puede escanear...",
            "estado": "en_proceso",
            "aula": { ... },
            "usuarioReporta": { ... },
            "usuarioAsignado": { ... }
        }
    ]
}
```

#### Respuestas de Error

**422 - Categoría No Válida**
```json
{
    "success": false,
    "message": "Categoría no válida"
}
```

---

### 10. Obtener Mis Reportes

Obtiene todos los reportes creados por el usuario autenticado.

**Endpoint:** `GET /api/classroom-reports/get/my/all`

**Acceso:** Todos los usuarios autenticados

#### Parámetros de Entrada

Ninguno (se obtiene el usuario desde el token de autenticación).

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "aula_id": 5,
            "usuario_reporta_id": 12,
            "categoria": "qr_danado",
            "descripcion": "El código QR está rayado...",
            "estado": "reportado",
            "created_at": "2025-11-13T15:52:00.000000Z",
            "aula": {
                "id": 5,
                "nombre": "Aula 201 - Edificio A"
            },
            "usuarioAsignado": null
        },
        {
            "id": 3,
            "aula_id": 8,
            "usuario_reporta_id": 12,
            "categoria": "limpieza",
            "descripcion": "El aula necesita limpieza...",
            "estado": "resuelto",
            "created_at": "2025-11-12T10:30:00.000000Z",
            "aula": { ... },
            "usuarioAsignado": { ... }
        }
    ]
}
```

---

### 11. Cambiar Estado de un Reporte

Permite a administradores y gestores cambiar el estado de un reporte.

**Endpoint:** `PATCH /api/classroom-reports/change-status/{id}`

**Acceso:** Solo Administradores y Gestores (ROOT, Administrador Académico, Jefe de Departamento, Coordinador de Carreras)

**Content-Type:** `application/json`

#### Parámetros de URL

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | integer | ID del reporte a actualizar |

#### Parámetros de Entrada

| Campo | Tipo | Obligatorio | Descripción |
|-------|------|-------------|-------------|
| `estado` | string | ✅ Sí | Nuevo estado del reporte. Valores: `reportado`, `en_revision`, `asignado`, `en_proceso`, `resuelto`, `rechazado`, `cerrado` |

#### Ejemplo de Petición

```json
{
    "estado": "en_revision"
}
```

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "message": "Estado del reporte actualizado exitosamente",
    "data": {
        "id": 1,
        "aula_id": 5,
        "categoria": "qr_danado",
        "estado": "en_revision",
        "fecha_resolucion": null,
        "aula": { ... },
        "usuarioReporta": { ... },
        "usuarioAsignado": null
    }
}
```

**Nota:** Si se cambia el estado a `resuelto` y no tiene `fecha_resolucion`, se asigna automáticamente la fecha y hora actual.

#### Respuestas de Error

**422 - Error de Validación**
```json
{
    "success": false,
    "message": "Error de validación",
    "errors": {
        "estado": ["El estado debe ser: reportado, en_revision, asignado, en_proceso, resuelto, rechazado o cerrado"]
    }
}
```

**403 - Permisos Insuficientes**
```json
{
    "success": false,
    "message": "Acceso no autorizado"
}
```

**404 - No Encontrado**
```json
{
    "success": false,
    "message": "Reporte no encontrado"
}
```

---

### 12. Asignar Usuario a un Reporte

Asigna un usuario responsable para resolver el problema reportado.

**Endpoint:** `PATCH /api/classroom-reports/assign-user/{id}`

**Acceso:** Solo Administradores y Gestores (ROOT, Administrador Académico, Jefe de Departamento, Coordinador de Carreras)

**Content-Type:** `application/json`

#### Parámetros de URL

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | integer | ID del reporte |

#### Parámetros de Entrada

| Campo | Tipo | Obligatorio | Descripción |
|-------|------|-------------|-------------|
| `usuario_asignado_id` | integer | ✅ Sí | ID del usuario que será asignado para resolver el problema. Debe existir en la tabla `users` |

#### Ejemplo de Petición

```json
{
    "usuario_asignado_id": 25
}
```

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "message": "Usuario asignado exitosamente al reporte",
    "data": {
        "id": 1,
        "aula_id": 5,
        "categoria": "qr_danado",
        "estado": "asignado",
        "usuario_asignado_id": 25,
        "aula": { ... },
        "usuarioReporta": { ... },
        "usuarioAsignado": {
            "id": 25,
            "nombre": "Carlos Martínez",
            "email": "carlos.martinez@universidad.edu"
        }
    }
}
```

**Nota:** Al asignar un usuario, si el estado es `reportado` o `en_revision`, se cambia automáticamente a `asignado`.

#### Respuestas de Error

**422 - Error de Validación**
```json
{
    "success": false,
    "message": "Error de validación",
    "errors": {
        "usuario_asignado_id": ["El usuario especificado no existe"]
    }
}
```

**403 - Permisos Insuficientes**
```json
{
    "success": false,
    "message": "Acceso no autorizado"
}
```

**404 - No Encontrado**
```json
{
    "success": false,
    "message": "Reporte no encontrado"
}
```

---

### 13. Marcar Reporte como Resuelto

Marca un reporte como resuelto y opcionalmente agrega notas de resolución.

**Endpoint:** `POST /api/classroom-reports/mark-resolved/{id}`

**Acceso:** Solo Administradores y Gestores (ROOT, Administrador Académico, Jefe de Departamento, Coordinador de Carreras)

**Content-Type:** `application/json`

#### Parámetros de URL

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | integer | ID del reporte a marcar como resuelto |

#### Parámetros de Entrada

| Campo | Tipo | Obligatorio | Descripción |
|-------|------|-------------|-------------|
| `notas_resolucion` | string | ❌ No | Notas o comentarios sobre cómo se resolvió el problema. Máximo 1000 caracteres |

#### Ejemplo de Petición

```json
{
    "notas_resolucion": "Se reemplazó el código QR dañado por uno nuevo. Se colocó en un marco protector para evitar daños futuros."
}
```

#### Respuesta Exitosa (200 OK)

```json
{
    "success": true,
    "message": "Reporte marcado como resuelto exitosamente",
    "data": {
        "id": 1,
        "aula_id": 5,
        "categoria": "qr_danado",
        "descripcion": "El código QR está rayado...",
        "estado": "resuelto",
        "usuario_asignado_id": 25,
        "fecha_resolucion": "2025-11-13T18:45:00.000000Z",
        "notas_resolucion": "Se reemplazó el código QR dañado por uno nuevo...",
        "aula": { ... },
        "usuarioReporta": { ... },
        "usuarioAsignado": { ... }
    }
}
```

**Nota:** Al marcar como resuelto, se asigna automáticamente:
- `estado` = `resuelto`
- `fecha_resolucion` = fecha y hora actual del servidor

#### Respuestas de Error

**422 - Error de Validación**
```json
{
    "success": false,
    "message": "Error de validación",
    "errors": {
        "notas_resolucion": ["Las notas de resolución no pueden exceder 1000 caracteres"]
    }
}
```

**403 - Permisos Insuficientes**
```json
{
    "success": false,
    "message": "Acceso no autorizado"
}
```

**404 - No Encontrado**
```json
{
    "success": false,
    "message": "Reporte no encontrado"
}
```

---

## Estructura de Datos

### Objeto Reporte Completo

```json
{
    "id": 1,
    "aula_id": 5,
    "usuario_reporta_id": 12,
    "categoria": "qr_danado",
    "descripcion": "Descripción detallada del problema",
    "estado": "reportado",
    "foto_evidencia": "reportes_problemas/reporte_1699999999_abc123.jpg",
    "usuario_asignado_id": null,
    "fecha_resolucion": null,
    "notas_resolucion": null,
    "created_at": "2025-11-13T15:52:00.000000Z",
    "updated_at": "2025-11-13T15:52:00.000000Z",
    "aula": {
        "id": 5,
        "codigo": "A-201",
        "nombre": "Aula 201 - Edificio A",
        "capacidad": 40,
        "estado": "activo"
    },
    "usuarioReporta": {
        "id": 12,
        "nombre": "Juan Pérez",
        "email": "juan.perez@universidad.edu",
        "rol": "Estudiante"
    },
    "usuarioAsignado": null
}
```

---

## Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | OK - Petición exitosa |
| 201 | Created - Recurso creado exitosamente |
| 401 | Unauthorized - No se proporcionó token de autenticación válido |
| 403 | Forbidden - El usuario no tiene permisos para esta acción |
| 404 | Not Found - El recurso solicitado no existe |
| 422 | Unprocessable Entity - Error de validación de datos |
| 500 | Internal Server Error - Error del servidor |

---

## Flujo de Trabajo Típico

### Para Docentes/Estudiantes (App Móvil):

1. **Obtener categorías disponibles:** `GET /api/classroom-reports/catalogs/categories` (para poblar dropdown)
2. **Reportar problema:** `POST /api/classroom-reports/new` (con foto opcional)
3. **Ver mis reportes:** `GET /api/classroom-reports/get/my/all`
4. **Ver estado de un reporte:** `GET /api/classroom-reports/get/{id}`

### Para Administradores/Gestores (Panel Web):

1. **Ver estadísticas generales:** `GET /api/classroom-reports/statistics` (dashboard)
2. **Ver todos los reportes:** `GET /api/classroom-reports/get/all`
3. **Filtrar por estado:** `GET /api/classroom-reports/get/status/reportado`
4. **Filtrar por categoría:** `GET /api/classroom-reports/get/category/qr_danado`
5. **Cambiar estado a "en revisión":** `PATCH /api/classroom-reports/change-status/{id}`
6. **Asignar responsable:** `PATCH /api/classroom-reports/assign-user/{id}`
7. **Ver reportes de un aula:** `GET /api/classroom-reports/get/classroom/{aula_id}`
8. **Marcar como resuelto:** `POST /api/classroom-reports/mark-resolved/{id}` (con notas opcionales)

---

## Notas Adicionales

- **Foto de Evidencia:** Las fotos se almacenan en `storage/app/public/reportes_problemas/` y son accesibles mediante `{APP_URL}/storage/reportes_problemas/{nombre_archivo}`
- **Fechas:** Todas las fechas están en formato ISO 8601 con zona horaria UTC
- **Ordenamiento:** Los reportes siempre se retornan ordenados por fecha de creación descendente (más recientes primero)
- **Validación:** Todos los endpoints validan los datos de entrada y retornan errores descriptivos en español
- **Relaciones:** Los reportes siempre incluyen las relaciones `aula`, `usuarioReporta` y `usuarioAsignado` (cuando aplique)

---

## Resumen de Endpoints

| # | Método | Endpoint | Descripción | Acceso |
|---|--------|----------|-------------|--------|
| 1 | GET | `/api/classroom-reports/catalogs/categories` | Obtener lista de categorías | Todos |
| 2 | GET | `/api/classroom-reports/catalogs/statuses` | Obtener lista de estados | Todos |
| 3 | GET | `/api/classroom-reports/statistics` | Obtener estadísticas | Admin/Gestores |
| 4 | POST | `/api/classroom-reports/new` | Crear reporte | Todos |
| 5 | GET | `/api/classroom-reports/get/all` | Listar todos los reportes | Admin/Gestores |
| 6 | GET | `/api/classroom-reports/get/{id}` | Ver reporte específico | Todos |
| 7 | GET | `/api/classroom-reports/get/classroom/{aula_id}` | Reportes por aula | Todos |
| 8 | GET | `/api/classroom-reports/get/status/{estado}` | Reportes por estado | Todos |
| 9 | GET | `/api/classroom-reports/get/category/{categoria}` | Reportes por categoría | Todos |
| 10 | GET | `/api/classroom-reports/get/my/all` | Mis reportes | Todos |
| 11 | PATCH | `/api/classroom-reports/change-status/{id}` | Cambiar estado | Admin/Gestores |
| 12 | PATCH | `/api/classroom-reports/assign-user/{id}` | Asignar usuario | Admin/Gestores |
| 13 | POST | `/api/classroom-reports/mark-resolved/{id}` | Marcar como resuelto | Admin/Gestores |

---

**Última actualización:** 2025-11-14
**Versión:** 1.1.0
**Cambios en esta versión:**
- ✅ Agregados endpoints de catálogos (categorías y estados)
- ✅ Agregado endpoint de estadísticas para dashboards
- ✅ Documentación completa con ejemplos de uso en Flutter/Dart
