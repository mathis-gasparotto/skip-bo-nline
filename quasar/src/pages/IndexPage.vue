<template>
  <q-page class="flex flex-center">
    <q-dialog v-model="showJoinPopup" @hide="joinCode = null">
      <q-card style="min-width: 350px">
        <q-form @submit="joinParty">
          <q-card-section>
            <div class="text-h6">Code d'accès</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <q-input
              dense
              v-model="joinCode"
              autofocus
              lazy-rules
              :rules="[ val => val.length > 0 || 'Veullez renseigner un code d\'accès' ]"
            />
          </q-card-section>

          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Annuler" v-close-popup />
            <q-btn flat label="Rejoindre" type="submit" :loading="joinLoading" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
    <q-banner rounded class="bg-primary text-white" v-if="user.current_party">
      Vous avez une partie en cours
      <template v-slot:action>
        <q-btn flat color="white" label="Rejoindre" @click="$router.push({name: 'party', params: { uid: user.current_party }})" />
      </template>
    </q-banner>
    <div class="flex column btns">
        {{ user.username }}
      <q-btn @click="showJoinPopup = true">
        Rejoindre une partie
      </q-btn>
      <q-btn @click="createParty" :loading="createLoading">
        Créer une partie
      </q-btn>
    </div>
  </q-page>
</template>

<script>
import { SessionStorage } from 'quasar'
import { api } from 'boot/axios'
import translate from 'src/services/translate'

export default {
  name: 'IndexPage',
  setup() {
    const user = SessionStorage.getItem('user')
    return { user }
  },
  data() {
    return {
      showJoinPopup: false,
      joinCode: '',
      joinLoading: false,
      createLoading: false
    }
  },
  created() {
    this.reloadUser()
  },
  methods: {
    reloadUser() {
      api.get('/users/me')
        .then((res) => {
          SessionStorage.set('user', res.data)
          this.user = res.data
        })
        .catch((err) => {
          translate().showErrorMessage(err.response ? err.response.data.message : err.message)
        })
    },
    joinParty() {
      this.joinLoading = true

      api.post('/party/join', { code: this.joinCode })
        .then((res) => {
          if (res.data.partyId) {
            return this.$router.push({ name: 'party', params: { uid: res.data.partyId } })
          }
          this.$router.push({ name: 'partyLobby', params: { joinCode: res.data.joinCode } })
        })
        .catch((err) => {
          this.joinLoading = false
          translate().showErrorMessage(err.response ? err.response.data.message : err.message)
        })
    },
    createParty() {
      this.createLoading = true

      api.post('/party/create')
        .then((res) => {
          this.$router.push({ name: 'partyLobby', params: { joinCode: res.data.joinCode } })
        })
        .catch((err) => {
          this.createLoading = false
          translate().showErrorMessage(err.response ? err.response.data.message : err.message)
        })
    }
  }
}
</script>

<style lang="scss" scoped>
.btns {
  gap: 30px;
}
</style>
