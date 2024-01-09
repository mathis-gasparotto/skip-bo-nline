import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: process.env.VUE_PUSHER_APP_KEY,
  cluster: process.env.VUE_PUSHER_APP_CLUSTER,
  forceTLS: true
})
