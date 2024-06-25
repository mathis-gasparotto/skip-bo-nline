<template>
  <q-page class="flex column items-center" v-if="!Loading.isActive">
    <q-dialog v-model="showLeaveGameModal">
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


    <div class="title">
      Code d'invitation
    </div>
    <div class="code_inv q-pa-md">
      {{ $route.params.joinCode }}
    </div>
    <div class="title2">
      <p>Liste des joueurs </p> <span class="nbjoueur">({{ game.players.length }} / 4 max) </span>
    </div>

    <table class="q-mb-lg">
      <tr v-for="user in game.players" :key="user.id">
        <td>
          <div class="user flex ">
            <q-icon
              name="account_circles"
              class="avatar bg-white d-block"
              alt="avatar"
              color="primary"
              size="45px"
            />
            <div>{{ user.username }}<span v-if="user.id === currentUser.id"> (moi)</span></div>
          </div>
        </td>
      </tr>
    </table>

    <q-btn v-if="game.author.id === currentUser.id" :class="{'bg-grey-4 text-grey-5':game.players.length<1}" @click="start"
           :disable="game.players.length<1">
      Commencer la partie
    </q-btn>

    <div class="fixed-bottom-right q-mb-md q-mr-lg flex">
      <q-btn class="party__quit-btn q-mr-md" color="red" icon="logout"
             round size="15px" @click.prevent="showLeaveGameModal = true" />
      <q-btn class="party__share-btn" color="primary" icon="share"
             round size="15px" @click.prevent="share()" />
    </div>

  </q-page>
</template>

<script>
import { Loading, SessionStorage } from 'quasar'
import { api } from 'boot/axios'
import notify from 'src/services/notify'
import { Share } from '@capacitor/share'
import translate from 'src/services/translate'
import GameHelper from 'src/helpers/GameHelper'

export default {
  name: 'GameLobbyPage',
  setup() {
    return {Loading}
  },
  data() {
    return {
      showLeaveGameModal: false,
      leaveLoading: false,
      game: {},
      loading: true,
      currentUser: {}
    }
  },
  created() {
    this.reloadData()

    window.Echo.channel('game.' + this.$route.params.joinCode)
      .listen('UserJoined', (e) => {
        this.game.players.push(e.user)
      })
      .listen('UserLeaved', (e) => {
        this.game.players = this.game.players.filter((user) => user.id !== e.user.id)
      })

    window.Echo.channel('game.' + this.$route.params.joinCode + '.started.' + SessionStorage.getItem('user')?.id)
      .listen('GameStarted', (e) => {
        this.$router.push({ name: 'game', params: { uid: e.gameId } })
      })
  },
  methods: {
    share() {
      Share.share({
        title: 'Inviter un ami',
        text: 'Clique sur le lien ci-dessous, ou rend toi sur l\'application Skip-Bo\'nline et rentre le code d\'accès suivant : ' + this.$route.params.joinCode,
        url: `/game/join/${this.$route.params.joinCode}`,
        dialogTitle: 'Inviter un ami'
      })
    },
    reloadData() {
      Loading.show()
      this.currentUser = SessionStorage.getItem('user')
      if (!this.currentUser) {
        api.get('/users/me').then((res) => {
          SessionStorage.set('user', res.data)
          this.currentUser = res.data
        }).catch((err) => {
          translate().showErrorMessage(err.response ? err.response.data.message : err.message)
        })
      }
      api.get('/game/join-code/' + this.$route.params.joinCode).then((res) => {
        this.game = res.data
        Loading.hide()
      })
    },
    leave() {
      this.leaveLoading = true
      api.post('/game/leave', { data: this.$route.params.joinCode, type: GameHelper.CODE_TYPE_JOIN_CODE }).then(() => {
        this.$router.push({ name: 'home' })
        notify().showPositiveNotify('Vous avez bien quitté la partie')
      }).catch((err) => {
        this.leaveLoading = false
        translate().showErrorMessage(err.response ? err.response.data.message : err.message)
      })
    },
    start() {
      api.post('/game/start', { code: this.$route.params.joinCode }).then(() => {
        this.$router.push({ name: 'game', params: { uid: this.$route.params.joinCode } })
      }).catch((err) => {
        translate().showErrorMessage(err.response ? err.response.data.message : err.message)
      })

    }
  }
}
</script>

<style lang="scss" scoped>
.game {
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
      border-radius: 50%;
      color: #fff;

      &::before {
        box-shadow: none !important;
      }
    }
  }
}

.title {
  display: flex;
  justify-content: center;
  text-transform: uppercase;
  font-size: 19px;
  font-weight: 600;
  padding: 30px 0;
}

.title2 {
  display: flex;
  justify-content: space-between;
  text-transform: uppercase;
  margin: 0 20px;
  font-size: 15px;
  font-weight: 600;
  padding: 30px 0;
}

.nbjoueur {
  font-size: 12px !important;
  font-weight: 400 !important;
  padding-left: 3px;
}

.code_inv {
  display: flex;
  justify-content: center;
  text-transform: uppercase;
  margin: 0 20px;
  font-size: 17px;
  font-weight: 800;
  border: solid 3px red;
}
</style>
