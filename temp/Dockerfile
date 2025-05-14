# Utiliser l'image officielle Apache (httpd)
FROM httpd:2.4

# Copier le fichier index.html dans le dossier par défaut d'Apache
COPY ./index.html /usr/local/apache2/htdocs/

# Exposer le port 80
EXPOSE 80

# Commande par défaut pour démarrer Apache
CMD ["httpd-foreground"]