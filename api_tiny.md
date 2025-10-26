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
- **GET** `/api/spent/filter?month=10&year=2024&categories=["Food","Transport"]`
- **Query:** `month` (1-12, required), `year` (1900-9999, required), `categories` (array, optional, default: [])
- **Returns:** `{"data": [...], "count": 1, "filters": {"month": 10, "year": 2024, "categories": ["Food"]}}`

### Delete Spent Entry
- **DELETE** `/api/spent/delete/{id}`
- **URL:** `{id}` - spent entry ID
- **Returns:** `{"message": "Spent entry deleted successfully", "id": 123}`

### Edit Spent Entry
- **PUT** `/api/spent/edit/{id}`
- **URL:** `{id}` - spent entry ID
- **Body:** `{"description": "string", "category": "string", "amount": "number", "date": "string"}` (all optional)
- **Returns:** `{"id": 123, "description": "Updated lunch", "category": "Food", "amount": "35.75", "date": "2024-10-26 15:00:00", "month": 10, "year": 2024}`

### Get All Descriptions
- **GET** `/api/spent/all_descriptions`
- **Returns:** `{"descriptions": ["Coffee", "Lunch", "Transport"], "count": 3}`

### Get All Categories
- **GET** `/api/spent/all_categories`
- **Returns:** `{"categories": ["Food", "Transportation", "Entertainment"], "count": 3}`

### Copy Month Entries
- **POST** `/api/spent/copy_month`
- **Body:** `{"source_month": 8, "source_year": 2025, "target_month": 9, "target_year": 2025, "category": "Food"}` (category optional)
- **Returns:** `{"message": "Entries copied successfully", "copied_count": 15, "filters": {...}}`

### Monthly Breakdown
- **GET** `/api/spent/breakdown_month?month=10&year=2024`
- **Query:** `month` (1-12, required), `year` (1900-9999, required)
- **Returns:** `{"total": "1250.75", "expense_amount": "-800.25", "income_amount": "2051.00", "entry_count": 45, "filters": {...}}`

### Yearly Breakdown
- **GET** `/api/spent/breakdown_year?year=2024`
- **Query:** `year` (1900-9999, required)
- **Returns:** `{"total": "15420.50", "expense_amount": "-12800.75", "income_amount": "28221.25", "entry_count": 540, "filters": {...}}`

### Balance Before Date
- **GET** `/api/spent/balance?month=10&year=2024`
- **Query:** `month` (1-12, required), `year` (1900-9999, required)
- **Returns:** `{"balance": "5420.75", "entry_count": 125, "filters": {"before_month": 10, "before_year": 2024}}`

### Category Breakdown by Month
- **GET** `/api/spent/breakdown_category_month?month=10&year=2024`
- **Query:** `month` (1-12, required), `year` (1900-9999, required)
- **Returns:** `{"breakdown": [{"category": "Food", "total": "450.75", "expense_amount": "-320.25", "income_amount": "771.00", "entry_count": 15}], "count": 1, "filters": {...}}`

### Category Breakdown by Year
- **GET** `/api/spent/breakdown_category_year?year=2024`
- **Query:** `year` (1900-9999, required)
- **Returns:** `{"breakdown": [{"category": "Food", "total": "5420.75", "expense_amount": "-3850.25", "income_amount": "9271.00", "entry_count": 180}], "count": 1, "filters": {...}}`

### Description Breakdown by Month
- **GET** `/api/spent/breakdown_description_month?month=10&year=2024`
- **Query:** `month` (1-12, required), `year` (1900-9999, required)
- **Returns:** `{"breakdown": [{"description": "Lunch", "total": "150.75", "expense_amount": "-150.75", "income_amount": "0.00", "entry_count": 6}], "count": 1, "filters": {...}}`

### Description Breakdown by Year
- **GET** `/api/spent/breakdown_description_year?year=2024`
- **Query:** `year` (1900-9999, required)
- **Returns:** `{"breakdown": [{"description": "Salary", "total": "36000.00", "expense_amount": "0.00", "income_amount": "36000.00", "entry_count": 12}], "count": 1, "filters": {...}}`

## Response Format
All endpoints return JSON with `data`, `count`, and relevant metadata.

## Error Codes
- **400:** Bad Request (validation errors)
- **401:** Unauthorized (missing/invalid token)  
- **404:** Not Found (spent entry doesn't exist)
- **500:** Internal Server Error