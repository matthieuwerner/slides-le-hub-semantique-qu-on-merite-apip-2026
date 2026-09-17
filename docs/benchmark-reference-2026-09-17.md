# Référence de latence avec MongoDB — 17 septembre 2026

## Statut

Campagne descriptive du laboratoire courant, pas garantie de latence ni résultat de capacité. Afficher la dispersion avec la médiane. Ces chiffres remplacent le périmètre historique sans MongoDB pour décrire l’autorisation actuelle ; ils ne rendent pas les anciens résultats faux.

## Protocole

Cinq campagnes pour chacune des deux tailles de forêt, quatre répétitions par profil et configuration, 200 mesures précédées de 40 appels de chauffe par bloc. Soit 64 000 autorisations mesurées, hors chauffe et preuves. Chaque configuration occupe chaque position de l’ordre tournant une fois par campagne. Aucun run de cette série n’est écarté.

Chaque bloc produit son p50 (nearest rank). Une campagne est résumée par la médiane de ses quatre p50, donc la moyenne des deux valeurs centrales. La valeur affichée est la médiane des cinq résumés de campagne. La plage est leur minimum et maximum : **ce n’est pas un intervalle de confiance ni un percentile des échantillons fusionnés**.

Les premières passes exploratoires ne sont pas incorporées à cette série définie avant son lancement. Le poste n’est pas une machine de benchmark dédiée ; la mesure reste sensible à sa charge et à Docker Desktop. Pour reproduire les mesures, éviter les compilations et campagnes concurrentes.

## Mesures

### RULES, forêt 256

| Approche | Médiane API (ms) | Min–max des campagnes (ms) | Médiane frontière moteur (ms) |
|---|---:|---:|---:|
| PHP local | 2,81 | 1,61–3,88 | 0,009 |
| PHP HTTP | 4,31 | 2,80–7,83 | 1,075 |
| Go HTTP | 3,89 | 2,07–5,41 | 0,609 |
| Go natif | 2,76 | 1,80–4,60 | 0,024 |

### ENSEMBLE, 256 arbres

| Approche | Médiane API (ms) | Min–max des campagnes (ms) | Médiane frontière moteur (ms) |
|---|---:|---:|---:|
| PHP local | 2,89 | 1,76–3,77 | 0,042 |
| PHP HTTP | 4,49 | 3,37–5,82 | 1,124 |
| Go HTTP | 3,82 | 2,52–4,93 | 0,602 |
| Go natif | 2,97 | 1,85–3,16 | 0,035 |

### ENSEMBLE, 4 096 arbres

| Approche | Médiane API (ms) | Min–max des campagnes (ms) | Médiane frontière moteur (ms) |
|---|---:|---:|---:|
| PHP local | 3,71 | 3,64–4,61 | 0,502 |
| PHP HTTP | 5,51 | 5,05–6,21 | 1,719 |
| Go HTTP | 4,32 | 4,17–5,78 | 0,837 |
| Go natif | 3,17 | 3,00–3,52 | 0,213 |

### Contrôle RULES, forêt 4 096

| Approche | Médiane API (ms) | Min–max des campagnes (ms) | Médiane frontière moteur (ms) |
|---|---:|---:|---:|
| PHP local | 2,87 | 2,28–3,30 | 0,009 |
| PHP HTTP | 4,84 | 3,84–5,12 | 1,160 |
| Go HTTP | 3,89 | 3,48–4,34 | 0,607 |
| Go natif | 2,88 | 2,61–3,04 | 0,025 |

## Ce que les durées couvrent

La durée API couvre la requête HTTP et la réception du corps, incluant le traitement serveur de l’autorisation : lecture MongoDB, enrichissement, calcul, décision et sauvegarde atomique avec réservation lorsqu’elle est acceptée. Le montage du scénario, la construction de la requête côté client et son décodage JSON sont hors chronométrage. Le clearing et les rejeux ne font pas partie de la mesure de latence.

La durée frontière moteur inclut l’adaptateur et le transport éventuel, mais exclut enrichissement et MongoDB. Ce n’est pas le calcul pur. Les deux profils peuvent produire des états métier différents ; comparer les moteurs à profil identique, pas attribuer tout écart entre profils au seul nombre d’arbres.

Commande neuve à chaque appel, compte neuf à chaque bloc avec même provision initiale. Les documents comptes accumulent les autorisations au cours du bloc. Cette croissance, identique par configuration, fait partie du coût du laboratoire borné. Le repository lit puis réécrit les collections embarquées : ce n’est pas un modèle de ledger extensible à une charge arbitraire.

## Runtime et preuves

Symfony 8.1.6, environnement prod, debug false ; PHP 8.5.10, OPcache actif, Xdebug absent, JIT actif et workers FrankenPHP. Cache Symfony préchauffé au build, moteur préchauffé au démarrage. Les rapports conservent les informations du runtime HTTP. Le code métier et les paramètres de cache n’ont pas été modifiés pour obtenir ces résultats.

Parité validée aux deux tailles sur 552 évaluations chacune. Chaque campagne vérifie le contrat public, les sorties des moteurs, les exports et les refus avant de retenir ses temps. La parité seule ne prouve pas l’absence d’un défaut partagé. Pas de preuve de capacité, de saturation, de coût cloud ou de productivité d’équipe. Le microbenchmark CLI historique n’a pas été relancé.

## Conséquence pour la conférence

Ne pas reprendre le gain historique de 38 % comme gain établi du cycle avec MongoDB. Un gain sur la frontière moteur ne garantit pas le même gain sur l’autorisation. La médiane fournit un résumé ; une grande plage indique que la valeur exacte reste instable. Il faut afficher cette limite, et non sélectionner les passes qui avantagent un moteur. Les chiffres à trois décimales ne sont pas une précision expérimentale garantie.

La configuration par défaut du laboratoire utilise 256 arbres. Après une campagne à 4 096 arbres, rétablir cette valeur pour retrouver le scénario des démonstrations. Les dix campagnes sources et leur résumé sont versionnés dans ce dépôt.

## Fichiers sources

- ../benchmark/results/bench-256-20260917T094758Z.json
- ../benchmark/results/bench-256-20260917T094828Z.json
- ../benchmark/results/bench-256-20260917T094908Z.json
- ../benchmark/results/bench-256-20260917T094954Z.json
- ../benchmark/results/bench-256-20260917T095031Z.json
- ../benchmark/results/bench-4096-20260917T095113Z.json
- ../benchmark/results/bench-4096-20260917T095149Z.json
- ../benchmark/results/bench-4096-20260917T095237Z.json
- ../benchmark/results/bench-4096-20260917T095318Z.json
- ../benchmark/results/bench-4096-20260917T095408Z.json

Agrégat complet : ../benchmark/results/reference-20260917.json. Script exécuté : benchmark/compare.sh bench, avec REPEATS=4, SAMPLES=200, WARMUP=40, cinq fois par taille (256 puis 4096). Aucune campagne exploratoire antérieure au début de cette série n’est retenue.
