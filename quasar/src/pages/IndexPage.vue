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
import { SessionStorage, Notify } from 'quasar'
import { api } from 'boot/axios'

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
  methods: {
    joinParty() {
      this.joinLoading = true

      api.post('/party/join', { partyId: this.joinCode })
        .then((res) => {
          this.$router.push({ name: 'party', params: { uid: res.data.partyId } })
        })
        .catch((err) => {
          this.joinLoading = false
          Notify.create({
            message: err.response.data.message,
            color: 'negative',
            icon: 'report_problem',
            position: 'top',
            timeout: 3000,
            actions: [
              {
                icon: 'close',
                color: 'white'
              }
            ]
          })
        })
    },
    createParty() {
      // api request to create party

      this.createLoading = true
      // this.$router.push({ name: 'party', params: { uid: 'new' } })
    }
  }
}
</script>

<style lang="scss" scoped>
.btns {
  gap: 30px;
}
</style>
