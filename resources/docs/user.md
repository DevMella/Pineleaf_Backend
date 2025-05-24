Here’s a clear and structured API documentation for your updated `search()` method:

---

# 📘 Admin User Search API

**Endpoint:** `GET /api/users/search`
**Auth Required:** ✅ Yes (Admin only)
**Description:**
Allows admin users to search for non-admin users by specific fields, with support for sorting and pagination.

---

## 🔐 Authorization

| Requirement | Value                           |
| ----------- | ------------------------------- |
| Role        | `admin`                         |
| Header      | `Authorization: Bearer {token}` |

---

## 📥 Query Parameters

Here is a clean and complete documentation for the `search` method you provided, suitable for internal developer docs or API documentation:

---

## 🔍 `search(Request $request)` – Admin User Search API

Search and filter non-admin users based on various parameters like email, name, referral code, enabled status, and registration dates.

### 📌 Endpoint

```
GET /api/users/search
```

### 🔐 Authorization

* **Required Role**: `admin`
* **Response on failure**:

  ```json
  {
    "message": "Unauthorized"
  }
  ```

  * **Status Code**: `403 Forbidden`

---

### 📥 Query Parameters

| Parameter   | Type         | Description                                                                                |
| ----------- | ------------ | ------------------------------------------------------------------------------------------ |
| `search`    | `string`     | Performs a partial match search across `email`, `fullName`, and `my_referral_code` fields. |
| `enabled`   | `0` or `1`   | Filters users by enabled status (`0` = disabled, `1` = enabled).                           |
| `from_date` | `YYYY-MM-DD` | Filters users registered on or after this date (based on `created_at`).                    |
| `to_date`   | `YYYY-MM-DD` | Filters users registered on or before this date.

---


## ✅ Success Response

**Status:** `200 OK`

Returns a paginated list of users:

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "email": "user@example.com",
      "fullName": "John Doe",
      "my_referral_code": "ABC123",
      ...
    }
  ],
  "first_page_url": "...",
  "last_page": 2,
  "per_page": 50,
  "total": 100,
  ...
}
```

---

## ❌ Error Responses

| Status | Message        | Description                   |
| ------ | -------------- | ----------------------------- |
| 403    | `Unauthorized` | When the user is not an admin |

---

## 🔍 Search Logic

* Performs a partial match (`LIKE`) on:

  * `email`
  * `fullName`
  * `my_referral_code`
* If the search term is numeric and the `number` column exists, performs exact match on `number`.

---

## 🔃 Sorting Logic

* Default: `created_at` (if available), otherwise `id`
* Sorts in descending order (`desc`)

---

## 📦 Example Requests

### 1. Basic request

```http
GET /api/users/search
```

### 2. Search by referral code

```http
GET /api/users/search?search=REF123
```
`

### ⚠ Notes

* Only non-admin users are returned.
* Date filters must be valid `YYYY-MM-DD` format strings.
* Search is case-insensitive and supports partial matches for selected fields.
* You can combine multiple filters in a single request.
