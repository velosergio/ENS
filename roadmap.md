# ENS App - Roadmap

## Implementación por Fases

El roadmap está dividido en 10 fases que implementan los módulos del sistema de forma incremental, priorizando las dependencias técnicas y la funcionalidad base.

**Nota importante:** Cada fase incluye una sección de "Infraestructura Base" para ajustar la lógica y composición de la app según los cambios detectados durante el desarrollo.

---

## Fase 1: Módulo Parejas

**Objetivo:** Completar el módulo de gestión de parejas con todas las funcionalidades requeridas.

### Infraestructura Base:
- [x] Revisar y ajustar modelos User y Pareja según necesidades detectadas
  - Agregados scopes `scopeSinMango()` y `scopeBuscar()` al modelo Pareja
- [x] Verificar relaciones básicas
- [x] Actualizar migraciones si es necesario
- [x] Ajustar factories si hay cambios
- [x] Agregar middleware `CheckParejaActiva` para validar pareja activa

### Tareas:
- [x] Crear controlador `ParejaController` (mango/admin)
  - [x] `index`: Listar parejas con búsqueda en tiempo real y scroll infinito
  - [x] `create`: Formulario crear pareja (con datos de él y ella)
  - [x] `store`: Crear pareja con ambos usuarios
  - [x] `edit`: Formulario editar pareja (datos de pareja y usuarios en la misma vista)
  - [x] `update`: Actualizar pareja y usuarios
  - [x] `retirar`: Retirar pareja del movimiento (cierra sesión automáticamente)
  - [x] `reactivar`: Reactivar pareja cambiando estado
  - [x] `editSettings`: Método para configuración de pareja propia
  - [x] `updateSettings`: Actualizar configuración de pareja propia
  - [x] `retirarSettings`: Retirar pareja propia desde settings
- [x] Form Requests para validación:
  - [x] `ParejaCreateRequest`
  - [x] `ParejaUpdateRequest`
- [x] Páginas frontend (Disponible para Roles Mango y Admin):
  - [x] Lista de parejas con búsqueda en tiempo real y scroll infinito
  - [x] Formulario crear pareja (reutiliza código del registro público)
  - [x] Formulario editar pareja (datos de pareja y usuarios en la misma vista)
  - [x] Vista expandible por pareja mostrando nombres completos (Él & Ella), número de equipo y fecha de ingreso
  - [x] Filtros: estado (activo/retirado), número de equipo
  - [x] Búsqueda por nombres, emails, número de equipo
- [x] Búsqueda en tiempo real mientras escribe (implementada con scroll infinito)
- [x] Refactorizar `Settings/ParejaController` para delegar al `ParejaController` principal
- [x] Actualizar navegación en header y sidebar para mostrar "Parejas" solo a roles mango/admin
- [x] Tests para gestión de parejas (ParejasTest.php)

### Entregables:
- [x] CRUD completo de parejas
- [x] Búsqueda en tiempo real con scroll infinito
- [x] Filtros por estado y número de equipo
- [x] Gestión de activación/desactivación (retirar/reactivar)
- [x] Vista expandible de parejas con información completa
- [x] Middleware de validación de pareja activa

---

## Fase 2: Módulo Equipo (Vista y Configuraciones)

**Objetivo:** Implementar gestión completa de equipos con vista y configuraciones.

### Infraestructura Base:
- [ ] Crear modelo `Equipo` con migración
  - Campos: `nombre`, `numero`, `estado` (activo/inactivo), `responsable_id` (user mango/admin)
  - Relación con consiliario (agregar `consiliario_nombre` a equipos)
- [ ] Agregar campo `consiliario_nombre` a tabla `parejas` (migración)
- [ ] Actualizar modelo `Equipo` con relaciones:
  - `hasMany` usuarios
  - `hasMany` parejas
  - `belongsTo` responsable (User)
- [ ] Actualizar modelo `User` con relación `belongsTo Equipo`
- [ ] Actualizar modelo `Pareja` con relación `belongsTo Equipo` y campo consiliario
- [ ] Crear factory para `Equipo`
- [ ] Crear seeder inicial con datos de prueba
- [ ] Revisar y ajustar modelos según necesidades detectadas
- [ ] Verificar relaciones entre Equipo, Pareja y User

### Tareas:
- [ ] Crear controlador `EquipoController` (mango/admin)
  - `index`: Listar equipos con búsqueda en tiempo real
  - `create`: Formulario crear equipo
  - `store`: Crear equipo
  - `show`: Detalle equipo con lista de parejas y usuarios
  - `edit`: Formulario editar equipo
  - `update`: Actualizar equipo
  - `destroy`: Eliminar equipo
  - `asignarResponsable`: Asignar responsable al equipo
  - `configurarConsiliario`: Configurar consiliario del equipo
- [ ] Form Requests para validación:
  - `EquipoCreateRequest`
  - `EquipoUpdateRequest`
  - `EquipoAsignarResponsableRequest`
  - `EquipoConfigurarConsiliarioRequest`
- [ ] Páginas frontend:
  - Lista de equipos con búsqueda en tiempo real
  - Formulario crear/editar equipo
  - Vista detalle de equipo:
    - Información general del equipo
    - Lista de parejas del equipo
    - Lista de usuarios del equipo
    - Configuración de responsable
    - Configuración de consiliario
  - Filtros: nombre, numero, estado, responsable
- [ ] Lógica para asignar parejas a equipos
- [ ] Lógica para actualizar `consiliario_nombre` en parejas cuando se configura en equipo
- [ ] Agregar permisos `equipos.*` al `PermissionService`
- [ ] Integrar auditoría para equipos (Fase 8)
- [ ] Tests para gestión de equipos

### Entregables:
- CRUD completo de equipos
- Vista detalle con lista de parejas y usuarios
- Configuración de responsable por equipo
- Configuración de consiliario por equipo
- Búsqueda y filtros implementados

---

## Fase 3: Módulo Calendario

**Objetivo:** Implementar calendario que muestre eventos, formaciones, cumpleaños y aniversarios.

### Infraestructura Base:
- [ ] Revisar y ajustar modelos según necesidades detectadas
- [ ] Verificar relaciones necesarias para el calendario
- [ ] Actualizar migraciones si es necesario
- [ ] Ajustar factories si hay cambios

### Tareas:
- [ ] Crear componente `Calendar` (frontend)
  - Vista mensual (por defecto)
  - Vista semanal
  - Vista diaria
  - Navegación entre fechas
- [ ] Crear controlador `CalendarController`
  - `index`: Retornar eventos del mes/semana/día
  - `events`: API endpoint para obtener eventos en rango de fechas
- [ ] Lógica para mostrar:
  - Eventos ENS (del equipo del usuario) - cuando exista Fase 6
  - Formaciones (Fase 7)
  - Cumpleaños (Fase 4)
  - Aniversarios (Fase 4)
- [ ] Colores diferentes por tipo de evento
- [ ] Integración con Google Calendar (opcional, Fase futura)
  - Exportar eventos a Google Calendar
  - Sincronización bidireccional (futuro)
- [ ] Página frontend del calendario
- [ ] Tests para calendario

### Entregables:
- Calendario funcional con vistas mensual/semanal/diaria
- Muestra eventos, formaciones, cumpleaños y aniversarios
- Colores diferenciados por tipo

---

## Fase 4: Módulo Cumpleaños y Aniversarios

**Objetivo:** Implementar sistema de gestión y visualización de cumpleaños y aniversarios.

### Infraestructura Base:
- [ ] Revisar y ajustar modelos Pareja y User según necesidades detectadas
- [ ] Verificar campos de fechas (fecha_nacimiento, fecha_boda, fecha_ingreso)
- [ ] Actualizar migraciones si es necesario
- [ ] Ajustar factories si hay cambios

### Tareas:
- [ ] Crear modelo `Aniversario` con migración (para aniversarios de pareja)
  - Campos: `pareja_id`, `tipo` (enum: boda, ingreso_movimiento), `fecha`, `created_at`
  - Relación: `belongsTo Pareja`
- [ ] Actualizar lógica para calcular cumpleaños desde `fecha_nacimiento` de usuarios
- [ ] Crear servicio `CumpleanosAniversariosService`
  - Método para obtener cumpleaños del mes
  - Método para obtener aniversarios del mes
  - Método para calcular próximos (1 día, 1 semana)
- [ ] Crear controlador `CumpleanosAniversariosController`
  - `index`: Vista de cumpleaños/aniversarios del mes actual
  - `proximos`: Próximos cumpleaños/aniversarios (1 día, 1 semana)
- [ ] Páginas frontend:
  - Vista de cumpleaños/aniversarios del mes
  - Widget en dashboard con próximos
- [ ] Componente para mostrar en calendario (Fase 3)
- [ ] Agregar campo `fecha_boda` a modelo `Pareja` (migración)
- [ ] Tests para cálculo de fechas

### Entregables:
- Vista de cumpleaños del mes
- Vista de aniversarios del mes (boda, ingreso)
- Cálculo de próximos eventos (1 día, 1 semana)
- Integración con calendario

---

## Fase 5: Módulo Notificaciones

**Objetivo:** Implementar sistema de notificaciones en dashboard y preparar base para futuras extensiones.

### Infraestructura Base:
- [ ] Revisar y ajustar modelos según necesidades detectadas
- [ ] Verificar relaciones con User y otros modelos
- [ ] Actualizar migraciones si es necesario
- [ ] Ajustar factories si hay cambios

### Tareas:
- [ ] Crear modelo `Notificacion` con migración
  - Campos: `user_id` (nullable para notificaciones globales), `tipo` (enum: cumpleaños, aniversario, evento, recordatorio, sistema), `titulo`, `mensaje`, `leida` (boolean), `leida_at` (nullable), `created_at`
  - Relación: `belongsTo User` (nullable)
- [ ] Crear servicio `NotificacionService`
  - Método para crear notificaciones
  - Método para marcar como leída
  - Método para obtener no leídas
- [ ] Crear controlador `NotificacionController` (mango/admin para crear, todos para leer)
  - `index`: Listar notificaciones del usuario
  - `store`: Crear notificación (mango/admin)
  - `update`: Marcar como leída
  - `markAllRead`: Marcar todas como leídas
  - `destroy`: Eliminar notificación
- [ ] Integrar creación automática de notificaciones:
  - Cumpleaños (1 día y 1 semana antes) - usar jobs programados
  - Aniversarios (1 día y 1 semana antes) - usar jobs programados
  - Recordatorios de eventos (1 día antes) - usar jobs programados cuando exista Fase 6
- [ ] Crear jobs programados (Laravel Schedule):
  - `EnviarNotificacionesCumpleanos`
  - `EnviarNotificacionesAniversarios`
  - `EnviarRecordatoriosEventos`
- [ ] Páginas frontend:
  - Widget en dashboard con notificaciones no leídas
  - Lista completa de notificaciones
  - Dropdown de notificaciones en header
- [ ] Integrar auditoría para notificaciones creadas (Fase 8)
- [ ] Agregar permisos `notificaciones.*` al `PermissionService`
- [ ] Tests para notificaciones

### Entregables:
- Sistema de notificaciones en dashboard
- Notificaciones automáticas para cumpleaños/aniversarios/eventos
- Widget de notificaciones no leídas
- Jobs programados para notificaciones automáticas

---

## Fase 6: Módulo Eventos

**Objetivo:** Implementar gestión completa de eventos del movimiento ENS.

### Infraestructura Base:
- [ ] Revisar y ajustar modelos según necesidades detectadas
- [ ] Verificar relaciones con Equipo y User
- [ ] Actualizar migraciones si es necesario
- [ ] Ajustar factories si hay cambios

### Tareas:
- [ ] Crear modelo `Evento` con migración
  - Campos: `titulo`, `descripcion`, `fecha`, `hora`, `lugar`, `tipo` (enum: evento, formación), `equipo_id`, `organizador_id` (user), `capacidad_maxima` (nullable)
  - Relaciones: `belongsTo Equipo`, `belongsTo User (organizador)`, `hasMany RegistrosEvento`
- [ ] Crear modelo `RegistroEvento` con migración
  - Campos: `evento_id`, `pareja_id`, `estado` (enum: confirmado, en_espera, cancelado), `confirmado_at`, `created_at`
  - Relaciones: `belongsTo Evento`, `belongsTo Pareja`
- [ ] Crear controlador `EventoController` (mango/admin)
  - `index`: Listar eventos (filtrados por equipo del usuario)
  - `create`: Formulario crear evento
  - `store`: Crear evento
  - `show`: Detalle evento con lista de registrados
  - `edit`: Formulario editar evento
  - `update`: Actualizar evento
  - `destroy`: Eliminar evento
- [ ] Crear controlador `RegistroEventoController`
  - `store`: Registrar pareja a evento (si hay límite, agregar a lista de espera)
  - `update`: Cambiar estado del registro
  - `destroy`: Cancelar registro
- [ ] Form Requests:
  - `EventoCreateRequest`
  - `EventoUpdateRequest`
  - `RegistroEventoRequest`
- [ ] Páginas frontend:
  - Lista de eventos
  - Formulario crear/editar evento
  - Detalle de evento con formulario de registro
  - Lista de confirmados y en espera
- [ ] Lógica de lista de espera cuando hay límite
- [ ] Agregar permisos `eventos.*` al `PermissionService`
- [ ] Integrar auditoría para eventos (Fase 8)
- [ ] Tests para eventos

### Entregables:
- CRUD completo de eventos
- Sistema de registro de parejas a eventos
- Lista de confirmados y en espera
- Relación con equipos

---

## Fase 7: Módulo Asistencia a Formación

**Objetivo:** Implementar registro de asistencia a formaciones relacionadas con eventos.

### Infraestructura Base:
- [ ] Revisar y ajustar modelos Evento y AsistenciaFormacion según necesidades detectadas
- [ ] Verificar relaciones con Pareja y Equipo
- [ ] Actualizar migraciones si es necesario
- [ ] Ajustar factories si hay cambios

### Tareas:
- [ ] Extender modelo `Evento` para formaciones
  - Agregar campo `tipo_formacion` (enum: charla, retiro, taller, otro)
  - Ya existe `tipo` en Fase 6, usar ese campo
- [ ] Crear modelo `AsistenciaFormacion` con migración
  - Campos: `evento_id`, `pareja_id`, `asistio` (boolean), `observaciones` (nullable), `registrado_por` (user_id), `created_at`
  - Relaciones: `belongsTo Evento`, `belongsTo Pareja`, `belongsTo User (registrado_por)`
- [ ] Crear controlador `AsistenciaFormacionController` (mango/admin)
  - `index`: Listar asistencias de una formación
  - `store`: Registrar asistencia de pareja
  - `update`: Actualizar asistencia
  - `destroy`: Eliminar registro de asistencia
  - `bulk`: Registrar múltiples asistencias a la vez
- [ ] Form Requests:
  - `AsistenciaFormacionRequest`
  - `BulkAsistenciaFormacionRequest`
- [ ] Páginas frontend:
  - Lista de asistencias de una formación
  - Formulario para marcar asistencia
  - Vista bulk para marcar múltiples parejas
- [ ] Relación con módulo de eventos (usar eventos con `tipo = 'formacion'`)
- [ ] Agregar permisos `formaciones.*` al `PermissionService`
- [ ] Integrar auditoría para asistencias (Fase 8)
- [ ] Tests para asistencias

### Entregables:
- Sistema de registro de asistencia a formaciones
- Registro individual y masivo
- Relación con eventos de tipo formación
- Vista de asistencias por formación

---

## Fase 8: Módulo Auditoría (Registro de Acciones)

**Objetivo:** Implementar sistema de registro de todas las acciones del sistema como base para otros módulos.

### Infraestructura Base:
- [ ] Revisar y ajustar modelos según necesidades detectadas
- [ ] Verificar relaciones y agregar campos necesarios
- [ ] Actualizar migraciones si es necesario
- [ ] Ajustar factories si hay cambios

### Tareas:
- [ ] Crear modelo `AuditLog` con migración
  - Campos: `user_id`, `action` (string), `model_type`, `model_id`, `changes` (json), `ip_address`, `user_agent`, `created_at`
- [ ] Crear trait `Auditable` para modelos que necesiten auditoría
- [ ] Crear servicio `AuditService` para registrar acciones
- [ ] Implementar middleware/observer para capturar acciones automáticamente:
  - Crear/editar/eliminar usuarios (Fase 1)
  - Crear/editar/eliminar parejas (Fase 1)
  - Crear/editar/eliminar equipos (Fase 2)
  - Crear/editar/eliminar eventos (Fase 6)
  - Crear/editar asistencia (Fase 7)
- [ ] Crear controlador `AuditLogController` (solo mango/admin)
  - Listar registros con paginación
  - Filtros: usuario, fecha, acción, modelo
  - Búsqueda en tiempo real
- [ ] Crear página frontend para visualizar auditoría
- [ ] Agregar permisos `audit.view` al `PermissionService`
- [ ] Tests para auditoría

### Entregables:
- Sistema de auditoría funcional
- Registro automático de acciones
- Vista de auditoría para mango/admin
- Búsqueda y filtros implementados

---

## Fase 9: Módulo Guía ENS (IA con RAG)

**Objetivo:** Implementar chatbot con IA usando RAG para consultar la guía ENS desde PDFs.

### Infraestructura Base:
- [ ] Revisar y ajustar modelos según necesidades detectadas
- [ ] Verificar estructura de almacenamiento de documentos
- [ ] Actualizar migraciones si es necesario
- [ ] Ajustar factories si hay cambios

### Tareas:
- [ ] Investigar e instalar librerías para RAG:
  - Procesamiento de PDFs (spatie/pdf, etc.)
  - Embeddings (OpenAI, DeepSeek compatible)
  - Vector database (Pinecone, Weaviate, o SQL con pgvector)
- [ ] Crear modelo `DocumentoGuia` con migración
  - Campos: `titulo`, `archivo_path`, `procesado` (boolean), `created_at`
- [ ] Crear modelo `Embedding` con migración (si se usa vector DB propio)
  - Campos: `documento_id`, `contenido`, `embedding` (vector/json), `metadata` (json)
- [ ] Crear servicio `RAGService`
  - Método para procesar y fragmentar PDFs
  - Método para generar embeddings
  - Método para buscar documentos similares (semantic search)
  - Método para generar respuesta usando IA
- [ ] Crear servicio `AIService` (abstracción para proveedores)
  - Configuración desde variables de entorno
  - Compatible con DeepSeek y OpenAI
  - Método para generar respuestas con contexto
- [ ] Crear controlador `GuiaENSController`
  - `index`: Página del chatbot
  - `chat`: Endpoint para enviar pregunta y recibir respuesta
  - `upload`: Subir PDFs de la guía (mango/admin)
  - `process`: Procesar PDFs para RAG (mango/admin)
- [ ] Páginas frontend:
  - Interfaz de chatbot con historial
  - FAQ's estáticas
  - Panel de gestión de documentos (mango/admin)
- [ ] Variables de entorno necesarias:
  - `AI_PROVIDER` (deepseek/openai)
  - `AI_API_KEY`
  - `AI_MODEL`
- [ ] Agregar permisos `guia.*` al `PermissionService`
- [ ] Tests básicos para RAG

### Entregables:
- Chatbot funcional con IA
- Sistema RAG para consultar PDFs de la guía
- Compatible con DeepSeek y OpenAI
- FAQ's estáticas
- Panel de gestión de documentos

---

## Fase 10: Módulo Informes

**Objetivo:** Implementar generación de informes y exportación a Excel con estadísticas del sistema.

### Infraestructura Base:
- [ ] Revisar y ajustar todos los modelos según necesidades detectadas
- [ ] Optimizar relaciones y queries para reportes
- [ ] Verificar índices de base de datos para mejor performance
- [ ] Actualizar migraciones si es necesario
- [ ] Ajustar factories si hay cambios

### Tareas:
- [ ] Instalar librería para Excel (maatwebsite/excel)
- [ ] Crear controlador `InformeController` (mango/admin)
  - `index`: Lista de informes disponibles
  - `asistenciaEventos`: Asistencia a eventos
  - `asistenciaFormaciones`: Asistencia a formaciones
  - `parejasActivasRetiradas`: Estadísticas de parejas
  - `usuariosPorEquipo`: Distribución de usuarios
  - `estadisticasGenerales`: Dashboard de estadísticas
- [ ] Crear servicios de reportes:
  - `AsistenciaEventosReportService`
  - `AsistenciaFormacionesReportService`
  - `ParejasReportService`
  - `UsuariosReportService`
  - `EstadisticasReportService`
- [ ] Form Requests con filtros:
  - Fechas (desde/hasta)
  - Equipo
  - Tipo de evento/formación
  - Estado (activo/retirado)
- [ ] Páginas frontend:
  - Lista de informes disponibles
  - Formulario de filtros para cada informe
  - Vista previa de datos en pantalla
  - Botón exportar a Excel
- [ ] Exportación a Excel:
  - Formato profesional
  - Incluir filtros aplicados
  - Múltiples hojas si es necesario
- [ ] Agregar permisos `informes.*` al `PermissionService`
- [ ] Tests para generación de informes

### Entregables:
- Módulo completo de informes
- Exportación a Excel funcional
- Filtros personalizables por fecha, equipo, tipo
- Vista previa en pantalla antes de exportar

---

## Resumen de Dependencias

```
Fase 1 (Usuarios)
  ↓
Fase 2 (Equipo - Vista y Configuraciones)
  ↓
Fase 3 (Calendario) ← Fase 4 (Cumpleaños/Aniversarios)
  ↓                    ↓
Fase 5 (Notificaciones) ← Fase 6 (Eventos)
  ↓                    ↓
Fase 7 (Asistencia a Formación)
  ↓
Fase 8 (Auditoría)
  ↓
Fase 9 (Guía IA) ──────┐
  ↓                     │
Fase 10 (Informes) ─────┘
```

**Nota:** Cada fase incluye ajustes de infraestructura base según los cambios detectados durante el desarrollo. Las fases pueden implementarse en paralelo cuando no tienen dependencias directas entre sí.

---

## Notas de Implementación

- **Testing:** Cada fase debe incluir tests con Pest.
- **Auditoría:** Todas las acciones importantes deben registrarse automáticamente usando el módulo de Fase 8.
- **Permisos:** Cada módulo debe agregar sus permisos al `PermissionService`.
- **Mobile First:** Todas las interfaces deben priorizar dispositivos móviles.

---
