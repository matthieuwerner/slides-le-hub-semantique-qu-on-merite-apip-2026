# Architecture du cycle carte local

API Platform reste la façade métier publique. « Hub sémantique » désigne cette responsabilité, pas un produit ou un plugin supplémentaire.

## Périmètre

Un utilisateur synthétique, un compte EUR et des cartes synthétiques. Une autorisation évalue le risque, vérifie carte/devise/disponible et réserve le montant si approuvée. Le clearing intégral débite le compte et libère cette réservation. Aucun réseau carte, règlement interbancaire, capture partielle, FX, remboursement ou expiration de réservation.

| Opération publique (préfixe /api) | Point d’entrée | Service applicatif |
| --- | --- | --- |
| GET /users/{userId} | UserProvider | BankService::user |
| GET /accounts/{accountId}/balance | AccountBalanceProvider | BankService::balance |
| POST /payment-authorizations | PaymentAuthorizationProcessor | BankService::authorize |
| GET /payment-authorizations/{authorizationId} | PaymentAuthorizationProvider | BankService::authorization |
| POST /clearings | ClearingProcessor | BankService::clear |
| GET /merchant-risk-profiles/{merchantId} | MerchantRiskProfileProvider | MerchantQuery::get |

Provider/Processor est le raccord prévu par API Platform. Le service applicatif porte le cas d’usage. Les adaptateurs portent le transport et le stockage. Les ressources sont des DTO : aucun ORM/ODM imposé. Sources : [Providers](https://api-platform.com/docs/core/state-providers/), [Processors](https://api-platform.com/docs/core/state-processors/).

## Invariants et identité

Disponible = comptabilisé − réservé. Les valeurs sont des entiers en unités mineures. Une autorisation approved réserve ; challenged et declined ne réservent pas. Seule une autorisation authorized peut passer à cleared. Le montant du clearing vient de l’autorisation : le client ne choisit pas un autre montant.

requestId identifie une commande dans le compte et l’opération. Le même contenu rejoue la réponse initiale, sans recalcul ni nouvel effet. Un contenu différent sous la même clé donne 409. Le GET retourne l’état courant ; le rejeu du POST conserve le résultat initial même après clearing. status est le verdict initial, state le cycle de vie. Le reçu de clearing n’a pas de GET dédié.

authorizationId encode le compte et le hash SHA-256 de requestId. Ce n’est ni un secret ni une autorisation d’accès. La recherche utilise l’index _id du compte, pas un scan de tous les comptes. Le GET de l’utilisateur suit la convention de fixtures un utilisateur/un compte, pas un modèle d’identité universel.

## Persistance et concurrence

AccountRepository isole le pilote MongoDB. Le compte, les décisions, réservations et clés d’idempotence vivent dans un document. La mise à jour conditionnelle sur _id et version écrit ces éléments ensemble. En conflit, le service recharge l’état et reteste les fonds. Il réutilise uniquement le calcul pur de risque. Huit tentatives au maximum, puis 503 : le client conserve la même requestId. Une erreur de commit incertaine n’est jamais convertie en approbation ou refus inventé.

Ce montage utilise l’atomicité mono-document de MongoDB, sans prétendre à une transaction distribuée. Le laboratoire limite le compte à 1 000 autorisations, conserve les clés sans purge et remplace le document agrégé. Ce n’est pas un ledger à historique illimité. La production demanderait un journal durable, une stratégie de rétention/idempotence, des limites de contention et éventuellement des transactions multi-documents. [Atomicité MongoDB](https://www.mongodb.com/docs/manual/core/write-operations-atomicity/).

POST /_lab/scenarios crée un compte neuf, sans réinitialiser les précédents. Il est activé seulement avec le drapeau de laboratoire. La base possède un volume Docker dédié, sans port publié. Aucun reset automatique entre démonstrations.

## Trois actes, quatre configurations

1. php : calcul par PhpRiskEngine dans le processus API. Le cycle carte est déjà complet.
2. php-http puis go-http : même HttpRiskEngine, deux instances et deux URL privées. RiskEngineClientFactory construit Symfony HttpClient adapté en PSR-18, puis le client Jane généré depuis le contrat OpenAPI privé. Le service reçoit les features enrichies, jamais le compte ou la clé d’idempotence. Il ne réserve ni ne débite. Timeouts bornés, validation de réponse, identité technique contrôlée, aucun retry implicite. La version v2 est convertie explicitement par WireMapper.
3. go-native : NativeGoRiskEngine convertit les valeurs en tableau et appelle BoundaryLab\Native\assess dans le processus. Le module Go est un petit adaptateur vers la même bibliothèque go/risk que le serveur HTTP. Il ne compile pas l’application PHP, ne lance pas un exécutable et n’appelle pas un microservice Go externe. La suppression d’HTTP s’accompagne d’un build lié et d’un domaine de panne partagé.

Le conteneur privé PHP réutilise l’image de laboratoire et sa configuration PHP. Cette image contient aussi l’extension native, inutilisée dans le chemin php-http : ne pas la présenter comme un service PHP minimal optimisé pour la production. Le stockage reste la responsabilité de l’application publique.

## Jane et mapping

MerchantRiskProfileProvider délègue à MerchantQuery. Ce service utilise Jane, valide la réponse puis JoliCode AutoMapper projette les cinq champs publics. internalOwner reste privé. Marchand absent : 404 ; réponse invalide/indisponible : 503. Symfony ObjectMapper est expliqué en réserve, pas utilisé à la place du mapper réellement installé.

WireMapper traite les invariants de risque : échelle entière 0..100, v2 sur la grille 0..1 par pas de 0,01, correspondance ALLOW/REVIEW/DENY. Jane fournit des types de transport, pas une preuve des unités. OpenAPI, JSON-LD et Hydra décrivent le contrat public ; aucun ne réalise l’appel HTTP ou natif.

## Comparaison

make proof-cycle vérifie les effets persistés, les rejeux et des appels concurrents. make proof-public compare représentations, exports et intégrations. Le futur make bench mesure des autorisations neuves, jamais des replays, avec un compte neuf par bloc et un état initial identique. Il n’agrège pas les temps du cycle entier : ce cycle est une preuve fonctionnelle distincte. Les anciens chiffres ne mesurent pas cette version.
