![Logo ENS](public/logo.svg)

# ENS App

Aplicación web para la gestión del movimiento "Equipos de Nuestra Señora" (ENS), diseñada con enfoque **mobile first** y optimizada como **Progressive Web App (PWA)**.

## 🚀 Tecnologías

### Backend
- **Laravel 12** (PHP 8.4)
- **Inertia.js v2** - Integración SPA sin API
- **Laravel Fortify** - Autenticación
- **Laravel Wayfinder** - Rutas tipadas TypeScript
- **Pest 4** - Testing automatizado
- **Laravel Pint** - Formateo de código

### Frontend
- **React 19** con **TypeScript**
- **Tailwind CSS 4** - Estilos utility-first
- **Radix UI** - Componentes accesibles
- **Lucide React** - Iconos
- **Vite** - Build tool y HMR

## 📋 Características Principales

### Módulos Implementados

#### ✅ Fase 1: Módulo Parejas
- CRUD completo de parejas (roles mango/admin)
- Gestión de usuarios (él y ella) en la misma vista
- Búsqueda en tiempo real y scroll infinito
- Filtros por estado y equipo
- Funcionalidad de retiro y reactivación
- Configuración de pareja propia desde settings

#### ✅ Fase 2: Módulo Equipos
- CRUD completo de equipos
- Asignación de responsables con elevación automática de roles
- Configuración de Padre Consiliario por equipo
- Vista detalle con lista de parejas y usuarios
- Búsqueda y filtros avanzados

### Sistema de Roles
- **Mango**: Acceso completo al sistema
- **Admin**: Gestión de parejas y equipos
- **Equipista**: Acceso limitado a su información

### Optimizaciones
- **Sistema de thumbnails**: Generación automática de 3 tamaños (50x50, 100x100, 500x500)
- **Scroll infinito**: Carga progresiva de datos con Inertia v2
- **Imágenes optimizadas**: Almacenamiento local con thumbnails para carga rápida
- **Mobile-first**: Diseño responsive priorizado para móviles

## 🛠️ Requisitos Técnicos

### Principios de Desarrollo
- ✅ Código limpio con arquitectura separada por servicios
- ✅ Principio DRY (Don't Repeat Yourself)
- ✅ Máximo 500 líneas por archivo
- ✅ Documentación PHPDoc completa
- ✅ Tests automatizados con Pest

### Requisitos de Interfaz
- ✅ Diseño intuitivo con botones grandes
- ✅ Animaciones fluidas con Tailwind CSS
- ✅ Interfaz completamente en español
- ✅ PWA instalable en dispositivos

## 📦 Instalación

```bash
# Instalar dependencias PHP
composer install

# Instalar dependencias Node.js
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Poblar base de datos (opcional)
php artisan db:seed

# Compilar assets
npm run build
```

## 🏃 Desarrollo

```bash
# Iniciar servidor de desarrollo (Laravel + Vite + Queue)
composer run dev

# Ejecutar tests
php artisan test

# Formatear código PHP
vendor/bin/pint

# Formatear código TypeScript/React
npm run format

# Verificar tipos TypeScript
npm run types
```

## 📚 Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/        # Controladores principales
│   │   └── Settings/      # Controladores de configuración
│   └── Requests/          # Form Requests (validación)
├── Models/                # Modelos Eloquent
├── Services/              # Servicios de negocio
│   ├── ImageService.php   # Procesamiento de imágenes
│   └── PermissionService.php  # Gestión de permisos
└── ...

resources/
├── js/
│   ├── components/        # Componentes React reutilizables
│   ├── layouts/           # Layouts de Inertia
│   ├── pages/             # Páginas de Inertia
│   └── routes/            # Rutas tipadas (Wayfinder)
└── ...

tests/
├── Feature/               # Tests de integración
└── Unit/                  # Tests unitarios
```

## 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar tests con filtro
php artisan test --filter=ParejasTest

# Tests con cobertura
php artisan test --coverage
```

## 📖 Documentación

- **Roadmap**: Ver `roadmap.md` para el plan de desarrollo por fases
- **Change Notes**: Ver `changenotes.md` para el historial de cambios
- **Laravel Boost**: Herramientas MCP disponibles para desarrollo

## 🎯 Próximas Fases

- **Fase 3**: Módulo Calendario
- **Fase 4**: Cumpleaños y Aniversarios
- **Fase 5**: Notificaciones
- **Fase 6**: Eventos
- **Fase 7**: Asistencia a Formación
- **Fase 8**: Auditoría
- **Fase 9**: Guía ENS con IA (RAG)
- **Fase 10**: Informes y Exportación

## 📝 Acerca de ENS

Los Equipos de Nuestra Señora (ENS) constituyen un movimiento de la Iglesia Católica dedicado a cultivar la espiritualidad conyugal y fortalecer el Sacramento del Matrimonio. A través de una metodología basada en el apoyo mutuo, pequeños grupos de parejas se reúnen para progresar en su fe y ser testigos del amor cristiano en la sociedad contemporánea. Los miembros se comprometen con puntos concretos de esfuerzo, tales como la oración diaria, el diálogo conyugal profundo y el retiro espiritual anual. La estructura del movimiento integra la participación de un consiliario espiritual, promoviendo la colaboración esencial entre el matrimonio y el sacerdocio.

## 📄 Licencia

Este proyecto es de código abierto y está licenciado bajo la [Licencia MIT](LICENSE).
