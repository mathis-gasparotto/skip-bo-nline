<template>
  <q-page class="flex flex-center">
    <q-dialog v-model="showJoinPopup" persistent>
      <q-card style="min-width: 350px">
        <q-form @submit="joinParty">
          <q-card-section>
            <div class="text-h6">Code d'accès</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <q-input dense v-model="joinCode" autofocus @keyup.enter="showJoinPopup = false" />
          </q-card-section>

          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Annuler" v-close-popup />
            <q-btn flat label="Rejoindre" type="submit" v-close-popup />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
    <div class="flex column btns">
      <q-btn @click="showJoinPopup = true">
        Rejoindre une partie
      </q-btn>
      <q-btn @click="createParty">
        Créer une partie
      </q-btn>
    </div>
  </q-page>
</template>

<script>
import { api } from 'boot/axios'

export default {
  name: 'IndexPage',
  data() {
    return {
      showJoinPopup: false,
      joinCode: ''
    }
  },
  created() {
    api.get('/api/users/me').then((res) => {
      console.log(res.data)
    })
  },
  methods: {
    joinParty() {
      this.$router.push({ name: 'party', params: { uid: this.joinCode } })
    },
    createParty() {
      // api request to create party
      this.$router.push({ name: 'party', params: { uid: 'new' } })
    }
  }
}
</script>

<style lang="scss" scoped>
.btns {
  gap: 30px;
}
</style>
