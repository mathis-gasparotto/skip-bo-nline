import notify from './notify'

export default () => ({
  translateErrorMessage(message) {
    switch (message) {
      case 'Unauthenticated.':
        return 'Vous devez être connecté pour effectuer cette action'
      case 'You are not allow to join this game':
        return 'Vous n\'êtes pas autorisé à rejoindre cette partie'
      case 'You are not on this game':
        return 'Vous n\'êtes pas dans cette partie'
      case 'Invalid data':
        return 'Données invalides'
      case 'Game not found':
        return 'Partie introuvable'
      case 'Game is finished':
        return 'La partie est terminée'
      case 'These credentials do not match our records.':
        return 'Identifiants incorrects'
      case 'The username has already been taken. (and 1 more error)':
        return 'Nom d\'utilisateur déjà utilisé'
      case 'The email has already been taken.':
        return 'Adresse email déjà utilisée'
      case 'Game is full':
        return 'La partie est pleine'
      case 'Game has already started':
        return 'La partie a déjà commencé'
      case 'Game is already finished':
        return 'La partie est déjà terminée'
      case 'You\'re not the host of this game':
        return 'Vous n\'êtes pas l\'hôte de cette partie'
      case 'Game already joined':
        return 'Vous avez déjà rejoinds cette partie'
      case 'Already on a game':
        return 'Vous êtes déjà dans une partie'
      case 'Card not found':
        return 'Carte introuvable'
      case 'Not enough players':
        return 'Pas assez de joueurs'
      case 'You cannot draw another card':
        return 'Vous ne pouvez pas tirer une autre carte'
      case 'Game has not started yet':
        return 'La partie n\'a pas encore commencé'
      case 'It\'s not your turn':
        return 'Ce n\'est pas votre tour'
      default:
        console.log(message)
        return 'Une erreur est survenue'
    }
  },
  showErrorMessage(message) {
    const translatedMessage = this.translateErrorMessage(message)
    notify().showNegativeNotify(translatedMessage)
  }
})
