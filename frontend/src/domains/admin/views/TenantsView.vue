<template>
  <div>
    <v-row class="mb-4">
      <v-col>
        <h1>Tenants</h1>
      </v-col>
      <v-col cols="auto">
        <v-btn color="primary" @click="showCreateDialog = true">
          <v-icon left>mdi-plus</v-icon>
          Add Tenant
        </v-btn>
      </v-col>
    </v-row>

    <v-card>
      <v-data-table :headers="headers" :items="tenants" :loading="loading" item-key="id">
        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" small>
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn icon size="small" @click="editTenant(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon size="small" color="error" @click="deleteTenant(item.id)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="showCreateDialog" max-width="500">
      <v-card>
        <v-card-title>
          {{ editingTenant ? 'Edit Tenant' : 'Create Tenant' }}
        </v-card-title>
        <v-card-text>
          <v-form v-model="formValid">
            <v-text-field v-model="tenantForm.name" label="Name" :rules="nameRules" required />
            <v-text-field
              v-model="tenantForm.subdomain"
              label="Subdomain"
              :rules="subdomainRules"
              required
            />
            <v-switch v-if="editingTenant" v-model="tenantForm.is_active" label="Active" />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" :disabled="!formValid" @click="saveTenant">
            {{ editingTenant ? 'Update' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
  import { ref, onMounted } from 'vue'
  import type { Tenant } from '../types'
  import { tenantService } from '../services/tenantService'

  const tenants = ref<Tenant[]>([])
  const loading = ref(false)
  const showCreateDialog = ref(false)
  const formValid = ref(false)
  const editingTenant = ref<Tenant | null>(null)

  const tenantForm = ref({
    name: '',
    subdomain: '',
    is_active: true,
  })

  const headers = [
    { title: 'ID', key: 'id' },
    { title: 'Name', key: 'name' },
    { title: 'Subdomain', key: 'subdomain' },
    { title: 'Status', key: 'is_active' },
    { title: 'Created', key: 'created_at' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const nameRules = [(v: string) => !!v || 'Name is required']

  const subdomainRules = [
    (v: string) => !!v || 'Subdomain is required',
    (v: string) =>
      /^[a-z0-9-]+$/.test(v) ||
      'Subdomain must contain only lowercase letters, numbers, and hyphens',
  ]

  const loadTenants = async () => {
    loading.value = true
    try {
      const response = await tenantService.getTenants()
      tenants.value = response.data
    } catch (error) {
      console.error('Failed to load tenants:', error)
    } finally {
      loading.value = false
    }
  }

  const editTenant = (tenant: Tenant) => {
    editingTenant.value = tenant
    tenantForm.value = {
      name: tenant.name,
      subdomain: tenant.subdomain,
      is_active: tenant.is_active,
    }
    showCreateDialog.value = true
  }

  const saveTenant = async () => {
    try {
      if (editingTenant.value) {
        await tenantService.updateTenant(editingTenant.value.id, tenantForm.value)
      } else {
        await tenantService.createTenant(tenantForm.value)
      }
      await loadTenants()
      closeDialog()
    } catch (error) {
      console.error('Failed to save tenant:', error)
    }
  }

  const deleteTenant = async (id: number) => {
    if (confirm('Are you sure you want to delete this tenant?')) {
      try {
        await tenantService.deleteTenant(id)
        await loadTenants()
      } catch (error) {
        console.error('Failed to delete tenant:', error)
      }
    }
  }

  const closeDialog = () => {
    showCreateDialog.value = false
    editingTenant.value = null
    tenantForm.value = {
      name: '',
      subdomain: '',
      is_active: true,
    }
  }

  onMounted(() => {
    loadTenants()
  })
</script>
