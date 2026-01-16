# Change Notes

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