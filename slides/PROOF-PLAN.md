# Conduite des preuves

Ce plan correspond aux 67 slides actuelles. Les notes de [slides.md](slides.md) font référence pour le discours et les timings. Voir aussi [les démos](../demos/README.md) et [la référence de mesure](../docs/benchmark-reference-2026-09-17.md).

## Avant la scène

Depuis la racine du dépôt :

1. Lancer `make start` suffisamment tôt pour terminer la compilation native.
2. Vérifier `make proof-public` et `make parity-test`.
3. Répéter `./demos/demo-1.sh`, `./demos/demo-2.sh` et `./demos/demo-3.sh`, avec les pauses et les changements de fenêtre.
4. Garder les services à 256 arbres. Le bilan à 4 096 arbres utilise la campagne enregistrée, sans redéploiement sur scène.

## Démo 1 : cycle carte PHP local, slide 27

Lancer `./demos/demo-1.sh`. Compte initial : 1 000 €. Autorisation : 420,69 € réservés, 579,31 € disponibles. Au clearing : 579,31 € comptabilisés, 0 € réservé et toujours 579,31 € disponibles. Les rejeux ne réservent ni ne débitent une deuxième fois.

Une même clé avec un contenu différent doit provoquer un conflit. C’est un test volontaire, pas une panne. La relecture GET montre l’état courant, tandis que le rejeu restitue la réponse de la commande initiale.

## Démo 2 : extraction HTTP et contrat public, slide 45

Lancer `./demos/demo-2.sh`, pas seulement `make demo-go-http`.

- Comparer le cycle PHP HTTP puis Go HTTP.
- Montrer la représentation privée V1/V2 et la conservation du modèle public. Les scénarios ont des identifiants distincts : le script les valide avant de comparer leurs champs métier.
- Lire le profil marchand par Provider, Jane et AutoMapper. C’est une opération distincte de l’autorisation. Le champ internalOwner reste privé.

Jane et les adaptations sont déjà présents. La démo vérifie une bascule préparée, pas une migration de production ni une génération improvisée. Les détails privés affichés sont des appels d’inspection, pas une capture du trafic interne.

## Démo 3 : extension native, slide 60

Lancer `./demos/demo-3.sh`. Montrer le même cycle carte et le marqueur native_call produit après l’appel réel. Go s’exécute dans FrankenPHP sans HTTP pour ce calcul. Modifier l’extension impose de reconstruire puis redéployer le binaire ; la sélection des moteurs déjà installés n’impose pas de compilation sur scène.

## Hors parcours

`./demos/demo-4.sh` reste un consommateur préparé sans LLM. Il n’est pas une quatrième démo du talk. gRPC est uniquement une alternative expliquée sur une slide.

## Mesures et secours

Les démos fonctionnelles ne mesurent pas les gains de performance. Les slides utilisent la campagne du 17 septembre : cinq campagnes par charge, quatre répétitions par campagne, 200 appels après chauffe. Le chrono couvre l’appel HTTP client jusqu’à réception complète du corps de réponse, avec MongoDB et serveurs déjà démarrés.

Les captures de répétition se créent dans benchmark/results/ et restent locales. En cas de problème, annoncer explicitement qu’une capture est enregistrée, avec sa date. Ne pas présenter un résultat attendu comme une preuve exécutée.
