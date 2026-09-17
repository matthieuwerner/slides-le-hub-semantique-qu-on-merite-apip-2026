# Contrats et vérifications

Le laboratoire vérifie un même contrat public avec quatre configurations : PHP local, PHP HTTP, Go HTTP et Go natif dans FrankenPHP.

## Ce que les tests vérifient

| Vérification | Commande | Périmètre |
| --- | --- | --- |
| Cycle persistant | `make proof-cycle` | Autorisation, réservation, clearing, rejeu et commandes concurrentes |
| Contrat public | `make proof-public` | Réponses publiques, exports, lecture du marchand et compatibilité du format privé V2 |
| Parité des moteurs | `make parity-test` | Comparaison des évaluations sur les cas de référence |
| Tests PHP | `make test-php` | Tests unitaires, fonctionnels et métadonnées du contrat OpenAPI |
| Analyse statique PHP | `make stan` | Vérification des types, y compris le stub de l’extension native |
| Tests Go | `make test-go` | Formatage, analyse avec `go vet` et tests du moteur et de son adaptateur HTTP |

Les trois premières commandes nécessitent la stack démarrée avec `make start`. Les tests PHP et l’analyse statique nécessitent les dépendances Composer de développement dans `api/`.

## Les invariants du paiement

- Une autorisation approuvée réserve les fonds dans MongoDB.
- `requestId` déduplique les demandes par compte et opération. Réutiliser la même clé avec un contenu différent produit un conflit HTTP 409.
- Le clearing comptabilise le débit et libère la réservation. Le rejouer ne provoque pas de deuxième débit.
- Le GET de l’autorisation expose son état courant ; rejouer une commande ne remplace pas cette lecture.
- Les identités restent stables. Les comparaisons entre comptes de test indépendants vérifient ces identités avant de les exclure du diff métier.

## La façade et les adaptations

API Platform expose les ressources et leurs descriptions OpenAPI, JSON-LD et Hydra. Les Providers assurent les lectures, les Processors reçoivent les commandes, et les services applicatifs portent les règles métier. Les DTO publics ne dépendent pas d’un ORM.

Jane génère le client du contrat HTTP privé. Le mapping explicite convertit le format de risque V2 vers le modèle attendu. Une valeur incompatible provoque une erreur technique, pas un score arrondi ni un refus métier inventé. AutoMapper projette les champs publics du profil marchand sans exposer sa propriété interne.

Comparer les moteurs ne suffit pas : ils pourraient partager la même erreur. Les tests vérifient aussi des résultats attendus indépendamment de cette comparaison, notamment les conversions incompatibles.

`OpenApiContractTest` vérifie les réponses déclarées 200, 400, 404, 409, 422 et 503 des deux commandes, ainsi que leurs schémas de succès. Il ne constitue pas un outil de comparaison historique de versions OpenAPI.

## Reproduire les démonstrations

Les [scripts de démonstration](../demos/README.md) exécutent les scénarios et arrêtent l’exécution si une assertion échoue. Ils affichent le chemin des traces produites dans `benchmark/results/`.

Les [mesures de performance](results.md) sont distinctes de ces contrôles fonctionnels. La référence publiée conserve ses campagnes sources ; les nouvelles exécutions produisent leurs propres résultats locaux.

## Limites

Ces vérifications ne démontrent ni une capacité de production, ni une conformité bancaire, ni une qualité prédictive du modèle de risque. Le laboratoire ne met en œuvre aucun règlement interbancaire. L’authentification et le contrôle d’accès sont hors périmètre.

Voir les [limites détaillées](limitations.md). Les documents d’archives décrivent des versions antérieures et ne font pas référence pour le comportement actuel.
