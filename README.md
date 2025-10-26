# SpendTap Backend

Backend API para la aplicación SpendTap construido con Symfony.

## Requisitos

- PHP 8.2 o superior
- Composer
- Docker y Docker Compose (recomendado)
- Extensiones PHP requeridas:
  - ext-ctype
  - ext-iconv
  - ext-pdo_sqlite

## Instalación con Docker (Recomendado)

1. Clona el repositorio:
```bash
git clone <repository-url>
cd spendtap_backend
```

2. Construir y ejecutar con Docker Compose:
```bash
docker-compose up -d --build
```

El servidor estará disponible en: `http://localhost:8000`

### Comandos Docker Útiles

```bash
# Ver logs de la aplicación
docker-compose logs -f

# Acceder al contenedor
docker-compose exec spendtap-api bash

# Parar la aplicación
docker-compose down

# Parar y eliminar volúmenes
docker-compose down -v
```

## Instalación Manual (Desarrollo)

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
# Edita .env.local con tu configuración
```

4. Crea la base de datos:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Ejecutar el servidor (Desarrollo)

Para ejecutar la aplicación en desarrollo, usa el servidor integrado de PHP:

```bash
export APP_API_TOKEN="your-secure-api-token-here"
php -S localhost:8000 -t public/
```

El servidor estará disponible en: `http://localhost:8000`

## API Endpoints

Consulta la documentación completa de la API en [api.md](api.md).

### Endpoints principales:
- `POST /api/spent/create` - Crear nueva entrada de gasto
- `GET /api/spent/filter` - Filtrar gastos por mes/año/categorías
- `GET /api/spent/breakdown_month` - Resumen financiero mensual
- `GET /api/spent/breakdown_year` - Resumen financiero anual

## Ejemplo de uso

```bash
# Crear una nueva entrada de gasto
curl -X POST http://localhost:8000/api/spent/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-secure-api-token-here" \
  -d '{
    "description": "Almuerzo",
    "category": "Comida", 
    "amount": "15.50"
  }'

# Obtener gastos de un mes específico
curl -X GET "http://localhost:8000/api/spent/filter?month=10&year=2024" \
  -H "Authorization: Bearer your-secure-api-token-here"
```

## Comandos útiles

- Ver rutas disponibles: `php bin/console debug:router`
- Limpiar cache: `php bin/console cache:clear`
- Generar entidades: `php bin/console make:entity`
- Generar controladores: `php bin/console make:controller`