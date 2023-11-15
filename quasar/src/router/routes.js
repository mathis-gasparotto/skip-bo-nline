
const routes = [
  {
    path: '/welcome',
    component: () => import('layouts/GuestLayout.vue'),
    children: [
      {
        path: 'signup',
        name: 'signup',
        component: () => import('pages/SignupPage.vue')
      },
      {
        path: 'signin',
        name: 'signin',
        component: () => import('pages/SigninPage.vue')
      },
    ]
  },
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('pages/IndexPage.vue')
      },
      {
        path: 'party',
        name: 'party',
        component: () => import("pages/PartiePage.vue")
      }
    ]
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes
