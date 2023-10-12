<template>
  <q-container class="flex flex-center column page h-100">
    <p class="text-h6 q-px-xl q-py-md bg-primary text-center text-bold title text-white">
      Connexion
    </p>
    <q-form
      class="flex flex-center column form login-form"
      ref="loginForm"
      @submit.prevent="onsubmit()"
    >
      <q-input
        name="email"
        rounded
        outlined
        label="Adresse mail"
        autofocus
        class="q-mb-md login-input"
        type="email"
        v-model="form.email"
        :rules="[
          (val, rules) =>
            rules.email(val) || 'Veullez rensigner une addresse email valide'
        ]"
        lazy-rules
        hide-bottom-space
      ></q-input>
      <q-input
        name="password"
        rounded
        outlined
        label="Mot de passe"
        class="q-mb-md login-input"
        :type="showPassword ? 'text' : 'password'"
        v-model="form.password"
        :rules="[(val) => val.trim().length > 0 || 'Veullez remplir ce champ']"
        lazy-rules
        hide-bottom-space
      >
        <template v-slot:append>
          <q-icon
            :name="showPassword ? 'visibility' : 'visibility_off'"
            class="cursor-pointer"
            color="secondary"
            @click="showPassword = !showPassword"
          />
        </template></q-input>
      <q-btn
        label="Se connecter"
        type="submit"
        rounded
        :loading="loading"
        padding="sm 50px"
        size="18px"
        :disable="!validate"
        :class="`form-btn btn btn-${validate ? 'secondary' : 'disabled'}`"
      />
    </q-form>
    <q-card flat>
      <q-separator spaced size="2px" color="white" rounded />
      <q-card-section>
        <p class="q-my-xs text-center">
          Tu n'as pas de compte ?
          <router-link :to="{ name: 'signup' }" class="text-bold"
            >Inscris toi</router-link
          >
        </p>
      </q-card-section>
    </q-card>
  </q-container>
</template>

<script>
import { Notify } from 'quasar'

export default {
  name: 'SigninPage',
  data() {
    return {
      form: {
        email: '',
        password: ''
      },
      showPassword: false,
      loading: false,
      validate: false
    }
  },
  watch: {
    form: {
      handler() {
        if (this.form.email && this.form.password) {
          this.$refs.loginForm.validate().then((success) => {
            if (success) {
              this.validate = true
            } else {
              this.validate = false
            }
          })
          this.validate = true
        } else {
          this.validate = false
        }
      },
      deep: true
    }
  },
  methods: {
    onsubmit() {
      this.loading = true
      this.$refs.loginForm.validate().then((success) => {
        if (success) {
          // this.form.email
          // this.form.password

          // .then(() => {
              this.$router.push({ name: 'home' })
              Notify.create({
                message: 'Vous êtes désormais connecté',
                color: 'positive',
                icon: 'check_circle',
                position: 'top',
                timeout: 3000,
                actions: [
                  {
                    icon: 'close',
                    color: 'white'
                  }
                ]
              })
            // }).catch((err) => {
            //   this.loading = false
            //   Notify.create({
            //     message: err,
            //     color: 'negative',
            //     icon: 'report_problem',
            //     position: 'top',
            //     timeout: 3000,
            //     actions: [
            //       {
            //         icon: 'close',
            //         color: 'white'
            //       }
            //     ]
            //   })
            // })
        } else {
          this.loading = false
        }
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.title {
  border-radius: 15px;
}
.bg-primary .q-card {
  background: none;
}
</style>
