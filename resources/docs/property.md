# **Property Search API Documentation**

Search properties using flexible filters such as name, location, price range, type, purpose, and more.

---

## **Endpoint**

```
GET /api/properties/search
```

---

## **Query Parameters**

| Parameter        | Type      | Description                                                             |
| ---------------- | --------- | ----------------------------------------------------------------------- |
| `name`           | `string`  | Search by property `name` or `estate_name`.                             |
| `location`       | `string`  | Filter properties by `location` or `estate_name`.                       |
| `min_price`      | `number`  | Minimum price for the property.                                         |
| `max_price`      | `number`  | Maximum price for the property.                                         |
| `type`           | `string`  | Property type. Accepts: `land`, `house`.                                |
| `purpose`        | `string`  | Purpose of property. Accepts: `residential`, `commercial`, `mixed_use`. |
| `size`           | `string`  | Search by property `size` (e.g. `500sqm`).                              |
| `document_title` | `string`  | Filter by document title (e.g. `Certificate of Occupancy`).             |
| `units`          | `boolean` | If `true`, return only properties with available units.                 |
| `sort_by`        | `string`  | Field to sort by. Default: `created_at`.                                |
| `sort_direction` | `string`  | Sort direction: `asc` or `desc`. Default: `desc`.                       |
| `page`           | `number`  | Page number for pagination. Default: `1`.                               |
| `per_page`       | `number`  | Number of items per page. Default: `10`.                                |

---

## **Basic Search Examples**

### 0. Basic search with no filters (returns all properties with default pagination)

```
GET /api/properties/search
```

### 1. Search by name
```
GET /api/properties/search?name=oganiru
```

### 2. Search by location

```
GET /api/properties/search?location=Lagos
```

### 3. Search by price range

```
GET /api/properties/search?min_price=100000&max_price=500000
```

### 4. Search by location and price range

```
GET /api/properties/search?location=Lagos&min_price=100000&max_price=500000
```

### 5. Search with pagination

```
GET /api/properties/search?page=1&per_page=20
```

---

### 6. Search by property type

```
GET /api/properties/search?type=land
```

### 7. Search by property purpose

```
GET /api/properties/search?purpose=residential
```

### 8. Complex search with multiple filters

```
GET /api/properties/search?location=Lagos&min_price=200000&max_price=1000000&type=house&purpose=residential
```

### 9. Search with custom sorting

```
GET /api/properties/search?sort_by=price&sort_direction=asc
```

### 10. Search for properties with specific document title

```
GET /api/properties/search?document_title=Certificate+of+Occupancy
```

---

## **Full Example with All Parameters**

```
GET /api/properties/search?location=Lagos&min_price=100000&max_price=500000&type=house&purpose=residential&document_title=Certificate+of+Occupancy&units=true&size=500sqm&page=1&per_page=10&sort_by=price&sort_direction=asc
```

---

## **Response Structure**

### ✅ Success (200)

```json
{
  "success": true,
  "message": "Properties retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Oganiru Estate",
        "location": "Lagos",
        "price": 450000,
        "type": "house",
        "purpose": "residential",
        "size": "500sqm",
        "document_title": "Certificate of Occupancy",
        "landmark": [ ... ],
        "property_features": [ ... ],
        "images": [ ... ],
        ...
      }
    ],
    "total": 1,
    "last_page": 1,
    ...
  }
}
```

### ❌ Not Found (404)

```json
{
  "success": false,
  "message": "No properties found",
  "data": []
}
```

### ❌ Server Error (500)

```json
{
  "success": false,
  "message": "Failed to search properties",
  "error": "Detailed error message"
}
```

---

## Notes

* Fields like `landmark`, `property_features`, and `images` are decoded from JSON.
* Sorting is only allowed on valid fields (default is `created_at`).
* Booleans like `units=true` should be passed as actual booleans (`true`/`false`).
* Invalid values for filters (e.g. `type=apartment`) are ignored.

---
