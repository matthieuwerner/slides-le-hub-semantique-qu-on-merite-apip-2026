# API Platform : le hub sémantique qu’on mérite

Slides et exemples du talk de Matthieu Werner à API Platform Conference 2026.

**Changer le moteur, pas le sens de l’API.** Ce laboratoire utilise API Platform comme façade métier devant un calcul de risque exécuté en PHP local, dans un service HTTP PHP puis Go, ou dans une extension Go embarquée dans FrankenPHP.

Le scénario suit un paiement par carte : autorisation, réservation, clearing et rejeu sans double débit. MongoDB conserve l’état du compte. Les données sont synthétiques : aucun paiement ni échange avec un réseau bancaire réel.

## Démarrer le laboratoire

Prérequis : Docker avec Docker Compose, Node.js 22+, Make, Bash, curl et jq.

Depuis la racine du dépôt :

```sh
make start
./demos/demo-1.sh
./demos/demo-2.sh
./demos/demo-3.sh
```

La première compilation de FrankenPHP et de son extension peut prendre plusieurs minutes.

- API publique du laboratoire : http://localhost:8099
- Service Go interne : http://localhost:8098
- Arrêt des services : `make stop`

Ce laboratoire est destiné à un usage local, sans authentification ni contrôle d’accès. Ne pas l’exposer sur Internet ni réutiliser sa configuration en production.

## Les trois démonstrations

1. **PHP local** : cycle complet du paiement, persistance et idempotence.
2. **PHP puis Go en HTTP** : extraction du calcul, client généré avec Jane, évolution du format privé sans changement du contrat public et projection d’un profil marchand avec AutoMapper.
3. **Go dans FrankenPHP** : appel du même calcul via une extension native, sans traversée HTTP interne.

Les scripts vérifient les résultats et conservent leurs traces. Voir les [commandes, options et résultats attendus](demos/README.md). Les démonstrations fonctionnelles ne sont pas des benchmarks.

## Slides

Voir les [instructions Slidev](slides/README.md) pour afficher la présentation, consulter les notes orateur ou construire une version statique.

## Tests et benchmarks

Une fois les services démarrés :

```sh
make proof-cycle
make proof-public
make parity-test
```

Ces contrôles vérifient le cycle persistant, le contrat public et la cohérence des moteurs. Les [tests et leurs limites](docs/CONFERENCE-VALIDATION.md) détaillent ce que chaque vérification couvre.

Pour lancer les tests PHP et PHPStan, installer les dépendances de développement avec Composer dans `api/`, puis utiliser `make test-php` et `make stan`. `make test-go` exécute les tests Go dans Docker. `make contract-php` régénère le client Jane depuis le contrat privé ; ne pas modifier ses fichiers générés à la main.

`make bench` lance une nouvelle comparaison sur votre poste. Les [résultats et le protocole](docs/results.md) expliquent les mesures. La [référence du 17 septembre 2026](docs/benchmark-reference-2026-09-17.md), utilisée dans les slides, inclut MongoDB et la dispersion entre campagnes. Ses dix campagnes sources et leur résumé sont versionnés. Ces mesures locales ne prédisent pas les performances en production.

## Pour aller plus loin

- [Architecture](docs/architecture.md) et [limites du laboratoire](docs/limitations.md)
- [Modèle de risque synthétique](docs/risk-model.md)
- [Contrat OpenAPI privé](contract/risk-engine.openapi.yaml)
- [Extension Go pour FrankenPHP](go/ext/README.md)

Le code de l’API se trouve dans `api/`, le moteur et l’extension dans `go/`, la construction de FrankenPHP dans `build/`, et les scénarios dans `demos/` et `benchmark/`.

## Licence et visuels

Le code original est sous [licence MIT](LICENSE), copyright 2026 Matthieu Werner. Les licences des dépendances tierces restent applicables.

Les visuels Treezor sont utilisés avec l’autorisation obtenue par l’auteur. Les marques et visuels Treezor ne sont pas couverts par la licence MIT du dépôt ; leur présence n’accorde pas d’autorisation générale de réutilisation.
