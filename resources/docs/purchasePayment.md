
## 📄 `GET /api/admin/transactions` – Filter Purchase Transactions

Retrieves a paginated list of **purchase** transactions. Supports filtering by date, status, exact amount, and amount range.

---

### 🔐 Authorization

* **Admin check**: Only users with the `admin` role can access this endpoint.
* **Authentication**: Requires a valid JWT token in the `Authorization` header.

### 📥 Query Parameters

| Parameter    | Type         | Description                                                          |
| ------------ | ------------ | -------------------------------------------------------------------- |
| `per_page`   | `integer`    | Number of results per page (default: `50`).                          |
| `from_date`  | `YYYY-MM-DD` | Start date for filtering transactions (`created_at >= from_date`).   |
| `to_date`    | `YYYY-MM-DD` | End date for filtering transactions (`created_at <= to_date`).       |
| `status`     | `string`     | Filter by transaction status (e.g., `success`, `pending`, `failed`). |
| `transaction_type`     | `string`     | Filter by transaction type (e.g., `purchase`, `registration`). |
| `amount`     | `number`     | Filter by an exact transaction amount.                               |
| `min_amount` | `number`     | Minimum transaction amount.                                          |
| `max_amount` | `number`     | Maximum transaction amount.                                          |

> **Note**: You can combine filters in one request.

---

### ✅ Success Response

**HTTP 200 OK**

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "user_id": 3,
      "amount": 50000.00,
      "transaction_type": "purchase",
      "ref_no": "rfer0bmioi",
      "property_purchased_id": null,
      "proof_of_payment": "rfer0bmioi",
      "status": "success",
      "created_at": "2025-05-09T13:00:36.000000Z",
      "updated_at": "2025-05-09T13:00:36.000000Z"
    },
    ...
  ],
  "last_page": 3,
  "total": 75,
  ...
}
```

---

### ❌ Error Response

**HTTP 500 Internal Server Error**

```json
{
  "success": false,
  "message": "Failed to retrieve transactions",
  "error": "Detailed error message here"
}
```

---

### 🔍 Example Requests

* All successful transactions:

  ```
  GET /api/admin/transactions?status=success
  ```

* Transactions from May 1 to May 9, 2025:

  ```
  GET /api/admin/transactions?from_date=2025-05-01&to_date=2025-05-09
  ```

* Transactions between ₦20,000 and ₦80,000:

  ```
  GET /api/admin/transactions?min_amount=20000&max_amount=80000
  ```

* Exact amount ₦50,000:

  ```
  GET /api/admin/transactions?amount=50000
  ```

---

Would you like me to generate this documentation in Swagger/OpenAPI format too?
