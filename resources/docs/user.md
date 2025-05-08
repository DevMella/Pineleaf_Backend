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

| Parameter  | Type     | Description                                                                                                                   |
| ---------- | -------- | ----------------------------------------------------------------------------------------------------------------------------- |
| `search`   | `string` | Search term that will be matched against `email`, `fullName`, or `my_referral_code`. If numeric, it will also match `number`. |
| `per_page` | `int`    | Number of records per page (default: 50, minimum: 1)                                                                          |
| `sort_by`  | `string` | Column to sort by (default: `created_at` if it exists, otherwise `id`)                                                        |

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

### 3. Paginate results

```http
GET /api/users/search?page=2&per_page=25
```

### 4. Sort by fullName (if column exists)

```http
GET /api/users/search?sort_by=fullName
```
