#!/bin/bash

# cbApp V1 Complete Testing Script
# Führt alle Tests mit einem Klick aus: Backend, Frontend, E2E

set -e

echo "🚀 cbApp V1 Complete Testing Suite"
echo "=================================="

# Überprüfe ob wir im richtigen Verzeichnis sind
if [ ! -f "CLAUDE.md" ]; then
    echo "❌ Fehler: Bitte das Script aus dem cbApp V1 Root-Verzeichnis ausführen"
    exit 1
fi

# Funktion für farbige Ausgabe
print_status() {
    echo -e "\033[1;34m$1\033[0m"
}

print_success() {
    echo -e "\033[1;32m✅ $1\033[0m"
}

print_error() {
    echo -e "\033[1;31m❌ $1\033[0m"
}

print_warning() {
    echo -e "\033[1;33m⚠️ $1\033[0m"
}

# Parse command line arguments
RUN_E2E=true
COVERAGE=false
while [[ $# -gt 0 ]]; do
  case $1 in
    --no-e2e)
      RUN_E2E=false
      shift
      ;;
    --coverage)
      COVERAGE=true
      shift
      ;;
    --help)
      echo "Usage: $0 [--no-e2e] [--coverage] [--help]"
      echo "  --no-e2e    Skip E2E tests"
      echo "  --coverage  Run with coverage reports"
      echo "  --help      Show this help"
      exit 0
      ;;
    *)
      echo "Unknown option $1"
      exit 1
      ;;
  esac
done

BACKEND_FAILED=false
FRONTEND_FAILED=false
E2E_FAILED=false

# Backend Tests
print_status "🔧 Backend Tests (Laravel/PHPUnit)"
echo "-----------------------------------"

cd backend

print_status "📋 Überprüfe Backend-Setup..."
if [ ! -f "vendor/autoload.php" ]; then
    echo "⚠️ Vendor-Ordner nicht gefunden. Führe composer install aus..."
    composer install
fi

if [ ! -f ".env" ]; then
    echo "⚠️ .env Datei nicht gefunden. Kopiere .env.example..."
    cp .env.example .env
    php artisan key:generate
fi

print_status "🧪 Führe Backend-Tests aus..."
if [ "$COVERAGE" = true ]; then
    if composer test:coverage; then
        print_success "Backend Tests bestanden (mit Coverage)"
    else
        print_error "Backend Tests fehlgeschlagen"
        BACKEND_FAILED=true
    fi
else
    if composer test; then
        print_success "Backend Tests bestanden"
    else
        print_error "Backend Tests fehlgeschlagen"
        BACKEND_FAILED=true
    fi
fi

cd ..

# Frontend Unit Tests
print_status "🎨 Frontend Unit Tests (Vue/Vitest)"
echo "------------------------------------"

cd frontend

print_status "📋 Überprüfe Frontend-Setup..."
if [ ! -d "node_modules" ]; then
    echo "⚠️ node_modules nicht gefunden. Führe pnpm install aus..."
    pnpm install
fi

print_status "🧪 Führe Frontend Unit-Tests aus..."
if [ "$COVERAGE" = true ]; then
    if pnpm test:coverage; then
        print_success "Frontend Unit Tests bestanden (mit Coverage)"
    else
        print_error "Frontend Unit Tests fehlgeschlagen"
        FRONTEND_FAILED=true
    fi
else
    if pnpm test; then
        print_success "Frontend Unit Tests bestanden"
    else
        print_error "Frontend Unit Tests fehlgeschlagen"
        FRONTEND_FAILED=true
    fi
fi

# E2E Tests
if [ "$RUN_E2E" = true ]; then
    print_status "🤖 E2E Tests (Playwright)"
    echo "--------------------------"
    
    print_status "📋 Überprüfe E2E Setup..."
    if [ ! -f "playwright.config.ts" ]; then
        print_warning "Playwright nicht konfiguriert - überspringe E2E Tests"
        E2E_FAILED=true
    else
        # Check if playwright browsers are installed
        if ! pnpm playwright --version > /dev/null 2>&1; then
            echo "⚠️ Playwright Browsers nicht gefunden. Installiere..."
            pnpm playwright install
        fi
        
        print_status "🧪 Führe E2E-Tests aus..."
        if pnpm test:e2e; then
            print_success "E2E Tests bestanden"
        else
            print_error "E2E Tests fehlgeschlagen"
            E2E_FAILED=true
        fi
    fi
else
    print_warning "E2E Tests übersprungen (--no-e2e)"
fi

cd ..

# Test Zusammenfassung
echo ""
echo "📊 Test-Ergebnisse Zusammenfassung"
echo "=================================="

if [ "$BACKEND_FAILED" = false ]; then
    print_success "Backend Tests: ✅ Bestanden"
else
    print_error "Backend Tests: ❌ Fehlgeschlagen"
fi

if [ "$FRONTEND_FAILED" = false ]; then
    print_success "Frontend Tests: ✅ Bestanden"
else
    print_error "Frontend Tests: ❌ Fehlgeschlagen"
fi

if [ "$RUN_E2E" = true ]; then
    if [ "$E2E_FAILED" = false ]; then
        print_success "E2E Tests: ✅ Bestanden"
    else
        print_error "E2E Tests: ❌ Fehlgeschlagen"
    fi
fi

# Exit Code
if [ "$BACKEND_FAILED" = true ] || [ "$FRONTEND_FAILED" = true ] || [ "$E2E_FAILED" = true ]; then
    echo ""
    print_error "Einige Tests sind fehlgeschlagen!"
    echo ""
    echo "🔍 Debugging-Befehle:"
    echo "  Backend Details:     cd backend && composer test"
    echo "  Frontend Details:    cd frontend && pnpm test"
    if [ "$RUN_E2E" = true ]; then
        echo "  E2E Browser sichtbar: cd frontend && pnpm test:e2e:headed"
        echo "  E2E Test Report:     cd frontend && pnpm test:e2e:report"
    fi
    exit 1
else
    echo ""
    print_success "🎉 Alle Tests erfolgreich abgeschlossen!"
    echo ""
    echo "📈 Zusätzliche Befehle:"
    echo "  Test-Coverage:       ./test.sh --coverage"
    echo "  Nur Unit Tests:      ./test.sh --no-e2e"
    echo "  Frontend UI Tests:   cd frontend && pnpm test:ui"
    echo "  E2E Debug Mode:      cd frontend && pnpm test:e2e:debug"
    echo "  Watch Mode:          cd frontend && pnpm test:watch"
    exit 0
fi