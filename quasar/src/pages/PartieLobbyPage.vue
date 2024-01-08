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
import { uid, SessionStorage } from 'quasar'
import { useRoute } from 'vue-router'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { api } from 'boot/axios'
import notify from 'src/services/notify'
import { Share } from '@capacitor/share'
import translate from 'src/services/translate'

export default {
  name: 'PartiePage',
  setup() {
    const route = useRoute()

    window.Pusher = Pusher

    window.Echo = new Echo({
      broadcaster: 'pusher',
      key: process.env.VUE_PUSHER_APP_KEY,
      cluster: process.env.VUE_PUSHER_APP_CLUSTER,
      forceTLS: true
    })

    window.Echo.channel('party.' + route.params.joinCode)
      .listen('UserJoined', (e) => {
        console.log('User Joined!', e.user)
      })
      .listen('UserLeaved', (e) => {
        console.log('User Leaved', e.user)
      })

    window.Echo.private('party.' + route.params.joinCode + '.started.1')
      .listen('PartyStarted', (e) => {
        console.log('PartyStarted', e)
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
    },
    selectCardMainJoueur(card) {
      if (this.selectedCard && this.selectedCard === card) {
        this.selectedCard = null
      } else {
        this.selectedCard = card
      }
      console.log(this.selectedCard)
    },
    selectCardDefausseJoueur(index) {
      if (this.selectedCard !== null && this.countDefausseJoueur == true) {
        this.user.defausse[index].push(this.selectedCard.number)
        // Retirer la carte de la main du joueur en la recherchant par son numéro
        const indexCarteDansMain = this.user.main.findIndex(
          (carte) => carte.number === this.selectedCard.number
        )

        if (indexCarteDansMain !== -1) {
          this.user.main.splice(indexCarteDansMain, 1)
        }

        this.countDefausseJoueur = false
        this.selectedCard = null
      }
    },
    selectCardDefausseCentrale(pile, index) {
      if (this.selectedCard !== null) {
        // Récupérer la dernière carte de la pile sans la retirer
        const derniereCarteDansPile = pile.slice(-1)[0]

        // Vérifier si la carte sélectionnée est +1 par rapport à la carte actuelle
        if (this.selectedCard.number === derniereCarteDansPile + 1) {
          // Déplacer la carte vers la défausse centrale
          this.plateauCentre.defausse[index].push(this.selectedCard.number)

          // Retirer la carte de la main du joueur en la recherchant par son numéro
          const indexCarteDansMain = this.user.main.findIndex(
            (carteMain) => carteMain.number === this.selectedCard.number
          )

          if (indexCarteDansMain !== -1) {
            this.user.main.splice(indexCarteDansMain, 1)
          }

          this.selectedCard = null
        }
      }
    },
    selectCardPiocheCentrale() {
      if (this.user.main.length < 5) {
        this.user.main.push(this.plateauCentre.pioche)
      }
    },
    selectCardPiocheJoueur(carte) {},
    game() {
      //pioche
      //joue
      //dans sa defausse 1 seule
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
