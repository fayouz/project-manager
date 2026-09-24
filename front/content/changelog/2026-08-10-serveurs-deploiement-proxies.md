---
title: Serveurs de déploiement & proxies
description: Nouvelle gestion des serveurs de déploiement et des proxies réseau pour vos intégrations.
date: 2026-08-10
badge: Fonctionnalité
---

Ajout de deux nouvelles ressources dans le tableau de bord :

- **Serveurs de déploiement** : rattachez vos serveurs cibles à vos projets pour piloter vos déploiements.
- **Proxies** : configurez des proxies réseau réutilisables par vos connecteurs d'intégration (Jenkins, Gitea, SonarQube, Mantis, Nexus).

La résolution des proxys est désormais centralisée via `ProxyResolver`, garantissant un comportement cohérent quel que soit le connecteur utilisé.
