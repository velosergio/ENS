# Change Notes

## *0.0.4.2*
- **Corrección de Mixed Content en producción**: Agregada configuración de proxies (`trustProxies`) en `bootstrap/app.php` para detectar correctamente HTTPS cuando la aplicación está detrás de un proxy. Implementada lógica en `AppServiceProvider` para forzar HTTPS en producción cuando las peticiones vienen por HTTPS. Agregada opción de configuración `force_https` en `config/app.php`. Esto corrige el error "Mixed Content" donde la página carga por HTTPS pero las peticiones XMLHttpRequest se hacían por HTTP.

## *0.0.4.1*
- **Limpieza de tests**: Eliminados tests de ejemplo (`ExampleTest.php` en Feature y Unit) que venían por defecto con el proyecto. Actualizado `phpunit.xml` para remover la referencia al testsuite "Unit" ya que el directorio está vacío, corrigiendo error en GitHub Actions. Todos los tests pasando (86 tests, 337 aserciones).

## *0.0.4*
- **Implementación completa del módulo Equipos (Fase 2)**: CRUD completo de equipos con gestión de responsables y consiliarios. Modelo `Equipo` creado con relaciones `hasMany` parejas, `hasManyThrough` usuarios, y `belongsTo` responsable. Migración de `numero_equipo` a `equipo_id` en tabla `parejas`, eliminación de `equipo_id` de tabla `users` (acceso indirecto a través de pareja). Campo `consiliario_nombre` agregado a tabla `equipos`.
- **Gestión de responsables**: Sistema de asignación de responsables con elevación automática de roles. Al asignar responsable, usuarios de la pareja se elevan a rol `admin`. Al quitar responsable, se degradan a rol `equipista`. Validación para evitar eliminar equipos con parejas asignadas.
- **Interfaz de usuario para equipos**: Lista de equipos con búsqueda en tiempo real y scroll infinito. Vista detalle con información general, lista de parejas (scroll infinito) y usuarios del equipo. Formularios crear/editar con selector de responsable. Configuración de "Padre Consiliario" por equipo. Filtros por número y responsable. Navegación visible solo para roles mango/admin.
- **Integración con módulo Parejas**: Selector de equipos agregado a formularios de parejas (create/edit/settings). Actualización de filtros en lista de parejas para usar selector de equipos en lugar de campo numérico. Relación bidireccional entre parejas y equipos.
- **Permisos y validaciones**: Permisos `equipos.*` agregados al `PermissionService` (view, create, update, delete, asignar-responsable, configurar-consiliario). Form Requests para validación de todas las operaciones. Tests completos implementados (26 tests, 104 aserciones).
- **Seeder actualizado**: `ParejasSeeder` modificado para crear 12 equipos y distribuir automáticamente 50 parejas entre ellos rotativamente, permitiendo pruebas realistas del sistema.

## *0.0.3.2*
- Implementado sistema de thumbnails para optimización de imágenes: generación automática de 3 tamaños (50x50, 100x100, 500x500) para todas las fotos subidas (pareja, él, ella). Thumbnails generados automáticamente en registro público, módulo de parejas y configuraciones. Migración actualizada para incluir campos de thumbnails en tablas `parejas` y `users`.
- Optimización de rendimiento: lista de parejas (`/parejas`) ahora usa thumbnails de 50x50 en lugar de imágenes completas, mejorando significativamente los tiempos de carga. Implementado scroll infinito automático usando componente `<InfiniteScroll>` de Inertia v2.
- Mejoras de UI: foto de pareja agregada a la izquierda de cada card en la lista de parejas. Avatar del usuario en header y sidebar ahora muestra thumbnail de 50x50 en lugar de iniciales cuando hay foto disponible.
- Servicio ImageService creado para procesamiento de imágenes: redimensionamiento automático manteniendo proporciones, soporte para PNG (con transparencia), JPEG, GIF y WebP. Thumbnails guardados en base64 en la base de datos.
- Seeder de prueba: creado ParejasSeeder para generar 50 parejas con usuarios para pruebas de scroll infinito y rendimiento.

## *0.0.3.1.1*
- Corregidos tests fallidos: actualizado PasswordConfirmationTest para usar `password.confirm.show`, DashboardTest y PasswordConfirmationTest para redirección a `iniciar-sesion`, RegistrationTest actualizado para registro de parejas con datos de él y ella, ProfileUpdateTest actualizado con campos requeridos (`nombres`, `apellidos`, `celular`, `fecha_nacimiento`, `sexo`), ParejasTest ajustado para reflejar que admin/mango pueden ver parejas con usuarios mango. Todos los tests pasando (62 tests, 235 aserciones).

## *0.0.3.1*
- Resuelto conflicto de nombres en rutas generadas por Wayfinder: renombradas rutas personalizadas de confirmación de contraseña (`password.confirm` → `password.confirm.show` y `password.confirm.store` → `password.confirm.show.store`) para evitar colisiones con rutas de Fortify. Build de producción funcionando correctamente.

## *0.0.3*
- Implementado módulo de gestión de parejas para roles mango/admin: CRUD completo con búsqueda en tiempo real, filtros por estado y equipo, creación/edición de parejas con datos de él y ella en la misma vista, funcionalidad de retiro y reactivación. Refactorizado Settings/ParejaController para delegar al ParejaController principal. Agregado middleware CheckParejaActiva y nuevos scopes al modelo Pareja (sinMango, buscar). Navegación de Parejas visible solo para mango/admin en header y sidebar.

## *0.0.2*
- El registro ahora es de parejas en lugar de usuarios individuales
- Registro de pareja con datos de ÉL y ELLA (nombres, apellidos, celular, fecha de nacimiento, email, foto, Fecha de ingreso al movimiento agregada al registro, numero del equipo)
- Autenticación permite login con cualquiera de los 2 emails de la pareja (misma contraseña)
- Eliminado sistema de onboarding
- Vista de registro personalizada en español usando Fortify
- Actualizacion del dashboard
- Actualizacion de las opciones de configuración
- Documentado Roadmap

## *0.0.1*
- Version inicial del proyecto 🎉
- Se traduce toda la app al español
- Sistema de Onboard implementado