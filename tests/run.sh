#!/bin/bash
set -e

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
TESTS_DIR="$ROOT_DIR/tests"
RESULTS_FILE="$TESTS_DIR/results.json"
REPORT_FILE="$TESTS_DIR/report/index.html"
API_PORT=8080
MOCK_PORT=18983
E2E_MODE=${E2E:-0}

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "=============================="
echo "  peviitor API Test Suite"
echo "=============================="
echo ""

# Clean previous results
> "$RESULTS_FILE"

# ===== UNIT TESTS =====
echo -e "${YELLOW}[UNIT TESTS]${NC}"
UNIT_FILES=$(ls "$TESTS_DIR"/unit/Test*.php 2>/dev/null || true)
UNIT_PASSED=0
UNIT_TOTAL=0
UNIT_FAILED=0

for f in $UNIT_FILES; do
    NAME=$(basename "$f" .php)
    OUTPUT=$(php "$f" 2>&1) || true
    RESULT=$(echo "$OUTPUT" | grep '^{' | tail -1)
    if [ -n "$RESULT" ]; then
        echo "$RESULT" >> "$RESULTS_FILE"
        FAILED=$(echo "$RESULT" | php -r 'echo json_decode(file_get_contents("php://stdin"))->failed;')
        TOTAL=$(echo "$RESULT" | php -r 'echo json_decode(file_get_contents("php://stdin"))->total;')
        UNIT_TOTAL=$((UNIT_TOTAL + TOTAL))
        if [ "$FAILED" -gt 0 ]; then
            echo -e "  ${RED}✗${NC} $NAME ($FAILED/$TOTAL failed)"
            UNIT_FAILED=$((UNIT_FAILED + FAILED))
        else
            echo -e "  ${GREEN}✓${NC} $NAME ($TOTAL passed)"
            UNIT_PASSED=$((UNIT_PASSED + TOTAL))
        fi
    else
        echo -e "  ${RED}✗${NC} $NAME (no result)"
        echo "$OUTPUT" | while IFS= read -r line; do echo "     $line"; done
        UNIT_FAILED=$((UNIT_FAILED + 1))
    fi
done
echo ""

# ===== INTEGRATION TESTS =====
echo -e "${YELLOW}[INTEGRATION TESTS]${NC}"

# Backup and create test api.env
if [ -f "$ROOT_DIR/api.env" ]; then
    cp "$ROOT_DIR/api.env" "$ROOT_DIR/api.env.bak"
fi

cat > "$ROOT_DIR/api.env" << 'TESTENV'
PROD_SERVER = 127.0.0.1:18983
SOLR_USER = 
SOLR_PASS = 
CLEANUP_API_KEY = test-key-123456
CLEANUP_SECRET = test-secret-789012
NODE_ENV = test
TESTENV

cleanup() {
    # Kill servers
    kill $MOCK_PID $API_PID 2>/dev/null || true
    # Restore api.env
    if [ -f "$ROOT_DIR/api.env.bak" ]; then
        mv "$ROOT_DIR/api.env.bak" "$ROOT_DIR/api.env"
    fi
    wait 2>/dev/null || true
}
trap cleanup EXIT INT TERM

# Start mock Solr server
php -S "127.0.0.1:$MOCK_PORT" "$TESTS_DIR/integration/mock-handler.php" > /dev/null 2>&1 &
MOCK_PID=$!

# Start API server
php -S "127.0.0.1:$API_PORT" -t "$ROOT_DIR" > /dev/null 2>&1 &
API_PID=$!

# Wait for servers to be ready
sleep 1

INT_FILES=$(ls "$TESTS_DIR"/integration/Test*.php 2>/dev/null || true)
INT_PASSED=0
INT_TOTAL=0
INT_FAILED=0

for f in $INT_FILES; do
    NAME=$(basename "$f" .php)
    OUTPUT=$(php "$f" 2>&1) || true
    RESULT=$(echo "$OUTPUT" | grep '^{' | tail -1)
    if [ -n "$RESULT" ]; then
        echo "$RESULT" >> "$RESULTS_FILE"
        FAILED=$(echo "$RESULT" | php -r 'echo json_decode(file_get_contents("php://stdin"))->failed;')
        TOTAL=$(echo "$RESULT" | php -r 'echo json_decode(file_get_contents("php://stdin"))->total;')
        INT_TOTAL=$((INT_TOTAL + TOTAL))
        if [ "$FAILED" -gt 0 ]; then
            echo -e "  ${RED}✗${NC} $NAME ($FAILED/$TOTAL failed)"
            INT_FAILED=$((INT_FAILED + FAILED))
        else
            echo -e "  ${GREEN}✓${NC} $NAME ($TOTAL passed)"
            INT_PASSED=$((INT_PASSED + TOTAL))
        fi
    else
        echo -e "  ${RED}✗${NC} $NAME (no result)"
        echo "$OUTPUT" | while IFS= read -r line; do echo "     $line"; done
        INT_FAILED=$((INT_FAILED + 1))
    fi
done

# Kill servers before restoring env
kill $MOCK_PID $API_PID 2>/dev/null || true
wait 2>/dev/null || true
echo ""

# ===== E2E TESTS (only after deploy) =====
E2E_PASSED=0
E2E_TOTAL=0
E2E_FAILED=0

if [ "$E2E_MODE" = "1" ]; then
    echo -e "${YELLOW}[E2E TESTS]${NC}"
    E2E_FILES=$(ls "$TESTS_DIR"/e2e/Test*.php 2>/dev/null || true)
    for f in $E2E_FILES; do
        NAME=$(basename "$f" .php)
        OUTPUT=$(php "$f" 2>&1) || true
        RESULT=$(echo "$OUTPUT" | grep '^{' | tail -1)
        if [ -n "$RESULT" ]; then
            echo "$RESULT" >> "$RESULTS_FILE"
            FAILED=$(echo "$RESULT" | php -r 'echo json_decode(file_get_contents("php://stdin"))->failed;')
            TOTAL=$(echo "$RESULT" | php -r 'echo json_decode(file_get_contents("php://stdin"))->total;')
            E2E_TOTAL=$((E2E_TOTAL + TOTAL))
            if [ "$FAILED" -gt 0 ]; then
                echo -e "  ${RED}✗${NC} $NAME ($FAILED/$TOTAL failed)"
                E2E_FAILED=$((E2E_FAILED + FAILED))
            else
                echo -e "  ${GREEN}✓${NC} $NAME ($TOTAL passed)"
                E2E_PASSED=$((E2E_PASSED + TOTAL))
            fi
        else
            echo -e "  ${RED}✗${NC} $NAME (no result)"
            E2E_FAILED=$((E2E_FAILED + 1))
        fi
    done
fi

# ===== GENERATE HTML REPORT =====
php "$TESTS_DIR/generate-report.php" "$RESULTS_FILE" > "$REPORT_FILE"
echo -e "${YELLOW}[REPORT]${NC} $REPORT_FILE"
echo ""

# ===== SUMMARY =====
TOTAL_PASSED=$((UNIT_PASSED + INT_PASSED + E2E_PASSED))
TOTAL_FAILED=$((UNIT_FAILED + INT_FAILED + E2E_FAILED))
TOTAL_TOTAL=$((UNIT_TOTAL + INT_TOTAL + E2E_TOTAL))

echo "=============================="
echo "  SUMMARY"
echo "  Total: $TOTAL_TOTAL | Passed: $TOTAL_PASSED | Failed: $TOTAL_FAILED"
if [ "$TOTAL_FAILED" -gt 0 ]; then
    echo -e "  Result: ${RED}FAILED${NC}"
else
    echo -e "  Result: ${GREEN}PASSED${NC}"
fi
echo "=============================="

exit $TOTAL_FAILED
