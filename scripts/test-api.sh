#!/usr/bin/env bash

# KampusLMS Week 6 - API Authorization Test
#
# Jalankan dari Git Bash / WSL:
#   bash scripts/test-api.sh
#
# Token jangan ditulis langsung di file.
#
# Contoh:
#   export DOSEN_A_TOKEN="TOKEN_DOSEN_A"
#   export DOSEN_B_TOKEN="TOKEN_DOSEN_B"
#   export MAHASISWA_TOKEN="TOKEN_MAHASISWA"
#
# Sesuaikan ID resource bila data lokal berbeda.

BASE_URL="${BASE_URL:-http://kampuslms-kelompok-03.test/api/v1}"

DOSEN_A_TOKEN="${DOSEN_A_TOKEN:-}"
DOSEN_B_TOKEN="${DOSEN_B_TOKEN:-}"
MAHASISWA_TOKEN="${MAHASISWA_TOKEN:-}"

COURSE_A_ID="${COURSE_A_ID:-1}"
COURSE_B_ID="${COURSE_B_ID:-2}"
ASSIGNMENT_A_ID="${ASSIGNMENT_A_ID:-1}"
ASSIGNMENT_B_ID="${ASSIGNMENT_B_ID:-4}"
SUBMISSION_A_ID="${SUBMISSION_A_ID:-1}"

PASS=0
FAIL=0

request_status() {
    local expected="$1"
    local description="$2"
    shift 2

    local actual

    actual="$(curl -s -o /dev/null -w "%{http_code}" "$@")"

    if [[ "$actual" == "$expected" ]]; then
        printf "PASS  %-65s [%s]\n" "$description" "$actual"
        PASS=$((PASS + 1))
    else
        printf "FAIL  %-65s [expected %s, got %s]\n" \
            "$description" "$expected" "$actual"
        FAIL=$((FAIL + 1))
    fi
}

echo "=============================================="
echo " KampusLMS Week 6 - API Authorization Test"
echo "=============================================="
echo "Base URL: $BASE_URL"
echo

if [[ -z "$DOSEN_A_TOKEN" || -z "$DOSEN_B_TOKEN" || -z "$MAHASISWA_TOKEN" ]]; then
    echo "ERROR: Token belum diisi."
    echo
    echo "Contoh:"
    echo '  export DOSEN_A_TOKEN="TOKEN_DOSEN_A"'
    echo '  export DOSEN_B_TOKEN="TOKEN_DOSEN_B"'
    echo '  export MAHASISWA_TOKEN="TOKEN_MAHASISWA"'
    echo
    exit 1
fi

# ==================================================
# 1. AUTHENTICATION
# ==================================================

echo "[ AUTHENTICATION ]"
echo

request_status "401" \
    "GET /courses tanpa token -> 401" \
    "$BASE_URL/courses" \
    -H "Accept: application/json"

request_status "401" \
    "GET /me tanpa token -> 401" \
    "$BASE_URL/me" \
    -H "Accept: application/json"

request_status "401" \
    "GET /courses/{id}/assignments tanpa token -> 401" \
    "$BASE_URL/courses/$COURSE_A_ID/assignments" \
    -H "Accept: application/json"

request_status "401" \
    "GET /courses/{id}/materials tanpa token -> 401" \
    "$BASE_URL/courses/$COURSE_A_ID/materials" \
    -H "Accept: application/json"

request_status "401" \
    "GET /assignments/{id}/submissions tanpa token -> 401" \
    "$BASE_URL/assignments/$ASSIGNMENT_A_ID/submissions" \
    -H "Accept: application/json"

request_status "401" \
    "POST /assignments/{id}/submissions tanpa token -> 401" \
    -X POST \
    "$BASE_URL/assignments/$ASSIGNMENT_A_ID/submissions" \
    -H "Accept: application/json"

request_status "401" \
    "PUT /submissions/{id}/grade tanpa token -> 401" \
    -X PUT \
    "$BASE_URL/submissions/$SUBMISSION_A_ID/grade" \
    -H "Accept: application/json"

request_status "401" \
    "GET /notifications tanpa token -> 401" \
    "$BASE_URL/notifications" \
    -H "Accept: application/json"

request_status "401" \
    "POST /notifications/{id}/read tanpa token -> 401" \
    -X POST \
    "$BASE_URL/notifications/test/read" \
    -H "Accept: application/json"

# ==================================================
# 2. COURSE AUTHORIZATION
# ==================================================

echo
echo "[ COURSE AUTHORIZATION ]"
echo

# Hak benar.
request_status "200" \
    "GET /courses dengan token mahasiswa -> 200" \
    "$BASE_URL/courses" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $MAHASISWA_TOKEN"

request_status "200" \
    "GET /courses dengan token dosen A -> 200" \
    "$BASE_URL/courses" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_A_TOKEN"

request_status "200" \
    "GET course milik dosen B oleh dosen B -> 200" \
    "$BASE_URL/courses/$COURSE_B_ID" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_B_TOKEN"

# Dosen A tidak boleh membuka course Dosen B.
request_status "403" \
    "GET course milik dosen B oleh dosen A -> 403" \
    "$BASE_URL/courses/$COURSE_B_ID" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_A_TOKEN"

# ==================================================
# 3. COURSE MATERIALS
# ==================================================

echo
echo "[ COURSE MATERIALS ]"
echo

# Hak benar.
request_status "200" \
    "GET materials course milik dosen A -> 200" \
    "$BASE_URL/courses/$COURSE_A_ID/materials" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_A_TOKEN"

# Dosen A mencoba material course Dosen B.
request_status "403" \
    "GET materials course dosen B oleh dosen A -> 403" \
    "$BASE_URL/courses/$COURSE_B_ID/materials" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_A_TOKEN"

# ==================================================
# 4. COURSE ASSIGNMENTS
# ==================================================

echo
echo "[ COURSE ASSIGNMENTS ]"
echo

# Hak benar.
request_status "200" \
    "GET assignments course milik dosen B -> 200" \
    "$BASE_URL/courses/$COURSE_B_ID/assignments" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_B_TOKEN"

# Dosen A mencoba assignment list course Dosen B.
request_status "403" \
    "GET assignments course dosen B oleh dosen A -> 403" \
    "$BASE_URL/courses/$COURSE_B_ID/assignments" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_A_TOKEN"

# Mahasiswa tidak boleh membuat assignment.
request_status "403" \
    "POST /assignments oleh mahasiswa -> 403" \
    -X POST \
    "$BASE_URL/assignments" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $MAHASISWA_TOKEN" \
    -H "Content-Type: application/json" \
    -d "{\"course_id\":$COURSE_A_ID,\"title\":\"Authorization Test\",\"instructions\":\"Test\",\"max_score\":100,\"status\":\"draft\"}"

# Dosen A tidak boleh mengubah assignment Dosen B.
request_status "403" \
    "PUT assignment dosen B oleh dosen A -> 403" \
    -X PUT \
    "$BASE_URL/assignments/$ASSIGNMENT_B_ID" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_A_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"title":"Unauthorized Update"}'

# ==================================================
# 5. SUBMISSION AUTHORIZATION
# ==================================================

echo
echo "[ SUBMISSION AUTHORIZATION ]"
echo

# Mahasiswa tidak boleh melihat semua submission assignment.
request_status "403" \
    "GET submissions oleh mahasiswa -> 403" \
    "$BASE_URL/assignments/$ASSIGNMENT_A_ID/submissions" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $MAHASISWA_TOKEN"

# Dosen pemilik boleh melihat submission.
request_status "200" \
    "GET submissions oleh dosen pemilik -> 200" \
    "$BASE_URL/assignments/$ASSIGNMENT_A_ID/submissions" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_A_TOKEN"

# Dosen yang bukan pemilik tidak boleh melihat submission.
request_status "403" \
    "GET submissions oleh dosen bukan pemilik -> 403" \
    "$BASE_URL/assignments/$ASSIGNMENT_A_ID/submissions" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_B_TOKEN"

# Role yang salah tidak boleh submit tugas.
# Authorization FormRequest harus menolak dosen sebelum proses file.
request_status "403" \
    "POST submission oleh dosen -> 403" \
    -X POST \
    "$BASE_URL/assignments/$ASSIGNMENT_A_ID/submissions" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_A_TOKEN"

# ==================================================
# 6. GRADE AUTHORIZATION
# ==================================================

echo
echo "[ GRADE AUTHORIZATION ]"
echo

# Mahasiswa tidak boleh memberi nilai.
request_status "403" \
    "PUT grade oleh mahasiswa -> 403" \
    -X PUT \
    "$BASE_URL/submissions/$SUBMISSION_A_ID/grade" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $MAHASISWA_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"score":80,"feedback":"Authorization test"}'

# Dosen B tidak boleh memberi nilai pada submission milik Dosen A.
request_status "403" \
    "PUT grade submission dosen A oleh dosen B -> 403" \
    -X PUT \
    "$BASE_URL/submissions/$SUBMISSION_A_ID/grade" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $DOSEN_B_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"score":80,"feedback":"Authorization test"}'

# ==================================================
# 7. NOTIFICATION AUTHORIZATION
# ==================================================

echo
echo "[ NOTIFICATION AUTHORIZATION ]"
echo

# User yang login boleh melihat notification miliknya.
request_status "200" \
    "GET /notifications dengan token mahasiswa -> 200" \
    "$BASE_URL/notifications" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $MAHASISWA_TOKEN"

echo
echo "Catatan:"
echo "- Pengujian mark-as-read memakai NOTIFICATION_ID bila diberikan."
echo "- Untuk menghindari perubahan data berulang, test ownership memakai"
echo "  notification milik user lain dan hanya memverifikasi 403."

if [[ -n "${NOTIFICATION_ID:-}" ]]; then
    request_status "403" \
        "POST notification milik user lain -> 403" \
        -X POST \
        "$BASE_URL/notifications/$NOTIFICATION_ID/read" \
        -H "Accept: application/json" \
        -H "Authorization: Bearer $MAHASISWA_TOKEN"
else
    echo "SKIP  POST notification milik user lain -> isi NOTIFICATION_ID"
fi

# ==================================================
# RESULT
# ==================================================

echo
echo "=============================================="
echo "Hasil: PASS=$PASS FAIL=$FAIL"
echo "=============================================="

if [[ "$FAIL" -eq 0 ]]; then
    echo "Semua pengujian yang dijalankan berhasil."
    exit 0
fi

echo "Ada pengujian yang gagal."
exit 1