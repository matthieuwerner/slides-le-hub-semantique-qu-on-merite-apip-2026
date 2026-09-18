---
theme: default
title: 'API Platform : le hub sémantique qu’on mérite'
titleTemplate: '%s — API Platform Conference 2026'
author: Matthieu Werner
info: |
  Révision éditoriale : le contrat public et sa signification restent stables
  quand le calcul change de langage ou de transport.
  Trois cas exécutables, comparés sur des mesures locales identifiées.
  Voir ../docs/CONFERENCE-VALIDATION.md et ../docs/results.md.
  Budget oral indicatif : 45 min 25 s + 3 minutes de questions, à valider en répétition.
class: cover treezor-cover
transition: fade
colorSchema: dark
aspectRatio: 16/9
canvasWidth: 980
drawings:
  persist: false
fonts:
  provider: none
  sans: Helvetica Neue
  mono: Menlo
---

<div class="eyebrow">API Platform Conference · 18 septembre 2026</div>

# Le hub sémantique<br>qu’on mérite.

<div class="subtitle">Et comment piloter du Go avec.</div>

<div class="callout">Changer le moteur.<br><strong>Pas le sens de l’API.</strong></div>

<div class="signature">Matthieu Werner <span class="small">· Card Payments @ Treezor</span></div>

<!--
**Slide 1 · 00:00–00:15 · 15 s**

### Le message de cette slide

Poser la question du talk sans expliquer déjà toute la solution.

### À dire

Bonjour et bienvenue. Aujourd’hui, nous allons voir comment garder les outils que nous aimons dans API Platform, même quand une partie de notre application passe en Go. Notre fil conducteur sera simple : changer le moteur sans changer ce que notre API promet à ses consommateurs.

### Appui visuel / conduite

Laisser le titre en fond pendant l’installation du public. Commencer en regardant la salle.

### Transition vers la suite

Quelques mots sur le point de vue depuis lequel je vous parle.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Un contrat comprend la forme des messages et les attentes de comportement. La sémantique est la signification des données et des opérations. La stabilité promise concerne une migration d’implémentation à politique métier constante. Une évolution volontaire du modèle de risque peut changer les résultats : elle demande une gouvernance distincte.
-->

---
---


# Matthieu Werner

<div class="speaker-grid">
<div>
<p class="sub"><strong>Développeur chez Treezor</strong><br>Équipe Card, systèmes de paiement par carte</p>
<p>Former CTO</p>
<div class="callout">Mon fil conducteur :<br><strong>des systèmes robustes<br>qui peuvent évoluer.</strong></div>
</div>
<a class="speaker-contact" href="https://www.linkedin.com/in/matthieu-werner-2427a5281/" target="_blank" rel="noopener noreferrer" aria-label="Profil LinkedIn de Matthieu Werner">
<img src="/qr-linkedin.svg" width="190" height="190" alt="QR code vers le profil LinkedIn de Matthieu Werner">
<span>LinkedIn</span>
</a>
</div>

<p class="small">Architectures distribuées, haute disponibilité, performance mesurée.<br>Le laboratoire présenté est fictif, indépendant des systèmes de Treezor.</p>

<!--
**Slide 2 · 00:15–00:40 · 25 s**

### Le message de cette slide

Situer ton expérience et distinguer le laboratoire de la production.

### À dire

Je suis Matthieu Werner, développeur chez Treezor dans l’équipe Card. Je travaille sur des systèmes de paiement par carte, avec des questions de disponibilité, de robustesse et de performance. J’ai aussi un parcours de CTO. Le paiement nous servira d’exemple, mais le laboratoire présenté est fictif et indépendant des systèmes de Treezor.

### Appui visuel / conduite

Pointer l’équipe Card, puis la mention du laboratoire fictif. Ne pas réciter le CV.

### Transition vers la suite

La question qui m’intéresse vient d’abord de mon quotidien de développeur PHP.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Intention

Situer le point de vue, sans réciter un CV ni faire de l’employeur un argument d’autorité. Le contexte monétique explique l’attention aux engagements du contrat et aux défaillances. Le parcours de CTO explique l’attention aux choix techniques et à leurs conséquences pour les équipes.


### Source de la présentation

Biographie fournie dans PITCH.md. Le speaker présente ici son poste chez Treezor et son parcours de CTO.

Profil LinkedIn fourni par le speaker : https://www.linkedin.com/in/matthieu-werner-2427a5281/
Logo Treezor : variantes officielles du site https://www.treezor.com/fr/societe/ (en-tête et pied de page). Cette signature indique l’employeur du speaker, sans attribuer le laboratoire fictif à Treezor.
-->

---
class: light
---


# Notre API nous enferme-t-elle<br>dans une stack ?

<p><strong>Les équipes se diversifient.</strong> Leurs outils aussi.</p>

<p><strong>Le projet évolue.</strong> Certains besoins deviennent spécialisés.</p>

<p><strong>Un composant doit changer.</strong> Le reste doit continuer à fonctionner.</p>

<div class="callout">Peut-on faire évoluer la stack<br><strong>sans casser le contrat public ?</strong></div>


<!--
**Slide 3 · 00:40–01:15 · 35 s**

### Le message de cette slide

Faire comprendre le besoin de conserver l’investissement PHP.

### À dire

Je suis développeur PHP et j’apprécie les outils de cet écosystème. Mais une autre équipe peut choisir Go pour son composant, ou avoir besoin de le livrer séparément. Est-ce que je dois abandonner API Platform pour autant ? HTTP permet déjà de faire communiquer les langages. La vraie difficulté, c’est de conserver ce que les clients attendent quand l’intérieur change. C’est cette difficulté que nous allons explorer.

### Appui visuel / conduite

S’appuyer sur le composant qui doit changer, plutôt que lire les trois lignes.

### Transition vers la suite

Prenons plusieurs services internes : qui doit s’adapter à leurs différences ?

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

L’enfermement technologique désigne ici un couplage qui rend coûteux le remplacement ou l’intégration d’un composant. Il ne se limite pas au verrouillage commercial d’un fournisseur. Le sujet est la marge de manœuvre des équipes lorsque les besoins évoluent.

Cette situation est le problème d’architecture étudié dans le laboratoire, pas le récit d’une migration réelle chez Treezor. Elle ne suppose pas qu’une stack homogène soit un mauvais choix : la simplicité d’une seule technologie peut rester le meilleur compromis.


### Ce que le talk propose réellement

Nous conservons l’investissement PHP/API Platform en frontal. Les moteurs ou sources de données peuvent utiliser d’autres technologies derrière une interface publique choisie. Nous déplaçons et explicitons certaines dépendances, nous ne supprimons pas tout couplage.

Les trois cas vont comparer la décision locale en PHP, l’intégration d’un service autonome, puis le calcul natif spécialisé. Nous mesurerons la latence et discuterons ownership, livraison, diagnostic et contraintes de production. Il ne s’agit pas de multiplier les langages pour le plaisir. Un changement de technologie doit répondre à un besoin concret : calcul spécialisé, autonomie de livraison ou contrainte d’exploitation, plutôt qu’à une mode.




Préserver le contrat ne signifie pas figer l’API pour toujours. Elle peut évoluer en maintenant la compatibilité avec les usages documentés. Une rupture nécessaire se prépare et s’accompagne avec les consommateurs. Ici, nous cherchons à éviter qu’un changement d’implémentation interne leur impose, à lui seul, une migration.
-->


---
---

# Qui doit s’adapter aux APIs internes ?

<FacadeComparison />

<p class="sub"><strong>Un contrat métier pour les appels concernés.</strong><br>Nous définissons les conventions et les traductions ; API Platform les expose.</p>

<!--
**Slide 4 · 01:15–02:00 · 45 s**

### Le message de cette slide

Montrer où se situe le travail d’adaptation.

### À dire

À gauche, notre consommateur parle directement à plusieurs services. Chacun peut avoir ses noms de champs, ses unités ou ses erreurs. À droite, nous lui proposons une API métier commune, construite avec API Platform. Nos adaptateurs prennent en charge les différences internes. Les équipes derrière restent responsables de leurs contrats privés. Nous déplaçons le travail d’adaptation, nous ne le supprimons pas. Et nous ne faisons pas passer tous les échanges de l’entreprise dans cette façade.

### Appui visuel / conduite

Comparer gauche et droite en suivant un consommateur, sans énumérer tous les domaines.

### Transition vers la suite

Pour voir ce que cela signifie concrètement, suivons un paiement par carte.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Ce dessin compare deux architectures possibles, pas cinq services réellement déployés dans notre laboratoire. Plusieurs APIs ne sont pas nécessairement mal conçues : leur consommation directe convient parfois très bien. La façade apporte un point commun de contrat et de politique métier, mais aussi un composant à exploiter, du couplage et parfois un saut réseau. API Platform ne découvre ni ne normalise automatiquement les règles de cinq services. Nous définissons ressources, opérations, traductions et tests. Une gateway peut router ou authentifier ; la cohérence métier montrée ici vient de notre code. Le contrat facilite le remplacement d’une implémentation, sans garantir à lui seul la compatibilité comportementale.

Le schéma couvre les appels utiles à cette API métier, pas tous les échanges du système. Dans les démonstrations, nous gardons le cycle carte en PHP et ne déplaçons que le calcul du risque. Ne pas laisser penser que la démo installe cinq microservices ou que centraliser tous les flux serait une recommandation générale.
-->

---
---

<div class="eyebrow">Le scénario · un paiement par carte</div>

# Un client paie 420,69 € par carte

<CardTransactionDiagram />

<p><strong>Autoriser</strong> réserve le disponible. Dans ce labo, le <strong>clearing</strong> comptabilise le débit local.</p>
<p class="small">Comptes synthétiques persistés. Circuit terminal simplifié, aucun réseau bancaire.</p>

<!--
**Slide 5 · 02:00–02:45 · 45 s**

### Le message de cette slide

Installer le paiement qui servira de référence.

### À dire

Un client paie quatre cent vingt euros et soixante-neuf centimes. Dans notre scénario simplifié, la demande arrive directement à notre API. Elle évalue le risque, contrôle la carte et les fonds, puis accepte ou refuse. Si elle accepte, elle réserve le montant disponible. Plus tard, notre opération appelée clearing comptabilise le débit local. Nous ne reproduisons pas un réseau bancaire : nous voulons observer des effets précis sur un compte synthétique.

### Appui visuel / conduite

Suivre le terminal jusqu’à l’API, puis la décision. Garder les détails des soldes pour la démo.

### Transition vers la suite

Qu’est-ce que le consommateur doit pouvoir attendre de cette API ?

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Le terminal symbolise l’origine de la demande : nous ne reproduisons ni acquéreur, ni réseau carte, ni message ISO 8583. Le compte démarre à 1 000 EUR. Après autorisation : comptabilisé 1 000, réservé 420,69, disponible 579,31. Après clearing intégral : comptabilisé 579,31, réservé 0, disponible 579,31. Le risque reste synthétique. Les effets locaux, eux, sont persistés dans MongoDB. Risque puis contrôles locaux est l’ordre de notre service, pas une prescription de traitement bancaire. Aucun journal interbancaire, FX, remboursement ou capture partielle.

### Vocabulaire paiement

Clearing se traduit généralement par compensation. Settlement signifie règlement : ce sont deux notions distinctes. Notre opération de clearing simule la comptabilisation locale après autorisation, pas une compensation ni un règlement interbancaire complet. La réservation est un effet côté compte, pas une somme envoyée au terminal. Dire « sans réseau bancaire simulé », plutôt que « sans réseau » : les appels HTTP existent bien dans le laboratoire.

**Source :** [Glossaire des paiements, BRI](https://www.bis.org/publ/cpss70fr.pdf).
-->

---
class: light public-contract
---

<div class="eyebrow">Le contrat public · POST /api/payment-authorizations</div>

# Qui possède le contrat de votre API ?

<div class="split">
<div class="compact">
<div class="label">Entrée · une demande identifiée, un montant explicite</div>

```yaml
post:
  operationId: authorize
  requestBody:
    content:
      application/ld+json:
        schema:
          properties:
            requestId:
              description: Clé de rejeu par compte
              type: string
            amount:
              type: integer
              description: Unités mineures
              example: 42069
            currency: {type: string, example: EUR}
            # … compte, carte, marchand, etc.
```

</div>
<div>
<div class="label">post.responses · décision métier et erreurs distinctes</div>

```yaml
'200':
  description: Décision rendue, même si refusée
  content:
    application/ld+json:
      schema:
        properties:
          status:
            enum: [approved, declined, challenged]
          riskScore:
            type: integer
            description: Score 0–100
            example: 12
'409': {description: Conflit}
'422': {description: Données invalides}
'503': {description: Indisponibilité technique}
# … autres propriétés et réponses 400, 404
```
</div>
</div>


<p class="small">Extraits abrégés, schémas dépliés et descriptions traduites. En EUR, 42069 = 420,69 €.</p>
<p class="sub">Si le moteur change, <strong>le contrat client doit-il changer ?</strong></p>

<!--
**Slide 6 · 02:45–03:20 · 35 s**

### Le message de cette slide

Définir le contrat avec des attentes concrètes.

### À dire

Ce POST est l’opération que nous recevons, pas un appel sortant de notre application. Le contrat décrit les données attendues et les réponses possibles. Il dit aussi comment les interpréter : un montant a une unité, un score a une échelle. Une décision de refus et une panne ne racontent pas la même chose. Si nous remplaçons le moteur, le client doit pouvoir garder ces repères. C’est à nous de choisir et de maintenir ce contrat public.

### Appui visuel / conduite

Ne pas lire tout le YAML. Pointer à gauche requestId, puis amount : 42069 signifie 420,69 euros dans notre exemple. À droite, pointer status et riskScore : une décision métier et son évaluation ne sont pas un code HTTP. Terminer par 409 et 503 : conflit et indisponibilité doivent rester distinguables. Les schémas sont dépliés et abrégés pour montrer leurs propriétés, les descriptions sont traduites.

### Transition vers la suite

Voici comment nous répartissons les responsabilités pour le faire.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

OpenAPI décrit une interface HTTP : opérations, schémas, réponses et descriptions. Les deux fragments montrent la demande et les réponses de la même opération. Les propriétés des schémas référencés sont dépliées, et les descriptions traduites et raccourcies pour la présentation : ce n’est pas un export intégral à copier. Les listes de champs requis, les contraintes de format, les autres propriétés et les réponses 400/404 sont omises. Le minimum 0 du montant correspond à la validation réelle ; zéro passe la validation de forme mais entraîne un refus métier. L’échelle 0–100 du score est documentée, sans inventer de bornes de schéma que notre classe de sortie ne déclare pas.

Les unités et l’échelle figurent dans les descriptions des propriétés, pas dans les réponses HTTP affichées. Un statut 200 signifie ici que l’évaluation a abouti, y compris lorsque son résultat métier est declined. Une violation de contrainte donne 422. Une erreur technique d’accès au moteur donne 503. Ces choix appartiennent à cette API, pas à une règle universelle sur tous les POST. L’export déclare les réponses attendues des commandes, notamment 404, 409 et 503. OpenApiContractTest vérifie leur présence et conserve le schéma de succès généré. L’extrait affiché reste une sélection.


### Le sens dépasse le schéma

Une description OpenAPI peut exprimer les engagements, mais elle ne les fait pas respecter toute seule. Notre code et les tests portent les invariants. JSON-LD apporte l’identité des termes et les liens, Hydra décrit notamment les ressources et opérations dans son vocabulaire. Nous détaillons leurs rôles plus loin : ne pas faire dès maintenant un cours sur les formats.


### Enjeu d’architecture et d’équipe

Posséder le contrat signifie décider de son vocabulaire public, de ses comportements et de sa politique d’évolution. Le hub regroupe cette responsabilité et les adaptations nécessaires. Les équipes des services gardent la maîtrise de leurs modèles privés. L’abstraction ne rend pas invisible un changement réel de politique métier : celui-ci exige une décision explicite de compatibilité.

La différence 12 / 0,12 reviendra dans la démo HTTP comme test d’une conversion définie, pas comme justification générale du hub. Go et PHP savent tous deux exposer une API riche. Le choix porte sur la responsabilité du contrat et l’outillage de l’équipe.

**Source locale :** api/src/ApiResource/PaymentAuthorization.php et export courant /api/docs.jsonopenapi. La décision finale inclut maintenant les contrôles locaux carte/solde. Les anciennes captures de scoring seul ne représentent pas ce contrat enrichi.
-->

---
---


# API Platform publie le sens.<br>Les moteurs exécutent le calcul.

<HubDiagram />

<div class="callout">Le « hub » est une <strong>frontière de contrat</strong>.</div>

<!--
**Slide 7 · 03:20–03:50 · 30 s**

### Le message de cette slide

Répartir les responsabilités entre API Platform, notre code et le moteur.

### À dire

Regardons ce que nous mettons dans cette façade. API Platform expose nos ressources et leurs opérations, sérialise les réponses et publie leur description. Notre code appelle le moteur et traduit son résultat. Le moteur, lui, fournit l’évaluation du risque. Nous écrivons les règles de décision et de conversion : le framework ne les déduit pas des données reçues.

### Appui visuel / conduite

Pointer les responsabilités dans le rectangle central, puis le moteur. Le choix de placer une façade a déjà été expliqué sur le schéma des services. Ici, expliquer ce que chacun prend en charge, sans détailler Provider et Processor.

### Transition vers la suite

Que contiennent les descriptions publiées par API Platform ?

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Le hub sémantique est le nom donné dans ce talk à cette façade métier. Ce n’est pas un composant officiel nommé SemanticHub. Une façade présente une interface cohérente. Un adaptateur relie cette interface à une implémentation. En DDD, une couche anticorruption protège le vocabulaire d’un domaine contre celui d’un système externe.

Le rectangle API Platform inclut ici du code applicatif : ressources, Provider/Processor et traduction. API Platform ne déduit pas les conversions métier à notre place. Le dessin résume des responsabilités et ne prétend pas représenter l’ordre exact des listeners Symfony.


### Question probable

« Pourquoi ne pas le faire dans un contrôleur ? » C’est possible. API Platform est pertinent quand on veut capitaliser sur les ressources, formats et points d’extension. Il évite de réassembler cette chaîne nous-mêmes, il ne rend pas les autres choix illégitimes.
-->

---
---

# OpenAPI, JSON-LD et Hydra

| Standard | Rôle dans notre API |
| --- | --- |
| **OpenAPI** | Décrire les appels HTTP : opérations, entrées et réponses |
| **JSON-LD** | Relier les termes du JSON à un vocabulaire identifié |
| **Hydra** | Décrire les ressources, liens et opérations avec un vocabulaire hypermédia |

<p class="sub">API Platform publie ces descriptions.<br>Notre code applique les règles métier et nos tests vérifient les scénarios attendus.</p>

<!--
**Slide 8 · 03:50–04:35 · 45 s**

### Le message de cette slide

Donner un exemple mental pour chaque standard.

### À dire

Le YAML que nous venons de voir est un extrait OpenAPI : il décrit l’appel et les réponses possibles. JSON-LD permet d’identifier le vocabulaire auquel appartiennent les termes de la réponse. Hydra fournit notamment des conventions pour les ressources, les opérations et les liens de pagination. Ces noms répondent à des besoins différents. Dans un instant, nous suivrons le contexte JSON-LD d’une réponse réelle.

### Appui visuel / conduite

Lire une ligne à la fois, puis laisser une courte pause. Ne pas ouvrir une parenthèse de normalisation.

### Transition vers la suite

Nous allons garder ce contrat comme repère dans trois situations.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

OpenAPI est une spécification de description des API HTTP, indépendante du langage d’implémentation. Son document sert notamment à la documentation et à la génération de clients. Dans notre laboratoire, Jane génère le client PHP à partir du contrat OpenAPI privé du moteur. L’export public d’API Platform et ce contrat privé sont deux documents distincts.

JSON-LD signifie JSON for Linked Data. Le contexte associe notamment des noms courts à des identifiants de vocabulaire, les IRI. Une IRI est un identifiant internationalisé, plus général qu’une URI. @id identifie une ressource et @type son type. Le contexte ne définit pas automatiquement ce que veut dire un score de 12, son échelle ou sa politique de calcul. Nous verrons un exemple concret plus loin.

Hydra est un vocabulaire de description des API hypermédias, utilisable avec JSON-LD. Hypermédia signifie que des liens et des descriptions d’opérations rendent des possibilités de navigation et d’interaction découvrables par un client capable de les interpréter. Exemples : membres d’une collection, pagination, méthode d’une opération, types attendus et retournés. Ce n’est ni un transport interlangage, ni un exécuteur de workflow, ni une garantie qu’un client générique saura autoriser un paiement.

OpenAPI peut aussi porter des descriptions métier et des liens. La distinction présentée est un repère sur leurs rôles, pas une séparation absolue de leurs capacités. Ne pas présumer que les exports Hydra et OpenAPI expriment toutes les contraintes à l’identique : vérifier les documents de la version utilisée.


### Ce qui garantit le comportement

Nos services et nos tests font respecter les engagements publiés. Aucun de ces standards ne rend automatiquement les implémentations PHP et Go équivalentes. API Platform n’invente pas le vocabulaire métier à notre place.

**Sources :** [OpenAPI dans API Platform](https://api-platform.com/docs/core/openapi/), [JSON-LD 1.1](https://www.w3.org/TR/json-ld11/), [Hydra Core Vocabulary](https://www.hydra-cg.com/spec/latest/core/).
-->

---
---


# Une API, trois étapes d’évolution

<div class="steps">
<div class="step"><strong>1. PHP local</strong><p>Le cycle carte et le contrat public de référence.</p></div>
<div class="step"><strong>2. Service distant</strong><p>Extraire le PHP via HTTP, puis remplacer ce service par Go.</p></div>
<div class="step"><strong>3. Go embarqué</strong><p>Appeler la même bibliothèque Go dans FrankenPHP.</p></div>
</div>
<p class="sub">API Platform conserve les ressources et les comportements publics.</p>

<!--
**Slide 9 · 04:35–04:40 · 5 s**

### Le message de cette slide

Annoncer les trois cas, pas les quatre configurations en détail.

### À dire

D’abord, le PHP local. Ensuite, le calcul dans un service HTTP. Enfin, la même bibliothèque Go embarquée dans FrankenPHP.

### Appui visuel / conduite

Pointer les trois colonnes sans développer les outils.

### Transition vers la suite

Avant les démos, précisons ce que nous voulons préserver.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Trois actes, quatre configurations exécutables : php, php-http, go-http, go-native. Le détour PHP HTTP isole le coût organisationnel et technique du réseau du changement de langage. Le Provider marchand et le consommateur préparé restent dans le même cas métier.
-->

---
class: chapter chapter-hub
---


# Le contrat<br>public

<p class="chapter-sub">Un vocabulaire métier porté par API Platform</p>

<!--
**Slide 10 · 04:40–04:45 · 5 s**

### Le message de cette slide

Ouvrir la partie contrat.

### À dire

Commençons par une promesse très concrète : répéter une demande ne doit pas réserver deux fois.

### Appui visuel / conduite

Pause de chapitre. Une phrase suffit.

### Transition vers la suite

Voici comment nous la décrivons.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

Aucun complément nécessaire pour cet intercalaire.
-->



---
---

# Une demande répétée,<br>une seule réservation

<div class="compact">

```yaml
requestId:
  type: string
  example: authorization_001
  description: >-
    Idempotency key scoped to this account and operation.
    Same payload replays the original decision;
    a changed payload returns 409.
```

</div>

<p>Même compte, même clé, même demande : <strong>la réponse initiale, sans nouvelle réservation.</strong><br>Même clé, demande modifiée : <strong>conflit 409.</strong></p>
<p class="sub">Un engagement décrit dans OpenAPI et appliqué par notre code.</p>

<!--
**Slide 11 · 04:45–05:20 · 35 s**

### Le message de cette slide

Expliquer l’idempotence par le problème du client.

### À dire

Le client envoie sa demande, mais ne reçoit pas la réponse. Il ne sait pas si elle a été traitée. Nous lui permettons de la renvoyer avec la même clé. Pour le même compte et le même contenu, nous rendons la réponse initiale sans nouvelle réservation. S’il réutilise cette clé avec un autre montant, nous signalons un conflit. La description affichée précise ces deux cas : une nouvelle tentative et une demande différente ne doivent pas produire le même effet.

### Appui visuel / conduite

Pointer la clé puis la phrase sur le contenu identique. Ne pas lire la description anglaise mot à mot.

### Transition vers la suite

Voyons maintenant une réponse obtenue dans le laboratoire.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Le fragment provient de components.schemas["PaymentAuthorization.AuthorizationRequest"].properties.requestId dans GET /api/docs.jsonopenapi. Le fragment de l’export JSON est présenté en YAML pour répartir la description sur plusieurs lignes. Le texte anglais et les valeurs sont ceux de l’export vérifié pendant la répétition ; default est omis. La clé est limitée au compte et à l’opération. Notre code contrôle aussi le contenu de la commande. Un rejeu du POST rend le résultat initial, même après clearing ; le GET de relecture rend l’état courant.

Une description textuelle n’est pas une contrainte que Jane ou OpenAPI exécutent automatiquement. BankService et les tests du cycle portent la déduplication. Le schéma réel contient aussi les montants et les devises ; cette slide choisit une promesse comportementale plutôt que d’énumérer des types primitifs.


### Limite à connaître

L’export déclare 409 et 503 dans responses, en complément du succès et des autres erreurs attendues. OpenApiContractTest vérifie cette déclaration. Les assertions du cycle vérifient séparément le comportement de rejeu et de conflit : la publication et l’exécution sont deux contrôles complémentaires.

**Source :** export local GET /api/docs.jsonopenapi, vérifié le 16 septembre 2026 ; api/src/ApiResource/AuthorizationRequest.php ; api/src/Application/Bank/BankService.php.
-->

---
---

# La réponse de notre autorisation

<div class="compact">

```json
{
  "@context": "/api/contexts/PaymentAuthorization",
  "@type": "PaymentAuthorization",
  "state": "authorized",
  "status": "approved",
  "authorizationReason": "APPROVED",
  "amount": 42069,
  "currency": "EUR"
}
```

</div>

<p class="sub">Une décision métier et un contexte JSON-LD consultable.</p>

<!--
**Slide 12 · 05:20–06:00 · 40 s**

### Le message de cette slide

Relier les champs visibles à l’opération métier.

### À dire

Voici une partie de la réponse réelle. Le montant est exprimé en centimes, avec la devise séparée. Le statut indique la décision, et l’état indique où en est l’autorisation dans son cycle. Ici, elle est autorisée. Après comptabilisation, son état courant pourra changer. Le contexte est une adresse consultable qui décrit les identifiants de nos termes. Ce n’est donc pas seulement un JSON dont il faudrait deviner le sens à partir des noms.

### Appui visuel / conduite

Pointer amount et currency, puis state et @context. Ce fragment ne contient pas toute la réponse.

### Transition vers la suite

Suivons cette adresse du contexte.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Le fragment conserve les valeurs réelles de benchmark/results/cycle-consumer-1789565807358/004.json, body. Les autres champs, notamment @id, les identifiants de compte, le score et son explication, sont omis pour la lecture. Ce n’est pas une réponse complète. Le contexte est réellement servi par /api/contexts/PaymentAuthorization.

status conserve le verdict initial. state peut devenir cleared après comptabilisation locale. Le POST rejoué conserve la réponse initiale ; le GET de la ressource expose son état courant. On préserve les règles de changement d’état, pas une valeur figée pour toujours.

La capture comporte aussi un en-tête Link qui désigne /api/docs.jsonld comme documentation Hydra. Cet en-tête permet de découvrir la description d’API ; il ne fait pas l’appel au moteur. Ne pas inventer une collection paginée sur cet endpoint d’autorisation.

**Source :** benchmark/results/cycle-consumer-1789565807358/004.json ; api/src/ApiResource/PaymentAuthorization.php.
-->

---
---

# Le contexte publié par notre API

<div class="compact">

```json
{
  "@context": {
    "@vocab": "http://localhost:8099/api/docs.jsonld#",
    "hydra": "http://www.w3.org/ns/hydra/core#",
    "status": "PaymentAuthorization/status",
    "authorizationReason": "PaymentAuthorization/authorizationReason"
  }
}
```

</div>

<p>Les termes renvoient au <strong>vocabulaire de notre API.</strong></p>
<p class="sub">Le contexte identifie les termes.<br>Le code applique leurs règles et les tests vérifient les scénarios.</p>

<!--
**Slide 13 · 06:00–06:50 · 50 s**

### Le message de cette slide

Montrer ce que JSON-LD apporte réellement, sans lui attribuer de magie.

### À dire

Nous avons suivi l’adresse du contexte présente dans la réponse. Ici, status désigne précisément le statut de PaymentAuthorization. Un service marchand pourrait aussi appeler un champ status, avec une autre signification. Le contexte permet de distinguer ces termes en leur donnant des identifiants. Le vocabulaire montré appartient à notre API : il ne constitue pas un standard bancaire partagé avec les autres services.

### Appui visuel / conduite

Pointer status et la base @vocab. Ne pas lire l’URL localhost.

### Transition vers la suite

Ces précisions comptent pour tous nos consommateurs, y compris un outil utilisé par une IA.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Le fragment est extrait de GET /api/contexts/PaymentAuthorization lu pendant la répétition. @vocab donne la base utilisée pour les identifiants de vocabulaire relatifs. status est associé à PaymentAuthorization/status dans cette base. Le préfixe hydra référence le vocabulaire Hydra. Les autres propriétés sont omises, aucune correspondance externe n’a été inventée.

Un consommateur peut consulter /api/docs.jsonld, documentation Hydra également annoncée par le Link de la réponse, pour explorer les descriptions publiées. JSON-LD et Hydra ne convertissent pas automatiquement une unité et n’exécutent pas la politique métier. L’hôte localhost appartient au laboratoire ; une publication réelle doit choisir et maintenir les identifiants publics. La présence de ce contexte ne prouve ni un vocabulaire bancaire partagé ni une interopérabilité automatique avec tous les services.


### Statut des spécifications

JSON-LD 1.1 est une recommandation W3C. Hydra est une spécification communautaire, pas une recommandation W3C. La page du groupe annonce sa fermeture le 21 mai 2026 ; cela ne suffit pas à établir l’abandon de toutes les implémentations, mais nous ne présentons pas une gouvernance active comme acquise.

**Sources :** export local GET /api/contexts/PaymentAuthorization, vérifié le 16 septembre 2026 ; [JSON-LD 1.1](https://www.w3.org/TR/json-ld11/) ; [Hydra Community Group](https://www.w3.org/community/hydra/).
-->

---
---

<div class="eyebrow">Consommation de l’API par un agent</div>

# Un agent consomme aussi le contrat public

<div class="flow"><div>Demande<br><span class="small">Autoriser 420,69 €</span></div><span>→</span><div>Outil client<br><span class="small">42069 + EUR</span></div><span>→</span><div>API Platform<br><span class="small">La même autorisation</span></div></div>

<p>Le contrat décrit <strong>les arguments, la décision et ses effets</strong>, quel que soit le moteur.</p>
<p class="sub">Le client doit distinguer autorisation, réservation et débit.<br>Les droits et les contrôles restent côté serveur.</p>
<p class="small">MCP peut exposer ces actions comme outils à un client IA compatible.<br>Notre exemple utilise un consommateur préparé, sans LLM ni mesure de fiabilité.</p>

<!--
**Slide 14 · 06:50–07:40 · 50 s**

### Le message de cette slide

Rattacher l’IA au contrat, sans annoncer une quatrième démo.

### À dire

Imaginons qu’un agent prépare la demande à la place d’un écran classique. Son outil doit transmettre le montant dans la bonne unité, puis distinguer une réservation d’un débit. C’est le même contrat public qui lui fournit ces repères. Une description claire aide à construire l’appel, mais elle ne donne aucun droit : le serveur garde ses contrôles. Dans notre labo, le consommateur est préparé, sans modèle d’IA. Nous n’en tirons donc aucune preuve de fiabilité d’un agent.

### Appui visuel / conduite

Suivre la demande en euros jusqu’aux arguments de l’outil. Ne pas développer MCP.

### Transition vers la suite

Revenons aux opérations concrètes que tous ces consommateurs utilisent.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Cette illustration complète les représentations du contrat public : elle présente un autre consommateur avant de comparer les moteurs. Aucun quatrième passage en démo n’est prévu. Le script demo-consumer reste disponible pour explorer le dépôt. Il ne lance aucun LLM, ne fait pas le clearing et ne mesure ni hallucinations ni fiabilité d’un agent. JSON-LD et Hydra apportent une valeur supplémentaire seulement si le consommateur sait les exploiter. Une description ne confère aucun droit d’agir.


### Si la question porte sur MCP

MCP permet à un client compatible de découvrir et d’appeler des outils. L’intégration API Platform documentée est expérimentale et demande une configuration explicite des outils, de la validation et des droits. Elle n’est pas installée dans notre laboratoire. Une description claire aide le consommateur à construire son appel, mais ne prouve pas qu’un agent interprète correctement une opération financière.

Source : https://api-platform.com/docs/core/mcp/
-->

---
---

# Une API commune aux trois cas

| API publique | Responsabilité |
| --- | --- |
| <code>GET /users/{userId}</code> | Lire l’utilisateur et son compte |
| <code>GET /accounts/{accountId}/balance</code> | Comptabilisé, réservé, disponible |
| <code>POST /payment-authorizations</code> | Décider et réserver |
| <code>GET /payment-authorizations/{id}</code> | Relire l’état courant |
| <code>POST /clearings</code> | Comptabiliser, sans double débit |

<p class="small">Préfixe <code>/api</code>. Un compte EUR par utilisateur, clearing intégral uniquement.</p>

<!--
**Slide 15 · 07:40–08:15 · 35 s**

### Le message de cette slide

Présenter le terrain commun, sans recommencer chaque cas.

### À dire

Voici les appels que nous allons retrouver dans les scripts. Nous lisons d’abord l’utilisateur et le solde pour connaître le point de départ. Nous demandons une autorisation, puis nous pouvons relire son état. Enfin, le clearing comptabilise le débit. Les lectures du solde nous permettront d’observer les effets des commandes. Le GET du solde ne déplace aucun argent.

### Appui visuel / conduite

Regrouper les lignes : lire, autoriser, relire, comptabiliser. Ne pas réciter les URL.

### Transition vers la suite

La pièce que nous allons déplacer est beaucoup plus petite.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Le service BankService porte les cas d’usage. AccountRepository isole MongoDB. Aucun ORM ni ODM n’est nécessaire à cet exemple. Ce choix ne désavoue pas les intégrations Doctrine d’API Platform : un provider et un processor personnalisés conviennent à une source et à une opération particulières. Les URI exactes utilisent authorizationId, abrégé ici en id pour la lecture.
-->

---
class: light
---


# Nous déplaçons le calcul du risque.<br>Pas toute l’autorisation.

<div class="split">
<div>
<div class="label">Le moteur interchangeable</div>
<p>Évalue le risque de la transaction.</p>
<p>Retourne son évaluation et son explication.</p>
</div>
<div>
<div class="label">L’application API Platform</div>
<p>Combine le risque avec les contrôles de carte et de solde.</p>
<p>Décide, réserve si elle accepte et publie la réponse.</p>
</div>
</div>

<p class="sub">Un risque acceptable ne suffit pas : <strong>sans fonds disponibles, l’autorisation est refusée.</strong></p>

<!--
**Slide 16 · 08:15–09:00 · 45 s**

### Le message de cette slide

Délimiter le calcul déplaçable et la décision bancaire.

### À dire

À gauche, le moteur évalue le risque d’une transaction et renvoie son résultat. À droite, notre application prend la décision finale. Elle combine ce risque avec la carte et le solde, puis réserve si elle accepte. Un risque acceptable ne suffit pas si le compte n’a pas les fonds. Nous allons changer uniquement la manière d’obtenir le risque. La décision finale et les écritures du compte restent dans notre application PHP.

### Appui visuel / conduite

Comparer les deux colonnes avec l’exemple des fonds insuffisants.

### Transition vers la suite

Pour vérifier que ce changement ne casse rien, nous avons besoin de tests.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

API Platform ne fournit pas ces règles bancaires. Notre Processor délègue à BankService ; RiskExecution obtient l’évaluation du moteur et AuthorizationPolicy combine celle-ci avec l’état du compte. BankService persiste la décision et la réservation éventuelle. Dans le code, le calcul du risque précède la décision locale ; les deux colonnes présentent les responsabilités, pas deux processus parallèles.

Le moteur retourne un score, un statut de risque et une explication. Le statut public d’autorisation tient aussi compte des contrôles locaux. decisionReason expose l’explication du risque ; authorizationReason explique la décision d’autorisation, par exemple des fonds insuffisants. Ce ne sont pas deux noms pour le même résultat. Les seuils précis de notre politique synthétique ne sont pas nécessaires pour comprendre cette frontière.


### Périmètre de la comparaison

À données d’entrée, état initial du compte et politique identiques, changer l’implémentation doit conserver la décision et les effets attendus. Une évolution volontaire de politique métier est un autre changement, à traiter explicitement. Le clearing et la persistance restent dans l’application PHP pour toutes les configurations.

**Sources locales :** api/src/Application/Bank/BankService.php, api/src/Application/Bank/AuthorizationPolicy.php, api/src/Application/RiskExecution.php.
-->

---
class: authorization-code
---

# Vérifier les engagements de notre API

| Ce que nous promettons | Ce que nous vérifions dans le labo |
| --- | --- |
| Une représentation publique stable | Champs, statuts HTTP, contexte et exports |
| Un sens métier précis | Montant en centimes, décision et motifs attendus |
| Des effets maîtrisés | Une réservation, un débit, aucun doublon au rejeu |

<div class="flow"><div>Scénario connu</div><span>→</span><div>API publique</div><span>→</span><div>Réponse + état du compte</div></div>
<p class="small">La comparaison entre moteurs couvre la version actuelle.<br>La compatibilité avec une ancienne version exige aussi une référence publiée et conservée.</p>

<!--
**Slide 17 · 09:00–09:45 · 45 s**

### Le message de cette slide

Transformer les promesses en observations vérifiables.

### À dire

Pour notre paiement, qu’allons-nous observer ? La réponse doit annoncer la décision attendue et le montant de 420,69 euros. Mais une réponse correcte ne suffit pas : nous devons aussi lire le compte pour vérifier la réservation. Puis nous rejouons la demande et vérifions que cette réservation n’augmente pas. C’est ce lien entre réponse et effet sur le compte que résume le tableau.

### Appui visuel / conduite

Parcourir les trois lignes en reliant chacune à réponse ou état du compte.

### Transition vers la suite

Voici deux extraits pour voir à quoi ces tests ressemblent.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Outillage et portée
PHPUnit et ApiTestCase couvrent les réponses publiques et le cycle métier. Les tests fonctionnels PHP utilisent un dépôt de comptes en mémoire, pas MongoDB. make proof-public vérifie par HTTP les quatre configurations, les exports publics, le Provider et l’équivalence de la V2 privée. make proof-cycle vérifie séparément la persistance MongoDB et des commandes concurrentes. Ne pas confondre comparaison de moteurs et résultat métier correct : les attentes sont explicites.

La validation de schéma peut compléter ces tests. ApiTestCase fournit assertMatchesResourceItemJsonSchema ; le labo ne l’utilise pas actuellement, il fait des assertions ciblées. Un schéma généré depuis le code courant ne remplace pas une référence contractuelle versionnée : code et schéma pourraient dériver ensemble. Les réponses d’erreur attendues des commandes sont déclarées dans OpenAPI et un test vérifie leur présence avec celle des schémas de succès. Ce test porte sur la publication actuelle, pas sur un diff historique de versions.

Pour des consommateurs et fournisseurs livrés indépendamment, Pact permet des contrats d’interaction pilotés par les consommateurs. Il n’est pas installé ici et ne remplace pas les tests du cycle métier. Nous n’ajoutons pas cette infrastructure pour une présentation.

Sources : api/tests/Functional ; api/config/services_test.yaml ; go/cmd/compare/main.go ; [tests API Platform](https://api-platform.com/docs/symfony/testing/) ; [fonctionnement de Pact](https://docs.pact.io/getting_started/how_pact_works).
-->

---
class: authorization-code
---

# Le contrat devient des assertions

<div class="split"><div>
<div class="label">PaymentAuthorizationTest · ApiTestCase</div>

~~~php
// … POST du scénario de référence
self::assertResponseStatusCodeSame(200);
self::assertJsonContains([
    '@type' => 'PaymentAuthorization',
    'status' => 'approved',
    'authorizationReason' => 'APPROVED',
    'riskScore' => 12,
    'amount' => 42069,
    'currency' => 'EUR',
    // … autres engagements
]);
~~~

</div><div>
<div class="label">CardCycleTest · même demande rejouée</div>

~~~php
// … autorisation initiale et rejeu
self::assertSame($initial, $replay->toArray());
// … clearing, puis même clearing rejoué
self::assertSame($receipt, $again);
self::assertSame(57931,
    $this->balance($c)['booked']);
self::assertSame(0,
    $this->balance($c)['reserved']);
~~~

</div></div>
<p class="sub">On teste ce que le consommateur reçoit et ce que l’opération fait.</p>
<p class="small">Extraits réels abrégés · PHPUnit avec ApiTestCase · dépôt en mémoire pour ces tests.</p>

<!--
**Slide 18 · 09:45–10:30 · 45 s**

### Le message de cette slide

Donner un sens lisible aux assertions.

### À dire

Voici comment nous écrivons ces vérifications avec PHPUnit. À gauche, assertJsonContains recherche les champs et les valeurs attendus dans la réponse. À droite, assertSame compare la réponse initiale au rejeu. Les deux dernières assertions vérifient qu’après le clearing, le solde comptabilisé vaut 579,31 euros et que la réservation est à zéro. Ces tests utilisent un dépôt en mémoire. Les scripts des démos vérifient aussi le cycle avec MongoDB.

### Appui visuel / conduite

Lire status, amount à gauche, puis assertSame et reserved à droite. Laisser le temps de parcourir le code.

### Transition vers la suite

Avec cette référence, nous pouvons suivre le premier cas en PHP.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Limites de l’extrait
Les requêtes, fixtures et autres assertions sont omises pour lire les attentes. assertJsonContains vérifie un sous-ensemble ; il ne démontre pas à lui seul l’absence de tout champ supplémentaire. Des tests distincts vérifient certains champs interdits. Les tests HTTP du labo complètent ces tests fonctionnels avec les services réels et MongoDB. Le jeu de scénarios n’est pas une preuve exhaustive de conformité bancaire.

Sources : api/tests/Functional/PaymentAuthorizationTest.php ; api/tests/Functional/CardCycleTest.php. Commande locale : make test-php. Aucun framework de tests supplémentaire n’a été ajouté.
-->

---
class: chapter chapter-php
---

<div class="chapter-index">CAS 1 / 3</div>

# PHP<br>local

<p class="chapter-sub">Le calcul du risque dans notre application</p>

<!--
**Slide 19 · 10:30–10:45 · 15 s**

### Le message de cette slide

Introduire le cas local.

### À dire

Nous partons d’une application PHP qui fonctionne déjà. Regardons son chemin d’exécution avant de déplacer quoi que ce soit.

### Appui visuel / conduite

Laisser lire le titre, puis avancer vers le schéma.

### Transition vers la suite

Voici où le calcul s’exécute.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

Aucun complément nécessaire pour cet intercalaire.
-->

---
---

# Le risque s’exécute en PHP local

<ExecutionDiagram variant="local" />

<p class="sub"><strong>API Platform et Symfony, servis par FrankenPHP en mode worker.</strong><br>PhpRiskEngine::assess() : un appel local, sans HTTP interne.</p>

<!--
**Slide 20 · 10:45–11:30 · 45 s**

### Le message de cette slide

Situer le processus et le raccord Symfony.

### À dire

FrankenPHP sert déjà notre application Symfony et API Platform en mode worker. Une fois la demande préparée, API Platform appelle notre Processor, qui délègue à BankService. Celui-ci obtient le risque auprès d’un service Symfony, en appelant sa méthode PHP. Le résultat revient pour décider et réserver. Le calcul est local au processus, mais MongoDB reste une dépendance externe. Ce même dessin reviendra ensuite : on verra exactement quelle liaison change.

### Appui visuel / conduite

Suivre Processor, BankService puis le moteur. Montrer MongoDB séparément.

### Transition vers la suite

Ouvrons BankService pour situer le risque dans l’autorisation.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Détails à garder pour les questions

Symfony reste initialisé entre les requêtes. Le binaire contient l’extension dans les trois cas, mais seul go-native l’appelle. BankService passe par RiskExecution, qui sélectionne PhpRiskEngine et appelle assess(). Aucun exec ni nouvel appel HTTP pour ce calcul local.


### Le raccord API Platform, à expliquer si nécessaire

API Platform désérialise et valide la demande, puis appelle process() sur PaymentAuthorizationProcessor. Celui-ci délègue à BankService::authorize() et retourne le résultat à sérialiser. Le Processor reste visible dans le schéma, mais ne mérite pas un second détour dans le parcours. La sélection du moteur par en-tête est un dispositif réservé au laboratoire, désactivé par défaut, et non une recommandation d’API publique.
-->

---
class: php-walkthrough
---

# Le risque est une étape de l’autorisation

<AuthorizationZoom />

<p class="callout">Dans nos trois cas, nous changeons uniquement<br><strong>la façon d’obtenir l’évaluation du risque.</strong></p>

<p class="sub">La politique de décision et la réservation restent dans BankService.<br>La slide suivante ouvre le cadre vert, pas les deux autres étapes.</p>

<!--
**Slide 21 · 11:30–12:00 · 30 s**

### Le message de cette slide

Montrer le rôle de chaque bloc avant le zoom.

### À dire

Dans notre service, nous obtenons d’abord l’évaluation du risque. Notre politique la combine avec les contrôles locaux pour décider. Si la demande est acceptée, nous réservons et sauvegardons. Ce sont des responsabilités différentes. Le cadre vert indique celle qui nous intéresse pour la suite : obtenir le risque. Nous allons ouvrir ce cadre, sans déplacer la décision bancaire ni la sauvegarde.

### Appui visuel / conduite

Pointer obtenir le risque, puis décider et enregistrer. Revenir au cadre vert.

### Transition vers la suite

Voyons ce qui se passe dans cet appel du risque.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Cette slide situe le périmètre, sans extrait de code. Dans BankService, l’évaluation de risque alimente AuthorizationPolicy, qui contrôle carte, devise et fonds et choisit le motif de décision. BankService construit ensuite la réponse, augmente held seulement si state vaut authorized, mémorise la commande et sauvegarde le compte. Ne pas laisser entendre que la décision et le stockage sont implémentés comme le moteur : ils ont leurs propres responsabilités, conservées dans les trois cas.

Avant le parcours représenté, BankService charge le compte et recherche une commande déjà traitée. Un rejeu compatible retourne sa réponse sans recalculer le risque. Le parcours est dans une boucle de tentatives bornée. save utilise la version du compte pour ne pas écraser une réservation concurrente. Si la version a changé, le service recharge le compte et recontrôle la décision locale ; il conserve l’évaluation de risque déjà obtenue, d’où ??=. Aucun verrou MongoDB n’est conservé pendant l’appel de risque. Si les tentatives échouent, le service signale une indisponibilité.

RiskExecution est le collaborateur qui prépare l’entrée du moteur, choisit l’implémentation et mesure son exécution. AuthorizationPolicy prend la décision finale. AccountRepository est l’interface de stockage, implémentée ici avec MongoDB. Ces classes sont les nôtres : ce n’est pas une architecture bancaire générée par API Platform.

Ne pas dérouler toutes les subtilités de concurrence à l’oral. Elles servent à répondre aux questions sans présenter ce schéma de responsabilités comme une transaction complète.

**Sources locales :** api/src/Application/Bank/BankService.php ; api/src/Application/Bank/AuthorizationPolicy.php ; api/src/Domain/Bank/AccountRepository.php.
-->

---
class: php-walkthrough
---

# Zoom sur l’appel qui évalue le risque

<AuthorizationZoom detail />

<div class="compact">

~~~php
$selected = $this->engines->select($requested);
$input = $this->enricher->enrich($data);
$assessment = $selected->engine->assess($input, $data->profile);
// … instrumentation
return $assessment;
~~~

</div>



<!--
**Slide 22 · 12:00–12:35 · 35 s**

### Le message de cette slide

Expliquer appel et retour avec le code réel.

### À dire

Les trois lignes font trois choses simples. Nous sélectionnons le moteur, ici celui en PHP. Nous préparons ses données, par exemple les informations utiles sur la transaction. Puis nous appelons sa méthode assess. Elle renvoie un objet RiskAssessment, qui contient le résultat du calcul. Ce dernier nom ne désigne pas un autre traitement : c’est ce qui revient à l’appelant. La décision finale n’a pas encore été prise.

### Appui visuel / conduite

Pointer select, enrich et assess, puis la flèche de retour. RiskAssessment est une donnée.

### Transition vers la suite

Suivons le résultat jusque dans la décision et la réservation.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Les lignes proviennent de RiskExecution::assess, avec instrumentation et gestion des erreurs omises. La flèche pleine montre l’appel ; la flèche pointillée montre le chemin de retour de la même évaluation, du moteur à RiskExecution puis à BankService. RiskAssessment est un objet de données, ni un service ni une troisième étape de traitement. Le registre sélectionne ici PhpRiskEngine, qui implémente RiskEngine. La méthode assess reçoit un RiskInput et un RiskProfile, puis retourne un RiskAssessment. L’interface complète reste dans le dépôt, inutile de la réciter pour suivre ce chemin.

PhpRiskEngine vérifie d’abord les règles bloquantes. Sans refus bloquant, le profil RULES appelle RulesScorer et construit RiskAssessment à partir du score et de son motif. Le profil ENSEMBLE existe pour d’autres expériences. Notre scénario de cycle carte utilise RULES. « Local » concerne le calcul du risque : la persistance MongoDB reste une dépendance distincte, donc ne pas dire que toute la requête s’exécute sans réseau.

**Sources locales :** api/src/Application/RiskExecution.php ; api/src/Engine/Php/PhpRiskEngine.php ; api/src/Domain/RiskEngine.php.
-->


---
class: authorization-code
---

# Du risque à la réservation

<div class="split">
<div class="compact">
<div class="label">1 · BankService::authorize() obtient le risque</div>

~~~php
// … compte chargé, rejeu déjà traité
$assessment ??= $this->risk->assess(
    $request, $engine,
);
// … construction du montant métier
$reason = $this->policy->decide(
    $account, $request->cardId,
    $money, $assessment,
);
~~~

<div class="label">2 · AuthorizationPolicy::decide() contrôle les fonds</div>

~~~php
// … contrôles carte, risque et devise
if ($amount->minorUnits > $account->available())
    return
        AuthorizationReason::InsufficientFunds;
// … décision finale
~~~

</div>
<div class="compact">
<div class="label">3 · BankService::authorize() réserve et sauvegarde</div>

~~~php
// … décision et réponse construites
if ('authorized' === $state)
    $account->held += $view->amount;

// … mémorisation de la commande
if ($this->accounts->save(
    $account, $account->version,
)) return $view;
// … conflit de version : nouvelle tentative
~~~

<p class="sub">Seule une autorisation acceptée réserve le montant.</p>
<p class="small">// … : extraits abrégés.<br>Déduplication et concurrence restent dans le code exécuté.</p>
</div>
</div>


<!--
**Slide 23 · 12:35–13:20 · 45 s**

### Le message de cette slide

Faire lire la cascade de fonctions sans expliquer toutes les branches.

### À dire

Dans le premier extrait, BankService obtient le risque puis appelle sa politique de décision. Le deuxième ouvre cette politique sur une règle concrète : si le montant dépasse le disponible, nous refusons pour fonds insuffisants. Dans le troisième, nous revenons au service. Seule une autorisation acceptée augmente la réservation, puis nous sauvegardons. Les ellipses omettent notamment la gestion du rejeu et des conflits de version. Elles sont dans le code exécuté ; ces extraits seuls ne suffisent pas à gérer la concurrence.

### Appui visuel / conduite

Bloc 1, bloc 2, puis bloc 3 : appel, règle de fonds, sauvegarde. Pause sur chaque bloc.

### Transition vers la suite

Avant de lancer ce parcours, distinguons répéter une commande et lire son état.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre les ellipses

Ce sont des extraits abrégés des méthodes réelles, pas une nouvelle implémentation. Les appels ont seulement été répartis sur plusieurs lignes pour la lecture. Le contrôle de provision est identique au code source, avec mise en ligne du return. Le schéma de lecture est appel du service, entrée dans sa politique, retour dans le service. Ce ne sont ni trois processus ni trois appels HTTP.

Avant le premier extrait, le compte est chargé et un éventuel rejeu reconnu. Un rejeu retourne la réponse enregistrée sans repasser par le calcul. ??= conserve le résultat de risque lors d’une nouvelle tentative liée à un conflit de version. La politique recontrôle alors le compte actualisé. Le deuxième extrait omet volontairement les contrôles de carte, risque, devise et la décision finale. Le troisième omet la construction de state et view, ainsi que l’enregistrement du fingerprint et du snapshot dans le compte. save compare la version attendue ; en cas de conflit, la boucle recharge le compte et réessaie dans une limite bornée. Aucun verrou de base n’est maintenu pendant le calcul du risque. Ces quelques lignes seules ne constituent pas une implémentation complète de l’idempotence ou de la concurrence.


### Sources locales

api/src/Application/Bank/BankService.php, méthode authorize ; api/src/Application/Bank/AuthorizationPolicy.php, méthode decide. Le code du laboratoire n’est pas modifié pour cette slide.
-->

---
---



# Rejouer la demande ou relire l’autorisation ?

| Dans notre contrat | Choix explicite |
| --- | --- |
| Autorisation | Identité stable, GET de relecture |
| POST autorisation | 200 : résultat de la commande, décision persistée |
| Rejeu du même POST | Réponse initiale, aucun nouvel effet |
| GET après clearing | État courant : <code>cleared</code> |

<p class="small">Un 201 serait un autre contrat de création possible. Ni 200 ni 201 ne choisit votre stockage.</p>

<!--
**Slide 24 · 13:20–13:55 · 35 s**

### Le message de cette slide

Éviter la confusion entre réponse rejouée et état courant.

### À dire

Supposons que l’autorisation a été comptabilisée. Si je refais le même POST avec la même clé, je demande la réponse de ma commande initiale : je la récupère sans nouvel effet. Si je fais un GET, je demande où en est l’autorisation maintenant : son état est cleared. Ce n’est pas contradictoire. Les deux appels répondent à des questions différentes, et cette distinction fait partie du contrat que nous voulons conserver.

### Appui visuel / conduite

Comparer uniquement les deux dernières lignes. Garder le débat 200/201 pour les questions.

### Transition vers la suite

Il faut aussi distinguer un refus et une impossibilité de décider.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

BankService conserve séparément le snapshot de réponse initiale et l’état courant. authorize retourne le snapshot lors d’un rejeu compatible. authorization reconstruit la vue avec l’état courant. clear fait évoluer cet état vers cleared. Le 200 décrit le résultat de notre commande ; il ne signifie pas que rien n’a été persisté. Un contrat de création pourrait choisir 201. Aucun de ces statuts n’impose un ORM.

**Source locale :** api/src/Application/Bank/BankService.php, méthodes authorize, authorization et clear.
-->

---
class: light
---


# « Non » n’est pas « je ne sais pas ».

<div class="steps">
<div class="step"><strong>Décision</strong><p>Un refus métier est une réponse calculée.</p></div>
<div class="step"><strong>Validation</strong><p>Une entrée mal formée ne doit pas atteindre le calcul.</p></div>
<div class="step"><strong>Indisponible</strong><p>Un moteur en panne ne doit pas inventer une décision.</p></div>
</div>

<div class="callout">Trois situations. Trois significations.<br>À préserver <strong>dans les quatre configurations</strong>.</div>

<!--
**Slide 25 · 13:55–14:45 · 50 s**

### Le message de cette slide

Faire comprendre les issues par leur conséquence pour le consommateur.

### À dire

Une carte inactive peut donner une demande bien formée, mais un refus métier. Une entrée invalide est un autre problème, détecté avant le calcul. Enfin, si le moteur est indisponible, nous n’avons pas obtenu de décision fiable. Nous ne fabriquons pas un refus pour cacher la panne. Ces situations sont distinctes pour le consommateur et doivent le rester, quel que soit le moteur. C’est ce que résume le titre : dire non n’est pas dire que nous ne savons pas décider.

### Appui visuel / conduite

Pointer décision, validation, indisponibilité, sans développer toutes les erreurs HTTP.

### Transition vers la suite

Regardons maintenant les effets attendus sur les soldes.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Une validation structurelle contrôle l’entrée. Le métier peut refuser une requête pourtant bien formée. Une panne technique signifie qu’aucune décision fiable n’a été obtenue. Le code courant utilise 200, 422 et 503 pour ces catégories, avec des limites à vérifier sur les autres erreurs possibles.


### Nuance pour les experts

Le choix des statuts relève du contrat, pas d’une règle universelle imposée par API Platform. Une autre API pourrait exposer certaines politiques via un 4xx documenté. Un mécanisme de secours métier peut aussi exister en production : il doit être explicite, approuvé et observable. Le lab n’en implémente pas.
-->

---
---

# Avant la démo : un paiement, un seul débit

<p class="sub">Compte de départ : 1 000 €. Autorisation : 420,69 €.</p>

| Action du client | Solde comptabilisé | Réservé | Disponible |
| --- | ---: | ---: | ---: |
| Autoriser | 1 000,00 € | 420,69 € | 579,31 € |
| Comptabiliser le clearing | 579,31 € | 0,00 € | 579,31 € |
| Répéter le même clearing | 579,31 € | 0,00 € | 579,31 € |

<p class="small">Disponible = solde comptabilisé − montant réservé.</p>

<p>À vérifier : <strong>le rejeu ne crée aucun débit supplémentaire.</strong></p>

<!--
**Slide 26 · 14:45–15:05 · 20 s**

### Le message de cette slide

Faire comprendre pourquoi le disponible reste identique au clearing.

### À dire

Nous partons de mille euros. L’autorisation réserve quatre cent vingt euros et soixante-neuf centimes : il reste cinq cent soixante-dix-neuf euros et trente et un centimes disponibles. Au clearing, le comptabilisé baisse et la réservation disparaît du même montant. Le disponible reste donc identique. Répéter le clearing ne doit plus rien changer. Voilà ce que nous allons vérifier, au-delà du score retourné.

### Appui visuel / conduite

Suivre les colonnes comptabilisé et réservé ensemble, puis le disponible.

### Transition vers la suite

Passons au laboratoire.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Les valeurs sont les résultats attendus du scénario synthétique, pas un benchmark. requestId est une clé d’idempotence par compte et par opération. La décision initiale reste rejouable après le clearing. GET permet de lire l’état courant, qui peut avoir changé.
-->

---
class: demo-break
---

<div class="demo-system">PHP local · scénario de référence</div>

# DÉMO 01

<p class="demo-title">Autoriser, comptabiliser, rejouer</p>
<p class="demo-expectation">Nous suivons la réservation et le solde.<br>La même demande répétée doit garder les mêmes effets.</p>

<!--
**Slide 27 · 15:05–17:05 · 120 s**

### Le message de cette slide

Guider la manipulation, laisser voir les preuves.

### À dire

Je lance le scénario PHP sur un compte neuf. Regardons d’abord le montant réservé et le disponible. Puis le clearing : la réservation disparaît, le montant est comptabilisé. Enfin, je vous montre la vérification du rejeu : aucune seconde écriture ne doit modifier le solde. Je ne vais pas lire tout le JSON ; nous cherchons les valeurs annoncées et les assertions qui les contrôlent.

### Appui visuel / conduite

Lancer make demo-php depuis la racine du labo. Ouvrir les captures utiles si le résumé ne montre pas l’étape commentée.

### Transition vers la suite

Revenons à ce que cette première exécution nous donne comme référence.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Lancer make demo-php. Le script crée un scénario neuf et conserve les requêtes/réponses dans benchmark/results/cycle-php-*. Montrer les trois soldes, puis le rejeu et le 409 en cas de changement de contenu. Les tests vérifient également carte bloquée, fonds insuffisants, refus risque et validation. Ne pas lire toutes les captures en direct. En cas de panne de démonstration, présenter une capture réelle identifiée, sans prétendre à une exécution live. La réservation, la décision et la déduplication sont écrites ensemble par contrôle de version MongoDB. Deux demandes concurrentes ne peuvent pas valider la même version. Ce point est testé mais la couverture des interleavings n’est pas exhaustive.
-->

---
---

# Bilan : notre référence fonctionnelle

<div class="demo-takeaway">
<div><span>Vérification</span><p>420,69 € réservés, puis comptabilisés.<br>Le rejeu ne débite pas une seconde fois.</p></div>
<div><span>Ce qu’on en tire</span><p><strong>Ce cycle sert de référence</strong> pour les deux changements de moteur.</p></div>
</div>

<p class="sub">Le calcul, la décision et la réservation restent pilotés par PHP.</p>

<!--
**Slide 28 · 17:05–17:30 · 25 s**

### Le message de cette slide

Conclure la preuve fonctionnelle, sans commenter déjà la vitesse.

### À dire

Sur ce scénario, nous avons vérifié la réservation, la comptabilisation et le rejeu sans double débit. Nous avons donc une référence observable pour les changements suivants. Il ne suffira pas de retrouver un score : nous comparerons aussi la décision et les effets sur le compte. Pour l’instant, nous n’avons rien démontré sur la vitesse.

### Appui visuel / conduite

Après succès seulement : montrer le montant et l’absence de double débit. Si échec, présenter le bilan comme attendu.

### Transition vers la suite

Les temps viennent d’une expérience séparée. Précisons son périmètre.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

Aucun complément nécessaire pour cet intercalaire.
-->

---
class: light
---

<div class="eyebrow">Et la performance ?</div>

# Ce que mesurent les chiffres des bilans

| Périmètre | Usage |
| --- | --- |
| **Autorisation HTTP complète** | **Temps affichés dans les bilans, MongoDB inclus** |
| Durée à la frontière moteur | Diagnostic séparé dans les rapports |
| Cycle avec clearing et rejeu | Vérification fonctionnelle, hors de ce chrono |

<p class="sub"><strong>A : avant l’appel HTTP du client de benchmark.</strong><br><strong>B : après réception complète du corps de la réponse.</strong></p>
<p class="small">Docker local, même FrankenPHP à chaud. Commande neuve par appel, compte neuf par bloc.</p>

<!--
**Slide 29 · 17:30–18:05 · 35 s**

### Le message de cette slide

Définir une fois le chronomètre des bilans.

### À dire

Tous les temps des bilans mesurent l’aller-retour HTTP d’une autorisation complète, avec MongoDB. Le client démarre le chrono avant l’appel et l’arrête après avoir reçu tout le corps de réponse. Les serveurs tournent déjà et sont préchauffés. Chaque demande est neuve, sinon nous pourrions mesurer un rejeu sans recalcul. La durée du moteur est aussi enregistrée pour le diagnostic. Le cycle avec clearing et rejeu, lui, est une vérification fonctionnelle hors de ce chronomètre.

### Appui visuel / conduite

Pointer la première ligne puis A et B. Les autres lignes servent à distinguer diagnostic et preuve fonctionnelle.

### Transition vers la suite

Voici la première ligne de notre comparaison.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Le protocole séquentiel comporte quatre configurations, deux profils, cinq campagnes et quatre répétitions en ordre tournant. La préparation du compte est hors chronométrage. Le document compte grandit au même rythme dans chaque bloc : ce coût fait partie de ce laboratoire borné. La durée Server-Timing moteur exclut MongoDB et enrichissement. La campagne chronomètre POST authorization, pas toute la suite lecture/clearing. Celle-ci est une preuve fonctionnelle distincte. Les anciens chiffres excluent la persistance et ne doivent pas servir de résultats de cette nouvelle version.
-->

---
---

<div class="eyebrow">Bilan du cas 1 · autorisation avec MongoDB · RULES</div>

# PHP local : notre référence de latence

| Approche | Médiane API | Plage entre campagnes |
| --- | ---: | ---: |
| PHP local | 2,81 ms | 1,61–3,88 ms |
| <span class="bench-pending">Go HTTP</span> | <span class="bench-pending">Après le cas 2</span> | <span class="bench-pending">—</span> |
| <span class="bench-pending">Go natif</span> | <span class="bench-pending">Après le cas 3</span> | <span class="bench-pending">—</span> |

<p class="sub">Atout : un seul cycle de livraison.<br>Limite : le calcul partage le budget des workers PHP.</p>
<p class="small"><strong>A : avant l’appel HTTP → B : corps de réponse entièrement reçu.</strong><br>Client de benchmark dans Docker, serveur déjà démarré et préchauffé.<br>5 campagnes × 4 répétitions × 200 appels · médiane des p50 et plage min–max.</p>

<!--
**Slide 30 · 18:05–18:30 · 25 s**

### Le message de cette slide

Installer la référence mesurée sans promettre une performance de production.

### À dire

Sur ce poste et ce scénario, la référence PHP est autour de deux virgule huit millisecondes. La plage montre que les campagnes varient : ce n’est pas un temps garanti pour chaque requête. Nous compléterons ce tableau après les autres cas, avec le même protocole. L’intérêt du PHP local reste sa simplicité de livraison. Nous avons maintenant un comportement de référence et une mesure de comparaison.

### Appui visuel / conduite

Lire uniquement la ligne PHP et sa dispersion. Laisser les lignes futures grisées.

### Transition vers la suite

Voyons pourquoi une équipe pourrait malgré tout extraire son calcul.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre
Ces mesures portent sur les autorisations neuves avec contrôles et persistance MongoDB, pas sur le clearing. La dispersion du poste local reste importante. Les plages sont descriptives, pas des intervalles de confiance.

Chaque bloc contient 200 mesures après 40 appels de chauffe. Une campagne produit la médiane de quatre p50 de blocs, chaque moteur occupant chaque position une fois. Nous affichons la médiane des cinq résumés de campagne et leur minimum–maximum. Ce ne sont ni des percentiles fusionnés ni des intervalles de confiance. Aucun run de la série n’a été écarté. Les mesures détaillées sont dans docs/benchmark-reference-2026-09-17.md.


### Équipe et exploitation
Ces arbitrages sont une analyse architecturale, pas une mesure de vélocité ni un retour d’incident de production. Le conteneur API expose les quatre adaptateurs. Les moteurs PHP HTTP et Go HTTP tournent dans des services séparés, contrairement au PHP local et au Go natif. Cette séparation permet une livraison indépendante, sans supprimer la dépendance synchrone.

**Source locale :** docs/benchmark-reference-2026-09-17.md et benchmark/results/reference-20260917.json.
-->

---
class: chapter chapter-go
---

<div class="chapter-index">CAS 2 / 3</div>

# Le service<br>PHP, puis Go

<p class="chapter-sub">Un contrat HTTP privé, un client Jane</p>

<!--
**Slide 31 · 18:30–18:40 · 10 s**

### Le message de cette slide

Ouvrir le cas distant sans confondre extraction et langage.

### À dire

Deuxième cas : nous sortons le calcul derrière HTTP. D’abord en PHP, puis en Go, pour distinguer l’extraction du changement de langage.

### Appui visuel / conduite

Pointer PHP puis Go dans le titre.

### Transition vers la suite

Le besoin vient ici de l’équipe qui possède le service.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Laisser respirer cet intercalaire. L’extraction réseau et le changement de langage sont deux décisions séparées.
-->

---
---

# L’équipe risque veut faire évoluer son service

<div class="split"><div>
<div class="label">Son nouveau format privé</div>

~~~json
{
  "score": 0.12,
  "outcome": "ALLOW"
}
~~~

<p class="sub">Un score sur 1 et de nouveaux statuts.<br>Le comportement métier reste identique.</p>
</div><div>
<div class="label">Ce que nos consommateurs connaissent</div>

~~~json
{
  "riskScore": 12,
  "status": "approved"
}
~~~

<p class="sub">Un score sur 100 et une décision publique.<br><strong>Qui doit absorber la différence ?</strong></p>
</div></div>
<p class="small">Notre objectif : adapter la façade sans modifier les appels de ses consommateurs.</p>

<!--
**Slide 32 · 18:40–19:10 · 30 s**

### Le message de cette slide

Créer l’enjeu produit avant les outils.

### À dire

Imaginons que l’équipe risque veut faire évoluer son service et sa représentation des résultats. Elle préfère un score sur un et d’autres noms de statuts. Nos consommateurs connaissent un score sur cent. Qui doit absorber cette différence ? Nous voulons l’adapter une fois dans la façade, sans demander à chaque client de changer. La politique métier reste la même. Nous allons d’abord construire le raccord HTTP, puis vérifier cette évolution de format.

### Appui visuel / conduite

Comparer les deux formats sans les expliquer déjà ligne par ligne.

### Transition vers la suite

Voici ce qui change dans notre schéma.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Portée
Les deux versions existent déjà dans le labo. Nous montrons une bascule contrôlée, pas un déploiement de production en direct. La politique métier reste la même : une vraie nouvelle politique demande une validation distincte.
-->

---
---

# Le risque devient un service HTTP

<ExecutionDiagram variant="http" />

<p class="sub">Par rapport au PHP local : <strong>un appel réseau et un déploiement séparé.</strong><br>Le contrat public et les écritures sur le compte restent dans l’application.</p>

<!--
**Slide 33 · 19:10–19:50 · 40 s**

### Le message de cette slide

Montrer le seul déplacement de frontière.

### À dire

Cette fois, le moteur est un service déjà démarré dans un autre processus. L’application lui envoie les données utiles au risque par HTTP. Le compte et les réservations restent dans BankService. Nous pouvons appeler le service PHP, puis le service Go avec le même contrat privé. Jane fournit le client PHP de cet appel. Le bénéfice recherché est de pouvoir livrer le composant séparément ; le coût est d’ajouter une dépendance réseau.

### Appui visuel / conduite

Pointer la liaison réseau et le processus distant. Les autres blocs restent à leur place.

### Transition vers la suite

Pour appeler ce service, il faut d’abord préciser ce qu’il attend et ce qu’il renvoie.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Le dépôt configure deux instances du même HttpRiskEngine avec deux RiskEngineClientFactory, des URL privées et une identité technique attendue. Symfony HttpClient est adapté en PSR-18 pour Jane. Les timeouts sont bornés, les réponses sont validées, aucun retry implicite. Le conteneur PHP privé réutilise l’image de laboratoire : l’extension Go est présente mais inutilisée pour ce chemin. Ce n’est pas une comparaison entre deux images minimales de production.
-->

---
class: authorization-code
---

# Le contrat privé du moteur de risque

<div class="split"><div>
<div class="label">POST /v1/assess · operationId : assessRisk</div>

~~~yaml
# AssessResponse.properties · champs sélectionnés
riskScore:
  type: integer
  format: int64
  minimum: 0
  maximum: 100
  example: 12
status:
  type: string
  enum: [approved, challenged, declined]
  # … description et exemple
~~~

<p class="small">Le contrat définit aussi les champs requis,<br>le motif et l’identité du moteur.</p>
</div><div>
<div class="label">Ce que les deux briques se promettent</div>

<p class="sub"><strong>Entrée déjà enrichie</strong><br>L’application prépare les données du calcul.</p>
<p class="sub"><strong>Une évaluation en retour</strong><br>Aucune réservation ni écriture sur les comptes.</p>
<p class="sub"><strong>Mêmes conventions en PHP et en Go</strong><br>approved concerne le risque.<br>BankService décide de l’autorisation.</p>
</div></div>

<p class="small"><strong>Jane génère le client de ce contrat.</strong><br>Notre adaptateur traduit le résultat vers le modèle public.</p>

<!--
**Slide 34 · 19:50–20:35 · 45 s**

### Le message de cette slide

Définir la responsabilité du contrat privé.

### À dire

Ceci est le contrat privé du risque, différent de notre API de paiement publique. Nous envoyons des données déjà préparées pour le calcul. Le service renvoie un score entier entre zéro et cent et un statut de risque. Approved concerne ici le risque, pas nécessairement l’autorisation finale. Le compte peut toujours manquer de fonds. Le moteur ne réserve rien. Cette responsabilité limitée permet aux implémentations PHP et Go de recevoir les mêmes entrées et de rendre des résultats comparables.

### Appui visuel / conduite

Pointer score entier et enum, puis la limite sur les écritures.

### Transition vers la suite

Ce document sert aussi à fabriquer notre client PHP.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pourquoi ces choix ?

La frontière suit le calcul que nous souhaitons déplacer. L’enrichissement reste dans RiskExecution : pays de l’émetteur, activité de l’appareil et niveau de risque marchand. Les différents moteurs reçoivent ainsi des entrées équivalentes. Nous n’envoyons pas simplement la requête publique telle quelle et nous ne transférons pas la responsabilité des réservations au service distant.

Le YAML est extrait de components.schemas.AssessResponse.properties dans contract/risk-engine.openapi.yaml. Le commentaire indique les champs sélectionnés ; ce n’est pas une spécification complète. L’entrée AssessRequest définit notamment amountMinor en unités mineures et currency ; la réponse complète inclut decisionReason et engine. Le statut de risque est distinct du statut de l’autorisation publique. Le labo ne réalise pas un parcours 3-D Secure complet lorsque le risque renvoie challenged.


### Erreurs et exploitation

Dans ce contrat privé, 200 signifie qu’une évaluation a été produite, y compris declined. 400, 413 et 422 décrivent respectivement une requête mal formée, trop volumineuse et invalide. Une panne réseau n’est pas un refus métier : l’adaptateur la traduit en indisponibilité côté API publique. Ne pas prétendre que tous ces échecs prennent la forme d’un 503 émis par le serveur de risque.

Les délais et l’adresse du service sont configurés dans RiskEngineClientFactory ; l’OpenAPI ne les applique pas. Le contrat de données et la politique de transport sont complémentaires.


### Compatibilité entre équipes

Les implémentations PHP et Go doivent respecter les mêmes conventions. Le document et le client généré ne prouvent pas seuls le comportement : les cas de référence et tests de parité vérifient aussi les résultats. La variante privée v2 permettra ensuite de changer l’échelle et les noms via une traduction explicite, sans propager ce changement au consommateur public. Dans ce labo, contrat et client sont coordonnés ; une livraison réellement indépendante demanderait de gérer la coexistence de versions compatibles.


### Ce que la génération garantit, et ce qu’elle ne garantit pas

Jane produit les modèles, méthodes et normalizers depuis le document OpenAPI. Après régénération, l’analyse statique peut repérer une méthode disparue ; elle ne détecte pas nécessairement un changement d’unité à type identique. Le serveur doit aussi être testé contre le contrat. WireMapper contrôle les enums et construit les objets métier : la génération ne remplace pas cette traduction ni les validations à l’exécution.

**Source :** [Jane OpenAPI](https://jane.jolicode.com/latest/openapi/component/).

**Sources locales :** contract/risk-engine.openapi.yaml ; api/src/Engine/Http/WireMapper.php ; api/src/Engine/Http/RiskEngineClientFactory.php ; api/src/Application/Bank/AuthorizationPolicy.php.
-->

---
class: php-walkthrough
---

# Le client PHP généré par Jane

<div class="split"><div class="compact">
<div class="label">L’opération dans notre OpenAPI privé</div>

~~~yaml
paths:
  /v1/assess:
    post:
      operationId: assessRisk
      # … entrée : AssessRequest
      # … réponse 200 : AssessResponse
~~~

<p class="sub">La configuration Jane indique le contrat,<br>le namespace et le dossier de sortie.</p>
</div><div class="compact">
<div class="label">Les classes produites dans le labo</div>

~~~text
Client.php
Endpoint/AssessRisk.php
Model/AssessRequest.php
Model/AssessResponse.php
Normalizer/AssessRequestNormalizer.php
Normalizer/AssessResponseNormalizer.php
~~~

<p class="sub">Une méthode assessRisk(), ses modèles<br>et leur sérialisation.</p>
</div></div>

~~~sh
vendor/bin/jane-openapi generate --config-file=jane-configuration.php
~~~

<p class="small">Dans le conteneur PHP · raccourci du dépôt : make contract-php · Jane 7.14</p>

<!--
**Slide 35 · 20:35–21:20 · 45 s**

### Le message de cette slide

Expliquer la génération avant l’utilisation de Jane.

### À dire

À gauche, l’opération est nommée assessRisk dans OpenAPI. Jane s’en sert pour générer une méthode d’appel et les objets échangés. Les normalizers transforment ces objets pour le JSON et inversement. La configuration indique le contrat source et où écrire les classes. La commande du bas réalise cette génération pendant la préparation du projet, pas à chaque requête. Nous n’éditons pas ces fichiers générés : nos adaptations restent à côté.

### Appui visuel / conduite

Suivre operationId vers Endpoint, Model et Normalizer. Pointer la commande sans la lancer.

### Transition vers la suite

Une fois ce client généré, suivons une requête.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Mise en place réelle dans le labo

Le contrat est contract/risk-engine.openapi.yaml. api/jane-configuration.php définit openapi-file, namespace et directory, ainsi que strict = true. Le code sort dans api/src/Infrastructure/Generated/RiskEngine. La commande make contract-php exécute dans le conteneur : vendor/bin/jane-openapi generate --config-file=jane-configuration.php. Elle régénère les fichiers ; on ne la lance pas au milieu de la démonstration fonctionnelle.

Dans Composer, jane-php/open-api-3 est une dépendance de développement et open-api-runtime une dépendance d’exécution. Le verrou du labo fixe les deux à 7.14.0. Cette version installée ne constitue pas une affirmation sur la toute dernière release disponible.


### Qui fait quoi ?

Client et Endpoint contiennent les méthodes d’appel et la construction des requêtes. Les modèles décrivent les données échangées. Les normalizers servent à transformer ces objets en données sérialisables et inversement. La factory du labo fournit Symfony HttpClient via Psr18Client, configure l’adresse du service et les délais. Elle n’ajoute pas de retry automatique.

Ne pas éditer à la main les fichiers générés : les adaptations restent dans HttpRiskEngine, RiskEngineClientFactory et WireMapper. Si le contrat change, régénérer puis examiner le diff, l’analyse statique et les tests. La génération ne prouve ni la conformité réelle du serveur, ni la validité de toutes les valeurs métier. Une signature peut notamment autoriser null ; notre adaptateur vérifie la réponse reçue.

La documentation actuelle couvre aussi OpenAPI 3.1 avec son paquet dédié. Nous n’avons pas besoin d’une migration ni d’une nouveauté non utilisée pour expliquer cette mécanique.

**Sources :** [Jane OpenAPI, génération et utilisation](https://jane.jolicode.com/latest/openapi/component/) ; api/jane-configuration.php ; api/composer.lock ; Makefile, cible contract-php ; api/src/Engine/Http/RiskEngineClientFactory.php.
-->

---
---

# Zoom sur l’appel HTTP du risque

<RiskBoundaryZoom />

<p class="sub">Même étape « obtenir le risque ». <strong>Cette fois, le calcul est dans un autre processus.</strong></p>
<p class="small">Le service est déjà démarré. Le client Jane envoie une requête HTTP ; aucun exec.</p>

<!--
**Slide 36 · 21:20–21:35 · 15 s**

### Le message de cette slide

Rappeler le zoom local en changeant seulement le transport.

### À dire

Nous ouvrons le même bloc de risque que dans le cas PHP. L’adaptateur prépare les données, Jane appelle le service, puis nous reconstruisons l’évaluation métier. La flèche de retour représente des données. Il n’y a ni programme lancé par exec ni serveur démarré pour chaque demande.

### Appui visuel / conduite

Suivre la flèche pleine à l’aller, pointillée au retour. Pause sur la frontière HTTP.

### Transition vers la suite

Voici les quelques lignes qui réalisent ce trajet.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

RiskExecution sélectionne toujours le moteur et enrichit l’entrée en amont. Dans ce zoom, on ouvre seulement HttpRiskEngine::assess(). Le même adaptateur et le même client généré servent PHP HTTP et Go HTTP, avec une adresse et une identité de moteur configurées différemment. La factory fournit Symfony HttpClient via un transport PSR-18, fixe les délais et n’ajoute pas de retry automatique. Le service ne démarre pas à chaque appel.

Le diagramme montre la v1 pour lire le trajet sans surcharge. La v2 a une opération et une traduction spécifiques ; elle n’est pas devinée par le client généré.
-->

---
class: authorization-code
---

# Du client généré au résultat métier

<div class="split"><div class="compact">
<div class="label">1 · HttpRiskEngine::assess() appelle le service</div>

~~~php
// Chemin V1 · corrélation et try omis
$response = $this->client()->assessRisk(
    $this->mapper->toWire($input, $profile),
    ['X-Correlation-Id' => $correlationId],
);
// … erreurs, type et identité du moteur
return $this->mapper->toDomain($response);
~~~

<p class="small">toWire() construit le modèle Jane.<br>Le client sérialise et envoie la requête HTTP.</p>
</div><div class="compact">
<div class="label">2 · WireMapper::toDomain() reconstruit le résultat</div>

~~~php
$status = AuthorizationStatus::tryFrom(
    $response->getStatus(),
);
// … valider le statut et lire le motif
return new RiskAssessment(
    $response->getRiskScore(),
    $status, $reason,
);
~~~

<p class="sub">BankService reçoit toujours<br><strong>une évaluation du risque.</strong></p>
<p class="small">Chemin V1 isolé pour la lecture.<br>// … : contrôles présents dans le code du labo.</p>
</div></div>

<!--
**Slide 37 · 21:35–22:20 · 45 s**

### Le message de cette slide

Séparer transport généré et traduction applicative.

### À dire

À gauche, toWire prépare l’objet que le client Jane attend. AssessRisk fait l’appel HTTP et nous rend un objet de réponse. À droite, notre mapper valide et traduit ce résultat pour construire RiskAssessment. BankService retrouve donc le même objet métier qu’avec le moteur local. Jane nous évite d’écrire les appels et la sérialisation à la main. Il n’invente pas le sens des valeurs. L’extrait isole le chemin V1 et abrège les contrôles d’erreur.

### Appui visuel / conduite

Pointer toWire, assessRisk, puis toDomain. Finir sur RiskAssessment.

### Transition vers la suite

Reprenons maintenant le changement de format annoncé au début de ce cas.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Extraits et limites

Extraits de HttpRiskEngine::assess() et WireMapper::toDomain(), spécialisés ici sur le chemin V1 : l’appel dynamique du laboratoire est présenté comme un appel direct à assessRisk pour rendre le trajet lisible. Le chemin V2 sera montré avec sa propre traduction. Les exceptions de transport, les réponses inattendues, l’identité du moteur et les valeurs inconnues sont contrôlées dans le vrai code. La construction de RiskAssessment contrôle aussi la plage du score. Les ellipses ne constituent pas une implémentation complète à copier seules.

Le client est créé par RiskEngineClientFactory et réutilisé. toWire() est notre code, pas une inférence de Jane. Ni HTTP ni la génération ne garantissent seuls que les deux services appliquent les mêmes règles.


### Sources locales

api/src/Engine/Http/HttpRiskEngine.php ; WireMapper.php ; RiskEngineClientFactory.php.
-->

---
class: authorization-code
---

# Le service Go évolue, le client garde ses repères

<div class="split"><div>
<div class="label">Contrat privé · ancienne puis nouvelle réponse</div>

~~~json
// V1 · POST /v1/assess · extrait
{"riskScore": 12, "status": "approved"}
// V2 · POST /v2/assess · extrait
{"score": 0.12, "outcome": "ALLOW"}
~~~

<p class="sub">L’équipe risque change son format.<br>La politique du scénario reste identique.</p>
</div><div>
<div class="label">Contrat public · réponse conservée</div>

~~~json
{
  "riskScore": 12,
  "status": "approved",
  "authorizationReason": "APPROVED",
  "amount": 42069,
  "currency": "EUR"
}
~~~

</div></div>
<div class="flow"><div>V2 privée</div><span>→</span><div>Jane + WireMapper<br><span class="small">0,12 → 12 · ALLOW → approved</span></div><span>→</span><div>Même modèle public</div></div>
<p class="small">L’adaptateur évolue et les tests vérifient l’équivalence. Ce n’est pas automatique.</p>

<!--
**Slide 38 · 22:20–23:20 · 60 s**

### Le message de cette slide

Montrer la valeur principale du hub avec une transformation vérifiable.

### À dire

Le service Go sait maintenant renvoyer zéro virgule douze et ALLOW. Notre adaptateur traduit ces valeurs en douze et approved. BankService continue à prendre la décision bancaire, et le consommateur reçoit le même modèle public. C’est le bénéfice recherché : le changement de représentation reste derrière notre façade. Les deux versions sont déjà présentes dans le labo. Nous vérifions une bascule, pas une migration de production en direct. Si la politique métier change vraiment, nous devons traiter ce changement séparément.

### Appui visuel / conduite

Comparer V1 et V2 à gauche puis le JSON public à droite. S’arrêter sur 0,12 vers 12.

### Transition vers la suite

Mais toutes les nouvelles valeurs ne sont pas forcément représentables dans l’ancien contrat.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Scénario réellement implémenté
Le serveur Go expose déjà /v1/assess et /v2/assess. La V2 exprime le score sur 0–1 et utilise ALLOW, REVIEW, DENY. Jane expose les deux opérations ; HttpRiskEngine sélectionne la version et WireMapper::toDomainV2 reconstruit RiskAssessment. La décision bancaire finale demeure dans BankService. Les fragments JSON omettent les autres champs, notamment reason et engine privés.

make proof-public vérifie l’équivalence de la réponse publique de Go HTTP V2 avec la référence PHP sur les deux profils. Les identités propres à chaque scénario sont validées puis exclues de la comparaison sémantique ; ce n’est pas une égalité brute d’autorisations différentes. Les exports publics sont comparés entre les moteurs, ce n’est pas une comparaison historique de deux déploiements OpenAPI.


### Frontière de la promesse
Nous montrons une évolution de représentation à comportement métier constant. Si l’équipe risque modifie réellement sa politique et refuse une transaction auparavant acceptée, la façade ne doit pas inventer une ancienne décision. Il faut traiter cette évolution métier et ses conséquences avec les consommateurs. Une valeur non représentable ou un statut inconnu doit produire une erreur explicite, pas une conversion silencieuse.

Sources : go/internal/riskhttp/handler.go ; api/src/Engine/Http/WireMapper.php ; api/tests/Unit/Engine/WireMapperTest.php ; go/cmd/compare/main.go.
-->

---
class: light
---

<div class="eyebrow">Le piège de compatibilité</div>

# 0,199 devient-il 19 ou 20 ?

<span class="aside-emoji" aria-hidden="true">🧐</span>

<div class="split">
<div><div class="label">Troncature</div><div class="big-number">19</div><p>Zone <strong>approved</strong> du laboratoire.</p></div>
<div><div class="label">Arrondi au plus proche</div><div class="big-number">20</div><p>Zone <strong>challenged</strong> du laboratoire.</p></div>
</div>

<div class="callout">0,199 : <strong>réponse moteur incompatible.</strong><br>Erreur technique, aucun refus métier inventé.</div>
<p class="small">Nous acceptons les centièmes de 0,00 à 1,00, sans arrondi avec perte.<br>Une précision supplémentaire exige une nouvelle décision de contrat.</p>

<!--
**Slide 39 · 23:20–24:10 · 50 s**

### Le message de cette slide

Expliquer une limite de compatibilité, pas un arrondi d’API Platform.

### À dire

Notre contrat public attend un score entier. Si le service propose zéro virgule cent quatre-vingt-dix-neuf, le convertir donne dix-neuf virgule neuf. Tronquer ou arrondir ne donne pas la même valeur, et pourrait même changer une classification fondée sur le seuil vingt. Nous ne faisons ni l’un ni l’autre : notre adaptateur signale une réponse incompatible, donc une erreur technique, sans inventer un refus de paiement. C’est notre règle de conversion, pas une décision automatique d’API Platform.

### Appui visuel / conduite

Montrer 19 et 20 comme deux erreurs possibles, puis la règle choisie en bas.

### Transition vers la suite

Et si toutes nos implémentations faisaient le même mauvais choix de conversion ?

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

La compatibilité sémantique dépasse la compatibilité de type. Ici, les deux sorties sont bien des entiers entre 0 et 100. Elles ne conduisent pas au même comportement.

Ce contre-exemple concerne la reclassification après conversion. Si le backend transmet déjà une décision, ne pas la recalculer silencieusement. Définir la relation exigée entre score affiché et décision, et contrôler leur cohérence.


### Notre choix et sa preuve

Notre migration conserve exactement les 101 scores entiers historiques : WireMapper::toDomainV2 accepte uniquement leur grille de centièmes, avec une tolérance numérique pour la représentation flottante. Il rejette 0,199 comme réponse incompatible et remonte une indisponibilité, au lieu d’inventer une décision métier. WireMapperTest couvre explicitement cette valeur et les 101 conversions exactes. Si le produit veut une précision supplémentaire, cela exige une nouvelle règle de contrat et sa validation métier.
-->

---
---

<div class="eyebrow">Tester la justesse, pas seulement la parité</div>

# Même résultat ne veut pas dire résultat correct

<div class="split">
<div><div class="label">Parité entre implémentations</div><p>PHP local = PHP HTTP<br>= Go HTTP = Go natif</p><p class="small">Utile pour détecter une divergence entre moteurs.</p></div>
<div><div class="label">Attendu métier indépendant</div><p>Résultat = cas calculé à la main</p><p class="small">Nécessaire pour détecter une erreur partagée.</p></div>
</div>

<div class="callout">Les tests aux seuils et les invariants publics<br>complètent <strong>la comparaison du corpus</strong>.</div>

<!--
**Slide 40 · 24:10–24:35 · 25 s**

### Le message de cette slide

Relier le test indépendant au contre-exemple précédent.

### À dire

Si tous nos adaptateurs arrondissent mal de la même façon, leurs résultats sont égaux et le test de comparaison passe. Pourtant, nous avons violé la règle que nous venons de définir. Il faut donc aussi des cas dont nous connaissons le résultat attendu indépendamment. Ici, le test attend une erreur pour cette valeur incompatible. Comparer les moteurs détecte leurs différences ; vérifier nos attentes détecte aussi une erreur qu’ils partageraient.

### Appui visuel / conduite

Pointer l’égalité à gauche puis le résultat attendu à droite. Ne pas rouvrir tout le cours de tests.

### Transition vers la suite

Nous avons protégé le sens du score d’un paiement. Prenons un autre besoin : consulter les informations d’un marchand sans exposer toutes les données internes du service Go.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Un oracle de test est une référence pour décider si un résultat est correct. Comparer deux implémentations est un test différentiel. Il révèle leurs désaccords, pas les erreurs qu’elles partagent.


### Point concret

Le dépôt contient des cas attendus à la main et un test de joignabilité des reason codes : un code annoncé mais impossible à produire peut signaler une incohérence du modèle ou de sa documentation. Les tests PHP couvrent notamment les seuils et la conversion des 101 scores entiers vers la grille v2. Les contrôles HTTP et la parité complètent cette couverture, sans prouver toute entrée possible.


### Objection probable

« Pourquoi pas du property-based testing ? » Bonne extension : générer des entrées pour vérifier bornes, déterminisme et invariants. Cela complète des cas aux seuils et un oracle métier, sans faire apparaître une preuve universelle par magie.
-->

---
---

<div class="eyebrow">Autre besoin · consulter les informations d’un marchand</div>

# Consulter le profil d’un marchand

<div class="label">GET /api/merchant-risk-profiles/merchant_42</div>
<p class="small">Son nom, son pays, son niveau de risque : une lecture indépendante du paiement.</p>

<div class="flow"><div>GET public<br><span class="small">MerchantRiskProfile</span></div><span>→</span><div>Provider + service<br><span class="small">Jane → AutoMapper</span></div><span>→</span><div>DTO public<br><span class="small">JSON-LD / Hydra / OpenAPI</span></div></div>

<div class="compact">

```php
$remote = $client->getMerchantProfile($id);
$public = $mapper->map($remote, MerchantRiskProfile::class);
return $public;
```

</div>


<!--
**Slide 41 · 24:35–25:25 · 50 s**

### Le message de cette slide

Introduire le Provider comme deuxième opération, pas étape du paiement.

### À dire

Jusqu’ici, nous avons suivi l’autorisation d’un paiement. Prenons maintenant un autre besoin : consulter les informations d’un marchand, son nom, son pays et son niveau de risque. Le service Go possède ces données. Comment les rendre accessibles avec notre API Platform ? Nous ajoutons une opération de lecture, indépendante de l’autorisation. API Platform appelle un Provider, qui délègue à notre service applicatif. Jane interroge Go, puis AutoMapper projette les champs vers notre objet public. Nous réutilisons donc le même service distant pour alimenter une ressource de notre API.

### Appui visuel / conduite

Commencer par le besoin de consultation, puis pointer le GET et suivre les trois lignes. Le niveau de risque du marchand et le score calculé pour un paiement sont deux informations distinctes. Ne pas présenter cette lecture comme une étape supplémentaire de l’autorisation.

### Transition vers la suite

Mais Go possède aussi des informations internes. Est-ce que nous voulons toutes les publier ? Regardons les champs que nous conservons.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Un Provider peut lire une API, un fichier ou un autre système. Une ressource publique est un modèle de représentation ; elle n’est pas nécessairement une ligne de base de données. Jane connaît le contrat distant. AutoMapper connaît la transformation configurée. API Platform connaît la ressource et son exposition.


### Attention au code affiché

Le Provider délègue à MerchantQuery : ce service valide la réponse Jane, projette cinq champs avec JoliCode AutoMapper 10.2.0, omet internalOwner et traduit le marchand absent en 404. Une panne distante produit 503. Le code affiché simplifie ces gardes. Le test unitaire exécute le vrai mapper, et la démonstration interroge le service Go.


### Question probable

« Pourquoi une lecture en plus du POST ? » L’abstract promet les State Providers. Une lecture minimale du profil déjà utile au cas marchand montre ce point d’extension sans inventer une quatrième architecture.

Ne pas ajouter artificiellement de nombreux champs pour justifier un outil. Garder le mapping manuel des invariants si c’est plus lisible.

**Sources :** [State Providers](https://api-platform.com/docs/main/core/state-providers/), [AutoMapper JoliCode](https://automapper.jolicode.com/10.2.0/).
-->

---
class: authorization-code
---

# Quelles informations publier ?

<div class="flow"><div>Service Go<br><span class="small">Modèle privé</span></div><span>→</span><div>Jane + AutoMapper<br><span class="small">Client et projection</span></div><span>→</span><div>API Platform<br><span class="small">Ressource publique</span></div></div>

| Profil marchand privé | Ressource publique |
| --- | --- |
| merchantId, displayName, country | Identité et informations du marchand |
| settlementCurrency, riskTier | Devise et niveau de risque documentés |
| internalOwner | Non exposé |

<p class="sub"><strong>MerchantRiskProfile est un DTO, pas une entité Doctrine.</strong><br>Le Provider l’alimente ; API Platform publie sa représentation et ses descriptions.</p>

<!--
**Slide 42 · 25:25–26:10 · 45 s**

### Le message de cette slide

Défendre concrètement API Platform et le DTO sans ORM.

### À dire

Pour cette consultation, nous publions l’identité du marchand, son pays, sa devise et son niveau de risque. Go renvoie aussi le responsable interne du marchand. Cette information reste privée : notre objet public ne contient pas ce champ. Voilà pourquoi nous construisons notre propre ressource au lieu de transmettre toute la réponse de Go. Le Provider l’alimente et API Platform la décrit et la sérialise à partir de nos métadonnées. Aucun besoin d’une entité Doctrine pour exposer ces données distantes. Si Go ajoute demain une propriété interne, nous gardons la maîtrise des champs publics.

### Appui visuel / conduite

Pointer internalOwner non exposé, puis la phrase DTO en bas.

### Transition vers la suite

Pour le paiement, nous avons converti une échelle de score. Pour le marchand, nous sélectionnons des propriétés. Ces deux adaptations demandent des règles différentes.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pourquoi API Platform ici ?
Nous réutilisons ses ressources, métadonnées, sérialisation et documents JSON-LD/Hydra/OpenAPI pour exposer un modèle choisi. Le Provider est un point d’extension documenté ; aucune couche ORM n’est contournée par un hack. API Platform n’impose pas que cette ressource soit persistée par Doctrine. Les règles de mapping, les droits et les garanties métier restent notre responsabilité.


### Ce qui est réellement dans le code
MerchantRiskProfile est une classe readonly ApiResource, avec une opération Get reliée à MerchantRiskProfileProvider. MerchantQuery utilise le modèle généré MerchantProfile et JoliCode AutoMapper, vérifie des valeurs, omet internalOwner, traduit l’absence en 404 et une erreur distante en 503. WireMapperTest vérifie la projection avec le vrai AutoMapper. Une nouvelle propriété privée n’impose pas de l’ajouter à notre ressource publique.

Sources : api/src/ApiResource/MerchantRiskProfile.php ; api/src/Application/MerchantQuery.php ; api/tests/Unit/Engine/WireMapperTest.php ; [Providers](https://api-platform.com/docs/core/state-providers/).
-->

---
class: authorization-code
---

# Deux adaptations, des règles explicites

<div class="split"><div>
<div class="label">Dans notre démo</div>
<p class="sub"><strong>JoliCode AutoMapper</strong><br>Projette les champs du profil marchand vers la ressource publique.</p>
<p class="sub"><strong>WireMapper</strong><br>Traduit explicitement l’échelle du score et les statuts du risque.</p>
<p class="small">Ces règles restent hors des fichiers générés par Jane.</p>
</div><div>
<div class="label">Les règles appartiennent à notre application</div>
<p class="sub">Une propriété compatible se projette.<br>Un changement d’unité se définit et se teste.</p>
<p class="sub">La génération du client peut être relancée.<br><strong>Nos règles restent dans nos adaptateurs.</strong></p>
<p class="small">Une projection et une traduction explicite, testées chacune sur leurs engagements.</p>
</div></div>

<!--
**Slide 43 · 26:10–26:40 · 30 s**

### Le message de cette slide

Donner une règle de maintenance simple pour les deux mappers.

### À dire

Nous avons vu deux exemples du même rôle de façade. Pour consulter le marchand, AutoMapper projette les propriétés configurées vers notre ressource. Pour autoriser le paiement, notre WireMapper traduit l’échelle du score et les statuts avec des règles explicites. Jane fournit le client dans les deux cas, mais il ne choisit ni les informations publiques ni le sens des conversions. Nous gardons ces règles hors de ses fichiers générés : nous pouvons régénérer le client sans perdre nos adaptations, et tester chaque règle séparément.

### Appui visuel / conduite

Comparer AutoMapper et WireMapper, puis pointer hors des fichiers générés.

### Transition vers la suite

Nous avons maintenant les éléments pour vérifier tout cela dans la démo.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Complément sur le mapping

« Copier une propriété compatible, c’est mécanique. Renommer une propriété, c’est une correspondance que nous pouvons déclarer. Convertir une échelle ou traiter un statut inconnu, c’est une décision de contrat. Un outil peut exécuter cette décision, mais il ne doit pas l’inventer. Mon objectif est de retirer du code répétitif, pas de rendre les conversions moins visibles. »


### Pour comprendre

JoliCode AutoMapper peut utiliser des règles et des transformateurs. Une transformation métier exécutée par un mapper reste une règle métier : elle doit avoir une définition, des cas limites et des tests.


### Subtilité

Le WireMapper existant manipule Money, Currency, CardBin et des enums. Il n’est pas mauvais parce qu’il est manuel. Le cas Provider illustre l’intérêt du mapping automatique à un autre endroit, plutôt que de forcer un outil dans chaque couture.


### Objection probable

« Pourquoi pas Symfony ObjectMapper ? » C’est une alternative légitime, gardée comme réponse aux questions. Le choix dépend des besoins de transformation, de l’intégration et des mesures. Aucune supériorité de performance n’est démontrée ici.






### Complément sur ObjectMapper

Avec Jane, nous possédons la ressource publique mais nous régénérons les classes distantes. Il faut donc garder les règles de mapping hors des fichiers générés. Une autre option existe dans Symfony ObjectMapper : depuis 8.1, Map(source: ...) permet de déclarer le mapping sur la cible. Cette possibilité reste un complément documentaire. Notre démo garde JoliCode AutoMapper.


### Pour comprendre

ObjectMapper existe depuis Symfony 7.3, le mapping côté cible est une amélioration de 8.1. Il faut installer et configurer le composant pour l’utiliser. Aucun gain de performance ni supériorité générale sur JoliCode AutoMapper n’est démontré ici. L’intérêt concret est de placer la configuration dans une classe que l’équipe maîtrise, sans modifier la source générée.

**Source vérifiée :** https://symfony.com/blog/new-in-symfony-8-1-objectmapper-improvements
-->

---
---

# Avant la démo : nos trois vérifications

<div class="flow"><div class="php">PHP local<br><span class="small">Notre référence</span></div><span>→</span><div class="php">PHP HTTP<br><span class="small">On extrait le calcul</span></div><span>→</span><div class="go">Go HTTP<br><span class="small">On remplace le service</span></div></div>

<p><strong>1 · Même paiement.</strong> Décision, réservation et solde final identiques.</p>
<p><strong>2 · Format privé V2.</strong> Réponse métier publique conservée.</p>
<p><strong>3 · Profil marchand.</strong> Champ interne absent de la réponse publique.</p>
<p class="small">Paiements sur des comptes neufs initialisés à 1 000 €.</p>

<!--
**Slide 44 · 26:40–27:05 · 25 s**

### Le message de cette slide

Annoncer les trois attentes dans l’ordre du script.

### À dire

Nous allons vérifier trois choses. D’abord le même paiement avec PHP puis Go derrière HTTP. Ensuite une réponse publique conservée malgré le format privé V2. Enfin, un profil marchand qui n’expose pas sa propriété interne. Les paiements partent de comptes neufs au même solde. La politique métier ne change pas : nous testons nos raccords, pas une nouvelle politique de risque.

### Appui visuel / conduite

Lire chaque attente en pointant sa ligne, sans déjà commenter des résultats.

### Transition vers la suite

Passons à ces trois vérifications.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

Aucun complément nécessaire pour cet intercalaire.
-->

---
class: demo-break
---

<div class="demo-system">PHP HTTP, puis Go HTTP</div>

# DÉMO 02

<p class="demo-title">Le service évolue, notre contrat reste lisible</p>
<p class="demo-expectation">Même paiement avec PHP puis Go HTTP.<br>Format privé V2, réponse publique conservée.<br>Profil marchand : seules nos données publiques sortent.</p>

<!--
**Slide 45 · 27:05–30:05 · 180 s**

### Le message de cette slide

Conduire les trois pauses sans lire tous les échanges.

### À dire

Première étape : comparons la décision et les soldes avec PHP HTTP puis Go HTTP. Deuxième étape : regardons les formats privés V1 et V2, puis les réponses publiques correspondantes. Nous cherchons le même sens malgré les champs différents. Troisième étape : comparons le profil marchand privé et public. Le champ interne doit disparaître. Les identifiants de nos scénarios ne sont pas identiques, puisque les comptes sont distincts ; ils sont vérifiés séparément avant la comparaison métier.

### Appui visuel / conduite

Lancer ./demos/demo-2.sh depuis le labo. Utiliser les trois pauses interactives. Génération et services déjà préparés.

### Transition vers la suite

Revenons à ce que nous venons de vérifier.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Lancer ./demos/demo-2.sh : cycles HTTP résumés, comparaison V1/V2, puis projection marchand. Le runner commun exécute les deux moteurs dans le même processus de test et compare leurs réponses métier sur des scénarios distincts au même solde initial. Les identifiants diffèrent et sont contrôlés avant projection pour comparer les champs métier. Le marqueur Server-Timing doit attester le moteur demandé : un rejeu d’autorisation ne constitue pas une preuve d’appel au nouveau moteur. Les variantes HTTP v1 et v2 utilisent donc des commandes fraîches. Le lanceur ./demos/demo-2.sh montre ensuite le profil marchand privé et sa projection publique via Jane et JoliCode AutoMapper, avec absence de internalOwner vérifiée et test du 404. La génération est préparée, pas improvisée sur scène. Les appels privés affichés sont des inspections directes sur une entrée enrichie de référence, pas une interception du trafic PHP vers Go. V1 et V2 sont déjà déployées : la bascule par en-tête est un outil du labo, pas une procédure de migration de production. Trois pauses interactives ; DEMO_AUTO=1 les désactive. CARD_DEMO_VERBOSE=1 montre les échanges complets, toujours archivés.
-->

---
---

# Bilan : le service change, le cycle reste

<div class="demo-takeaway">
<div><span>Vérification</span><p>Même paiement en PHP HTTP et Go HTTP.<br>V2 privée traduite ; champ interne marchand exclu.</p></div>
<div><span>Ce qu’on en tire</span><p><strong>L’adaptateur absorbe la différence de contrat privé.</strong><br>Le service de risque peut se déployer séparément.</p></div>
</div>

<p class="sub">Coût ajouté : transport HTTP, timeouts et exploitation d’un service.</p>

<!--
**Slide 46 · 30:05–30:30 · 25 s**

### Le message de cette slide

Fermer le cas fonctionnel avec le bénéfice consommateur.

### À dire

Nous avons retrouvé les effets attendus du paiement avec les services distants. La V2 change la représentation privée, mais notre adaptateur conserve les valeurs publiques. Et la lecture marchand expose seulement les propriétés choisies. Nous n’avons pas supprimé le travail de compatibilité : nous l’avons rendu explicite dans la façade. Le composant distant peut se livrer séparément, avec un contrat privé à maintenir.

### Appui visuel / conduite

Après assertions réussies seulement, pointer V2 et champ exclu. Sinon expliquer l’écart.

### Transition vers la suite

Cette séparation a également un coût que nous pouvons mesurer.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Le bénéfice pour le consommateur

Le point central est la propriété du modèle public : une évolution privée représentable est absorbée par notre adaptateur. Les scénarios publics vérifient les engagements conservés. La V2 est déjà vérifiée par le cycle standard. Le lanceur ./demos/demo-2.sh la met maintenant en scène : formats privés inspectés directement, réponses publiques comparées sur deux comptes neufs et soldes vérifiés. make proof-public apporte une couverture complémentaire sur deux profils et les exports.
-->

---
---

<div class="eyebrow">Bilan du cas 2 · autorisation avec MongoDB · RULES</div>

# Le service achète de l’autonomie

| Approche | Médiane API | Plage entre campagnes |
| --- | ---: | ---: |
| PHP local | 2,81 ms | 1,61–3,88 ms |
| Go HTTP | 3,89 ms | 2,07–5,41 ms |
| <span class="bench-pending">Go natif</span> | <span class="bench-pending">Après le cas 3</span> | <span class="bench-pending">—</span> |

<p class="sub">Atout : ownership et livraison indépendants.<br>Coût : réseau, budgets de temps, contrat privé à maintenir.</p>
<p class="small"><strong>A : avant l’appel HTTP → B : corps de réponse entièrement reçu.</strong><br>Client de benchmark dans Docker, serveur déjà démarré et préchauffé.<br>5 campagnes × 4 répétitions × 200 appels · médiane des p50 et plage min–max.</p>

<!--
**Slide 47 · 30:30–31:15 · 45 s**

### Le message de cette slide

Interpréter la latence comme un coût d’architecture, pas un classement des langages.

### À dire

Le tableau ajoute maintenant Go HTTP, autour de trois virgule neuf millisecondes de médiane sur ce poste. C’est toujours une autorisation complète avec MongoDB, mesurée séparément de la démo. Pour ces petites règles, passer par un service n’apporte pas une accélération dans nos chiffres. Son intérêt est ailleurs : un composant livrable et dimensionnable séparément. Les variations restent visibles, et cette possibilité d’autonomie ne supprime pas la dépendance entre les versions.

### Appui visuel / conduite

Ajouter visuellement la ligne Go HTTP à la référence PHP. Ne pas relire le protocole complet.

### Transition vers la suite

Les responsabilités d’exploitation ne se résument pas à cette latence.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre
Ces mesures portent sur les autorisations neuves avec contrôles et persistance MongoDB, pas sur le clearing. La dispersion du poste local reste importante. Les plages sont descriptives, pas des intervalles de confiance.

Chaque bloc contient 200 mesures après 40 appels de chauffe. Une campagne produit la médiane de quatre p50 de blocs, chaque moteur occupant chaque position une fois. Nous affichons la médiane des cinq résumés de campagne et leur minimum–maximum. Ce ne sont ni des percentiles fusionnés ni des intervalles de confiance. Aucun run de la série n’a été écarté. Les mesures détaillées sont dans docs/benchmark-reference-2026-09-17.md.


### Équipe et exploitation
Ces arbitrages sont une analyse architecturale, pas une mesure de vélocité ni un retour d’incident de production. Le conteneur API expose les quatre adaptateurs. Le moteur Go HTTP tourne dans un service séparé, contrairement au PHP local et au Go natif. Cette séparation permet une livraison indépendante, sans supprimer la dépendance synchrone.



**Source locale :** docs/benchmark-reference-2026-09-17.md et benchmark/results/reference-20260917.json.
-->

---
---

<div class="eyebrow">Bilan du service HTTP · compromis d’exploitation</div>

# Les responsabilités de notre façade métier

| Risque | Discipline |
| --- | --- |
| Tous les domaines couplés au même hub | Frontières métier et ownership explicites |
| Backends lents ou indisponibles | Budgets de temps, traduction des erreurs, traces |
| Contrat qui dérive silencieusement | Tests publics et revue de compatibilité |
| Optimisation plus chère que le problème | Mesure du coût total et possibilité de revenir en arrière |

<p class="small">La façade porte le contrat et sa traduction. Ces exigences de production dépassent la preuve du labo.</p>

<!--
**Slide 48 · 31:15–32:15 · 60 s**

### Le message de cette slide

Expliquer deux risques concrets pour éviter le hub universel.

### À dire

Cette façade devient une responsabilité à exploiter. Si un moteur ralentit, nous devons borner l’attente et rendre une erreur compréhensible. Si chaque service change et oblige la façade à changer avec lui, nous avons un problème de coordination. Je garderais donc des frontières métier explicites, plutôt qu’un hub qui connaît toute l’entreprise. API Platform est intéressant parce que nous exposons des ressources et leurs descriptions, pas seulement parce que nous relayons des octets. Le labo montre le raccord, pas toutes les garanties de production.

### Appui visuel / conduite

Choisir les lignes backend indisponible et domaines couplés. Ne pas réciter les quatre.

### Transition vers la suite

Avant de quitter le service distant, notre contrat public impose-t-il forcément HTTP/JSON derrière ?

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Un budget de temps est une limite d’exécution de bout en bout à répartir entre les dépendances. Le backpressure protège le système contre une demande supérieure à sa capacité. Un timeout interrompt l’attente, pas nécessairement le traitement distant.


### Arbitrages

Un cache peut aider certaines lectures, mais il faut définir la fraîcheur acceptable. Rejouer une ancienne décision de risque sans modèle de validité peut être incorrect. Les retries sont liés aux effets de l’opération et à l’idempotence.


### Limite du laboratoire

Il ne démontre pas la sécurité de production, la conformité, le comportement sous saturation ou tous les scénarios de défaillance. Ces limites ne disqualifient pas le pattern ; elles bornent ce que le talk prouve.


### Pourquoi API Platform ici ?

« C’est pour cette responsabilité que je choisis API Platform. Si je ne fais que relayer des octets, un proxy peut suffire. Si je porte un modèle public, des opérations, des représentations et leur traduction, le framework devient intéressant. Mais je ne veux pas non plus un hub qui connaît toute l’entreprise et bloque chaque équipe. »


### Pour comprendre

Une gateway traite souvent routage, accès ou trafic. Une façade métier traduit un modèle pour ses consommateurs. Ces rôles peuvent coexister dans une architecture sans devoir être le même composant.


### Réponse au superlatif de l’abstract

« La meilleure gateway » est une accroche. La proposition défendable est : un très bon candidat pour une façade de ressources dans notre contexte PHP, quand la publication du contrat justifie son coût.


### Coûts à reconnaître

Code de mapping, latence, exploitation, ownership et coordination des versions. Pour une seule intégration simple, un client et un contrôleur peuvent être suffisants. Un hub par domaine cohérent évite de centraliser tous les modèles privés.
-->

---
class: authorization-code grpc-option
---

<div class="eyebrow">Variante du cas 2 · non implémentée dans le labo</div>

# Et si le contrat privé était gRPC ?

<div class="split">
<div>
<div class="label">risk.proto · exemple minimal, champs abrégés</div>

```proto
syntax = "proto3";
package risk.v1;

service RiskService {
  rpc Assess(AssessRequest) returns (Assessment);
}
message AssessRequest {
  int64 amount_minor = 1;
  string currency = 2;
  // … autres données du risque
}
message Assessment {
  int32 risk_score = 1; // échelle 0–100
  string status = 2;
  string reason = 3;
}
```

</div>
<div>
<p><strong>Générer le raccord</strong><br>Un client PHP et des messages typés.<br>En Go, le code serveur à implémenter.</p>
<p><strong>Adapter le résultat</strong><br>Notre adaptateur construit toujours <code>RiskAssessment</code>.</p>
<p><strong>Garder la même API publique</strong><br>Les mêmes tests de contrat s’appliquent. Le réseau et ses pannes restent.</p>
</div>
</div>

<p class="sub">HTTP/JSON avec Jane ou gRPC/Protobuf : <strong>le consommateur public garde ses repères.</strong></p>

<!--
**Slide 49 · 32:15–33:00 · 45 s**

### Le message de cette slide

Le choix du protocole privé ne dicte pas le contrat public. Une variante du cas distant, pas une quatrième démo.

### À dire

Nous aurions aussi pu utiliser gRPC entre PHP et Go. Ce fichier proto décrit une opération d’évaluation et ses messages. À partir de lui, nous générerions le client PHP et le raccord serveur Go, puis nous brancherions notre même bibliothèque de calcul. Cela remplacerait Jane et le JSON pour cet appel privé. Mais la génération ne connaît pas nos règles métier : notre adaptateur devrait toujours convertir et vérifier le résultat. Le client public continuerait d’appeler la même API Platform. Nous ne l’avons pas implémenté dans le labo, et nous ne lui attribuons aucun gain mesuré.

### Appui visuel / conduite

Pointer Assess, puis les deux messages. Les numéros sont les identifiants des champs Protobuf, pas leur ordre d’exécution. Passer ensuite au bloc adaptateur et au contrat public. Ne pas ouvrir une parenthèse sur tous les transports ou lire le fichier ligne par ligne.

### Transition vers la suite

Avec gRPC, le calcul resterait dans un service distant. Dans notre troisième cas, nous allons supprimer cette frontière réseau et embarquer Go dans FrankenPHP.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

Le proto affiché est une proposition minimale illustrative, pas un fichier du dépôt ni un contrat complet. Les autres champs d’entrée sont omis. En production, préciser la présence des valeurs, les enums et leur valeur par défaut, les règles d’évolution des numéros de champ et les limites métier. Un int32 ne garantit pas une plage 0–100. La génération des messages ne remplace ni cette validation ni la conversion des unités et des décisions.

La chaîne officielle utilise protoc, le générateur PHP des messages et le plugin gRPC pour le client, avec le runtime PHP gRPC. En Go, protoc-gen-go et protoc-gen-go-grpc génèrent messages et raccords. Le serveur Go implémenterait Assess en appelant risk.Assess, et un adaptateur PHP traduirait la réponse en RiskAssessment. Ce raccord n’a pas été compilé ni testé dans notre labo. Le support PHP officiel documenté est côté client, ce qui suffit ici puisque le serveur serait en Go.

gRPC utilise généralement HTTP/2 et Protobuf : il ne supprime pas HTTP sous-jacent ni le coût réseau. Délais, authentification, erreurs et compatibilité restent à traiter. Il faudrait traduire les erreurs gRPC vers notre contrat public et repasser les mêmes assertions avant d’affirmer l’équivalence. Aucune mesure gRPC dans nos tableaux.

Sources : [gRPC PHP](https://grpc.io/docs/languages/php/basics/), [gRPC Go](https://grpc.io/docs/languages/go/basics/), [principes de gRPC](https://grpc.io/docs/what-is-grpc/core-concepts/).
-->

---
class: chapter chapter-go
---

<div class="chapter-index">CAS 3 / 3</div>

# Go dans<br>FrankenPHP

<p class="chapter-sub">Le même contrat public, une autre exécution</p>

<!--
**Slide 50 · 33:00–33:15 · 15 s**

### Le message de cette slide

Nommer explicitement le mécanisme de FrankenPHP.

### À dire

Troisième cas : nous utilisons le générateur d’extensions de FrankenPHP pour rendre notre calcul Go appelable comme une fonction PHP native.

### Appui visuel / conduite

Pause de chapitre. Mettre l’accent sur extension PHP.

### Transition vers la suite

Voici ce que cela change dans le schéma.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

Aucun complément nécessaire pour cet intercalaire.
-->

---
---

# Le calcul Go rejoint le processus PHP

<ExecutionDiagram variant="native" />

<p class="sub">Par rapport au service HTTP : <strong>une fonction d’extension appelle Go.</strong><br>Pas de réseau pour ce calcul. L’application et le module se déploient ensemble.</p>

<!--
**Slide 51 · 33:15–33:50 · 35 s**

### Le message de cette slide

Montrer même serveur, autre frontière d’appel.

### À dire

FrankenPHP servait déjà notre application dans les autres cas. Nous ne changeons donc pas de serveur pour obtenir artificiellement un gain. Cette fois, nous ajoutons notre extension au binaire. PHP appelle sa fonction, qui exécute le calcul Go dans le même processus. L’appel HTTP interne disparaît. En échange, l’application et le calcul embarqué partagent leur déploiement. Le consommateur continue à appeler la même API publique.

### Appui visuel / conduite

Pointer le moteur désormais dans le grand cadre. Rappeler une seule fois le worker commun.

### Transition vers la suite

Nous conservons le calcul Go déjà utilisé par le service.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

FrankenPHP est un serveur d’applications PHP construit avec Go et Caddy. Une extension peut exposer une fonction PHP dont l’implémentation appelle du Go. Le dépôt réutilise le package go/risk pour le service HTTP et le chemin natif.

Le mode worker et l’extension native sont deux sujets distincts. Le premier conserve l’application PHP en mémoire entre requêtes. La seconde fournit un point d’appel vers le code Go. Ne pas expliquer le gain de l’un avec le mécanisme de l’autre.


### Limite

Cette capacité est documentée upstream. Cela ne certifie ni notre adaptateur, ni le binaire construit, ni sa tenue sous charge. Ne pas employer « production-ready » sur la seule base de cette démo.

**Source :** [Extensions Go dans FrankenPHP](https://frankenphp.dev/docs/extensions/).
-->

---
class: authorization-code
---

# La bibliothèque Go reste la même

<div class="flow"><div>Service HTTP<br><span class="small">go/internal/riskhttp</span></div><span>→</span><div class="go">go/risk<br><span class="small">risk.Assess(input, profile)</span></div><span>←</span><div>Extension PHP<br><span class="small">go/ext</span></div></div>

<div class="split"><div>
<div class="label">Notre code métier</div>
<p class="sub"><strong>go/risk</strong> calcule une évaluation.<br>Ce package ne connaît ni HTTP, ni Symfony, ni les types PHP.</p>
</div><div>
<div class="label">L’outillage fourni par FrankenPHP</div>
<p class="sub"><strong>Générateur intégré : <code>extension-init</code></strong><br>Le package FrankenPHP fournit les conversions PHP ↔ Go.</p>
</div></div>
<p class="sub">Nous écrivons l’adaptateur. <strong>FrankenPHP génère la liaison.</strong><br>Aucun plugin tiers à installer. API Platform et BankService restent en PHP.</p>

<!--
**Slide 52 · 33:50–34:30 · 40 s**

### Le message de cette slide

Distinguer notre bibliothèque métier et l’outillage FrankenPHP.

### À dire

Au centre, go/risk est la bibliothèque que notre service HTTP appelle déjà. Nous écrivons un second point d’entrée autour de ce même calcul : notre extension PHP. Pour la créer, aucun plugin tiers n’est nécessaire. FrankenPHP fournit son générateur extension-init et les fonctions de conversion entre PHP et Go. Nous écrivons l’adaptateur, le générateur produit le code de liaison. Il faudra ensuite compiler les deux dans notre serveur. La bibliothèque métier reste inchangée.

### Appui visuel / conduite

Pointer le package central puis les deux appelants. Éviter de lire les chemins complets.

### Transition vers la suite

Suivons les données pendant l’appel.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Les outils
go/risk est notre bibliothèque métier. Le package FrankenPHP fournit GoMap, PHPMap et l’intégration avec PHP. CGO est le mécanisme d’interopérabilité Go/C utilisé par ce raccord, pas un serveur supplémentaire. Le module go/ext est séparé du cœur : il nécessite les headers PHP et CGO, contrairement aux tests du calcul. Aucune goroutine supplémentaire n’est nécessaire pour cet appel synchrone.

Sources : go/risk/engine.go ; go/internal/riskhttp/handler.go ; go/ext/risk.go ; go/ext/go.mod.
-->

---
---

# Zoom sur l’appel natif du risque

<RiskBoundaryZoom native />

<p class="sub">Même bibliothèque Go que le service HTTP. <strong>Un autre point d’entrée, sans le transport HTTP.</strong></p>
<p class="small">Avant de servir : générer l’extension → compiler FrankenPHP.<br>Pendant la requête : appeler la fonction déjà chargée.</p>

<!--
**Slide 53 · 34:30–35:05 · 35 s**

### Le message de cette slide

Faire comprendre l’aller-retour des données dans un même processus.

### À dire

PHP prépare un tableau. La fonction native lit ces valeurs, appelle la bibliothèque Go et prépare un tableau de résultat pour PHP. Notre adaptateur vérifie ce résultat et reconstruit l’évaluation que BankService attend. Tout ce trajet est dans le même processus. Il reste des conversions, mais plus de transport HTTP interne. La génération et la compilation ont eu lieu avant le démarrage, pas pendant cette requête.

### Appui visuel / conduite

Suivre le tableau d’entrée, le calcul, puis le retour. Le pointillé ramène un résultat.

### Transition vers la suite

Voici l’appel PHP et le code Go qu’il déclenche.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

La bibliothèque go/risk est partagée avec le serveur Go HTTP. Ce n’est pas une application Go externe lancée par exec, ni un serveur HTTP caché. La génération de l’extension et la compilation sont des étapes de construction du binaire, pas des opérations par requête. Le mode worker conserve l’application PHP chaude, mais n’est pas ce qui expose cette fonction Go.

Les conversions sont toujours présentes. La flèche de retour est un résultat ; la décision bancaire, la réservation et MongoDB restent derrière BankService comme dans les autres cas.

**Source :** [Extensions Go de FrankenPHP](https://frankenphp.dev/docs/extensions/).
-->

---
class: authorization-code
---

# De la fonction PHP à la bibliothèque Go

<div class="split"><div class="compact">
<div class="label">1 · NativeGoRiskEngine::assess()</div>

~~~php
// … présence de l’extension vérifiée
$raw = \BoundaryLab\Native\assess([
    'amountMinor' => $input->amount->minorUnits,
    'currency' => $input->amount->currency->code,
    // … autres données de risque
    'profile' => $profile->value,
]);
// … identité du moteur contrôlée
$assessment = $this->toDomain($raw);
// … mesure de l’appel natif
return $assessment;
~~~

</div><div class="compact">
<div class="label">2 · go/ext/risk.go appelle le calcul partagé</div>

~~~go
// export_php:function assess(array $input): array
func assess(arr *C.zend_array) unsafe.Pointer {
    in, err := frankenphp.GoMap[any](
        unsafe.Pointer(arr))
    // … vérifier err
    input, profile, problem := decode(in)
    // … vérifier problem
    assessment := risk.Assess(input, profile)
    return frankenphp.PHPMap(map[string]any{
        "riskScore": assessment.Score,
        // … statut, motif et moteur
    })
}
~~~

</div></div>
<p class="small">// … : extraits abrégés. Le résultat reste validé côté PHP avant de revenir à BankService.</p>

<!--
**Slide 54 · 35:05–36:05 · 60 s**

### Le message de cette slide

Identifier BoundaryLab et expliquer le raccord sans mystère.

### À dire

BoundaryLab Native est simplement le namespace que nous avons choisi pour notre extension. Ce n’est pas une bibliothèque supplémentaire à installer. À gauche, nous appelons sa fonction assess avec un tableau PHP. À droite, GoMap permet de lire ce tableau en Go, puis decode prépare les données de notre calcul. Risk Assess réalise le travail métier. PHPMap prépare le retour en tableau PHP. Enfin, à gauche, toDomain vérifie les valeurs et construit RiskAssessment. Les types C visibles appartiennent au raccord, pas aux règles de risque.

### Appui visuel / conduite

À gauche assess, à droite GoMap puis risk.Assess et PHPMap, retour à toDomain.

### Transition vers la suite

Comment PHP connaît-il cette fonction ? C’est le rôle du générateur.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre sans lire tout le code

L’annotation export_php fournit au générateur la signature exposée à PHP. L’espace de noms BoundaryLab\Native est défini en tête du fichier Go, non montré ici. Le générateur produit les raccords natifs et le stub PHP avant compilation. Nous n’avons pas compilé l’API Symfony en Go : le PHP applicatif reste du PHP. Seul ce point d’entrée vers la bibliothèque est exposé.

GoMap et PHPMap convertissent les valeurs ; ce n’est pas zéro copie. Les types C et unsafe.Pointer appartiennent au raccord natif, pas au métier du risque. Un contrat array vers array ne décrit pas toutes les clés : le code vérifie les champs, le statut, le motif et la plage du score. Les branches d’erreur, les autres champs et la mesure sont omis uniquement pour la lecture. La validation des erreurs retournées par Go se fait aussi dans toDomain.


### Sources

api/src/Engine/Native/NativeGoRiskEngine.php ; go/ext/risk.go ; package go/risk.
[Générateur d’extensions FrankenPHP](https://frankenphp.dev/docs/extensions/).
-->

---
class: authorization-code
---

# Déclarer la fonction exposée à PHP

<div class="split"><div>
<div class="label">go/ext/risk.go · notre source</div>

~~~go
// export_php:namespace BoundaryLab\Native
package ext
// … imports et conversions
// export_php:function assess(array $input): array
func assess(arr *C.zend_array) unsafe.Pointer {
    // … calcul et retour avec PHPMap
}
~~~

<p class="sub"><code>export_php:function</code> indique au générateur<br>la signature à exposer côté PHP.</p>
</div><div>
<div class="label">Code de liaison produit par FrankenPHP</div>
<p class="sub"><strong>risk_generated.go</strong><br>Raccord Go et enregistrement de l’extension.</p>
<p class="sub"><strong>risk.c / risk.h / risk_arginfo.h</strong><br>Raccord PHP et types des arguments.</p>
<p class="sub"><strong>risk.stub.php</strong><br>Signature PHP pour l’outillage.</p>
</div></div>

~~~sh
# 1. Générer la liaison depuis go/ext
GEN_STUB_SCRIPT=/usr/local/lib/php/build/gen_stub.php \
  frankenphp extension-init risk.go
~~~

<!--
**Slide 55 · 36:05–37:05 · 60 s**

### Le message de cette slide

Expliquer annotations, génération et stub.

### À dire

Nous écrivons notre fonction Go et ces commentaires que le générateur sait lire. Namespace choisit le nom PHP de notre extension. Export PHP function décrit la signature : ici, un tableau en entrée et en sortie. En bas, extension-init lit ce fichier et produit les liaisons Go et C, ainsi que le stub PHP pour les outils. La variable GEN_STUB_SCRIPT désigne un outil fourni par PHP, déjà présent dans notre image de construction. À ce stade, les fichiers sont générés, mais aucun nouveau serveur n’a encore été compilé.

### Appui visuel / conduite

Pointer namespace, signature, puis fichiers générés et commande. Ne pas lire les noms de tous les headers.

### Transition vers la suite

Voyons comment notre construction ajoute cette extension à FrankenPHP.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Version et portée
Le labo utilise FrankenPHP v1.12.7 et l’image 1.12.7-builder-php8.5. gen_stub.php est fourni dans cette image. La commande provient de build/api.Dockerfile. L’extrait Go est abrégé, non autonome. Le générateur est l’approche recommandée par la documentation consultée. Notre raccord utilise explicitement les types Zend/C et les conversions FrankenPHP. array vers array ne définit ni les clés ni leurs règles métier.

Sources : build/api.Dockerfile ; go/ext/risk.go ; [extensions FrankenPHP](https://frankenphp.dev/docs/extensions/).
-->

---
class: authorization-code native-build
---

# Intégrer l’extension au binaire FrankenPHP

<div class="label">build/frankenphp/main.go · imports utiles au raccord</div>

~~~go
import (
    // … commande et modules standard Caddy
    _ "github.com/dunglas/frankenphp/caddy"
    _ "github.com/matthieuwerner/slides-le-hub-semantique-qu-on-merite-apip-2026/go/ext"
)
~~~

<p class="sub">L’import inclut notre extension. Au démarrage, elle enregistre la fonction PHP.</p>
<div class="flow"><div>Image builder<br><span class="small">CGO + headers PHP</span></div><span>→</span><div>go build<br><span class="small">Serveur + extension</span></div><span>→</span><div>Image d’exécution<br><span class="small">Binaire FrankenPHP construit</span></div></div>
<p class="small"><strong>2. Compiler :</strong> <code>go build</code> assemble FrankenPHP et notre extension.<br>Options CGO et PHP dans build/api.Dockerfile. Une modification Go impose un nouveau build.</p>

<!--
**Slide 56 · 37:05–38:05 · 60 s**

### Le message de cette slide

Expliquer l’intégration au binaire et le coût de livraison.

### À dire

Après la génération, voici la compilation. L’import avec un underscore inclut notre extension dans le programme. Au démarrage du binaire, son initialisation enregistre la fonction auprès de PHP. Notre Dockerfile utilise go build avec CGO et les en-têtes PHP pour construire FrankenPHP avec cette extension. L’image finale reçoit ce binaire. Symfony reste du PHP. Si nous modifions le Go embarqué, nous reconstruisons l’image et remplaçons les instances qui tournent avec l’ancien binaire.

### Appui visuel / conduite

Pointer l’import de go/ext, puis suivre builder, compilation et runtime.

### Transition vers la suite

Avant de livrer cette image, nous vérifions que la fonction est réellement appelable.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Construction réelle
CGO relie Go au raccord C et aux bibliothèques PHP. php-config fournit leurs chemins et options. Le Dockerfile compile dans une image builder puis copie le binaire dans l’image runtime. Symfony reste du PHP. Ce chemin ne charge pas un .so par extension= dans php.ini et ne consiste pas à configurer une route Caddy.

La documentation montre xcaddy build --with. Notre labo emploie un main explicite et go build avec go.mod/go.sum. Ne pas prétendre que xcaddy est exécuté ici. Les difficultés locales de construction ne sont pas une limitation universelle de xcaddy. make build orchestre les images avant la présentation. Modifier le Go embarqué impose une reconstruction et un redéploiement. Le mode worker, commun aux trois cas, est indépendant de cette extension.


### Commande exacte du Dockerfile

CGO_ENABLED=1 est défini dans l’image builder. Depuis build/frankenphp :

```sh
CGO_CFLAGS="-D_GNU_SOURCE $(php-config --includes)" \
CGO_LDFLAGS="$(php-config --ldflags) $(php-config --libs)" \
go build -tags=nobadger,nomysql,nopgx,nomercure,nowatcher \
  -ldflags="-w -s" -o /out/frankenphp .
```

Sources : build/frankenphp/main.go ; build/api.Dockerfile ; Makefile ; [extensions FrankenPHP](https://frankenphp.dev/docs/extensions/).
-->

---
class: authorization-code native-build
---

# Vérifier l’extension avant de démarrer l’API

<div class="split"><div>
<div class="label">build/verify-extension.php · extrait abrégé</div>

~~~php
// … tester la présence de l’extension et de la fonction
// extension_loaded('risk')
// function_exists('BoundaryLab\\Native\\assess')
$verdict = \BoundaryLab\Native\assess([
    'amountMinor' => 42069,
    'currency' => 'EUR',
    // … scénario de référence complet
]);
// … vérifier 12 / approved / HIGH_AMOUNT
// … exit(1) si une vérification échoue
~~~

</div><div>
<div class="label">3. Vérifier le binaire construit</div>

~~~sh
/out/frankenphp php-cli /tmp/verify-extension.php
~~~

<p class="sub">Extension chargée.<br>Fonction disponible.<br>Résultat de référence conforme.</p>
<p class="small">Un échec interrompt la construction de l’image.</p>
</div></div>
<p class="sub">Puis déployer la nouvelle image et redémarrer les instances.<br><strong>Pas de chargement à chaud.</strong> La démo vérifie ensuite le cycle public.</p>

<!--
**Slide 57 · 38:05–38:35 · 30 s**

### Le message de cette slide

Expliquer le test du raccord, distinct du test de toute l’API.

### À dire

Notre build lance le nouveau binaire en mode PHP CLI. Le script vérifie que l’extension et la fonction existent, puis appelle le calcul sur un exemple connu. Si le résultat est incorrect, la construction s’arrête. Après ce contrôle, nous pouvons déployer l’image et démarrer de nouvelles instances avec ce binaire. L’ancien serveur ne charge pas l’extension à la volée. La démo complétera ce test du raccord par le cycle de paiement public.

### Appui visuel / conduite

Pointer l’appel PHP puis la commande utilisant le binaire construit.

### Transition vers la suite

Ce mécanisme retire certains coûts, mais il en ajoute aussi.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Portée
L’extrait abrège les conditions, les données et la collecte des erreurs. Le fichier réel est build/verify-extension.php. Ce contrôle ne prouve ni la sécurité mémoire ni la tenue en concurrence. Les tests métier et le cycle complet restent nécessaires.

Sources : build/verify-extension.php ; build/api.Dockerfile.
-->

---
---

<div class="eyebrow">Coût de la frontière native</div>

# Ce que l’appel local économise

| Ce qui change | Ce qui reste à gérer |
| --- | --- |
| Plus de transport HTTP ni de JSON pour le risque | Conversion des valeurs PHP ↔ Go |
| Code Go chargé dans FrankenPHP | Compilation, versions, packaging |
| Déploiement regroupé | Couplage de release et domaine de panne partagé |

<p class="sub"><strong>Un gain potentiel sur le transport.</strong><br>Le gain sur l’autorisation complète reste à mesurer.</p>

<!--
**Slide 58 · 38:35–39:10 · 35 s**

### Le message de cette slide

Mettre gains potentiels et contraintes sur le même plan.

### À dire

Nous supprimons le transport HTTP interne et le JSON du risque. Nous gardons les conversions entre PHP et Go ainsi que les validations. Nous supprimons aussi un service séparé à exploiter, mais nous devons construire un binaire spécifique et livrer les deux ensemble. Ils partagent davantage leurs risques de panne. C’est un échange de contraintes, pas une optimisation gratuite. Seules des mesures peuvent dire si cela vaut le coût dans un cas réel.

### Appui visuel / conduite

Lire une paire de colonnes à la fois : transport/conversion puis déploiement/couplage.

### Transition vers la suite

Pour la démonstration, nous allons d’abord prouver le chemin emprunté et son résultat.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Le marshalling est la conversion des valeurs à travers une frontière technique. Dans ce dépôt, on échange des tableaux et des scalaires. Un stub décrit la signature PHP d’une fonction. Une signature array → array ne garantit pas, à elle seule, les noms et le sens des clés.

Les array shapes PHPStan précisent les clés et types attendus ; celles du dépôt sont maintenues séparément. Le contrôle statique et la validation à l’exécution sont complémentaires.


### Objections probables

« Même processus veut-il dire zéro timeout ? » Il n’y a plus de timeout réseau sur cet appel, mais il reste un budget d’exécution et des scénarios de blocage. « Une erreur native tue forcément tout ? » Ne pas généraliser : distinguer erreur traitée, exception PHP, panic récupérée ou crash natif. La surface de panne reste plus couplée.


### Origine du gain

Le calcul Go est identique à celui du service HTTP. Nous supprimons le transport HTTP interne et son JSON, mais conservons GoMap, PHPMap et les validations. Ce n’est pas zéro copie. HTTP public et MongoDB restent présents. Le mode worker est commun aux trois cas. Les règles simples ne démontrent pas de gain utile face au PHP local.

**Source :** [Documentation des extensions](https://frankenphp.dev/docs/extensions/).
-->

---
---

# Avant la démo : vérifier le chemin natif

<div class="flow"><div>Nouvelle demande<br><span class="small">Aucun résultat rejoué</span></div><span>→</span><div class="go">Appel natif<br><span class="small">Trace après retour du calcul</span></div><span>→</span><div>Cycle complet<br><span class="small">Mêmes effets sur le compte</span></div></div>

<p><strong>Deux vérifications distinctes :</strong></p>

- Le marqueur <code>native_call</code> atteste le passage instrumenté dans l’extension.
- Les assertions vérifient la décision, la réservation et le clearing.

<p class="sub">Une fonction appelée ne prouve pas à elle seule un résultat correct.</p>

<!--
**Slide 59 · 39:10–39:35 · 25 s**

### Le message de cette slide

Séparer preuve du chemin natif et preuve du comportement.

### À dire

Une réponse correcte ne prouve pas que le calcul Go a été appelé : nous pourrions avoir rejoué un ancien résultat. Nous utilisons donc une commande neuve et un marqueur ajouté après l’appel natif validé. Ce marqueur indique le chemin instrumenté. Ensuite, les assertions vérifient la décision et les effets sur le compte. Les deux contrôles se complètent : un chemin utilisé ne suffit pas à prouver que son résultat est correct.

### Appui visuel / conduite

Pointer native_call puis les assertions. Une commande neuve évite de mesurer le rejeu.

### Transition vers la suite

Lançons le même paiement par ce chemin natif.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

make proof-cycle exécute les quatre cycles contre MongoDB et des appels concurrents de réservation, d’autorisation dupliquée et de clearing dupliqué. make proof-public compare représentations, exports, Provider et versions privées. make parity-test compare le corpus de risque. L’identité est stable et relisible : on valide accountId, authorizationId et @id, puis on les retire seulement pour comparer deux scénarios distincts. Le reste reste comparé. Les tests ne prouvent ni résilience prolongée, ni conformité bancaire, ni capacité de production.
-->

---
class: demo-break
---

<div class="demo-system">Go embarqué dans FrankenPHP</div>

# DÉMO 03

<p class="demo-title">Appeler Go sans le service HTTP</p>
<p class="demo-expectation">Voir l’appel natif, puis vérifier le même paiement.<br>Le gain de performance reste une question de mesure.</p>

<!--
**Slide 60 · 39:35–41:35 · 120 s**

### Le message de cette slide

Conduire la preuve native avant toute discussion de vitesse.

### À dire

Je lance le scénario avec une demande neuve. Regardons le marqueur de l’appel natif, puis revenons tout de suite au résultat métier. Nous vérifions la décision, la réservation, la comptabilisation et le rejeu comme dans le premier cas. Afficher seulement une version de Go n’aurait pas suffi : FrankenPHP est déjà écrit en Go. Ce qui nous intéresse est le passage par notre fonction pour cette demande, puis les effets attendus.

### Appui visuel / conduite

Lancer make demo-go-native. Montrer native_call dans les en-têtes archivés, puis décision, soldes et rejeu.

### Transition vers la suite

Faisons le bilan de ce qui a été vérifié, avant de regarder les temps.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Conduite de la démo

Afficher côte à côte la requête de référence et les champs de décision. Basculer le moteur avec l’instrumentation du laboratoire. Montrer un marqueur corrélé au vrai calcul natif. Revenir immédiatement aux mêmes assertions publiques. Lancer make demo-go-native. Le marqueur native_call est ajouté après retour et validation de BoundaryLab.Native.assess. Cette démo ne simule pas un crash natif.


### Ce qui ne prouve pas le calcul

Une version de Go ou un compteur de goroutines démontre la présence d’un runtime Go, pas que cette décision l’a utilisé. FrankenPHP est déjà écrit en Go. Un nom de moteur placé dans une configuration n’est pas davantage une preuve autonome.


### Pour comprendre

La corrélation relie un appel public à sa trace interne. Elle facilite le diagnostic, sans constituer une démonstration mathématique de correction. Les tests du résultat et du contrat restent nécessaires.


### Repli

La séquence et sa capture existent dans benchmark/results/cycle-go-native-*. En cas de panne live, basculer sur une trace enregistrée clairement annoncée.
-->

---
---

# Bilan : Go tourne dans notre processus

<div class="demo-takeaway">
<div><span>Vérification</span><p>La trace montre le chemin natif.<br>Le cycle conserve la décision et les effets attendus.</p></div>
<div><span>Ce qu’on en tire</span><p><strong>Le calcul ne passe plus par HTTP.</strong><br>Le module et l’application partagent leur déploiement.</p></div>
</div>

<p class="sub">La démo valide le parcours fonctionnel. Elle ne démontre aucun gain de vitesse.</p>

<!--
**Slide 61 · 41:35–41:50 · 15 s**

### Le message de cette slide

Clore la preuve fonctionnelle native.

### À dire

Nous avons vérifié le passage instrumenté dans l’extension et le même comportement attendu sur le compte. Ce calcul ne passe plus par le service HTTP. L’appel public et MongoDB restent présents. En revanche, notre module Go partage maintenant le processus et le déploiement de l’application. Cette démonstration vérifie le fonctionnement, pas un gain de vitesse.

### Appui visuel / conduite

Après succès seulement, pointer le chemin et les effets. Ne pas lire les résultats de performance ici.

### Transition vers la suite

Pour la vitesse, retrouvons notre benchmark séparé.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

Aucun complément nécessaire pour cet intercalaire.
-->

---
---

<div class="eyebrow">Bilan du cas 3 · autorisation avec MongoDB · RULES</div>

# Le tableau des trois approches

| Approche | Médiane API | Plage entre campagnes |
| --- | ---: | ---: |
| PHP local | 2,81 ms | 1,61–3,88 ms |
| Go HTTP | 3,89 ms | 2,07–5,41 ms |
| Go natif | 2,76 ms | 1,80–4,60 ms |

<p class="sub">Ces règles simples ne justifient pas à elles seules le natif.</p>
<p class="small"><strong>A : avant l’appel HTTP → B : corps de réponse entièrement reçu.</strong><br>Client de benchmark dans Docker, serveur déjà démarré et préchauffé.<br>5 campagnes × 4 répétitions × 200 appels · médiane des p50 et plage min–max.</p>

<!--
**Slide 62 · 41:50–42:05 · 15 s**

### Le message de cette slide

Éviter de présenter un minuscule écart comme la justification du natif.

### À dire

Le tableau est complet. PHP local et Go natif sont très proches sur ces petites règles, avec une dispersion importante. Je ne vais pas défendre un build spécifique pour cet écart. Ces chiffres mesurent le coût observé de nos choix, pas la qualité de notre contrat et pas une hiérarchie universelle des langages. Le paiement a surtout servi à vérifier le comportement. Pour explorer une raison de déplacer du calcul, nous pouvons maintenant augmenter le travail demandé au moteur.

### Appui visuel / conduite

Comparer PHP et natif, puis leurs plages. Ne pas annoncer de pourcentage de gain.

### Transition vers la suite

Voici cette expérience complémentaire, distincte de la démo fonctionnelle.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

Source : docs/benchmark-reference-2026-09-17.md et benchmark/results/reference-20260917.json.
Les chiffres mesurent uniquement le POST d’autorisation complète, pas le cycle avec clearing.
-->

---
---

<div class="eyebrow">Benchmark complémentaire · même périmètre HTTP public</div>

# Faire varier le travail du moteur

<div class="split"><div>
<div class="label">Démonstrations fonctionnelles</div>
<p class="sub"><strong>RULES</strong><br>Quelques règles déterministes.<br>Le paiement vérifie le contrat.</p>
</div><div>
<div class="label">Expérience de calcul</div>
<p class="sub"><strong>ENSEMBLE</strong><br>256 puis 4 096 arbres de décision synthétiques.<br>Chaque arbre parcourt six comparaisons.</p>
</div></div>
<div class="flow"><div>Mêmes entrées</div><span>→</span><div>Plus d’arbres à évaluer</div><span>→</span><div>Score agrégé</div></div>
<p class="small">Pour chaque taille : même modèle en PHP et en Go, préchauffé avant mesure.<br>Charge synthétique déterministe, sans entraînement ni validation d’un modèle de fraude réel.</p>

<!--
**Slide 63 · 42:05–42:40 · 35 s**

### Le message de cette slide

Introduire la charge lourde sans faire croire à un vrai modèle de fraude.

### À dire

Les règles du paiement calculent peu. Pour explorer une charge plus lourde, nous utilisons des arbres de décision synthétiques : chacun effectue une suite de comparaisons, puis nous agrégeons les résultats. Nous passons de deux cent cinquante-six à quatre mille quatre-vingt-seize arbres. Pour chaque taille, PHP et Go évaluent le même modèle préchauffé. Nous mesurons toujours l’autorisation complète. Ce n’est ni un modèle entraîné sur de la fraude ni une preuve de qualité métier.

### Appui visuel / conduite

Comparer RULES et ENSEMBLE, puis les deux tailles. Ne pas expliquer un algorithme d’apprentissage.

### Transition vers la suite

Regardons ce que cette charge change dans les mesures.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Méthode
Le modèle est déterministe, généré avec une graine fixe et préchauffé. Chaque arbre a six niveaux internes. Les moteurs reçoivent le même nombre d’arbres. Changer ce nombre change le modèle expérimental : nous ne prétendons pas conserver la décision de RULES ou celle d’une autre taille. La parité attendue concerne les moteurs à profil et taille identiques. Aucun sleep ni démarrage du modèle dans les appels mesurés. Cette charge sert à explorer le compromis technique, pas à revendiquer une qualité de détection de fraude.

Sources : go/risk/ensemble.go ; docs/risk-model.md ; docs/benchmark-reference-2026-09-17.md.
-->

---
---

<div class="eyebrow">Charge synthétique · autorisation avec MongoDB</div>

# Quand le calcul devient plus lourd

| API avec MongoDB · médiane | PHP local | Go HTTP | Go natif |
| --- | ---: | ---: | ---: |
| ENSEMBLE, 256 arbres | 2,89 ms<br><span class="small">1,76–3,77</span> | 3,82 ms<br><span class="small">2,52–4,93</span> | 2,97 ms<br><span class="small">1,85–3,16</span> |
| ENSEMBLE, 4 096 arbres | 3,71 ms<br><span class="small">3,64–4,61</span> | 4,32 ms<br><span class="small">4,17–5,78</span> | 3,17 ms<br><span class="small">3,00–3,52</span> |

<p class="sub">À 4 096 arbres, le natif a la médiane la plus basse dans cette campagne.</p>
<p class="small"><strong>A : avant l’appel HTTP → B : corps de réponse entièrement reçu.</strong><br>Client de benchmark dans Docker, serveur déjà démarré et préchauffé.<br>5 campagnes × 4 répétitions × 200 appels · médiane des p50 et plage min–max.</p>

<!--
**Slide 64 · 42:40–43:25 · 45 s**

### Le message de cette slide

Interpréter une expérience locale sans promettre un gain universel.

### À dire

Avec la petite charge, PHP local et natif restent proches. Sur la ligne à quatre mille quatre-vingt-seize arbres, le natif présente la médiane la plus basse de nos campagnes. Les plages restent affichées pour ne pas cacher les variations. Nous parlons toujours du temps HTTP complet avec MongoDB, pas seulement de la fonction. Ce résultat donne une piste à profiler sur un besoin réel ; il ne suffit pas à choisir le natif pour toute application.

### Appui visuel / conduite

Comparer les moteurs sur une même ligne, puis regarder la seconde taille. Ne pas lire les neuf chiffres.

### Transition vers la suite

La décision dépend donc aussi de ce que notre équipe accepte d’exploiter.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre
Ces mesures portent sur les autorisations neuves avec contrôles et persistance MongoDB, pas sur le clearing. La dispersion du poste local reste importante. Les plages sont descriptives, pas des intervalles de confiance.

Chaque bloc contient 200 mesures après 40 appels de chauffe. Une campagne produit la médiane de quatre p50 de blocs, chaque moteur occupant chaque position une fois. Nous affichons la médiane des cinq résumés de campagne et leur minimum–maximum. Ce ne sont ni des percentiles fusionnés ni des intervalles de confiance. Aucun run de la série n’a été écarté. Les mesures détaillées sont dans docs/benchmark-reference-2026-09-17.md.


### Équipe et exploitation
Ces arbitrages sont une analyse architecturale, pas une mesure de vélocité ni un retour d’incident de production. Le conteneur API expose les quatre adaptateurs. Le moteur Go HTTP tourne dans un service séparé, contrairement au PHP local et au Go natif. Cette séparation permet une livraison indépendante, sans supprimer la dépendance synchrone.

**Source locale :** docs/benchmark-reference-2026-09-17.md et benchmark/results/reference-20260917.json.
-->

---
---



# Quel compromis pour notre équipe ?

| Approche | Ce qu’on gagne | Ce qu’on accepte |
| --- | --- | --- |
| PHP local | Simplicité, outillage et livraison communs | Calcul dans les workers PHP |
| PHP ou Go HTTP | Livraison et dimensionnement indépendants | Réseau, latence et exploitation d’un service |
| Go natif | Appel local à une bibliothèque Go | Build spécifique, livraison et panne partagées |

<p class="sub">PHP reste le point de départ.<br>HTTP répond à un besoin d’autonomie. Le natif exige un gain mesuré.</p>

<!--
**Slide 65 · 43:25–44:25 · 60 s**

### Le message de cette slide

Répondre à quand choisir chaque option.

### À dire

Je garde le PHP local lorsque le besoin est satisfait simplement, avec les outils et la livraison de l’équipe. Je choisis un service distant si son autonomie de livraison ou de dimensionnement justifie le réseau et l’exploitation supplémentaire. J’envisage le natif pour réutiliser un calcul Go dans le processus, si les mesures justifient son coût d’intégration. Aucune option n’est l’étape supérieure obligatoire. Notre contrat public nous permet surtout d’étudier ces choix sans les imposer aux consommateurs.

### Appui visuel / conduite

Une raison et une contrepartie par ligne, sans revenir aux décimales.

### Transition vers la suite

Revenons à la question posée au début.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Le coût total comprend infrastructure, packaging, observabilité, incidents, compétences et temps de livraison. Une optimisation qui économise du CPU mais complexifie fortement les déploiements peut être un mauvais choix produit.


### Objection

« Pourquoi ne pas tout écrire en Go ? » C’est une option. Ici, l’équipe et le contrat public s’appuient sur l’écosystème PHP. Le propos est de préserver cet investissement tout en ouvrant une marge de choix derrière.


### Subtilité

Le hub ne protège pas automatiquement de tout changement interne. Un nouveau comportement métier reste observable. Il permet de gouverner ce changement et de contenir les différences purement techniques.
-->

---
---

# Notre contrat public reste le point de repère

<FacadeComparison />

<p class="sub"><strong>Dans le labo :</strong> même paiement · V2 traduite · champs privés exclus.</p>
<p class="small">API Platform publie notre modèle. Nos adaptateurs portent la traduction et nos tests vérifient les engagements.</p>

<!--
**Slide 66 · 44:25–45:25 · 60 s**

### Le message de cette slide

Fermer la boucle sur la propriété du contrat, pas sur Go gagnant.

### À dire

Au départ, nous demandions qui devait absorber les différences entre services. Dans notre laboratoire, la façade a traduit le format privé du risque et choisi les propriétés publiques du marchand. Nous avons aussi vérifié les effets du paiement quand le moteur changeait. API Platform nous fournit les ressources, les descriptions et les points d’extension. Nos adaptateurs et nos tests portent le travail de compatibilité. Nous pouvons donc faire évoluer une partie de la stack tout en conservant des engagements publics maîtrisés.

### Appui visuel / conduite

Reprendre gauche/droite du schéma initial, puis pointer les trois preuves du labo. Ralentir.

### Transition vers la suite

Merci. Je vous propose de prendre vos questions.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Pour comprendre

Le schéma reprend les cinq domaines illustratifs de l’introduction. Le labo a démontré deux raccords distants, risque et profil marchand, et trois modes d’exécution du risque. Il ne démontre pas cinq microservices autonomes. Pointer les preuves écrites sous le schéma pour borner la conclusion aux résultats observés.
-->

---
---



# Merci !

<div class="finish-grid">
<div>
<p class="punch">Vos questions ?</p>
<p class="sub">Et dans votre système,<br>quel contrat voulez-vous préserver ?</p>
<p class="small">Slides, code des démos et benchmarks<br>dans le même dépôt.</p>
</div>
<a class="speaker-contact" href="https://github.com/matthieuwerner/slides-le-hub-semantique-qu-on-merite-apip-2026" target="_blank" rel="noopener noreferrer" aria-label="Retrouver les slides sur GitHub">
<img src="/qr-repo.svg" width="190" height="190" alt="QR code vers le dépôt GitHub des slides">
<span>Slides et démos<br>GitHub</span>
</a>
<a class="speaker-contact" href="https://www.linkedin.com/in/matthieu-werner-2427a5281/" target="_blank" rel="noopener noreferrer" aria-label="Profil LinkedIn de Matthieu Werner">
<img src="/qr-linkedin.svg" width="190" height="190" alt="QR code LinkedIn de Matthieu Werner">
<span>Matthieu Werner<br>LinkedIn</span>
</a>
</div>

<!--
**Slide 67 · 45:25–48:25 · 180 s**

### Le message de cette slide

Terminer vraiment et ouvrir les questions.

### À dire

Merci pour votre attention. Vous avez ici le dépôt qui réunit les slides, le code des démonstrations et les benchmarks, ainsi que mon contact LinkedIn. L’accès public dépend de l’ouverture du dépôt avant la conférence. Quelles questions avez-vous sur les contrats ou sur les choix d’exécution que nous venons de voir ?

### Appui visuel / conduite

Laisser les QR codes affichés. Ne promettre un accès public qu’après vérification de la visibilité du dépôt.

### Transition vers la suite

Laisser la salle répondre. Reformuler une question avant d’y répondre, sans ouvrir une nouvelle présentation.

### Réserve technique et sources

Ces compléments servent à préparer les questions. Le fil à prononcer est au-dessus. Les durées restent des repères à recalibrer en répétition.

### Ressources

Le QR GitHub mène au dépôt commun des slides, démos et benchmarks : https://github.com/matthieuwerner/slides-le-hub-semantique-qu-on-merite-apip-2026. Le QR LinkedIn conserve le profil du speaker. Prévoir l’accès public au dépôt avant la conférence, sans confondre disponibilité du lien et permission de lecture. Laisser cette slide affichée pendant les échanges, elle termine le diaporama.


### Animation des questions

Inviter à une question concrète sur le contrat, le mapping ou une frontière d’exécution. Reprendre la question en une phrase avant de répondre. Si elle dépend d’une version ou d’un scénario non testé, le dire.


### Réponses courtes à garder en tête

- **Pourquoi API Platform ?** Pour ses ressources, métadonnées et points d’extension dans notre contexte PHP. Pas parce qu’un contrôleur ou un service Go serait incapable de bien faire.
- **Pourquoi deux mappers dans les slides ?** JoliCode AutoMapper projette le profil marchand. Notre WireMapper traduit les unités et statuts du risque. Symfony ObjectMapper est une alternative à discuter, pas une dépendance de la démo.
- **Le score est-il une probabilité ?** Non, ce modèle synthétique n’est pas calibré ainsi.
- **Le contexte garantit-il la compatibilité ?** Non. Il identifie des termes ; leur définition, les tests et la gouvernance restent nécessaires.
- **C’est prêt pour la production ?** Le mécanisme est faisable. Les garanties de production dépassent ce que le lab démontre.
- **Et si le moteur change réellement la politique ?** Ce n’est plus une simple migration d’implémentation, il faut valider ce changement séparément.


### Si une objection est juste

Remercier pour la précision, reformuler la portée correcte, noter le point à vérifier. Ne pas défendre un commentaire du dépôt contre une preuve contraire.
-->
