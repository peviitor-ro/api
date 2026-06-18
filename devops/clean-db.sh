#!/bin/bash
set -e

echo "Step 1: Cleaning all jobs from DB..."
curl -s -X DELETE "https://api.peviitor.ro/v1/empty/" \
  -H "X-API-Key: ff564852-8c9f-4ef0-adca-b2987a4b960b" \
  -H "X-Cleanup-Secret: 62a8b3a0-c6e0-4aa0-b96b-40bc6ef92e64" \
  -H "Content-Type: application/json" \
  -d '{"confirmation": "DELETE_ALL_DATA"}' | jq .

echo ""
echo "Step 2: Verifying jobs are 0..."
sleep 2
TOTAL=$(curl -s "https://api.peviitor.ro/v1/total/" -H "Accept: application/json")
echo "$TOTAL" | jq .
JOBS=$(echo "$TOTAL" | jq -r '.total.jobs')

if [ "$JOBS" -eq 0 ]; then
  echo ""
  echo "Step 3: Triggering import workflow..."
  gh workflow run import.yml --repo sebiboga/peviitor-joblio-scraper
  echo "Import workflow triggered successfully!"
else
  echo "Jobs still present ($JOBS), not triggering import."
  exit 1
fi
