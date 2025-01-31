# Installation

Il se peut que vous deviez créer le fichier acme.json avec les droits adéquats.
Tout sera indiqué en cas de problème dans les logs de traefik

Pour avoir Wordpress en https, il faut un nom de domaine correct.
Pour contourner ça, vous pouvez ajouter la ligne `127.0.0.1       localhost.fr`
dans /etc/hosts. Ainsi, vous pourrez accéder à votre wordpress avec https://localhost.fr

# Build

une fois dans le dossier VueJS, tapez `npm run build`
Cette commande va générer les fichiers JS et CSS qui seront intégré dans le plugin
