# Documentación API Materias

## Resumen de Cambios

El sistema de Materias ha sido actualizado para soportar **paginación server-side** completa, eliminando el límite anterior de 50 registros y permitiendo navegación eficiente a través de todos los datos del sistema.

---

## Endpoints de Materias

### 1. Obtener Materias con Paginación y Filtros

**Endpoint:** `GET /api/subjects/get/all`

**Descripción:** Obtiene todas las materias del sistema con soporte completo de paginación y filtrado. Este endpoint reemplaza la versión anterior que estaba limitada a 50 registros.

#### Parámetros de Consulta (Query Parameters)

| Parámetro | Tipo | Requerido | Valores Permitidos | Default | Descripción |
|-----------|------|-----------|-------------------|---------|-------------|
| `page` | integer | No | ≥ 1 | `1` | Número de página a obtener |
| `per_page` | integer | No | 10, 15, 25, 50, 100 | `15` | Cantidad de registros por página |
| `search` | string | No | Texto libre | `""` | Buscar por código o nombre de materia |
| `carrera_id` | integer | No | ID de carrera existente | `""` | Filtrar por carrera específica |
| `estado` | string | No | "activa", "inactiva" | `""` | Filtrar por estado de la materia |

#### Ejemplos de Uso

**1. Obtener primeras 15 materias (default):**
```
GET /api/subjects/get/all
```

**2. Obtener página 2 con 25 registros por página:**
```
GET /api/subjects/get/all?page=2&per_page=25
```

**3. Buscar materias que contengan "Admin":**
```
GET /api/subjects/get/all?search=admin
```

**4. Filtrar materias activas de la carrera con ID 5:**
```
GET /api/subjects/get/all?carrera_id=5&estado=activa
```

**5. Búsqueda avanzada combinada:**
```
GET /api/subjects/get/all?page=3&per_page=50&search=matemáticas&estado=activa
```

#### Respuesta Exitosa (200 OK)

```json
{
    "message": "Materias obtenidas exitosamente",
    "success": true,
    "data": [
        {
            "id": 97,
            "codigo": "NET05473",
            "nombre": "Abastecimiento de Agua y Alcantarillado",
            "descripcion": null,
            "carrera_id": 39,
            "estado": "activa",
            "created_at": "2025-11-17T06:54:14.000000Z",
            "updated_at": "2025-11-17T06:54:14.000000Z",
            "carrera": {
                "id": 39,
                "nombre": "Ingeniería Civil"
            }
        },
        // ... más materias
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 33,
        "per_page": 15,
        "total": 486,
        "from": 1,
        "to": 15
    }
}
```

#### Metadatos de Paginación

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `current_page` | integer | Página actual que se está mostrando |
| `last_page` | integer | Última página disponible |
| `per_page` | integer | Cantidad de registros por página |
| `total` | integer | Total de registros que coinciden con los filtros |
| `from` | integer | Número del primer registro en la página actual |
| `to` | integer | Número del último registro en la página actual |

#### Respuesta Sin Resultados (404 Not Found)

```json
{
    "message": "No se encontraron materias con los criterios especificados",
    "success": false,
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 0,
        "from": null,
        "to": null
    }
}
```

#### Comportamiento Especial

**Auto-corrección de página:** Si se solicita una página que no existe (ej: página 50 cuando solo hay 10 páginas), el sistema automáticamente devuelve la página 1.

**Ejemplo:**
```
GET /api/subjects/get/all?page=999&per_page=25
// Respuesta: Devuelve página 1 con 25 registros por página
```

---

### 2. Obtener Materia Específica

**Endpoint:** `GET /api/subjects/get/{id}`

**Descripción:** Obtiene los detalles de una materia específica por su ID.

#### Parámetros de URL

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `id` | integer | Sí | ID único de la materia |

#### Ejemplo de Uso
```
GET /api/subjects/get/97
```

#### Respuesta Exitosa (200 OK)
```json
{
    "message": "Materia encontrada",
    "success": true,
    "data": {
        "id": 97,
        "codigo": "NET05473",
        "nombre": "Abastecimiento de Agua y Alcantarillado",
        "descripcion": null,
        "carrera_id": 39,
        "estado": "activa",
        "created_at": "2025-11-17T06:54:14.000000Z",
        "updated_at": "2025-11-17T06:54:14.000000Z"
    }
}
```

---

### 3. Crear Nueva Materia

**Endpoint:** `POST /api/subjects/new`

**Descripción:** Crea una nueva materia en el sistema.

#### Cuerpo de la Solicitud (JSON)

| Campo | Tipo | Requerido | Validaciones | Descripción |
|-------|------|-----------|--------------|-------------|
| `codigo` | string | Sí | máx. 10 caracteres, único | Código identificador de la materia |
| `nombre` | string | Sí | máx. 100 caracteres | Nombre completo de la materia |
| `descripcion` | string | No | máx. 255 caracteres | Descripción opcional de la materia |
| `carrera_id` | integer | Sí | Debe existir en tabla carreras | ID de la carrera asociada |
| `estado` | string | Sí | "activa" o "inactiva" | Estado de la materia |

#### Ejemplo de Solicitud
```json
{
    "codigo": "CAL101",
    "nombre": "Cálculo Diferencial",
    "descripcion": "Curso introductorio de cálculo",
    "carrera_id": 1,
    "estado": "activa"
}
```

#### Respuesta Exitosa (201 Created)
```json
{
    "message": "Materia creada exitosamente",
    "success": true,
    "data": {
        "codigo": "CAL101",
        "nombre": "Cálculo Diferencial",
        "descripcion": "Curso introductorio de cálculo",
        "carrera_id": 1,
        "estado": "activa"
    }
}
```

---

### 4. Actualizar Materia

**Endpoint:** `PATCH /api/subjects/edit/{id}`

**Descripción:** Actualiza los datos de una materia existente.

#### Parámetros de URL

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `id` | integer | Sí | ID único de la materia a actualizar |

#### Cuerpo de la Solicitud (JSON)

| Campo | Tipo | Requerido | Validaciones | Descripción |
|-------|------|-----------|--------------|-------------|
| `codigo` | string | No | máx. 10 caracteres, único (excepto esta materia) | Código identificador de la materia |
| `nombre` | string | No | máx. 100 caracteres | Nombre completo de la materia |
| `descripcion` | string | No | máx. 255 caracteres | Descripción opcional de la materia |
| `carrera_id` | integer | No | Debe existir en tabla carreras | ID de la carrera asociada |
| `estado` | string | No | "activa" o "inactiva" | Estado de la materia |

#### Ejemplo de Solicitud
```json
{
    "nombre": "Cálculo Diferencial Actualizado",
    "descripcion": "Curso mejorado de cálculo diferencial",
    "estado": "activa"
}
```

#### Respuesta Exitosa (200 OK)
```json
{
    "message": "Materia actualizada exitosamente",
    "success": true,
    "data": {
        "id": 97,
        "codigo": "CAL101",
        "nombre": "Cálculo Diferencial Actualizado",
        "descripcion": "Curso mejorado de cálculo diferencial",
        "carrera_id": 1,
        "estado": "activa",
        "created_at": "2025-11-17T06:54:14.000000Z",
        "updated_at": "2025-11-17T07:15:30.000000Z"
    }
}
```

---

### 5. Eliminar Materia

**Endpoint:** `DELETE /api/subjects/delete/{id}`

**Descripción:** Elimina permanentemente una materia del sistema.

#### Parámetros de URL

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `id` | integer | Sí | ID único de la materia a eliminar |

#### Ejemplo de Uso
```
DELETE /api/subjects/delete/97
```

#### Respuesta Exitosa (200 OK)
```json
{
    "message": "Materia eliminada exitosamente",
    "success": true
}
```

---

## 6. Endpoints Adicionales (Mantenidos)

### Materias por Carrera
```
GET /api/subjects/get/by-career/{id}
```
Obtiene todas las materias de una carrera específica.

### Materias por Estado
```
GET /api/subjects/get/status/{estado}
```
Obtiene materias filtradas por estado ("activa" o "inactiva").

### Materias por Usuario
```
GET /api/subjects/get/user/{id}
GET /api/subjects/get/my/all
```
Obtiene materias asociadas a un usuario específico o al usuario autenticado.

---

## Códigos de Estado HTTP

| Código | Significado | Descripción |
|--------|-------------|-------------|
| 200 | OK | Solicitud exitosa |
| 201 | Created | Recurso creado exitosamente |
| 401 | Unauthorized | No autenticado o token inválido |
| 403 | Forbidden | Sin permisos para la operación |
| 404 | Not Found | Recurso no encontrado o sin resultados |
| 422 | Unprocessable Entity | Error de validación en datos enviados |
| 500 | Internal Server Error | Error del servidor |

---

## Consideraciones de Uso

### 1. Autenticación
Todos los endpoints requieren token de autenticación en el header:
```
Authorization: Bearer {token}
```

### 2. Cache
Las respuestas se almacenan en caché por 5 minutos con claves únicas basadas en los parámetros:
```
materias_page_{page}_per_{perPage}_search_{search}_career_{carreraId}_status_{estado}
```

### 3. Paginación
- **Página inicial:** Siempre comienza en 1
- **Auto-corrección:** Páginas inválidas redirigen a página 1
- **Tamaños permitidos:** 10, 15, 25, 50, 100 registros por página
- **Ordenamiento:** Las materias se ordenan alfabéticamente por nombre

### 4. Filtros
- Los filtros se combinan con lógica AND
- Los filtros vacíos se ignoran
- La búsqueda es case-insensitive y busca en código y nombre

### 5. Relaciones
Las respuestas incluyen información de carreras cuando están disponibles:
```json
"carrera": {
    "id": 39,
    "nombre": "Ingeniería Civil"
}
```

---

## Ejemplos Prácticos

### Ejemplo 1: Navegación típica
```
// Primera página con 15 registros
GET /api/subjects/get/all?page=1&per_page=15

// Segunda página con 15 registros
GET /api/subjects/get/all?page=2&per_page=15

// Última página (basado en total de páginas)
GET /api/subjects/get/all?page=33&per_page=15
```

### Ejemplo 2: Búsqueda y filtrado
```
// Buscar materias de "Admin" en Ingeniería
GET /api/subjects/get/all?search=admin&carrera_id=1&per_page=25

// Todas las materias inactivas
GET /api/subjects/get/all?estado=inactiva&per_page=50

// Materias que contengan " cálculo" o "math" (case-insensitive)
GET /api/subjects/get/all?search=calculo
```

### Ejemplo 3: Cambio de tamaño de página
```
// De 15 a 50 registros por página
GET /api/subjects/get/all?page=1&per_page=50

// El frontend automáticamente resetea a página 1
// cuando cambia el valor de per_page
```

---

## Mejoras Implementadas

1. ✅ **Eliminación de límite de 50 registros**
2. ✅ **Paginación server-side completa**
3. ✅ **Sistema de caché inteligente**
4. ✅ **Filtros combinados**
5. ✅ **Auto-corrección de páginas inválidas**
6. ✅ **Inclusión de relaciones (carreras)**
7. ✅ **Validaciones robustas**
8. ✅ **Documentación completa de metadata**