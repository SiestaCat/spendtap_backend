# API Documentation

## Spent Endpoints

### Create Spent Entry

**Endpoint:** `POST /api/spent`

**Description:** Creates a new spent entry in the database.

**Request Headers:**
- `Content-Type: application/json`

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
curl -X POST http://localhost:8000/api/spent \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Lunch at restaurant",
    "category": "Food",
    "amount": "25.50",
    "date": "2024-10-26 14:30:00"
  }'
```

#### Create Spent Entry - Minimal Data (only amount)
```bash
curl -X POST http://localhost:8000/api/spent \
  -H "Content-Type: application/json" \
  -d '{
    "amount": "15.75"
  }'
```

#### Create Spent Entry - With Category Only
```bash
curl -X POST http://localhost:8000/api/spent \
  -H "Content-Type: application/json" \
  -d '{
    "category": "Transportation",
    "amount": "12.00"
  }'
```

#### Create Spent Entry - With Description and Amount
```bash
curl -X POST http://localhost:8000/api/spent \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Bus ticket to downtown",
    "amount": "3.25"
  }'
```

#### Create Spent Entry - With Custom Date
```bash
curl -X POST http://localhost:8000/api/spent \
  -H "Content-Type: application/json" \
  -d '{
    "description": "Grocery shopping",
    "category": "Food",
    "amount": "45.80",
    "date": "2024-10-25"
  }'
```

### Error Examples

#### Invalid JSON
```bash
curl -X POST http://localhost:8000/api/spent \
  -H "Content-Type: application/json" \
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
curl -X POST http://localhost:8000/api/spent \
  -H "Content-Type: application/json" \
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
curl -X POST http://localhost:8000/api/spent \
  -H "Content-Type: application/json" \
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