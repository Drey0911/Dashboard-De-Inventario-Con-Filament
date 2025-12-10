<p align="center">
  <strong style="font-size:36px">Gestión de Inventario con Filament</strong>
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-FF2D20?logo=laravel&logoColor=white" alt="Laravel"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-777BB4?logo=php&logoColor=white" alt="PHP"></a>
  <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-Admin-16B1FF" alt="Filament"></a>
  <a href="https://livewire.laravel.com"><img src="https://img.shields.io/badge/Livewire-Components-4E46E5" alt="Livewire"></a>
  <a href="https://vite.dev"><img src="https://img.shields.io/badge/Vite-646CFF?logo=vite&logoColor=white" alt="Vite"></a>
  <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind%20CSS-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS"></a>
  <a href="https://www.mysql.com"><img src="https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white" alt="MySQL"></a>
  <a href="https://getcomposer.org"><img src="https://img.shields.io/badge/Composer-885630?logo=composer&logoColor=white" alt="Composer"></a>
  <a href="https://nodejs.org"><img src="https://img.shields.io/badge/Node.js-339933?logo=node.js&logoColor=white" alt="Node.js"></a>
  <a href="https://github.com/Maatwebsite/Laravel-Excel"><img src="https://img.shields.io/badge/Maatwebsite%20Excel-Exports-5A29E4" alt="Maatwebsite Excel"></a>
  <a href="https://github.com/barryvdh/laravel-dompdf"><img src="https://img.shields.io/badge/DomPDF-PDF-CC0000" alt="DomPDF"></a>
  <a href="https://www.chartjs.org"><img src="https://img.shields.io/badge/Chart.js-FF6384?logo=chart.js&logoColor=white" alt="Chart.js"></a>
</p>

## Descripción

Aplicación para la gestión de inventario construida con Laravel y el panel administrativo Filament. Permite registrar entradas de productos, administrar productos, proveedores, categorías y clientes, visualizar métricas en un dashboard y exportar datos a Excel y PDF.

## Características

- Panel de control con estadísticas y gráficos por categorías: Entradas, Productos y Proveedores.
- Registro de entradas con actualización automática de stock del producto.
- Edición y eliminación de entradas con ajuste/reversión de stock correspondiente.
- Gestión completa de productos, proveedores, categorías y clientes.
- Filtros avanzados, búsqueda y acciones en tabla.
- Exportaciones a Excel y PDF.
- Frontend moderno con Vite + Tailwind CSS.

## Tecnologías

- Backend: Laravel, Eloquent ORM, Filament (Admin Panel), Livewire.
- Frontend: Vite, Tailwind CSS.
- Base de datos: MySQL.
- Exportación: Maatwebsite Excel, DomPDF.
- Gráficos: Chart.js (a través de Filament ChartWidget).

## Requisitos

- PHP 8.x
- Composer
- Node.js 18+ (para Vite)
- MySQL 5.7/8+

## Instalación

1. Clonar el proyecto.
2. Instalar dependencias de PHP:
   
   ```bash
   composer install
   ```

3. Copiar y configurar variables de entorno:
   
   ```bash
   cp .env.example .env
   # Editar .env y configurar DB_* y APP_URL
   ```

4. Generar clave de la aplicación:
   
   ```bash
   php artisan key:generate
   ```

5. Ejecutar migraciones:
   
   ```bash
   php artisan migrate
   ```

6. Instalar dependencias de frontend y levantar Vite:
   
   ```bash
   npm install
   npm run dev
   ```

7. Levantar el servidor de Laravel:
   
   ```bash
   php artisan serve
   ```

## Uso

- Accede al panel en `/dashboard`.
- Desde el menú de navegación de Filament, gestiona Entradas, Productos, Proveedores, Categorías y Clientes.

## Estructura Principal

- `app/Filament/Resources` — Recursos CRUD de Filament (Entradas, Productos, Proveedores, Categorías, Clientes).
- `app/Filament/Widgets` — Widgets del dashboard (estadísticas y gráficos).
- `app/Http/Controllers` — Controladores web para vistas tradicionales y exportaciones.
- `app/Models` — Modelos Eloquent.
- `resources/views` — Vistas Blade.
- `public/` — Assets públicos.
- `vite.config.js` — Configuración de Vite + Tailwind.

## Dashboard y Widgets

- Entradas:
  - `EntradasStats` — Estadísticas de entradas del día/mes.
  - `EntradasPorMesChart` — Gráfico de entradas por mes.
  - `TopProductosEntradasChart` — Top productos más entrados.
- Productos:
  - `ProductosStats` — Totales, activos, stock, valor del stock, bajo stock.
  - `ProductosBajoStock` — Tabla de productos con bajo stock.
- Proveedores:
  - `ProveedoresStats` — Totales, activos e inactivos.

Los widgets están ordenados por categorías para una lectura rápida: primero Entradas, luego Productos y finalmente Proveedores.

## Lógica de Stock de Entradas

- Al crear una entrada desde Filament, se incrementa el stock del producto automáticamente:
  - `app/Filament/Resources/EntradaResource/Pages/CreateEntrada.php:28-47`.
- Al crear una entrada desde el flujo de controlador clásico, también se incrementa el stock:
  - `app/Http/Controllers/EntradaController.php:64-81`.
- Al editar o eliminar entradas desde el flujo clásico, se ajusta/revierte el stock:
  - `app/Http/Controllers/EntradaController.php:102-142`, `app/Http/Controllers/EntradaController.php:144-154`.

## Exportaciones

- Excel:
  - `app/Exports/ProductosExport.php`
  - `app/Exports/EntradasExport.php`
- PDF:
  - `Barryvdh\\DomPDF` con vistas dedicadas.

## Scripts útiles

- `php artisan migrate` — Ejecuta migraciones.
- `php artisan serve` — Servidor local.
- `npm run dev` — Arranca Vite en modo desarrollo.

## Licencia

Proyecto basado en Laravel. Consulta la licencia correspondiente al framework y a los paquetes utilizados. Andrey Mantilla.
