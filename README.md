# Projet modélisation des données

**Groupe 5** : Petro B., Martin C., Alexandre D., Louis G.

[Rapport final du projet (PDF)](./rapport.pdf)

**Gestion de portfolio financiers**

## Livrable 1: Cahier des charges

Disponible dans le dossier [Cahier des charges (cdc)](./cdc/)

## Livrable 2: Modèle Conceptuel, Logique et Physique de données

- [Modèle Conceptuel de Données (mcd)](./mcd/)

### Feedback

- Pour la classe Instrument financier, il faut scinder le type de l’instrument lui-même pour une meilleure lisibilité et éviter de nombreux champs NULL.

- Pour la classe Cours : normalement, les informations stockées sont journalières donc pourquoi avoir l’heure ?

- Pas besoin d’une relation historique de transactions, ces informations peuvent être récupérées via une requête SQL -> OK

- Une transaction concerne à la fois un portfolio et un utilisateur car un utilisateur effectue une transaction dans un portfolio, il s’agit donc d’une relation ternaire. -> OK

- La devise doit être liée au portfolio uniquement et les instruments de ce portfolio auront automatiquement la même devise. Relation devise entre instrument et devise à supprimer pour éviter un cycle inutile. -> pas sur que le cycle est inutile car instrument financier n'appartient pas portfolio.

- Il faut mettre à jour toute la partie relative aux instruments financiers avec les entreprises, pays et bourse. Vous trouverez une ébauche de proposition dans l’image du lien suivant : ...

- Modifier le MLD en fonction du MCD et ajouter l’information qu’un portfolio ne contient qu’une seule devise dans le cahier des charges (c’est bien noté dans les explications du MCD mais pas dans le cahier des charges) -> devise OK

## Livrable 3: Code source et Rapport

- [Rapport](./rapport.pdf)
