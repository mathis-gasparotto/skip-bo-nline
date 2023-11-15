<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>

        <q-toolbar-title>
          Skip-Bo'nline
        </q-toolbar-title>

        <q-btn-dropdown flat square class="account-dropdown">
          <template #label>
            <q-icon name="account_circles" class="d-block" />
          </template>
          <q-list>
            <q-item clickable v-close-popup @click="logout">
              <q-item-section>
                <q-item-label>Se déconnecter</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script>
import { SessionStorage } from 'quasar'

export default {
  name: 'MainLayout',
  methods: {
    logout() {
      SessionStorage.remove('token')
      SessionStorage.remove('user')
      this.$router.push({ name: 'signin' })
    }
  }
}
</script>

<style lang="scss">
.account-dropdown {
  .q-btn-dropdown__arrow {
    display: none;
  }
}
</style>
