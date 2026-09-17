# API Platform : le hub sémantique qu’on mérite

Présentation de Matthieu Werner pour API Platform Conference 2026, réalisée avec [Slidev](https://sli.dev/).

## Lancer les slides

Depuis ce dossier, avec Node.js 22+ :

```sh
npm ci
npm run dev -- --port 3038
```

- Présentation : http://localhost:3038
- Mode présentateur avec notes : http://localhost:3038/presenter/1

## Construire la version statique

```sh
npm run build
```

Les fichiers générés se trouvent dans `dist/`. Le contenu et les notes orateur sont dans [slides.md](slides.md).

Pour exécuter les exemples de la conférence, consulter le [README du laboratoire](../README.md).
