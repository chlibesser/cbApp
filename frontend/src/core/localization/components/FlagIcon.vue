<template>
  <img
    v-if="flagSvg"
    :src="flagSvg"
    :alt="`${localeCode} flag`"
    :class="['flag-icon', sizeClass]"
    :style="{ width: computedSize, height: computedSize }"
  />
  <span v-else class="flag-emoji">{{ fallbackEmoji }}</span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// Props
interface Props {
  localeCode: string
  size?: 'small' | 'medium' | 'large' | string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'medium'
})

// Size mapping
const sizeMap = {
  small: '16px',   // Used in header button (selected language)
  medium: '24px',  // Used in dropdown menu
  large: '24px'    // Used in dropdown menu (same as medium)
}

const computedSize = computed(() => {
  if (props.size in sizeMap) {
    return sizeMap[props.size as keyof typeof sizeMap]
  }
  return props.size // Custom size like '28px'
})

const sizeClass = computed(() => {
  if (props.size in sizeMap) {
    return `flag-icon--${props.size}`
  }
  return ''
})

// Map locale codes to ISO 3166-1 alpha-2 country codes
const localeToCountryCode = (locale: string): string => {
  const mapping: Record<string, string> = {
    'de': 'DE', // German → Germany
    'en': 'GB', // English → Great Britain
    'fr': 'FR', // French → France
    'es': 'ES', // Spanish → Spain
    'it': 'IT', // Italian → Italy
    'pt': 'PT', // Portuguese → Portugal
    'nl': 'NL', // Dutch → Netherlands
    'pl': 'PL', // Polish → Poland
    'ru': 'RU', // Russian → Russia
    'zh': 'CN', // Chinese → China
    'ja': 'JP', // Japanese → Japan
    'ko': 'KR', // Korean → South Korea
  }
  return mapping[locale] || locale.toUpperCase()
}

// Dynamic flag SVG URL using Vite's glob import feature
const flagSvg = computed(() => {
  try {
    const countryCode = localeToCountryCode(props.localeCode)

    // Use Vite's special handling for dynamic imports from node_modules
    // This creates a proper URL that Vite can resolve
    const modules = import.meta.glob('/node_modules/country-flag-icons/3x2/*.svg', {
      eager: true,
      query: '?url',
      import: 'default'
    })

    const flagPath = `/node_modules/country-flag-icons/3x2/${countryCode}.svg`
    const flagUrl = modules[flagPath] as string

    if (!flagUrl) {
      console.warn(`[FlagIcon] No flag found for locale: ${props.localeCode} (${countryCode})`)
      return null
    }

    return flagUrl
  } catch (error) {
    console.warn(`[FlagIcon] Failed to load flag for ${props.localeCode}:`, error)
    return null
  }
})

// Fallback emoji (for unsupported locales or load failures)
const fallbackEmoji = computed(() => {
  const flagMap: Record<string, string> = {
    'de': '🇩🇪',
    'en': '🇬🇧',
    'fr': '🇫🇷',
    'es': '🇪🇸',
    'it': '🇮🇹',
    'pt': '🇵🇹',
    'nl': '🇳🇱',
    'pl': '🇵🇱',
    'ru': '🇷🇺',
    'zh': '🇨🇳',
    'ja': '🇯🇵',
    'ko': '🇰🇷',
  }
  return flagMap[props.localeCode] || '🌐'
})
</script>

<style scoped>
.flag-icon {
  display: inline-block;
  vertical-align: middle;
  object-fit: cover;
  border-radius: 2px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

.flag-emoji {
  font-size: 1.25rem;
  line-height: 1;
  display: inline-block;
  vertical-align: middle;
}

/* Size variants */
.flag-icon--small {
  border-radius: 1px;
}

.flag-icon--medium {
  border-radius: 2px;
}

.flag-icon--large {
  border-radius: 3px;
}
</style>
