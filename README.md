# Skip-Bo

Bienvenue dans notre implémentation du jeu de cartes Skip-Bo !

## Description

Skip-Bo est un jeu de cartes amusant et stratégique où les joueurs doivent se débarrasser de leurs cartes en formant des piles de séquences numérotées.

## Règles du jeu

### Objectif du jeu

Le but du jeu est d'être le premier joueur à vider entièrement sa pile de stock.

### Déroulement du jeu

1. Chaque joueur commence son tour en piochant assez de cartes pour avoir un total de cinq cartes en main.
2. Pendant son tour, un joueur peut jouer des cartes de sa main, de sa pile de stock, ou des piles de défausse pour former des piles de construction au centre de la table. Les piles de construction doivent commencer à 1 et se terminer à 12. Les cartes Skip-Bo sont des jokers et peuvent être utilisées comme n'importe quel numéro.
3. Un joueur peut continuer à jouer des cartes tant qu'il le peut. Quand il ne peut plus jouer, il doit défausser une carte de sa main sur une de ses quatre piles de défausse, terminant ainsi son tour.
4. Le premier joueur à vider entièrement sa pile de stock remporte la partie.

### Piles de construction

- Il peut y avoir jusqu'à quatre piles de construction au centre de la table.
- Chaque pile de construction commence à 1 et doit être construite en ordre numérique jusqu'à 12.
- Une fois qu'une pile atteint 12, elle est retirée du jeu, et une nouvelle pile peut être commencée.

### Cartes Skip-Bo

- Les cartes Skip-Bo sont des jokers et peuvent être utilisées comme n'importe quel numéro.
- Elles peuvent être placées sur les piles de construction pour aider à compléter la séquence.

## Installation et Exécution

### Prérequis

- [Node.js](https://nodejs.org/)
- [npm](https://www.npmjs.com/)
- [Composer](https://getcomposer.org/)
- [PHP](https://www.php.net/)
- [Laravel](https://laravel.com/)

### Backend (Laravel)

1. Clonez le dépôt et accédez au répertoire backend :

```bash
git clone https://github.com/mathis-gasparotto/skip-bo-nline.git
cd skip-bo-nline/laravel/
```

2. Installez les dépendances PHP :

```bash
composer install
```

3. Créez votre base de données

4. Copiez le fichier `.env.example` en `.env`

5. Définissez les variables d'environnement liées à la base de données

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skip-bo-nline
DB_USERNAME=root
DB_PASSWORD=
DB_ENGINE=InnoDB
```

5. Définissez les informations Pusher dans le fichier `.env`

```bash
PUSHER_APP_ID=0000000
PUSHER_APP_KEY=00000000000000
PUSHER_APP_SECRET=00000000000000
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=eu
```

6. Configurez votre base de données et lancez les migrations :

```bash
php artisan migrate
```

7. Lancez le serveur Laravel :

```bash
php artisan serve
```

### Frontend (Quasar)

1. Clonez le dépôt et accédez au répertoire frontend :

```bash
git clone https://github.com/mathis-gasparotto/skip-bo-nline.git #Si besoin
cd skip-bo-nline/quasar/
```

2. Copiez le fichier `.env.example` en `.env`

3. Définissez les informations Pusher dans le fichier `.env`

```bash
VUE_PUSHER_APP_KEY=00000000000000
VUE_PUSHER_APP_CLUSTER=eu
```

4. Installez les dépendances npm :

```bash
npm install
```

5. Lancez le serveur de dev :
```bash
npm run dev
````

---

Amusez-vous bien en jouant à Skip-Bo, online 😉 !
