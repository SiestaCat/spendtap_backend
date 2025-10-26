# API Documentation (Basic)

## Authentication
All endpoints require: `Authorization: Bearer <your-api-token>`

## Endpoints

### Create Spent Entry
- **POST** `/api/spent/create`
- **Body:** `{"description": "string", "category": "string", "amount": "number", "date": "string"}`
- **Required:** `amount`
- **Returns:** `{"id": 1, "description": "Lunch", "category": "Food", "amount": "25.50", "date": "2024-10-26 14:30:00", "month": 10, "year": 2024}`

### Get Recent Descriptions  
- **GET** `/api/spent/last_descriptions?limit=5`
- **Query:** `limit` (optional, default: 5, max: 100)
- **Returns:** `{"descriptions": ["Lunch", "Coffee", "Bus"], "count": 3, "limit": 5}`

### Get Recent Categories
- **GET** `/api/spent/last_categories?limit=5` 
- **Query:** `limit` (optional, default: 5, max: 100)
- **Returns:** `{"categories": ["Food", "Transportation", "Entertainment"], "count": 3, "limit": 5}`

### Filter by Month/Year
- **GET** `/api/spent/filter?month=10&year=2024`
- **Query:** `month` (1-12, required), `year` (1900-9999, required)
- **Returns:** `{"data": [{"id": 1, "description": "Lunch", "category": "Food", "amount": "25.50", "date": "2024-10-26 14:30:00", "month": 10, "year": 2024}], "count": 1, "filters": {"month": 10, "year": 2024}}`

### Delete Spent Entry
- **DELETE** `/api/spent/delete/{id}`
- **URL:** `{id}` - spent entry ID
- **Returns:** `{"message": "Spent entry deleted successfully", "id": 123}`

### Edit Spent Entry
- **PUT** `/api/spent/edit/{id}`
- **URL:** `{id}` - spent entry ID
- **Body:** `{"description": "string", "category": "string", "amount": "number", "date": "string"}` (all optional)
- **Returns:** `{"id": 123, "description": "Updated lunch", "category": "Food", "amount": "35.75", "date": "2024-10-26 15:00:00", "month": 10, "year": 2024}`

## Response Format
All endpoints return JSON with `data`, `count`, and relevant metadata.

## Error Codes
- **400:** Bad Request (validation errors)
- **401:** Unauthorized (missing/invalid token)  
- **404:** Not Found (spent entry doesn't exist)
- **500:** Internal Server Error