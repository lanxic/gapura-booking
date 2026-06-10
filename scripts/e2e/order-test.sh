#!/usr/bin/env bash
set -euo pipefail

API_URL="http://localhost:8000/v1"

# Change these IDs if your database differs
PRODUCT_SLUG="safari-legend-selasa-minggu"
VARIANT_ID=1
PRODUCT_ID=1
SLOT_ID=9

PAYLOAD=$(cat <<JSON
{
  "product_slug": "${PRODUCT_SLUG}",
  "slot_id": ${SLOT_ID},
  "date": "$(date -I -d "+1 day")",
  "customer_name": "E2E Tester",
  "customer_email": "e2e.tester@example.com",
  "customer_phone": "+621234567890",
  "payment_type": "full",
  "variant_id": ${VARIANT_ID},
  "unit_price_adult": 185000,
  "unit_price_child": 130000,
  "qty_adult": 1,
  "qty_child": 0,
  "items": [
    { "product_id": ${PRODUCT_ID}, "variant_id": ${VARIANT_ID}, "slot_id": ${SLOT_ID}, "qty_adult": 1, "qty_child": 0, "addons": [] }
  ]
}
JSON
)

printf "Sending order payload to %s/orders\n" "$API_URL"
RESPONSE=$(curl -sS -w "\nHTTP_STATUS:%{http_code}\n" -X POST "$API_URL/orders" -H 'Content-Type: application/json' -d "$PAYLOAD")

echo "$RESPONSE"

if echo "$RESPONSE" | grep -q "HTTP_STATUS:201"; then
  echo "E2E: Order created successfully"
  exit 0
else
  echo "E2E: Order creation failed"
  exit 2
fi
