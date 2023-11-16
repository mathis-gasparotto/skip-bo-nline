<template>
  <q-page class="flex flex-center">
    <div class="plateaux-autres-joueurs">
      <div
        class="plateau-autre-joueur"
        v-for="(player, index) in adversaires"
        :key="index"
      >
        <img
          class="avatar"
          src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTQvJAJoyYLSV2Eq3j2v09H1gTXt9BvTawmIJ0HAHNe0baHwxACwJH3tVBs46Wy7je7Iaw&usqp=CAU"
          alt="avatar"
        />
        <p class="pseudo">{{ player.pseudo }}</p>
        <div class="defausse-autre-joueur">
          <div
            class="carte"
            v-for="(pile, index) in player.defausse"
            :key="index"
          >
            <img
              v-for="(carte, index) in pile"
              :key="index"
              class="image-carte image-carte-defausse"
              :src="`/assets/${carte}.png`"
              :alt="`${carte}`"
            />
          </div>
        </div>
        <div class="pioche-autre-joueur">
          <div class="carte">
            <img
              class="image-carte"
              :src="`/assets/${player.pioche}.png`"
              :alt="`${player.pioche}`"
            />
          </div>
          <div class="autre-joueurs-taillePioche">
            {{ player.taille_pioche }}
          </div>
        </div>
      </div>
    </div>

    <div class="plateau-centre">
      <div class="pioche-joueurs">
        <div class="carte">
          <img
            @click="selectCardPiocheCentrale()"
            class="image-carte"
            src="/assets/skipbo.png"
            alt="pioche"
          />
        </div>
      </div>
      <div class="defausse-joueurs">
        <div
          @click="selectCardDefausseCentrale(pile, index)"
          class="carte"
          v-for="(pile, index) in plateauCentre.defausse"
          :key="index"
        >
          <img
            v-for="(carte, index) in pile"
            :key="index"
            class="image-carte image-carte-defausse-centre"
            :src="`/assets/${carte}.png`"
            :alt="`${carte}`"
          />
        </div>
      </div>
    </div>

    <div class="plateau-joueur">
      <div class="partie-sup">
        <div class="pioche-joueur">
          <div class="carte">
            <img
              @click="selectCardPiocheJoueur(carte)"
              class="image-carte"
              :src="`/assets/${user.pioche}.png`"
              :alt="`${user.pioche}`"
            />
          </div>
          <div class="joueur-taillePioche">{{ user.taille_pioche }}</div>
        </div>
        <div class="defausse-joueur">
          <div
            @click="selectCardDefausseJoueur(index)"
            class="carte"
            v-for="(pile, index) in user.defausse"
            :key="index"
          >
            <!--{{ carte }}-->
            <img
              v-for="(carte, index) in pile"
              :key="index"
              class="image-carte image-carte-defausse"
              :src="`/assets/${carte}.png`"
              :alt="`${carte}`"
            />
          </div>
        </div>
        <div class="profil">
          <img
            class="avatar"
            src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTQvJAJoyYLSV2Eq3j2v09H1gTXt9BvTawmIJ0HAHNe0baHwxACwJH3tVBs46Wy7je7Iaw&usqp=CAU"
            alt="avatar"
          />
          <p class="pseudo">{{ user.pseudo }}</p>
        </div>
      </div>
      <div class="cartes-main">
        <div
          class="carte"
          v-for="carte in user.main"
          :key="carte.uid"
          :class="{ 'selected-card': selectedCard === carte }"
        >
          <img
            @click="selectCardMainJoueur(carte)"
            class="image-carte"
            :src="`/assets/${carte.number}.png`"
            :alt="`${carte.number}`"
          />
        </div>
      </div>
    </div>
  </q-page>
</template>

<script>
import { uid, Notify } from 'quasar'
import { useRoute } from 'vue-router'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { api } from 'boot/axios'

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

    window.Echo.channel('party-' + route.params.uid)
      .listen('UserJoined', (e) => {
        console.log('User Joined!', e.user)
      })

    return {
      route
    }
  },
  data() {
    return {
      adversaires: [
        {
          pseudo: 'njuh',
          defausse: [[1, 2, 3], [4, 5, 6], [], []],
          pioche: 4,
          taille_pioche: 5
        },
        {
          pseudo: 'klpi',
          defausse: [[], [], [1, 2, 3], [4, 5, 6]],
          pioche: 8,
          taille_pioche: 10
        }
      ],
      user: {
        pseudo: 'jiei',
        defausse: [[6, 7, 3], [9, 8, 6], [], []],
        pioche: 7,
        taille_pioche: 3,
        main: [
          {
            number: 1,
            uid: uid()
          },
          {
            number: 5,
            uid: uid()
          },
          {
            number: 7,
            uid: uid()
          },
          {
            number: 9,
            uid: uid()
          },
          {
            number: 7,
            uid: uid()
          }
        ]
      },
      plateauCentre: {
        defausse: [[], [9, 8, 6], [4, 5, 9], []],
        pioche: {
          number: 5,
          uid: uid()
        }
      },
      selectedCard: null,
      countDefausseJoueur: true
    }
  },
  methods: {
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
  top: 100px;
  border: 1px solid #333;
}
.pioche-autre-joueur {
  //background: brown;
  height: 70px;
  width: 50px;
}

.defausse-autre-joueur {
  //background: brown;
  height: 70px;
  width: 100%;
  margin-bottom: 10px;
  display: flex;
  justify-content: space-evenly;
}
.pseudo {
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
}
.pioche-joueur {
  //background: brown;
  height: 100px;
  width: 70px;
}
.joueur-taillePioche {
  position: absolute;
  top: 85px;
}
.defausse-joueur {
  background: rgb(169, 169, 187);
  height: 120px;
  width: 200px;
  display: flex;
  justify-content: space-evenly;
  border: 1px solid #333;
}

.partie-sup {
  //background: greenyellow;
  height: 120px;
  width: 100%;
  display: flex;
  justify-content: space-evenly;
  margin-bottom: 15px;
}
.cartes-main {
  //background: purple;
  height: 100px;
  width: 100%;
  display: flex;
  justify-content: center;
}
.profil .pseudo {
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

/**  Plateau de la pioche et de la défausse commune  **/
.plateau-centre {
  //background: rgb(169, 169, 187);
  height: 100px;
  width: 100%;
  display: flex;
  justify-content: space-evenly;
}
.pioche-joueurs {
  //background: brown;
  height: 100px;
  width: 70px;
}

.defausse-joueurs {
  //background: brown;
  height: 100px;
  width: 200px;
  display: flex;
  justify-content: space-evenly;
  align-items: center;
}

/**  Carte  **/
.carte {
  border: 1px solid #333;
  width: 20px;
  height: 35px;
  margin-top: 5px;
  border-radius: 10%;
}
.defausse-joueurs .carte,
.defausse-joueur .carte {
  width: 30px;
  height: 50px;
}
.cartes-main .carte,
.pioche-autre-joueur .carte,
.pioche-joueur .carte,
.pioche-joueurs .carte {
  width: 40px;
  height: 60px;
}
.pioche-autre-joueur,
.pioche-joueur,
.pioche-joueurs {
  display: flex;
  justify-content: center;
  align-items: center;
}

.image-carte {
  width: 38px;
  height: 58px;
  border-radius: 10%;
}
.defausse-joueur .carte .image-carte {
  width: 28px;
  height: 48px;
}
.image-carte-defausse-centre {
  position: absolute;
}
.image-carte-defausse:not(:first-child) {
  margin-top: -100px;
}
.defausse-autre-joueur .carte .image-carte {
  width: 20px;
  height: 35px;
}

.pioche-autre-joueur .carte .image-carte,
.pioche-autre-joueur .carte {
  width: 30px;
  height: 45px;
}

.carte.selected-card {
  border: 2px solid yellow;
}
</style>
