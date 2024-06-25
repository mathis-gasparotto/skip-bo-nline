import { route } from 'quasar/wrappers'
import { createRouter, createMemoryHistory, createWebHistory, createWebHashHistory } from 'vue-router'
import routes from './routes'
import { SessionStorage } from 'quasar'
import { api } from 'boot/axios'
import translate from 'src/services/translate'
import GameHelper from 'src/helpers/GameHelper'

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

export default route(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : (process.env.VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory)

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    // Leave this as is and make changes in quasar.conf.js instead!
    // quasar.conf.js -> build -> vueRouterMode
    // quasar.conf.js -> build -> publicPath
    history: createHistory(process.env.MODE === 'ssr' ? void 0 : process.env.VUE_ROUTER_BASE)
  })

  Router.beforeEach(async (to, from, next) => {
    const isAuthenticated = SessionStorage.has('user')
    if (!isAuthenticated && to.name !== 'signin' && to.name !== 'signup') {
      return next({
        name: 'signin',
      })
    } else if (
      isAuthenticated &&
      (to.name === 'signin' || to.name === 'signup')
    ) {
      return next({
        name:
          from.name === 'signin' || to.name === 'signup' ? 'home' : from.name,
      })
    } else if (isAuthenticated && to.name === 'game' && from.name !== 'gameLobby') {
      return api.post('/game/check', { data: to.params.uid, type: GameHelper.CODE_TYPE_GAME_ID }).then(() => {
        return next()
      }).catch((err) => {
        translate().showErrorMessage(err.response ? err.response.data.message : err.message)
        return next({
          name: from.name || 'home',
        })
      })
    } else if (isAuthenticated && to.name === 'joinGame') {
      return api.post('/game/join', { code: to.params.joinCode }).then((res) => {
        return next({ name: 'gameLobby', params: { joinCode: res.data.joinCode } })
      }).catch((err) => {
        translate().showErrorMessage(err.response ? err.response.data.message : err.message)
        return next({
          name: from.name || 'home',
        })
      })
    } else if (isAuthenticated && to.name === 'gameLobby' && from.name !== 'joinGame') {
      return api.post('/game/check', { data: to.params.joinCode, type: GameHelper.CODE_TYPE_JOIN_CODE }).then(() => {
        return next()
      }).catch((err) => {
        translate().showErrorMessage(err.response ? err.response.data.message : err.message)
        return next({
          name: from.name || 'home',
        })
      })
    } else {
      return next()
    }
  })

  return Router
})
