<template>
  <div>
    <v-row class="mb-4">
      <v-col>
        <h1>Accounts</h1>
      </v-col>
      <v-col cols="auto">
        <v-btn color="primary" @click="showCreateDialog = true">
          <v-icon left>mdi-plus</v-icon>
          Add Account
        </v-btn>
      </v-col>
    </v-row>

    <v-card>
      <v-data-table :headers="headers" :items="accounts" :loading="loading" item-key="id">
        <template v-slot:item.email_verified_at="{ item }">
          <v-chip :color="item.email_verified_at ? 'success' : 'warning'" small>
            {{ item.email_verified_at ? 'Verified' : 'Unverified' }}
          </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn icon size="small" color="error" @click="deleteAccount(item.id)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Create Dialog -->
    <v-dialog v-model="showCreateDialog" max-width="500">
      <v-card>
        <v-card-title>Create Account</v-card-title>
        <v-card-text>
          <v-form v-model="formValid">
            <v-text-field
              v-model="accountForm.email"
              label="Email"
              type="email"
              :rules="emailRules"
              required
            />
            <v-text-field
              v-model="accountForm.password"
              label="Password"
              type="password"
              :rules="passwordRules"
              required
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" :disabled="!formValid" @click="saveAccount"> Create </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import { ref, onMounted } from 'vue'
  import type { Account } from '../types'
  import { accountService } from '../services/accountService'

  const accounts = ref<Account[]>([])
  const loading = ref(false)
  const showCreateDialog = ref(false)
  const formValid = ref(false)

  const accountForm = ref({
    email: '',
    password: '',
  })

  const headers = [
    { title: 'ID', key: 'id' },
    { title: 'Email', key: 'email' },
    { title: 'Status', key: 'email_verified_at' },
    { title: 'Created', key: 'created_at' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const emailRules = [
    (v: string) => !!v || 'Email is required',
    (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid',
  ]

  const passwordRules = [
    (v: string) => !!v || 'Password is required',
    (v: string) => v.length >= 8 || 'Password must be at least 8 characters',
  ]

  const loadAccounts = async () => {
    loading.value = true
    try {
      const response = await accountService.getAccounts()
      accounts.value = response.data
    } catch (error) {
      console.error('Failed to load accounts:', error)
    } finally {
      loading.value = false
    }
  }

  const saveAccount = async () => {
    try {
      await accountService.createAccount(accountForm.value)
      await loadAccounts()
      closeDialog()
    } catch (error) {
      console.error('Failed to create account:', error)
    }
  }

  const deleteAccount = async (id: number) => {
    if (confirm('Are you sure you want to delete this account?')) {
      try {
        await accountService.deleteAccount(id)
        await loadAccounts()
      } catch (error) {
        console.error('Failed to delete account:', error)
      }
    }
  }

  const closeDialog = () => {
    showCreateDialog.value = false
    accountForm.value = {
      email: '',
      password: '',
    }
  }

  onMounted(() => {
    loadAccounts()
  })
</script>
