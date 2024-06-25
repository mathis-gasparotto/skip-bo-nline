<template>
  <q-page class="flex flex-center">
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
      <div class="code_inv">
        {{ this.route.params.joinCode }}
      </div>
   <div class="title2">
    <p >Liste des joueurs </p> <span class=" nbjoueur">({{ joinedUsers.length }} / 4 max) </span>
   </div>

   <table>
    <tr v-for="user in joinedUsers" :key="user.id">
      <td>
        <div class="user flex ">
          <img :src="user.avatar" alt="User Avatar" />
          <div>{{ user.username }}</div>
        </div>
      </td>
    </tr>
   </table>

    <!--<div>
      <div v-for="user in joinedUsers" :key="user.id">
        <div class="user">
          <img :src="user.avatar" alt="User Avatar" />
          <div>{{ user.username }}</div>
        </div>
      </div>
    </div>-->

    <q-btn  :class="{'bg-grey-4 text-grey-5':joinedUsers.length<1}" @click="commencerPartie" :disable="joinedUsers.length<1">
        Commencer la partie
      </q-btn>

    <q-btn class="party__quit-btn q-mt-md q-mr-lg fixed" color="red" icon="logout"
      round size="15px" @click.prevent="showLeaveGameModal = true" />
    <q-btn class="party__share-btn q-mt-md q-ml-lg fixed" color="primary" icon="share"
      round size="15px" @click.prevent="share()" />
  </q-page>
</template>

<script>
import { uid, SessionStorage } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import { api } from 'boot/axios'
import notify from 'src/services/notify'
import { Share } from '@capacitor/share'
import translate from 'src/services/translate'
import GameHelper from 'src/helpers/GameHelper'

export default {
  name: 'GameLobbyPage',
  setup() {
    const route = useRoute()
    const router = useRouter()

    window.Echo.channel('game.' + route.params.joinCode)
      .listen('UserJoined', (e) => {
        console.log('User Joined!', e.user)
        //this.joinedUsers.push(e.user)
      })
      .listen('UserLeaved', (e) => {
        console.log('User Leaved', e.user)
      })

    window.Echo.channel('game.' + route.params.joinCode + '.started.' + SessionStorage.getItem('user')?.id)
      .listen('GameStarted', (e) => {
        router.push({ name: 'game', params: { uid: e.gameId } })
      })

    return {
      route
    }
  },
  data() {
    return {
      showLeaveGameModal: false,
      leaveLoading: false,
      joinedUsers: [{
          username: 'njuh',
          avatar: 5
        },
        {
          username: 'nzdzdjuh',
          avatar: 5
        },
        {
          username: 'njzdfffzzh',
          avatar: 5
        },
        {
          username: 'klpi',
          avatar: 10
        }],
      currentuser:{
          username: 'moi',
          avatar: 5
        }
    }
  },
  methods: {
    share() {
      Share.share({
        title: 'Inviter un ami',
        text: 'Clique sur le lien ci-dessous, ou rend toi sur l\'application Skip-Bo\'nline et rentre le code d\'accès suivant : ' + this.route.params.joinCode,
        url: `https://skip-bo.online/game/join/${this.route.params.joinCode}`,
        dialogTitle: 'Inviter un ami'
      })
    },
    leave() {
      this.leaveLoading = true
      api.post('/game/leave', { data: this.route.params.joinCode, type: GameHelper.CODE_TYPE_JOIN_CODE }).then(() => {
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
.title{
  display: flex;
  justify-content: center;
  text-transform: uppercase;
  font-size: 19px;
  font-weight: 600;
  padding: 30px 0;
}.title2{
  display: flex;
  justify-content: space-between;
  text-transform: uppercase;
  margin: 0 20px;
  font-size: 15px;
  font-weight: 600;
  padding: 30px 0;
}
.nbjoueur{
  font-size: 12px !important;
  font-weight: 400 !important;
  padding-left: 3px;
}
.code_inv{
  display: flex;
  justify-content: center;
  padding: 10px 0;
  text-transform: uppercase;
  margin: 0 20px;
  font-size: 17px;
  font-weight: 800;
  border: solid 3px red;
}
</style>
