# Résultats et protocole des slides

## Référence actuelle

La [campagne du 17 septembre 2026](benchmark-reference-2026-09-17.md) est la référence utilisée dans les slides. Elle inclut les contrôles métier et MongoDB. Son [résumé](../benchmark/results/reference-20260917.json) identifie les dix fichiers de campagne versionnés à ses côtés.

Cinq campagnes par charge, quatre répétitions par configuration et profil, 200 appels mesurés après 40 appels de chauffe. La valeur affichée est la médiane des cinq estimations de campagne. La plage min–max décrit leur dispersion, pas un intervalle de confiance.

Le chrono commence avant l’appel HTTP du client de benchmark et s’arrête après réception complète du corps. Le serveur est déjà démarré et préchauffé. Le calcul, les accès MongoDB et la réservation font partie du traitement mesuré. Le clearing et les rejeux appartiennent aux preuves fonctionnelles, pas à cette mesure de latence.

## Interprétation

Avec les petites règles, le PHP local et le natif restent proches. Pour la charge synthétique lourde, le natif a la médiane la plus basse sur ce poste. Les chiffres ne prouvent ni un gain universel, ni la capacité en production, ni la qualité prédictive du moteur. Go HTTP apporte une séparation de processus et de livraison, avec un coût de transport.

Les anciennes mesures sans MongoDB ne décrivent pas le parcours actuel. Les documents sous archive/ sont historiques et ne doivent pas servir de chiffres pour le talk actuel.

## Relancer une mesure

`make bench` lance une nouvelle campagne locale, avec ses paramètres par défaut. Ce n’est pas à lui seul la reproduction de toute la référence multi-campagnes. Pour reprendre ses paramètres : `REPEATS=4 make bench`, puis répéter cinq fois à chaque charge dans des conditions comparables. Consulter [le protocole détaillé](benchmark-reference-2026-09-17.md) avant d’agréger ou de comparer.

Ne pas lancer les mesures en parallèle avec une compilation, une démo ou une autre campagne. Ne jamais substituer silencieusement une mesure récente aux chiffres publiés. Les nouveaux résultats restent locaux tant qu’ils ne sont pas sélectionnés et documentés.
