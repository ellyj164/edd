#!/bin/bash
# Google OAuth Integration Verification Script
# This script checks that all necessary components are in place

echo "=================================================="
echo "Google OAuth Integration Verification"
echo "=================================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

ERRORS=0
WARNINGS=0

# Check 1: OAuth callback file exists
echo -n "✓ Checking OAuth callback file... "
if [ -f "auth/google-callback.php" ]; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    echo "  auth/google-callback.php not found"
    ERRORS=$((ERRORS+1))
fi

# Check 2: Database migration file exists
echo -n "✓ Checking database migration file... "
if [ -f "database/migrations/024_add_oauth_fields.sql" ]; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    echo "  database/migrations/024_add_oauth_fields.sql not found"
    ERRORS=$((ERRORS+1))
fi

# Check 3: Documentation exists
echo -n "✓ Checking setup documentation... "
if [ -f "GOOGLE_LOGIN_SETUP.md" ]; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    echo "  GOOGLE_LOGIN_SETUP.md not found"
    ERRORS=$((ERRORS+1))
fi

# Check 4: Login page has Google button
echo -n "✓ Checking login page for Google button... "
if grep -q "Continue with Google" login.php; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    echo "  'Continue with Google' button not found in login.php"
    ERRORS=$((ERRORS+1))
fi

# Check 5: Register page has Google button
echo -n "✓ Checking register page for Google button... "
if grep -q "Sign up with Google" register.php; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    echo "  'Sign up with Google' button not found in register.php"
    ERRORS=$((ERRORS+1))
fi

# Check 6: Composer dependencies
echo -n "✓ Checking Composer dependencies... "
if [ -f "composer.json" ] && grep -q "league/oauth2-google" composer.json; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    echo "  league/oauth2-google not found in composer.json"
    ERRORS=$((ERRORS+1))
fi

# Check 7: Vendor directory exists
echo -n "✓ Checking vendor directory... "
if [ -d "vendor" ] && [ -d "vendor/league/oauth2-google" ]; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${YELLOW}WARNING${NC}"
    echo "  Vendor directory not found or incomplete. Run: composer install"
    WARNINGS=$((WARNINGS+1))
fi

# Check 8: .env.example has Google OAuth variables
echo -n "✓ Checking .env.example for OAuth variables... "
if grep -q "GOOGLE_CLIENT_ID" .env.example && grep -q "GOOGLE_CLIENT_SECRET" .env.example; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FAILED${NC}"
    echo "  GOOGLE_CLIENT_ID or GOOGLE_CLIENT_SECRET not found in .env.example"
    ERRORS=$((ERRORS+1))
fi

# Check 9: .env file configuration (only warning if not configured)
echo -n "✓ Checking .env file configuration... "
if [ -f ".env" ]; then
    if grep -q "GOOGLE_CLIENT_ID=" .env && ! grep -q "GOOGLE_CLIENT_ID=$" .env; then
        echo -e "${GREEN}OK - Configured${NC}"
    else
        echo -e "${YELLOW}WARNING${NC}"
        echo "  GOOGLE_CLIENT_ID not configured in .env file"
        echo "  Follow GOOGLE_LOGIN_SETUP.md to configure OAuth credentials"
        WARNINGS=$((WARNINGS+1))
    fi
else
    echo -e "${YELLOW}WARNING${NC}"
    echo "  .env file not found. Copy .env.example to .env and configure"
    WARNINGS=$((WARNINGS+1))
fi

# Check 10: Mobile navigation fixes
echo -n "✓ Checking mobile header scroll behavior... "
if grep -q "window.innerWidth > 768) return;" templates/header.php; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${YELLOW}WARNING${NC}"
    echo "  Mobile header scroll behavior may not be optimized"
    WARNINGS=$((WARNINGS+1))
fi

echo -n "✓ Checking mobile bottom nav scroll behavior... "
if grep -q "window.innerWidth > 768) return;" templates/footer.php; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${YELLOW}WARNING${NC}"
    echo "  Mobile bottom nav scroll behavior may not be optimized"
    WARNINGS=$((WARNINGS+1))
fi

echo ""
echo "=================================================="
echo "Summary"
echo "=================================================="

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Run database migration: php database/migrate.php"
    echo "2. Follow GOOGLE_LOGIN_SETUP.md to configure Google OAuth"
    echo "3. Test the login flow"
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}✓ Core components verified with $WARNINGS warning(s)${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Review warnings above"
    echo "2. Run database migration: php database/migrate.php"
    echo "3. Follow GOOGLE_LOGIN_SETUP.md to configure Google OAuth"
    echo "4. Test the login flow"
else
    echo -e "${RED}✗ Verification failed with $ERRORS error(s) and $WARNINGS warning(s)${NC}"
    echo ""
    echo "Please review the errors above and fix them before proceeding."
    exit 1
fi

echo ""
echo "For detailed setup instructions, see: GOOGLE_LOGIN_SETUP.md"
echo "=================================================="
