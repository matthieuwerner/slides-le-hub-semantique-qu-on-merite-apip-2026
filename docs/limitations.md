# Limites du laboratoire

- Le cycle **local** est réel dans le stockage : réservation, débit de clearing, idempotence et relecture. Les soldes et toutes les données sont synthétiques. Aucun paiement bancaire ni compensation interbancaire n’est exécuté.
- Un compte EUR par utilisateur. Clearing intégral, sans capture partielle, expiration, annulation, remboursement ni FX. challenged signifie attente de revue et ne réserve pas. Aucun protocole 3-D Secure.
- Un agrégat MongoDB borné à 1 000 autorisations. Version conditionnelle et écriture atomique mono-document ; pas de ledger infini, d’archivage, de transaction distribuée ni de preuve de haute disponibilité. L’agrégat est remplacé à chaque mutation : son coût augmente avec son historique. Le volume local doit être conservé pour préserver les clés d’idempotence.
- Identifiants et clés d’idempotence ne sont pas des mécanismes d’accès. Authentification, droits par compte, audit réglementaire, chiffrement, rétention, quotas et gestion des secrets restent à industrialiser. Ne jamais exposer ce laboratoire sur Internet.
- Les fixtures enrichies et le modèle de risque sont déterministes et synthétiques. Aucune qualité de détection de fraude ou conformité bancaire démontrée. Le risque est calculé avant la combinaison des contrôles locaux, sauf rejeu : c’est un choix de démonstration.
- Quatre configurations du même port RiskEngine, organisées en trois actes. PHP HTTP réutilise l’image API contenant une extension Go inutilisée. Cela contrôle une partie de l’environnement, pas toutes les différences de déploiement possibles.
- HTTP reste synchrone, même avec deux équipes. Timeouts explicites, aucun retry automatique. Une réponse de commit incertaine exige de rejouer la même requestId. Les erreurs techniques ne deviennent jamais des refus ou approbations inventés.
- L’extension native embarque la bibliothèque Go et son adaptateur, pas l’API PHP. Elle ne pilote pas un programme Go arbitraire distant. Pas d’isolation de panne mémoire/processus. Binaire spécifique et mécanisme extension-init annoncé expérimental par l’aide de la version utilisée.
- Jane et JoliCode AutoMapper ne prouvent ni la compatibilité sémantique ni un gain de productivité. Symfony ObjectMapper reste un exemple de réserve non installé. Le mapping des invariants reste explicite.
- OpenAPI, JSON-LD et Hydra sont complémentaires, pas une garantie automatique de sens ou de sécurité. Le vocabulaire example.org illustré dans les slides n’est pas un vocabulaire réellement publié.
- Le consommateur préparé n’embarque aucun LLM. Son opération de démonstration autorise et réserve seulement. Un clearing nécessite une commande séparée.
- Le benchmark mesure la latence séquentielle de POST authorization, pas le cycle entier, la capacité, le coût cloud ou la productivité. Comptes neufs par bloc, clés neuves par mesure, au plus 1 000 commandes par compte. La croissance du document reste dans le coût mesuré.
- La référence du 17 septembre inclut les contrôles et MongoDB, avec cinq campagnes par charge et leur dispersion. Les mesures antérieures sans persistance restent historiques et ne décrivent pas ce chemin.
- L’image recommande encore l’extension intl absente : ne pas annoncer une optimisation PHP exhaustive. Les tests couvrent des cas et des interleavings déterminés, pas toutes les pannes, toutes les entrées ou une garantie bancaire.

Voir [architecture](architecture.md), [preuves](CONFERENCE-VALIDATION.md), [référence mesurée](benchmark-reference-2026-09-17.md).
