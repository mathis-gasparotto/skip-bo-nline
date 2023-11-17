import { Notify } from 'quasar'

export default () => ({
  showNotify(message, type) {
    switch (type) {
      case 'negative':
        return this.showNegativeNotify(message)
      case 'positive':
        return this.showPositiveNotify(message)
      default:
        return this.showInformalNotify(message)
    }
  },
  showNegativeNotify(message) {
    Notify.create({
      message: message,
      color: 'negative',
      icon: 'report_problem',
      position: 'top',
      timeout: 3000,
      actions: [
        {
          icon: 'close',
          color: 'white'
        }
      ]
    })
  },
  showPositiveNotify(message) {
    Notify.create({
      message: message,
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
  },
  showInformalNotify(message) {
    Notify.create({
      message: message,
      color: 'primary',
      icon: 'info',
      position: 'top',
      timeout: 3000,
      actions: [
        {
          icon: 'close',
          color: 'white'
        }
      ]
    })
  }
})
