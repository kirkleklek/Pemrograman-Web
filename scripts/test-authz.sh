#!/usr/bin/env bash

set -euo pipefail

# Jalankan dari root proyek, bukan bergantung pada direktori pemanggil.
cd "$(dirname "${BASH_SOURCE[0]}")/.."

if ! command -v php >/dev/null 2>&1; then
    echo "ERROR: PHP tidak ditemukan di PATH."
    exit 1
fi

tests=(
    tests/Feature/AccessIntegrationTest.php
    tests/Feature/AssignmentApiTest.php
    tests/Feature/AssignmentWebTest.php
    tests/Feature/CourseApiTest.php
    tests/Feature/CourseManagementTest.php
    tests/Feature/EnrollmentManagementTest.php
    tests/Feature/GradeApiTest.php
    tests/Feature/IdorIntegrationTest.php
    tests/Feature/MaterialApiTest.php
    tests/Feature/MaterialWebTest.php
    tests/Feature/NotificationApiTest.php
    tests/Feature/PolicyAuthorizationTest.php
    tests/Feature/ResourceOwnershipTest.php
    tests/Feature/RoleMiddlewareTest.php
    tests/Feature/SubmissionApiTest.php
    tests/Feature/UserManagementTest.php
)

echo "Memeriksa file test otorisasi..."

for test_file in "${tests[@]}"; do
    if [[ ! -f "$test_file" ]]; then
        echo "ERROR: File test tidak ditemukan: $test_file"
        exit 1
    fi
done

echo "Menjalankan pengujian otorisasi dan IDOR..."

php artisan test --env=testing "${tests[@]}"

echo "SEMUA PENGUJIAN OTORISASI BERHASIL."
