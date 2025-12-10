<template>
  <v-app>
    <v-main class="auth-main">
      <v-container fluid class="fill-height">
        <v-row justify="center" align="center" class="fill-height">
          <v-col cols="12" sm="8" md="6" lg="4">
            <!-- Branding Section -->
            <div class="text-center mb-8">
              <img 
                src="/Logo_cbapp.svg" 
                alt="cbApp Logo" 
                class="auth-logo"
              />
            </div>

            <!-- Main Content -->
            <v-card 
              class="auth-card" 
              elevation="8"
              rounded="lg"
            >
              <router-view />
            </v-card>

            <!-- Footer -->
            <div class="text-center mt-6">
              <p class="text-caption text-medium-emphasis">
                © {{ currentYear }} cbApp.ch - Alle Rechte vorbehalten
              </p>
              <div class="mt-2">
                <v-btn 
                  variant="text" 
                  size="small" 
                  @click="toggleDarkMode"
                  class="text-medium-emphasis"
                >
                  <v-icon>{{ darkModeIcon }}</v-icon>
                  {{ darkModeText }}
                </v-btn>
              </div>
            </div>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Background Pattern -->
    <div class="auth-background" />
  </v-app>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useTheme } from 'vuetify'

const theme = useTheme()

const currentYear = computed(() => new Date().getFullYear())

const darkModeIcon = computed(() => 
  theme.global.current.value.dark ? 'mdi-white-balance-sunny' : 'mdi-moon-waning-crescent'
)

const darkModeText = computed(() => 
  theme.global.current.value.dark ? 'Light Mode' : 'Dark Mode'
)

const toggleDarkMode = () => {
  theme.global.name.value = theme.global.current.value.dark ? 'light' : 'dark'
}
</script>

<style scoped>
.auth-main {
  position: relative;
  overflow: hidden;
}

.auth-background {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    135deg,
    rgba(25, 118, 210, 0.1) 0%,
    rgba(25, 118, 210, 0.05) 50%,
    rgba(25, 118, 210, 0.02) 100%
  );
  z-index: -1;
}

.auth-logo {
  height: 64px;
  width: auto;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.auth-card {
  backdrop-filter: blur(10px);
  background-color: rgba(255, 255, 255, 0.95) !important;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.theme--dark .auth-card {
  background-color: rgba(33, 33, 33, 0.95) !important;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Mobile optimizations */
@media (max-width: 600px) {
  .auth-logo {
    height: 48px;
  }
  
  .auth-card {
    margin: 16px;
  }
}

/* Animation for smooth transitions */
.auth-card {
  transition: all 0.3s ease-in-out;
}

.auth-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12) !important;
}
</style>