#!/bin/bash
# Script de test de l'API certificates (Reporting / attestations externes)
#
# Usage:
#   export BASE_URL="https://api.example.com/api/v1"
#   export TOKEN="votre_bearer_token"
#   ./scripts/test-certificates-api.sh
#
# Ou avec période (format YYYY-MM-DD,YYYY-MM-DD) :
#   ./scripts/test-certificates-api.sh "2025-01-01,2025-01-31"
#
# Ou avec ddev :
#   ddev exec ./scripts/test-certificates-api.sh "2025-01-01,2025-01-31"

set -e

BASE_URL="${BASE_URL:-}"
TOKEN="${TOKEN:-}"
PRINTED_AT="${1:-}"

if [ -z "$BASE_URL" ] || [ -z "$TOKEN" ]; then
    echo "Usage: BASE_URL et TOKEN requis (variables d'environnement)"
    echo ""
    echo "Exemple:"
    echo "  export BASE_URL=\"https://api.example.com/api/v1\""
    echo "  export TOKEN=\"eyJ0eXAiOiJKV1QiLCJhbGc...\""
    echo "  ./scripts/test-certificates-api.sh"
    echo ""
    echo "Avec période (du 1er au 31 janv. 2025):"
    echo "  ./scripts/test-certificates-api.sh \"2025-01-01,2025-01-31\""
    exit 1
fi

URL="${BASE_URL}/certificates"
if [ -n "$PRINTED_AT" ]; then
    URL="${URL}?printed_at=${PRINTED_AT}&per_page=10"
else
    URL="${URL}?per_page=10"
fi

echo "GET $URL"
echo ""

RESPONSE=$(curl -s -w "\n%{http_code}" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    "$URL")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo "HTTP $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" -ge 200 ] && [ "$HTTP_CODE" -lt 300 ]; then
    COUNT=$(echo "$BODY" | jq -r '.data | length' 2>/dev/null || echo "?")
    TOTAL=$(echo "$BODY" | jq -r '.meta.total // .meta.per_page // "?"' 2>/dev/null || echo "?")
    echo "Nombre d'attestations sur cette page: $COUNT"
    echo "Total (si dispo): $TOTAL"
    echo ""
    echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
else
    echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
    exit 1
fi
