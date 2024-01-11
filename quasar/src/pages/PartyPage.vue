<template>
  <q-page class="flex flex-center" v-if="!loading">
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
    <div class="plateaux-autres-joueurs">
      <div
        class="plateau-autre-joueur"
        v-for="(opponent, index) in party.opponents"
        :key="index"
      >
        <q-icon
          name="account_circles"
          class="avatar bg-white d-block"
          alt="avatar"
          color="primary"
          size="45px"
        />
        <p class="username">{{ opponent.username }}</p>
        <div class="deck-autre-joueur">
          <div
            class="card"
            v-for="(pile, index) in opponent.deck"
            :key="index"
          >
            <img
              v-for="card in pile"
              :key="card.uid"
              class="image-card image-card-deck"
              :src="`/assets/${card.value}.png`"
              :alt="`${card.value}`"
            />
          </div>
        </div>
        <div class="cardDraw-autre-joueur">
          <div class="card">
            <img
              class="image-card"
              :src="`/assets/${opponent.cardDraw.value}.png`"
              :alt="`${opponent.cardDraw.value}`"
            />
          </div>
          <div class="autre-joueurs-taillePioche">
            {{ opponent.cardDrawCount }}
          </div>
        </div>
      </div>
    </div>

    <div class="plateau-center">
      <div class="cardDraw-joueurs">
        <div class="card">
          <!-- <img
            @click="selectCardPiocheCentrale()"
            class="image-card"
            src="/assets/skipbo.png"
            alt="cardDraw"
          /> -->
          <img
            class="image-card"
            src="/assets/skipbo.png"
            alt="cardDraw"
          />
        </div>
      </div>
      <div class="deck-joueurs">
        <div
          @click="selectCardDefausseCentrale(pile, index)"
          class="card"
          v-for="(pile, index) in party.stack"
          :key="index"
        >
          <img
            v-for="card in pile"
            :key="card.uid"
            class="image-card image-card-deck-center"
            :src="`/assets/${card.value}.png`"
            :alt="`${card.value}`"
          />
        </div>
      </div>
    </div>

    <div class="plateau-joueur">
      <div class="partie-sup">
        <div class="cardDraw-joueur">
          <div class="card" :class="{ 'selected-card': selectedCard === user.cardDraw }">
            <img
              @click="selectCard(user.cardDraw)"
              class="image-card"
              :src="`/assets/${user.cardDraw.value}.png`"
              :alt="`${user.cardDraw}`"
            />
          </div>
          <div class="joueur-taillePioche">{{ user.cardDrawCount }}</div>
        </div>
        <div class="deck-joueur">
          <div
            @click="clickOnStackInDeck(index)"
            class="card column no-wrap"
            v-for="(stack, index) in user.deck"
            :key="index"
          >
            <!--{{ card }}-->
            <img
              v-for="card in stack"
              :key="card.uid"
              class="image-card image-card-deck"
              :src="`/assets/${card.value}.png`"
              :alt="`${card.value}`"
            />
          </div>
        </div>
        <div class="profil">
          <q-icon
            name="account_circles"
            class="avatar d-block"
            alt="avatar"
            color="primary"
            size="45px"
          />
          <p class="username">{{ user.username }}</p>
        </div>
      </div>
      <div class="cards-hand">
        <div
          class="card"
          v-for="card in user.hand"
          :key="card.uid"
          :class="{ 'selected-card': selectedCard === card }"
        >
          <img
            @click="selectCard(card)"
            class="image-card"
            :src="`/assets/${card.value}.png`"
            :alt="`${card.value}`"
          />
        </div>
      </div>
    </div>
    <q-btn class="party__quit-btn q-mt-md q-mr-lg fixed" color="red" icon="logout"
      round size="15px" @click.prevent="showLeavePartyModal = true" />
    <q-btn class="party__share-btn q-mt-md q-ml-lg fixed" color="primary" icon="share"
      round size="15px" @click.prevent="share()" />
  </q-page>
</template>

<script>
import { SessionStorage, uid, Loading } from 'quasar'
import { useRoute } from 'vue-router'
import { api } from 'boot/axios'
import notify from 'src/services/notify'
import { Share } from '@capacitor/share'
import translate from 'src/services/translate'
import PartyHelper from 'src/helpers/PartyHelper'

export default {
  name: 'PartyPage',
  setup() {
    const route = useRoute()

    window.Echo.channel('party.' + route.params.uid)
      .listen('UserJoined', (e) => {
        console.log('User Joined!', e.user)
      })
      .listen('UserLeaved', (e) => {
        console.log('User Leaved', e.user)
      })
      .listen('UserDraw', (e) => {
        console.log('User Draw', e)
      })
      .listen('UserMove', (e) => {
        const user = this.components.find((user) => user.id === e.userId)
        if (user) {
          user.cardDraw = e.newCardDraw
          user.cardDrawCount = e.newCardDrawCount
          user.deck = e.userDeck
          this.party.stack = e.partyStack
        }
      })

    return {
      route
    }
  },
  data() {
    return {
      loading: true,
      opponents: [
        // {
        //   id: 2,
        //   username: 'maggioo',
        //   avatar: 'avatar_default.png',
        //   cardDrawCount: 5,
        //   cardDraw: {
        //       uid: '16e7cf86-1803-4bf5-8b0f-9c5797713905',
        //       value: 7
        //   },
        //   deck: [
        //       [],
        //       [],
        //       [],
        //       [],
        //       []
        //   ]
        // }
      ],
      user: {
        username: '',
        deck: [[], [], [], []],
        cardDraw: {},
        cardDrawCount: 5,
        hand: []
      },
      plateauCentre: {
        deck: [
          [],
          [
            { value: 9, uid },
            { value: 8, uid },
            { value: 6, uid }
          ],
          [
            { value: 4, uid },
            { value: 5, uid },
            { value: 9, uid }
          ],
          []
        ],
        cardDraw: {}
      },
      selectedCard: null,
      showLeavePartyModal: false,
      leaveLoading: false,
      party: null,
      myTurn: false
    }
  },
  created() {
    Loading.show()
    Promise.all([
      this.loadUser(),
      this.loadPartyUser(),
      this.loadParty(),
    ]).then(() => {
      Loading.hide()
      this.loading = false
    })
  },
  methods: {
    loadParty() {
      return api.get('/party/' + this.route.params.uid).then((res) => {
        this.party = res.data
        this.myTurn = res.data.myTurn
      })
    },
    loadPartyUser() {
      return api.get('/party_user/' + this.route.params.uid).then((res) => {
        this.user.deck = res.data.deck
        this.user.hand = res.data.hand
        this.user.cardDraw = res.data.cardDraw
        this.user.cardDrawCount = res.data.cardDrawCount
      })
    },
    loadUser() {
      return api.get('/users/me').then((res) => {
        SessionStorage.set('user', res.data)
        this.user.username = res.data.username
      })
    },
    share() {
      Share.share({
        title: 'Inviter un ami',
        text: 'Clique sur le lien ci-dessous, ou rend toi sur l\'application Skip-Bo\'nline et rentre le code d\'accès suivant : ' + this.party.joinCode,
        url: `https://skip-bo.online/#/party/join/${this.party.joinCode}`,
        dialogTitle: 'Inviter un ami'
      })
    },
    leave() {
      this.leaveLoading = true
      api.post('/party/leave', { data: this.route.params.uid, type: PartyHelper.CODE_TYPE_PARTY_ID }).then(() => {
        this.$router.push({ name: 'home' })
        notify().showPositiveNotify('Vous avez bien quitté la partie')
      }).catch((err) => {
        this.leaveLoading = false
        translate().showErrorMessage(err.response ? err.response.data.message : err.message)
      })
    },
    selectCard(card) {
      if (!this.myTurn) {
        return
      }
      if (this.selectedCard && this.selectedCard === card) {
        this.selectedCard = null
      } else {
        this.selectedCard = card
      }
      console.log(this.selectedCard)
    },
    clickOnStackInDeck(stackIndex) {
      if (!this.selectedCard || !this.myTurn || this.user.cardDraw.uid == this.selectedCard.uid) {
        return
      }

      api.post('/party/move', {
        partyId: this.party.partyId,
        from: PartyHelper.MOVE_TYPE_HAND,
        to: PartyHelper.MOVE_TYPE_DECK,
        cardUid: this.selectedCard.uid,
        toStackIndex: stackIndex
      }).then((res) => {
        this.user.deck = res.data.deck
        this.user.hand = res.data.hand
        this.selectedCard = null
        this.myTurn = false
      }).catch((err) => {
        translate().showErrorMessage(err.response ? err.response.data.message : err.message)
      })
    },
    selectCardDefausseCentrale(pile, index) {
      if (this.selectedCard !== null) {
        // Récupérer la dernière card de la pile sans la retirer
        const derniereCarteDansPile = pile.slice(-1)[0]
        console.log(this.selectedCard)
        // Vérifier si la card sélectionnée est +1 par rapport à la card actuelle
        if (this.selectedCard.value === derniereCarteDansPile.value + 1) {
          // Déplacer la card vers la défausse centrale
          this.plateauCentre.deck[index].push(this.selectedCard.value)

          // Retirer la card de la main du joueur en la recherchant par son numéro
          const indexCarteDansMain = this.user.hand.findIndex(
            (cardMain) => cardMain.value === this.selectedCard.value
          )

          if (indexCarteDansMain !== -1) {
            this.user.hand.splice(indexCarteDansMain, 1)
          }

          this.selectedCard = null
        }
      }
    },
    selectCardPiocheCentrale() {
      if (this.user.hand.length < 5) {
        this.user.hand.push(this.plateauCentre.cardDraw)
      }
    },
    game() {
      //cardDraw
      //joue
      //dans sa deck 1 seule
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

/**  Plateaux des autres joueurs  **/
.plateaux-autres-joueurs {
  //background: yellow;
  height: 200px;
  width: 100%;
  position: fixed;
  top: 100px;
  display: flex;
  justify-content: space-evenly;
}
.plateau-autre-joueur {
  background: rgb(169, 169, 187);
  height: 200px;
  width: 100px;
  border: 1px solid #333;
  position: relative;
}
.cardDraw-autre-joueur {
  //background: brown;
  height: 70px;
  width: 50px;
}

.deck-autre-joueur {
  //background: brown;
  height: 70px;
  width: 100%;
  margin-bottom: 10px;
  display: flex;
  justify-content: space-evenly;
}
.username {
  margin: 15px 0 5px;
  text-align: center;
}

.avatar {
  //background: purple;
  height: 45px;
  width: 45px;
  border-radius: 30px;
  position: absolute;
  top: -25px;
}

/**  Plateau du joueur  **/
.plateau-joueur {
  //background: red;
  height: 220px;
  width: 100%;
  position: fixed;
  bottom: 30px;
  padding-top: 5px;
}
.cardDraw-joueur {
  //background: brown;
  height: 100px;
  width: 70px;
  padding-top: 5px;
}
.joueur-taillePioche {
  position: absolute;
  top: 85px;
}
.deck-joueur {
  background: rgb(169, 169, 187);
  height: 120px;
  width: 200px;
  display: flex;
  justify-content: space-evenly;
  border: 1px solid #333;
  padding-top: 5px;
}

.partie-sup {
  //background: greenyellow;
  height: 120px;
  width: 100%;
  display: flex;
  justify-content: space-evenly;
  margin-bottom: 15px;
}
.cards-hand {
  //background: purple;
  height: 100px;
  width: 100%;
  display: flex;
  justify-content: center;
  padding-top: 5px;
}
.profil .username {
  text-align: center;
  margin-top: 40px;
}

.profil .avatar {
  //background: purple;
  height: 45px;
  width: 45px;
  border-radius: 30px;
  top: 10px;
}

.profil {
  width: 50px;
  height: 100px;
  display: flex;
  justify-content: center;
  align-items: center;
}

/**  Plateau de la cardDraw et de la défausse commune  **/
.plateau-center {
  //background: rgb(169, 169, 187);
  height: 100px;
  width: 100%;
  display: flex;
  justify-content: space-evenly;
}
.cardDraw-joueurs {
  //background: brown;
  height: 100px;
  width: 70px;
}

.deck-joueurs {
  //background: brown;
  height: 100px;
  width: 200px;
  display: flex;
  justify-content: space-evenly;
  align-items: center;
}

/**  Carte  **/
.card {
  border: 1px solid #333;
  width: 20px;
  height: 35px;
  border-radius: 10%;
}
.deck-joueurs .card,
.deck-joueur .card {
  width: 30px;
  height: 50px;
}
.cards-hand .card,
.cardDraw-autre-joueur .card,
.cardDraw-joueur .card,
.cardDraw-joueurs .card {
  width: 40px;
  height: 60px;
}
.cardDraw-autre-joueur,
.cardDraw-joueur,
.cardDraw-joueurs {
  display: flex;
  justify-content: center;
  align-items: center;
}

.image-card {
  width: 38px;
  height: 58px;
  border-radius: 10%;
}
.deck-joueur .card .image-card {
  width: 28px;
  height: 48px;
}
.image-card-deck-center {
  position: absolute;
}
.image-card-deck:not(:first-child) {
  margin-top: -31px;
}
.deck-autre-joueur .card .image-card {
  width: 20px;
  height: 35px;
}

.cardDraw-autre-joueur .card .image-card,
.cardDraw-autre-joueur .card {
  width: 30px;
  height: 45px;
}

.cardDraw-joueur,
.cards-hand {
  .card {
    &.selected-card {
      animation: selectedCard infinite 1s ease-in-out;
    }
  }
}

@keyframes selectedCard {
  0% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
  100% {
    transform: translateY(0);
  }
}
</style>
