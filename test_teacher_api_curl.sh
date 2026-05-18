#!/bin/bash

# Teacher Save API - cURL Test Script
# Endpoint: POST /api/admin/save-teacher

# Get authentication token first (replace with your actual login credentials)
AUTH_TOKEN="your_auth_token_here"

# API endpoint
API_URL="http://127.0.0.1:8000/api/admin/save-teacher"

echo "=========================================="
echo "Testing Teacher Save API"
echo "=========================================="
echo ""

# cURL request
curl -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $AUTH_TOKEN" \
  -d '{
    "admin_user_id": 1001,
    "teacherInfo": {
      "in_teacher_id": 0,
      "full_name": "Souvik Nag",
      "contact_no": "9876543219",
      "email": "souvik2@example.com",
      "highest_qualification": "M.Tech",
      "aadhar_no": "123456789012",
      "inst_id": 1,
      "inst_name": "ABC Pharmacy College",
      "designation_id": "1",
      "image": null,
      "remarks": "New teacher entry"
    },
    "subjectList": [
      {
        "dept_id": 1,
        "semester_id": 1,
        "subject_category_id": 1,
        "subject_id": 1
      },
      {
        "dept_id": 1,
        "semester_id": 1,
        "subject_category_id": 2,
        "subject_id": 2
      }
    ]
  }' | jq .

echo ""
echo "=========================================="
echo "Test Complete"
echo "=========================================="
