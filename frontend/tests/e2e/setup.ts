import { test as setup, expect } from '@playwright/test'

// Setup für Test-Datenbank und Test-Daten
setup('prepare test environment', async ({ request }) => {
  try {
    // Reset Test-Datenbank (falls Backend-Endpoint verfügbar)
    await request.post('http://localhost:8004/api/testing/reset-database')
    
    // Seed Test-Daten
    await request.post('http://localhost:8004/api/testing/seed-test-data')
    
    console.log('✅ Test environment prepared successfully')
  } catch (error) {
    console.warn('⚠️ Could not setup test database - using existing data')
    // Nicht kritisch wenn Backend nicht läuft oder Testing-Endpoints nicht verfügbar
  }
})

// Globale Setup für alle Tests
setup('global setup', async ({ page }) => {
  // Stelle sicher dass Frontend läuft
  await page.goto('/')
  
  // Basis-Checks
  await expect(page).toHaveTitle(/cbApp V1/)
  
  console.log('✅ Frontend is running and accessible')
})