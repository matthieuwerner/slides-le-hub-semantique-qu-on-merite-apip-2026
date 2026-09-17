# Exécuter les démonstrations

Depuis la racine du dépôt :

```sh
./demos/prepare.sh
./demos/demo-1.sh
./demos/demo-2.sh
./demos/demo-3.sh
```

Prérequis : Docker démarré, Docker Compose, Node.js 22+, Bash, curl et jq. Les scripts peuvent aussi être appelés par leur chemin absolu depuis un autre dossier.

`prepare.sh` démarre les images déjà construites, attend la disponibilité des services et affiche les moteurs déclarés. Pour la première installation ou après une modification du code, utiliser `./demos/prepare.sh --build` : la compilation de FrankenPHP et de son extension peut prendre plusieurs minutes. Le contrôle de disponibilité ne remplace pas les assertions exécutées par les démonstrations.

## Ce que chaque script fait

| Script | Parcours | Point à montrer |
| --- | --- | --- |
| `demo-1.sh` | Cycle carte, risque PHP local | API Platform conserve le contrat public ; autorisation, réservation, clearing et rejeu sont persistés. |
| `demo-2.sh` | Cycles HTTP, formats V1/V2, projection marchand | Montrer ce qui change derrière la façade et ce que le consommateur conserve. |
| `demo-3.sh` | Même cycle avec le module Go natif | `Server-Timing` identifie la fonction native ; pas de traversée HTTP pour le calcul du risque. Le reste de l'API reste PHP. |

Ces trois scripts correspondent aux trois actes des slides. Le cas 2 contient deux configurations : PHP HTTP puis Go HTTP. L’alternative gRPC est illustrative et non implémentée.

Le script facultatif `demo-4.sh` illustre un consommateur préparé, sans LLM, qui autorise et réserve sans clearing. Il utilise la même API et n’introduit pas de moteur supplémentaire.

Les lanceurs réutilisent `benchmark/card-cycle.mjs` : pas de deuxième implémentation des scénarios. Les démos 1 et 3 affichent les échanges détaillés. La démo 2 affiche des synthèses et tableaux comparatifs ; CARD_DEMO_VERBOSE=1 réactive ses échanges détaillés. Un statut 409 ou 422 peut être **attendu** : c'est alors un test de conflit ou de validation, pas une panne. Une assertion non satisfaite arrête le script avec un code d'échec.

Chaque exécution crée des comptes de laboratoire neufs dans MongoDB, sans remise à zéro globale. Les comptes restent persistés. Les captures de réponses et le résumé sont conservés dans `benchmark/results/cycle-*/` et, pour les comparaisons de la démo 2, `benchmark/results/demo-2-*/` ; leur chemin est affiché en fin de scénario. Aucun benchmark, mesure comparative ou effacement de données n'est lancé.

## Interpréter les résultats

1. Compte initial : comptabilisé et disponible à 1 000 €.
2. Autorisation de 420,69 € : réservé 420,69 €, disponible 579,31 €.
3. Clearing : comptabilisé 579,31 €, réservé 0 €, disponible toujours à 579,31 €.
4. Rejeu : les soldes restent inchangés, sans nouvelle réservation ni nouveau débit.

Le script attend Entrée avant chaque configuration dans un terminal interactif, puis déroule automatiquement. Les cas de conflit et de validation vérifient aussi que les demandes invalides n’altèrent pas les soldes.

```sh
# Exécution compacte, sans pause :
DEMO_AUTO=1 CARD_DEMO_VERBOSE=0 ./demos/demo-2.sh
```

URL par défaut : `http://localhost:8099`. `API_PORT` ou `API_ORIGIN` permettent une autre adresse ; ne les pointer que vers un laboratoire autorisé, car les scripts créent réellement des données. Le sélecteur de moteur par en-tête est réservé au laboratoire, pas une interface à exposer en production.

Ces lanceurs ne régénèrent pas Jane et ne modifient pas le contrat. La démo 2 montre aussi la projection du Provider marchand via Jane et AutoMapper. Aucun gain de performance ne doit être déduit de leur durée.

## Démo 2 : trois preuves lisibles

`./demos/demo-2.sh` enchaîne les cycles PHP HTTP et Go HTTP avec comparaison métier entre moteurs, puis affiche les formats Go V1/V2 face aux réponses publiques, et enfin le profil marchand privé/public. Les appels privés sont des inspections directes sur une entrée enrichie de référence, pas des captures du trafic interne. Les deux autorisations publiques utilisent des comptes neufs ; leurs identités sont validées et exclues du diff métier.

Pauses entre les trois étapes en terminal interactif. `DEMO_AUTO=1` désactive les pauses. `CARD_DEMO_VERBOSE=1` affiche les JSON complets ; ils sont toujours conservés dans `benchmark/results/`. `RISK_ENGINE_ORIGIN` permet de changer l’adresse privée Go (par défaut localhost:8098). Un échec HTTP ou une assertion arrête la démo avec un code non nul. Aucun benchmark ni effacement de comptes.

`make demo-go-http` conserve le cycle Go seul, et ne remplace pas cette présentation complète. Tests des vérifications du nouveau lanceur : `node --test benchmark/demo-2-checks.test.mjs`.
