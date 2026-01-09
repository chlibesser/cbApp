<template>
  <v-menu>
    <template #activator="{ props }">
      <v-btn
        v-bind="props"
        :loading="loading"
        :disabled="availableLocales.length === 0"
        variant="text"
        class="language-switcher-btn"
        data-testid="language-switcher"
      >
        <FlagIcon
          :locale-code="currentLocaleInfo?.code || defaultLocale"
          size="small"
        />
        <span class="ms-2 d-none d-sm-inline">
          {{ currentLocaleInfo?.native || 'Deutsch' }}
        </span>
        <v-icon end size="small">mdi-chevron-down</v-icon>
      </v-btn>
    </template>

    <!-- Language List Dropdown -->
    <v-list density="compact" min-width="200">
      <!-- Iterate through available locales -->
      <v-list-item
        v-for="localeItem in availableLocales"
        :key="localeItem.code"
        :active="locale === localeItem.code"
        @click="handleLocaleChange(localeItem.code)"
      >
        <!-- Flag -->
        <template #prepend>
          <FlagIcon
            :locale-code="localeItem.code"
            size="medium"
            class="me-3"
          />
        </template>

        <v-list-item-title>
          {{ localeItem.native }}
        </v-list-item-title>

        <v-list-item-subtitle class="text-caption">
          {{ localeItem.name }}
        </v-list-item-subtitle>

        <template v-if="locale === localeItem.code" #append>
          <v-icon color="primary" size="small">mdi-check</v-icon>
        </template>
      </v-list-item>
    </v-list>
  </v-menu>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useTranslations } from '@/core/localization/composables/useTranslations'
import type { SupportedLocale } from '@/core/localization/types/locale.types'
import FlagIcon from '@/core/localization/components/FlagIcon.vue'

// Get default locale from environment
const defaultLocale = (import.meta.env.VITE_DEFAULT_LOCALE || 'de') as SupportedLocale

// Get translation composable state
const { locale, availableLocales, setLocale, loading } = useTranslations()

// Get current locale information
const currentLocaleInfo = computed(() =>
  availableLocales.value.find(l => l.code === locale.value)
)

// Handle locale change click
const handleLocaleChange = async (newLocale: SupportedLocale) => {
  // Skip if already current locale
  if (newLocale === locale.value) {
    console.log('[LanguageSwitcher] Already using locale:', newLocale)
    return
  }

  try {

    // Switch locale (updates store, i18n, Vuetify, backend)
    // Toast notifications are handled inside setLocale()
    await setLocale(newLocale)

  } catch (error) {
    // Error already logged and toast shown by localeStore.setLocale()
    console.error('[LanguageSwitcher] Failed to change locale:', error)
  }
}
</script>

<style scoped>
.language-switcher-btn {
  text-transform: none !important;
  letter-spacing: normal !important;
}
.v-list-item__prepend {
  align-self: center;
}
:deep(.v-list-item__prepend) {
  margin-inline-end: 12px !important;
}
</style>
