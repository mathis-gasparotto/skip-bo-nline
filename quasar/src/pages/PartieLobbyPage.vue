<template>
  <q-page class="flex flex-center">
    <q-dialog v-model="showLeavePartyModal">
      <q-card style="min-width: 350px">
        <q-card-section>
          Voulez-vous vraiment quitter la partie ?
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="primary" v-close-popup />
          <q-btn flat label="Quitter" color="red" @click="leave()" :loading="leaveLoading" />
        </q-card-actions>
      </q-card>
    </q-dialog>
    <div class="container">
      Oui
    </div>
    <q-btn class="party__quit-btn q-mt-md q-mr-lg fixed" color="red" icon="logout"
      round size="15px" @click.prevent="showLeavePartyModal = true" />
    <q-btn class="party__share-btn q-mt-md q-ml-lg fixed" color="primary" icon="share"
      round size="15px" @click.prevent="share()" />
  </q-page>
</template>

<script>
import { SessionStorage } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import { api } from 'boot/axios'
import notify from 'src/services/notify'
import { Share } from '@capacitor/share'
import translate from 'src/services/translate'

export default {
  name: 'PartyLobbyPage',
  setup() {
    const route = useRoute()
    const router = useRouter()

    window.Echo.channel('party.' + route.params.joinCode)
      .listen('UserJoined', (e) => {
        console.log('User Joined!', e.user)
      })
      .listen('UserLeaved', (e) => {
        console.log('User Leaved', e.user)
      })

    window.Echo.channel('party.' + route.params.joinCode + '.started.' + SessionStorage.getItem('user')?.id)
      .listen('PartyStarted', (e) => {
        router.push({ name: 'party', params: { uid: e.partyId } })
      })

    return {
      route
    }
  },
  data() {
    return {
      showLeavePartyModal: false,
      leaveLoading: false
    }
  },
  methods: {
    share() {
      Share.share({
        title: 'Inviter un ami',
        text: 'Clique sur le lien ci-dessous, ou rend toi sur l\'application Skip-Bo\'nline et rentre le code d\'accès suivant : ' + this.route.params.joinCode,
        url: `https://skip-bo.online/#/party/join/${this.route.params.joinCode}`,
        dialogTitle: 'Inviter un ami'
      })
    },
    leave() {
      this.leaveLoading = true
      api.post('/party/leave', { data: this.route.params.joinCode, type: 'join_code' }).then(() => {
        this.$router.push({ name: 'home' })
        notify().showPositiveNotify('Vous avez bien quitté la partie')
      }).catch((err) => {
        this.leaveLoading = false
        translate().showErrorMessage(err.response ? err.response.data.message : err.message)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.party {
  &__quit {
    &-btn {
      bottom: 10px;
      left: 10px;
      border-radius: 50%;
      color: #fff;

      &::before {
        box-shadow: none !important;
      }
    }
  }
  &__share {
    &-btn {
      bottom: 10px;
      right: 10px;
      border-radius: 50%;
      color: #fff;

      &::before {
        box-shadow: none !important;
      }
    }
  }
}
</style>
