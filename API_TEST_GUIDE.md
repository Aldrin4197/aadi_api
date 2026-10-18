# 🧪 Aadi API - Quick Test Guide

## 🚀 Start Server
```bash
php artisan serve --port=8001
# API: http://127.0.0.1:8001/api
```

## 🔐 Authentication Tests

### Register New User
```bash
curl -X POST http://127.0.0.1:8001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"newuser@test.com","password":"password123","name":"New User"}' \
  | jq
```

### Login
```bash
curl -X POST http://127.0.0.1:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"maria@test.com","password":"password"}' \
  | jq > /tmp/auth.json

# Extract token
TOKEN=$(jq -r '.accessToken' /tmp/auth.json)
echo "Token: $TOKEN"
```

### Get Current User (/me)
```bash
curl -X GET http://127.0.0.1:8001/api/me \
  -H "Authorization: Bearer $TOKEN" \
  | jq
```

### Apply for Landlord
```bash
curl -X POST http://127.0.0.1:8001/api/me/landlord-application \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.landlordStatus'
```

### Logout
```bash
curl -X POST http://127.0.0.1:8001/api/auth/logout \
  -H "Authorization: Bearer $TOKEN"
```

---

## 🏠 Listing Tests

### Search Listings (Manila area, 5km radius)
```bash
curl -X GET "http://127.0.0.1:8001/api/listings?lat=14.5995&lng=120.9842&radiusKm=5" \
  -H "Authorization: Bearer $TOKEN" \
  | jq '[.[] | {title, type, price, city, distance}] | .[0:3]'
```

### Search with Filters
```bash
# Bedspace only, under 5000 pesos
curl -X GET "http://127.0.0.1:8001/api/listings?lat=14.6042&lng=120.9822&radiusKm=10&type=bedspace&maxPrice=5000" \
  -H "Authorization: Bearer $TOKEN" \
  | jq 'length'
```

### Get Single Listing
```bash
LISTING_ID="01kznddprjx5fj9cwmnyhtxts6"  # Replace with actual ID

curl -X GET "http://127.0.0.1:8001/api/listings/$LISTING_ID" \
  -H "Authorization: Bearer $TOKEN" \
  | jq '{id, title, ownerName, price, amenities}'
```

### Create New Listing (Landlords only)
```bash
# Login as landlord first
TOKEN=$(curl -X POST http://127.0.0.1:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"pedro@test.com","password":"password"}' -s \
  | jq -r '.accessToken')

curl -X POST http://127.0.0.1:8001/api/listings \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "New Bedspace in Manila",
    "description": "Affordable bedspace near university",
    "type": "bedspace",
    "price": 3000,
    "lat": 14.5833,
    "lng": 120.9833,
    "address": "Taft Avenue",
    "city": "Manila",
    "photos": ["https://example.com/photo1.jpg"],
    "amenities": ["WiFi", "Aircon"]
  }' \
  | jq '{id, title, ownerName}'
```

### Update Listing
```bash
curl -X PUT "http://127.0.0.1:8001/api/listings/$LISTING_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"price": 4500, "status": "active"}' \
  | jq '{id, title, price, status}'
```

### Get Listing Analytics
```bash
curl -X GET "http://127.0.0.1:8001/api/listings/$LISTING_ID/analytics" \
  -H "Authorization: Bearer $TOKEN" \
  | jq
```

### Get Listings by Owner
```bash
OWNER_ID="01kznddpd7pqf1kstyb3thd2yp"

curl -X GET "http://127.0.0.1:8001/api/users/$OWNER_ID/listings" \
  -H "Authorization: Bearer $TOKEN" \
  | jq '[.[] | {title, price, status}]'
```

---

## ❤️ Favorites Tests

### Get Favorites
```bash
curl -X GET http://127.0.0.1:8001/api/me/favorites \
  -H "Authorization: Bearer $TOKEN" \
  | jq
```

### Add to Favorites
```bash
curl -X PUT "http://127.0.0.1:8001/api/me/favorites/$LISTING_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -w "\nHTTP Status: %{http_code}\n"
```

### Remove from Favorites
```bash
curl -X DELETE "http://127.0.0.1:8001/api/me/favorites/$LISTING_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -w "\nHTTP Status: %{http_code}\n"
```

---

## 📅 Viewing Request Tests

### Get All Viewing Requests
```bash
curl -X GET http://127.0.0.1:8001/api/me/viewings \
  -H "Authorization: Bearer $TOKEN" \
  | jq '[.[] | {id, listingTitle, status, requestedAt}]'
```

### Create Viewing Request
```bash
curl -X POST http://127.0.0.1:8001/api/me/viewings \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "listingId": "'$LISTING_ID'",
    "requestedAt": "2026-08-15T14:00:00.000Z",
    "note": "Interested in viewing this property"
  }' \
  | jq '{id, listingTitle, status, requestedAt}'
```

### Update Viewing Request (Confirm)
```bash
VIEWING_ID="01kzne..."  # Replace with actual ID

# As landlord - confirm
curl -X PUT "http://127.0.0.1:8001/api/me/viewings/$VIEWING_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "confirmed"}' \
  | jq '{id, status}'
```

### Cancel Viewing Request
```bash
curl -X DELETE "http://127.0.0.1:8001/api/me/viewings/$VIEWING_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -w "\nHTTP Status: %{http_code}\n"
```

---

## 🔖 Saved Search Tests

### Get Saved Searches
```bash
curl -X GET http://127.0.0.1:8001/api/me/saved-searches \
  -H "Authorization: Bearer $TOKEN" \
  | jq
```

### Save a Search
```bash
curl -X POST http://127.0.0.1:8001/api/me/saved-searches \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Bedspace near UST",
    "query": {
      "lat": 14.6091,
      "lng": 120.9894,
      "radiusKm": 2,
      "type": "bedspace",
      "maxPrice": 5000
    }
  }' \
  | jq '{id, name, query}'
```

### Delete Saved Search
```bash
SEARCH_ID="01kzne..."  # Replace with actual ID

curl -X DELETE "http://127.0.0.1:8001/api/me/saved-searches/$SEARCH_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -w "\nHTTP Status: %{http_code}\n"
```

---

## 👤 User Tests

### Get Public User Profile
```bash
USER_ID="01kznddp22axy0en7zqr2rvsfx"

curl -X GET "http://127.0.0.1:8001/api/users/$USER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  | jq '{id, name, landlordStatus, trustScore, badges}'
```

---

## 🧪 Test All Test Accounts

```bash
# Test each account
for email in "maria@test.com" "juan@test.com" "pedro@test.com" "ana@test.com" "carlos@test.com"; do
  echo "Testing: $email"
  curl -X POST http://127.0.0.1:8001/api/auth/login \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$email\",\"password\":\"password\"}" -s \
    | jq '{email: .user.email, name: .user.name, landlordStatus: .user.landlordStatus}'
  echo ""
done
```

---

## 📊 Complete Test Flow

```bash
#!/bin/bash
echo "=== Aadi API Complete Test Flow ==="

# 1. Register
echo -e "\n1. Registering new user..."
REGISTER_RESPONSE=$(curl -X POST http://127.0.0.1:8001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"testflow@test.com","password":"password","name":"Test Flow"}' -s)
echo "$REGISTER_RESPONSE" | jq '{id: .user.id, name: .user.name}'

# 2. Login
echo -e "\n2. Logging in as Maria (tenant)..."
TOKEN=$(curl -X POST http://127.0.0.1:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"maria@test.com","password":"password"}' -s \
  | jq -r '.accessToken')
echo "Token: ${TOKEN:0:30}..."

# 3. Get Profile
echo -e "\n3. Getting user profile..."
curl -X GET http://127.0.0.1:8001/api/me \
  -H "Authorization: Bearer $TOKEN" -s \
  | jq '{name, landlordStatus, trustScore}'

# 4. Search Listings
echo -e "\n4. Searching listings near Manila..."
LISTINGS=$(curl -X GET "http://127.0.0.1:8001/api/listings?lat=14.5995&lng=120.9842&radiusKm=5" \
  -H "Authorization: Bearer $TOKEN" -s)
LISTING_ID=$(echo "$LISTINGS" | jq -r '.[0].id')
echo "$LISTINGS" | jq '[.[] | {title, price, distance}] | .[0:2]'

# 5. View Listing
echo -e "\n5. Viewing listing details..."
curl -X GET "http://127.0.0.1:8001/api/listings/$LISTING_ID" \
  -H "Authorization: Bearer $TOKEN" -s \
  | jq '{title, ownerName, price}'

# 6. Add to Favorites
echo -e "\n6. Adding to favorites..."
curl -X PUT "http://127.0.0.1:8001/api/me/favorites/$LISTING_ID" \
  -H "Authorization: Bearer $TOKEN" -s -o /dev/null -w "HTTP %{http_code}\n"

# 7. Get Favorites
echo -e "\n7. Getting favorites..."
curl -X GET http://127.0.0.1:8001/api/me/favorites \
  -H "Authorization: Bearer $TOKEN" -s \
  | jq 'length'

# 8. Create Viewing Request
echo -e "\n8. Creating viewing request..."
curl -X POST http://127.0.0.1:8001/api/me/viewings \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"listingId\":\"$LISTING_ID\",\"requestedAt\":\"2026-08-15T14:00:00.000Z\",\"note\":\"Interested!\"}" -s \
  | jq '{id, listingTitle, status}'

# 9. Save Search
echo -e "\n9. Saving search..."
curl -X POST http://127.0.0.1:8001/api/me/saved-searches \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"My Saved Search","query":{"lat":14.5995,"lng":120.9842,"radiusKm":5}}' -s \
  | jq '{id, name}'

echo -e "\n✅ Test flow complete!"
```

---

## 📝 Test Accounts Credentials

```
Tenant 1:    maria@test.com  / password (Trust Score: 85)
Tenant 2:    juan@test.com   / password (Trust Score: 72)
Landlord 1:  pedro@test.com  / password (Trust Score: 92, has listings)
Landlord 2:  ana@test.com    / password (Trust Score: 88, has listings)
Pending:     carlos@test.com / password (Pending landlord approval)
```

---

## 🔍 Debug Tips

### Check Server Logs
```bash
tail -f storage/logs/laravel.log
```

### Verify Database
```bash
mysql -u root aadi -e "SELECT id, name, email, landlord_status FROM users;"
mysql -u root aadi -e "SELECT id, title, type, price, status FROM listings;"
```

### Test Routes
```bash
php artisan route:list --path=api
```

### Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## 🎯 Expected HTTP Status Codes

- `200` - Success (GET, PUT)
- `201` - Created (POST register, POST listings)
- `204` - No Content (DELETE, PUT favorites)
- `401` - Unauthorized (missing/invalid token)
- `403` - Forbidden (wrong owner)
- `404` - Not Found (invalid ID)
- `422` - Validation Error (invalid input)

---

**All tests should pass! 🎉**
