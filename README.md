# SnowTricks
Projet 6 de la formation **PHP/Symfony** d'OpenClassrooms : Développez de A à Z le site communautaire SnowTricks !

Ce projet a été développé avec PHP **8.1.4** et Symfony **6.0.12**
## Installer le projet localement
Pour installer le projet sur votre machine, suivez ces étapes :
- Installez un environnement PHP & MySQL *(par exemple via [XAMPP](https://www.apachefriends.org/))*
- Installez [Composer](https://getcomposer.org/download/)
### 1) Clonez le projet et installez les dépendances :
> git clone https://github.com/GGOx94/SnowTricks.git 

> composer install 
### 3) Changez les variables d'environnement dans le fichier **.env**
Modifiez le chemin d'accès à la base de données :
>DATABASE_URL="mysql://**db_user**:**db_password**@127.0.0.1:3306/db_name?serverVersion=5.7&charset=utf8mb4"

Modifiez aussi le DSN du Mailer, par exemple pour [Mailtrap](https://mailtrap.io/) :
>MAILER_DSN=smtp://**User**:**Password**@smtp.mailtrap.io:2525?encryption=tls&auth_mode=login
### 4) Base de données et jeu de démonstration
Créez la base de données, lancez la migration et injectez les données de démonstration :
>php bin/console doctrine:database:create

>php bin/console doctrine:migrations:migrate

>php bin/console doctrine:fixtures:load

## Tout est prêt !
Vous pouvez lancer le serveur :
>symfony server:start

Le site est alors accessible, par défaut sur : http://localhost:8000