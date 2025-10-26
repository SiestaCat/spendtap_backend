# SpendTap Backend

Backend API para la aplicación SpendTap construido con Symfony.

## Requisitos

- PHP 8.2 o superior
- Composer
- Extensiones PHP requeridas:
  - ext-ctype
  - ext-iconv

## Instalación

1. Clona el repositorio:
```bash
git clone <repository-url>
cd spendtap_backend
```

2. Instala las dependencias:
```bash
composer install
```

3. Configura las variables de entorno:
```bash
cp .env .env.local
# Edita .env.local con tu configuración de base de datos
```

4. Crea la base de datos:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Ejecutar el servidor

Para ejecutar la aplicación en desarrollo, usa el servidor integrado de PHP:

```bash
php -S localhost:8000 -t public/
```

El servidor estará disponible en: `http://localhost:8000`

## API Endpoints

Consulta la documentación completa de la API en [api.md](api.md).

### Endpoint principal:
- `POST /api/spent` - Crear nueva entrada de gasto

## Ejemplo de uso

```bash
curl -X POST http://localhost:8000/api/spent \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Almuerzo",
    "category": "Comida", 
    "amount": "15.50"
  }'
```

## Comandos útiles

- Ver rutas disponibles: `php bin/console debug:router`
- Limpiar cache: `php bin/console cache:clear`
- Generar entidades: `php bin/console make:entity`
- Generar controladores: `php bin/console make:controller`