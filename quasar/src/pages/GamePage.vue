<template>
  <q-page class="flex flex-center" v-if="!loading">
    <q-dialog v-model="showLeaveGameModal">
      <q-card style="min-width: 350px">
        <q-card-section>
          Voulez-vous vraiment quitter la partie ? Vous allez perdre votre progression.
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="primary" v-close-popup />
          <q-btn flat label="Quitter" color="red" @click="leave()" :loading="leaveLoading" />
        </q-card-actions>
      </q-card>
    </q-dialog>
    <q-dialog v-model="showPlayerWhoWin" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <h5 class="q-my-md">{{ playerWhoWin.username }} à gagné.</h5>
          <p>La prochaine fois ça sera peut-être vous !</p>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Retourner à la page d'accueil" color="primary" @click="backToHome()" />
        </q-card-actions>
      </q-card>
    </q-dialog>
    <q-dialog v-model="youWin" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <h5 class="q-my-md">Vous avez gagné la partie !! 🎉</h5>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Retourner à la page d'accueil" color="primary" @click="backToHome()" />
        </q-card-actions>
      </q-card>
    </q-dialog>
    <div class="plateaux-autres-joueurs">
      <div
        class="plateau-autre-joueur"
        :class="{ 'user-to-play': game.userToPlayId === opponent.id }"
        v-for="(opponent, index) in game.opponents"
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
            class="card column no-wrap"
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
              v-if="opponent.cardDraw"
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
          @click="selectStackOnGameStacks(index)"
          class="card"
          v-for="(pile, index) in game.stacks"
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
              v-if="user.cardDraw"
              @click="selectCard(user.cardDraw)"
              class="image-card"
              :src="`/assets/${user.cardDraw.value}.png`"
              :alt="`${user.cardDraw}`"
            />
          </div>
          <div class="joueur-taillePioche">{{ user.cardDrawCount }}</div>
        </div>
        <div class="deck-joueur" :class="{'user-to-play': game.myTurn}">
          <div
            @click="clickOnStackInDeck(index)"
            class="card column no-wrap"
            v-for="(stack, index) in user.deck"
            :key="index"
          >
            <img
              v-for="card in stack"
              :key="card.uid"
              class="image-card image-card-deck"
              :class="{ 'selected-card': selectedCard === card }"
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
    <q-btn class="game__quit-btn q-mt-md q-mr-lg fixed" color="red" icon="logout"
      round size="15px" @click.prevent="showLeaveGameModal = true" />
  </q-page>
</template>

<script>
import { SessionStorage, uid, Loading, Notify } from 'quasar'
import { useRoute } from 'vue-router'
import { api } from 'boot/axios'
import notify from 'src/services/notify'
import translate from 'src/services/translate'
import GameHelper from 'src/helpers/GameHelper'

export default {
  name: 'GamePage',
  setup() {
    const route = useRoute()
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
      showLeaveGameModal: false,
      leaveLoading: false,
      game: null,
      youWin: false,
      showPlayerWhoWin: false,
      playerWhoWin: {
        username: 'Player'
      }
    }
  },
  created() {
    Loading.show()

    window.Echo.channel('game.' + this.route.params.uid)
      .listen('UserLeaved', (e) => {
        this.removeUserFromGame(e.user.id)
        if (e.gameEnded) {
          this.userWin()
        }
      })
      .listen('UserMove', (e) => {
        this.userMove(e)
      })

    Promise.all([
      this.loadUser(),
      this.loadGameUser(),
      this.loadGame(),
    ]).then(() => {
      Loading.hide()
      this.loading = false
    })
  },
  methods: {
    backToHome() {
      api.get('/users/me').then((res) => {
        SessionStorage.set('user', res.data)
        this.$router.push({ name: 'home' })
      }).catch((err) => {
        translate().showErrorMessage(err.response ? err.response.data.message : err.message)
      })
    },
    removeUserFromGame(userId) {
      this.game.opponents = this.game.opponents.filter((opponent) => opponent.id !== userId)
    },
    userMove(e) {
      if (!this.game.myTurn && e.userToPlayId === SessionStorage.getItem('user').id) {
        this.game.myTurn = true
        Notify.create({
          message: 'C\'est à vous de jouer !',
          color: 'positive',
          position: 'top',
          timeout: 2000
        })
      }
      this.game.userToPlayId = e.userToPlayId
      const opponent = this.game.opponents.find((user) => user.id === e.user.id)
      if (opponent) {
        opponent.cardDraw = e.newCardDraw
        opponent.cardDrawCount = e.newCardDrawCount
        opponent.deck = e.userDeck
        this.game.stacks = e.gameStacks
      }
      if (e.userWin) {
        this.userWin(e.userWin.id)
      }
    },
    userWin(userId = null) {
      if (userId === null || userId === SessionStorage.getItem('user').id) {
        this.youWin = true
      } else {
        const user = this.game.opponents.find((user) => user.id === userId)
        if (user) {
          this.playerWhoWin = user
          this.showPlayerWhoWin = true
        }
      }
    },
    loadGame() {
      return api.get('/game/' + this.route.params.uid).then((res) => {
        this.game = res.data
      })
    },
    loadGameUser() {
      return api.get('/game_user/' + this.route.params.uid).then((res) => {
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
    leave() {
      this.leaveLoading = true
      api.post('/game/leave', { data: this.route.params.uid, type: GameHelper.CODE_TYPE_GAME_ID }).then(() => {
        this.backToHome()
        notify().showPositiveNotify('Vous avez bien quitté la partie')
      }).catch((err) => {
        this.leaveLoading = false
        translate().showErrorMessage(err.response ? err.response.data.message : err.message)
      })
    },
    selectCard(card) {
      console.log(this.game)
      if (!this.game.myTurn) {
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
      if ( this.selectedCard && (
        !this.game.myTurn ||
        this.user.cardDraw.uid === this.selectedCard.uid ||
        this.user.deck.some((stack) => stack.some((card) => card.uid === this.selectedCard.uid))
      )) {
        return
      }

      if (!this.selectedCard) {
        this.selectedCard = this.user.deck[stackIndex].slice(-1)[0]
        console.log(this.selectedCard)
        return
      }

      const previousDeck = this.user.deck
      const previousHand = this.user.hand

      api.post('/game/move', {
        gameId: this.game.gameId,
        from: GameHelper.MOVE_TYPE_HAND,
        to: GameHelper.MOVE_TYPE_DECK,
        cardUid: this.selectedCard.uid,
        toStackIndex: stackIndex
      }).then((res) => {
        this.user.deck = res.data.deck
        this.user.hand = res.data.hand
      }).catch((err) => {
        this.user.deck = previousDeck
        this.user.hand = previousHand
        this.game.myTurn = true
        translate().showErrorMessage(err.response ? err.response.data.message : err.message)
      })

      this.user.hand = this.user.hand.filter((card) => card.uid !== this.selectedCard.uid)
      this.user.deck[stackIndex].push(this.selectedCard)

      this.selectedCard = null
      this.game.myTurn = false
    },
    selectStackOnGameStacks(index) {
      if (this.selectedCard !== null && this.game.myTurn) {
        // Récupérer la dernière card de la pile sans la retirer
        const lastStackCard = this.game.stacks[index].slice(-1)[0]
        const lastStackCardValue = lastStackCard ? lastStackCard.value : 0
        console.log(this.selectedCard)

        // Vérifier si la card sélectionnée est +1 par rapport à la card actuelle
        if (this.selectedCard.value === lastStackCardValue + 1) {
          const previousGameStack = this.game.stacks

          const fromHand = this.user.hand.some((card) => card.uid === this.selectedCard.uid)
          const fromDeck = this.user.deck.some((stack) => stack.some((card) => card.uid === this.selectedCard.uid))
          const fromCardDraw = this.user.cardDraw == this.selectedCard

          if (fromHand) {
            const previousHand = this.user.hand

            // API Request
            api.post('/game/move', {
              gameId: this.game.gameId,
              from: GameHelper.MOVE_TYPE_HAND,
              to: GameHelper.MOVE_TYPE_GAME_STACK,
              cardUid: this.selectedCard.uid,
              toStackIndex: index
            }).then((res) => {
              this.game.stacks = res.data.gameStacks
              this.user.hand = res.data.hand
            }).catch((err) => {
              this.game.stacks = previousGameStack
              this.user.hand = previousHand
              translate().showErrorMessage(err.response ? err.response.data.message : err.message)
            })

            // Déplacer la card vers la défausse centrale
            this.game.stacks[index].push(this.selectedCard)

            // Retirer la card de la main du joueur en la recherchant par son numéro
            const handCardIndex = this.user.hand.findIndex(
              (card) => card.value === this.selectedCard.value
            )

            if (handCardIndex !== -1) {
              this.user.hand.splice(handCardIndex, 1)
            }

          } else if (fromDeck) {
            const previousDeck = this.user.deck
            const fromStackIndex = this.user.deck.findIndex((stack) => stack.some((card) => card.uid === this.selectedCard.uid))

            // API Request
            api.post('/game/move', {
              gameId: this.game.gameId,
              from: GameHelper.MOVE_TYPE_DECK,
              to: GameHelper.MOVE_TYPE_GAME_STACK,
              cardUid: this.selectedCard.uid,
              fromStackIndex,
              toStackIndex: index
            }).then((res) => {
              this.game.stacks = res.data.gameStacks
              this.user.deck = res.data.deck
            }).catch((err) => {
              this.game.stacks = previousGameStack
              this.user.deck = previousDeck
              translate().showErrorMessage(err.response ? err.response.data.message : err.message)
            })

            // Add card to game stack
            this.game.stacks[index].push(this.selectedCard)

            // Remove card from deck
            this.user.deck[fromStackIndex].pop()

          } else if (fromCardDraw) {
            const previousCardDraw = this.user.cardDraw
            const previousCardDrawCount = this.user.cardDrawCount

            // API Request
            api.post('/game/move', {
              gameId: this.game.gameId,
              from: GameHelper.MOVE_TYPE_PLAYER_CARD_DRAW,
              to: GameHelper.MOVE_TYPE_GAME_STACK,
              toStackIndex: index
            }).then((res) => {
              this.game.stacks = res.data.gameStacks
              this.user.cardDraw = res.data.newCardDraw
              this.user.cardDrawCount = res.data.newCardDrawCount
              if (res.data.win) {
                this.youWin = true
              }
            }).catch((err) => {
              this.user.cardDraw = previousCardDraw
              this.user.cardDrawCount = previousCardDrawCount
              translate().showErrorMessage(err.response ? err.response.data.message : err.message)
            })

            // Add card to game stack
            this.game.stacks[index].push(this.selectedCard)

          }

          this.selectedCard = null
        }
      }
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
  .card {
    .image-card {
      width: 22px;
      // show only last 4 cards
      &:not(:first-child) {
        margin-top: -150%;
        &:nth-last-child(-n+4) {
          margin-top: -22px;
        }
      }
    }
  }
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
  width: 24px;
  height: 42px;
  border-radius: 10%;
  &.selected-card, .selected-card {
    animation: selectedCard infinite 1s ease-in-out;
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
  &-deck-center {
    width: 28px;
    height: 48px;
  }
}
.deck-joueur {
  .card {
    .image-card {
      width: 28px;
      // show only last 4 cards
      &:not(:first-child) {
        margin-top: -150%;
        &:nth-last-child(-n+4) {
          margin-top: -31px;
        }
      }
    }
  }
}
.image-card-deck-center {
  position: absolute;
}

.cardDraw-autre-joueur .card {
  width: 30px;
  height: 45px;
  .image-card {
    width: 28px;
    height: 43px;
  }
}
.user-to-play {
  border: 3px solid $primary;
}
</style>
