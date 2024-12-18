# EcoGarden & Co 🌱
Une API pour mieux jardiner, permettant de gérer des conseils, consulter la météo par ville, et bien plus encore !

![Build Status](https://img.shields.io/badge/build-passing-brightgreen)
![Symfony Version](https://img.shields.io/badge/symfony-7.2-blue)
![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)

## Description

EcoGarden est une API REST construite avec Symfony qui permet :
- De gérer des conseils pour jardiner en fonction des mois.
- De consulter la météo en temps réel via OpenWeatherMap.
- D'utiliser un système sécurisé avec authentification JWT.

Idéal pour les jardiniers amateurs ou experts souhaitant optimiser leur jardinage !

---

## Installation

1. Clonez le projet :
   ```bash
   git clone https://github.com/votre_nom/EcoGarden.git 

2. Installez les dépendances PHP :
   composer install

3. Configurez votre .env :
   Dupliquez le fichier .env.example
   Ajoutez votre clé OpenWeatherMap et configurez la base de données.

4. Créez la base de données
5. Lancez le serveur Symfony

## Utilisation

1. Créez un compte administrateur en chargeant les fixtures

2. Authentification
Générez un token en envoyant vos identifiants à l'endpoint /api/auth :
curl -X POST "http://127.0.0.1:8000/api/auth" \
  -H "Content-Type: application/json" \
  -d '{ "username": "votre_username", "password": "votre_password" }'

Ajoutez le token dans le header pour les requêtes suivantes :
Authorization: Bearer <votre_token>

2. Consulter un conseil par mois
Exemple pour obtenir des conseils pour le mois de janvier :
curl -X GET "http://127.0.0.1:8000/api/conseils?month=1" \
  -H "Authorization: Bearer <votre_token>"

## Contribuer
Les contributions sont les bienvenues ! Pour contribuer :

Forkez le projet.
Créez une branche pour vos modifications :
git checkout -b ma-branche
Faites vos modifications et soumettez une pull request.
N'oubliez pas de suivre les bonnes pratiques de code Symfony !