# Préparer et présenter le talk

## Format actuel

67 slides, sans annexe. Trois démonstrations : PHP local, PHP HTTP puis Go HTTP, Go natif dans FrankenPHP. L’alternative gRPC est illustrative, sans quatrième démonstration. Le consommateur préparé reste un outil facultatif hors parcours.

Les notes de slides.md donnent un message, un fil oral, un appui visuel, une transition et des précisions pour les questions. Le minutage indicatif totalise 45 min 25 s de présentation puis 3 minutes de questions. Il dépasse donc le créneau de 40 minutes. Répéter et sélectionner les explications à raccourcir ; parler plus vite n’est pas une validation de durée.

## Fil narratif

- Le contrat public décrit les engagements envers le consommateur.
- Le paiement synthétique rend visibles la réservation, le clearing et l’absence de double effet.
- Le cas PHP établit la référence fonctionnelle.
- Le cas HTTP change le lieu et le format du calcul, tout en préservant les engagements publics. La consultation du marchand illustre un autre besoin, indépendant du paiement : choisir les champs publics avec un Provider et AutoMapper.
- Le cas natif détaille la génération, la compilation et l’appel de l’extension. Le gain potentiel de transport a un coût de build, de déploiement et d’isolation.
- Les benchmarks comparent un même périmètre et montrent leur dispersion. La conclusion revient au choix architectural, sans présenter le natif comme une étape obligatoire.

## Répéter

Sur un extrait, nommer l’entrée, l’appel et le résultat. Sur un schéma, suivre une seule trajectoire. Laisser une pause sur le contre-exemple du score 0,199. Distinguer le refus métier de l’impossibilité de décider.

Chronométrer avec les changements de fenêtre et les trois scripts de démonstration. Les repères ci-dessous proviennent des notes actuelles, pas d’une durée mesurée en salle. Pour tenir 40 minutes questions comprises, viser environ 37 minutes de présentation en réduisant les commentaires optionnels, sans supprimer les preuves annoncées.

## Avant la scène

Suivre [le plan des preuves](PROOF-PLAN.md). Préparer la stack et les images avant le talk. Ne pas lancer les benchmarks longs pendant la présentation. Les captures de secours doivent provenir d’une répétition récente et être annoncées comme enregistrées.

Présenter le laboratoire comme fictif, avec des données synthétiques. Il n’exécute pas de paiement bancaire réel. Le vocabulaire et les mécanismes du framework ne remplacent ni les tests métier ni les droits d’accès.

## Conducteur slide par slide

| Slide | Sujet | Repère |
| --- | --- | --- |
| 1 | Le hub sémantique<br>qu’on mérite. | 00:00–00:15 · 15 s |
| 2 | Matthieu Werner | 00:15–00:40 · 25 s |
| 3 | Notre API nous enferme-t-elle<br>dans une stack ? | 00:40–01:15 · 35 s |
| 4 | Qui doit s’adapter aux APIs internes ? | 01:15–02:00 · 45 s |
| 5 | Un client paie 420,69 € par carte | 02:00–02:45 · 45 s |
| 6 | … autres propriétés et réponses 400, 404 | 02:45–03:20 · 35 s |
| 7 | API Platform publie le sens.<br>Les moteurs exécutent le calcul. | 03:20–03:50 · 30 s |
| 8 | OpenAPI, JSON-LD et Hydra | 03:50–04:35 · 45 s |
| 9 | Une API, trois étapes d’évolution | 04:35–04:40 · 5 s |
| 10 | Le contrat<br>public | 04:40–04:45 · 5 s |
| 11 | Une demande répétée,<br>une seule réservation | 04:45–05:20 · 35 s |
| 12 | La réponse de notre autorisation | 05:20–06:00 · 40 s |
| 13 | Le contexte publié par notre API | 06:00–06:50 · 50 s |
| 14 | Un agent consomme aussi le contrat public | 06:50–07:40 · 50 s |
| 15 | Une API commune aux trois cas | 07:40–08:15 · 35 s |
| 16 | Nous déplaçons le calcul du risque.<br>Pas toute l’autorisation. | 08:15–09:00 · 45 s |
| 17 | Vérifier les engagements de notre API | 09:00–09:45 · 45 s |
| 18 | Le contrat devient des assertions | 09:45–10:30 · 45 s |
| 19 | PHP<br>local | 10:30–10:45 · 15 s |
| 20 | Le risque s’exécute en PHP local | 10:45–11:30 · 45 s |
| 21 | Le risque est une étape de l’autorisation | 11:30–12:00 · 30 s |
| 22 | Zoom sur l’appel qui évalue le risque | 12:00–12:35 · 35 s |
| 23 | Du risque à la réservation | 12:35–13:20 · 45 s |
| 24 | Rejouer la demande ou relire l’autorisation ? | 13:20–13:55 · 35 s |
| 25 | « Non » n’est pas « je ne sais pas ». | 13:55–14:45 · 50 s |
| 26 | Avant la démo : un paiement, un seul débit | 14:45–15:05 · 20 s |
| 27 | DÉMO 01 | 15:05–17:05 · 120 s |
| 28 | Bilan : notre référence fonctionnelle | 17:05–17:30 · 25 s |
| 29 | Ce que mesurent les chiffres des bilans | 17:30–18:05 · 35 s |
| 30 | PHP local : notre référence de latence | 18:05–18:30 · 25 s |
| 31 | Le service<br>PHP, puis Go | 18:30–18:40 · 10 s |
| 32 | L’équipe risque veut faire évoluer son service | 18:40–19:10 · 30 s |
| 33 | Le risque devient un service HTTP | 19:10–19:50 · 40 s |
| 34 | AssessResponse.properties · champs sélectionnés | 19:50–20:35 · 45 s |
| 35 | Le client PHP généré par Jane | 20:35–21:20 · 45 s |
| 36 | Zoom sur l’appel HTTP du risque | 21:20–21:35 · 15 s |
| 37 | Du client généré au résultat métier | 21:35–22:20 · 45 s |
| 38 | Le service Go évolue, le client garde ses repères | 22:20–23:20 · 60 s |
| 39 | 0,199 devient-il 19 ou 20 ? | 23:20–24:10 · 50 s |
| 40 | Même résultat ne veut pas dire résultat correct | 24:10–24:35 · 25 s |
| 41 | Consulter le profil d’un marchand | 24:35–25:25 · 50 s |
| 42 | Quelles informations publier ? | 25:25–26:10 · 45 s |
| 43 | Deux adaptations, des règles explicites | 26:10–26:40 · 30 s |
| 44 | Avant la démo : nos trois vérifications | 26:40–27:05 · 25 s |
| 45 | DÉMO 02 | 27:05–30:05 · 180 s |
| 46 | Bilan : le service change, le cycle reste | 30:05–30:30 · 25 s |
| 47 | Le service achète de l’autonomie | 30:30–31:15 · 45 s |
| 48 | Les responsabilités de notre façade métier | 31:15–32:15 · 60 s |
| 49 | Et si le contrat privé était gRPC ? | 32:15–33:00 · 45 s |
| 50 | Go dans<br>FrankenPHP | 33:00–33:15 · 15 s |
| 51 | Le calcul Go rejoint le processus PHP | 33:15–33:50 · 35 s |
| 52 | La bibliothèque Go reste la même | 33:50–34:30 · 40 s |
| 53 | Zoom sur l’appel natif du risque | 34:30–35:05 · 35 s |
| 54 | De la fonction PHP à la bibliothèque Go | 35:05–36:05 · 60 s |
| 55 | Dans go/ext, pendant le build | 36:05–37:05 · 60 s |
| 56 | Intégrer l’extension au binaire FrankenPHP | 37:05–38:05 · 60 s |
| 57 | Vérifier l’extension avant de démarrer l’API | 38:05–38:35 · 30 s |
| 58 | Ce que l’appel local économise | 38:35–39:10 · 35 s |
| 59 | Avant la démo : vérifier le chemin natif | 39:10–39:35 · 25 s |
| 60 | DÉMO 03 | 39:35–41:35 · 120 s |
| 61 | Bilan : Go tourne dans notre processus | 41:35–41:50 · 15 s |
| 62 | Le tableau des trois approches | 41:50–42:05 · 15 s |
| 63 | Faire varier le travail du moteur | 42:05–42:40 · 35 s |
| 64 | Quand le calcul devient plus lourd | 42:40–43:25 · 45 s |
| 65 | Quel compromis pour notre équipe ? | 43:25–44:25 · 60 s |
| 66 | Notre contrat public reste le point de repère | 44:25–45:25 · 60 s |
| 67 | Merci ! | 45:25–48:25 · 180 s |
