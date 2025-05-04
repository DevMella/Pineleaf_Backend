<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>API Documentation</title>
    <meta name="description" content="API Documentation" />
    <meta name="keywords" content="API, Documentation" />
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f9f9f9;
            color: #333;
        }

        h1,
        h2 {
            color: #2F5318;
            ;
        }

        details {
            background: #fff;
            padding: 15px;
            border-left: 5px solid #2F5318;
            ;
            margin-bottom: 20px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
        }

        summary {
            font-weight: bold;
            cursor: pointer;
        }

        code {
            background: #eee;
            padding: 5px;
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <h1>API Documentation</h1>
    </summary>
    <p><strong>Endpoint:</strong>
    <p>Base URL: <code style="background-color: #2F531833;">http://localhost:8000/api/</code></p>

    <details>
        <summary>Register User</summary>
        <p><strong>Endpoint:</strong><code>POST /create</code></p>
        <p><strong>Request Body:</strong></p>
        <pre>
{
    "fullname": "John Doe",
    "email": "john@example.com",
    "photoUrl": "https://example.com/photo.jpg"
}
      </pre>
        <p><strong>Response:</strong></p>
        <pre>
{
    "message": "User created successfully.",
    "token": "JWT_TOKEN",
    "data": { "id": 1, "fullname": "John Doe", "email": "john@example.com" }
}
      </pre>
    </details>

    <details>
        <summary>Login User</summary>
        <p><strong>Endpoint:</strong> <code>POST /login</code></p>
        <p><strong>Request Body:</strong></p>
        <pre>
{
    "email": "john@example.com"
}
      </pre>
        <p><strong>Response:</strong></p>
        <pre>
{
    "message": "User logged in successfully.",
    "token": "JWT_TOKEN",
    "data": { "id": 1, "fullname": "John Doe", "email": "john@example.com" }
}
      </pre>
    </details>

    <details>
        <summary>Search</summary>
        <p><strong>Endpoint:</strong> <code>GET </code>
            <code>
                // Basic search
                GET
                http://localhost:8000/api/admin/users/search?search=john

                // Search with pagination
                GET
                http://localhost:8000/api/admin/users/search?search=john&per_page=10&page=1

                // Search with sorting
                GET
                http://localhost:8000/api/admin/users/search?search=john&sort_by=email&sort_dir=asc

                // Search with all parameters
                GET
                http://localhost:8000/api/admin/users/search?search=john&per_page=10&page=1&sort_by=email&sort_dir=asc

                // Empty search (will return all non-admin users)
                GET
                http://localhost:8000/api/admin/users/search
            </code>
        </p>
        <p><strong>Response:</strong></p>
        <pre>
{
    "exists": true
}
      </pre>
    </details>

    <details>
        <summary>Get User Details</summary>
        <p><strong>Endpoint:</strong> <code>GET /:id</code></p>
        <p><strong>Headers:</strong> <code>Authorization: Bearer JWT_TOKEN</code></p>
        <p><strong>Response:</strong></p>
        <pre>
{
    "message": "User found successfully.",
    "data": { "id": 1, "fullname": "John Doe", "email": "john@example.com" }
}
      </pre>
    </details>

    <details>
        <summary>Delete User</summary>
        <p><strong>Endpoint:</strong> <code>DELETE /:id</code></p>
        <p><strong>Headers:</strong> <code>Authorization: Bearer JWT_TOKEN</code></p>
        <p><strong>Response:</strong></p>
        <pre>
{
    "message": "User deleted successfully."
}
      </pre>
    </details>



    <h1>Search Property</h1>
    <details>
        <summary>Search Property</summary>
        <p><strong>Endpoint:</strong> <code>GET /property/search</code></p>
        <p><strong>Request Body:</strong></p>
        <pre>
            # Basic Search Examples

# 0. Basic search with no filters (returns all properties with default pagination)
GET /api/properties/search

# 1. Search by name
GET /api/properties/search?name=oganiru

# 2. Search by location
GET /api/properties/search?location=Lagos

# 3. Search by price range
GET /api/properties/search?min_price=100000&max_price=500000

# 4. Search by location and price range
GET /api/properties/search?location=Lagos&min_price=100000&max_price=500000

# 5. Search with pagination
GET /api/properties/search?page=1&per_page=20

# Advanced Search Examples

# 6. Search by property type
GET /api/properties/search?type=land

# 7. Search by property purpose
GET /api/properties/search?purpose=residential

# 8. Complex search with multiple filters
GET /api/properties/search?location=Lagos&min_price=200000&max_price=1000000&type=house&purpose=residential

# 9. Search with custom sorting
GET /api/properties/search?sort_by=price&sort_direction=asc

# 10. Search for properties with specific document title
GET /api/properties/search?document_title=Certificate+of+Occupancy

# Full Example with All Parameters
GET /api/properties/search?location=Lagos&min_price=100000&max_price=500000&type=house&purpose=residential&document_title=Certificate+of+Occupancy&has_units_available=true&size=500sqm&page=1&per_page=10&sort_by=price&sort_direction=asc
        </pre>
        <p><strong>Response:</strong></p>
        <pre>
{
    "message": "Properties found successfully.",
    "data": [
        {
            "id": 1,
            "location": "Lagos",
            "price": 300000,
            "type": "house",
            "purpose": "residential",
            "document_title": "Certificate of Occupancy"
        },
        {
            "id": 2,
            "location": "Abuja",
            "price": 500000,
            "type": "land",
            "purpose": "commercial",
            "document_title": "Deed of Assignment"
        }
    ]
}
      </pre>
    </details>
</body>

</html>
