# API Documentation

## Authentication

All API endpoints require authentication using a Bearer token in the Authorization header.

**Header Required:**
- `Authorization: Bearer <your-api-token>`

The API token is configured via the `APP_API_TOKEN` environment variable.

**Authentication Error Responses:**

**401 Unauthorized - Missing Authorization Header:**
```json
{
    "error": "Authorization header required"
}
```

**401 Unauthorized - Invalid Token:**
```json
{
    "error": "Invalid API token"
}
```

## Spent Endpoints

### Create Spent Entry

**Endpoint:** `POST /api/spent/create`

**Description:** Creates a new spent entry in the database.

**Request Headers:**
- `Content-Type: application/json`
- `Authorization: Bearer <your-api-token>`

**Request Body Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `description` | string | No | Description of the expense (max 255 characters) |
| `category` | string | No | Category of the expense (max 255 characters) |
| `amount` | string/number | Yes | Amount spent (decimal with up to 20 digits, 2 decimal places) |
| `date` | string | No | Date of the expense in ISO format (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS). If not provided, current date/time is used |

**Response:**

**Success (201 Created):**
```json
{
    "id": 1,
    "description": "Lunch at restaurant",
    "category": "Food",
    "amount": "25.50",
    "date": "2024-10-26 14:30:00",
    "month": 10,
    "year": 2024
}
```

**Error (400 Bad Request):**
```json
{
    "error": "Amount is required and must be numeric"
}
```

**Error (500 Internal Server Error):**
```json
{
    "error": "Failed to create spent entry"
}
```

### cURL Examples

#### Create Spent Entry - Complete Data
```bash
curl -X POST http://localhost:8000/api/spent/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-secure-api-token-here" \
  -d '{
    "description": "Lunch at restaurant",
    "category": "Food",
    "amount": "25.50",
    "date": "2024-10-26 14:30:00"
  }'
```

#### Create Spent Entry - Minimal Data (only amount)
```bash
curl -X POST http://localhost:8000/api/spent/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-secure-api-token-here" \
  -d '{
    "amount": "15.75"
  }'
```

#### Create Spent Entry - With Category Only
```bash
curl -X POST http://localhost:8000/api/spent/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-secure-api-token-here" \
  -d '{
    "category": "Transportation",
    "amount": "12.00"
  }'
```

#### Create Spent Entry - With Description and Amount
```bash
curl -X POST http://localhost:8000/api/spent/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-secure-api-token-here" \
  -d '{
    "description": "Bus ticket to downtown",
    "amount": "3.25"
  }'
```

#### Create Spent Entry - With Custom Date
```bash
curl -X POST http://localhost:8000/api/spent/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-secure-api-token-here" \
  -d '{
    "description": "Grocery shopping",
    "category": "Food",
    "amount": "45.80",
    "date": "2024-10-25"
  }'
```

### Error Examples

#### Missing Authorization Header
```bash
curl -X POST http://localhost:8000/api/spent/create \
  -H "Content-Type: application/json" \
  -d '{
    "amount": "10.00"
  }'
```
**Response (401):**
```json
{
    "error": "Authorization header required"
}
```

#### Invalid API Token
```bash
curl -X POST http://localhost:8000/api/spent/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer invalid-token" \
  -d '{
    "amount": "10.00"
  }'
```
**Response (401):**
```json
{
    "error": "Invalid API token"
}
```

#### Invalid JSON
```bash
curl -X POST http://localhost:8000/api/spent/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-secure-api-token-here" \
  -d '{"amount": "invalid"}'
```
**Response (400):**
```json
{
    "error": "Amount is required and must be numeric"
}
```

#### Missing Amount
```bash
curl -X POST http://localhost:8000/api/spent/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-secure-api-token-here" \
  -d '{
    "description": "Test expense"
  }'
```
**Response (400):**
```json
{
    "error": "Amount is required and must be numeric"
}
```

#### Invalid Date Format
```bash
curl -X POST http://localhost:8000/api/spent/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-secure-api-token-here" \
  -d '{
    "amount": "10.00",
    "date": "invalid-date"
  }'
```
**Response (400):**
```json
{
    "error": "Invalid date format"
}
```

### Data Types Details

- **description**: Optional string, maximum 255 characters, can be null
- **category**: Optional string, maximum 255 characters, can be null  
- **amount**: Required decimal as string, supports up to 20 total digits with 2 decimal places (e.g., "999999999999999999.99")
- **date**: Optional datetime string in formats:
  - ISO 8601: `2024-10-26T14:30:00`
  - Date only: `2024-10-26`
  - DateTime: `2024-10-26 14:30:00`
  - If not provided, current server date/time is used
- **month**: Automatically calculated from the date (1-12)
- **year**: Automatically calculated from the date (4-digit year)

### Get Recent Descriptions

**Endpoint:** `GET /api/spent/last_descriptions`

**Description:** Returns the most recent unique descriptions from spent entries (non-null values only), ordered by ID descending.

**Request Headers:**
- `Authorization: Bearer <your-api-token>`

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `limit` | integer | No | 5 | Number of results to return (1-100) |

**Response (200 OK):**
```json
{
    "descriptions": [
        "Lunch at restaurant",
        "Bus ticket to downtown", 
        "Grocery shopping"
    ],
    "count": 3,
    "limit": 5
}
```

**Error (500 Internal Server Error):**
```json
{
    "error": "Failed to fetch descriptions"
}
```

### Get Recent Categories

**Endpoint:** `GET /api/spent/last_categories`

**Description:** Returns the most recent unique categories from spent entries (non-null values only), ordered by ID descending.

**Request Headers:**
- `Authorization: Bearer <your-api-token>`

**Query Parameters:**

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `limit` | integer | No | 5 | Number of results to return (1-100) |

**Response (200 OK):**
```json
{
    "categories": [
        "Food",
        "Transportation",
        "Entertainment"
    ],
    "count": 3,
    "limit": 5
}
```

**Error (500 Internal Server Error):**
```json
{
    "error": "Failed to fetch categories"
}
```

### Filter Spent Entries by Month and Year

**Endpoint:** `GET /api/spent/filter`

**Description:** Returns all spent entries filtered by specific month and year (no pagination).

**Request Headers:**
- `Authorization: Bearer <your-api-token>`

**Query Parameters (Required):**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `month` | integer | Yes | Month number (1-12) |
| `year` | integer | Yes | Year (1900-9999) |

**Response (200 OK):**
```json
{
    "data": [
        {
            "id": 3,
            "description": "Lunch at restaurant",
            "category": "Food",
            "amount": "25.50",
            "date": "2024-10-26 14:30:00",
            "month": 10,
            "year": 2024
        },
        {
            "id": 1,
            "description": "Coffee",
            "category": "Food",
            "amount": "4.50",
            "date": "2024-10-15 09:00:00",
            "month": 10,
            "year": 2024
        }
    ],
    "count": 2,
    "filters": {
        "month": 10,
        "year": 2024
    }
}
```

**Error (400 Bad Request) - Missing Parameters:**
```json
{
    "error": "Month and year parameters are required"
}
```

**Error (400 Bad Request) - Invalid Month:**
```json
{
    "error": "Month must be between 1 and 12"
}
```

**Error (400 Bad Request) - Invalid Year:**
```json
{
    "error": "Year must be between 1900 and 9999"
}
```

**Error (500 Internal Server Error):**
```json
{
    "error": "Failed to fetch spent entries"
}
```

### Delete Spent Entry

**Endpoint:** `DELETE /api/spent/delete/{id}`

**Description:** Deletes a specific spent entry by ID.

**Request Headers:**
- `Authorization: Bearer <your-api-token>`

**URL Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | ID of the spent entry to delete |

**Response (200 OK):**
```json
{
    "message": "Spent entry deleted successfully",
    "id": 123
}
```

**Error (404 Not Found):**
```json
{
    "error": "Spent entry not found"
}
```

**Error (500 Internal Server Error):**
```json
{
    "error": "Failed to delete spent entry"
}
```

### cURL Examples for New Endpoints

#### Get Recent Descriptions (Default Limit)
```bash
curl -X GET http://localhost:8000/api/spent/last_descriptions \
  -H "Authorization: Bearer your-secure-api-token-here"
```

#### Get Recent Descriptions (Custom Limit)
```bash
curl -X GET "http://localhost:8000/api/spent/last_descriptions?limit=10" \
  -H "Authorization: Bearer your-secure-api-token-here"
```

#### Get Recent Categories (Default Limit)
```bash
curl -X GET http://localhost:8000/api/spent/last_categories \
  -H "Authorization: Bearer your-secure-api-token-here"
```

#### Get Recent Categories (Custom Limit)
```bash
curl -X GET "http://localhost:8000/api/spent/last_categories?limit=20" \
  -H "Authorization: Bearer your-secure-api-token-here"
```

#### Filter Spent Entries by Month and Year
```bash
curl -X GET "http://localhost:8000/api/spent/filter?month=10&year=2024" \
  -H "Authorization: Bearer your-secure-api-token-here"
```

#### Filter Spent Entries for Different Month/Year
```bash
curl -X GET "http://localhost:8000/api/spent/filter?month=12&year=2023" \
  -H "Authorization: Bearer your-secure-api-token-here"
```

#### Delete Spent Entry
```bash
curl -X DELETE http://localhost:8000/api/spent/delete/123 \
  -H "Authorization: Bearer your-secure-api-token-here"
```

#### Delete Spent Entry (Example with ID 5)
```bash
curl -X DELETE http://localhost:8000/api/spent/delete/5 \
  -H "Authorization: Bearer your-secure-api-token-here"
```

