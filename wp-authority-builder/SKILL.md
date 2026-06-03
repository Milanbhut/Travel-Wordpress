---
name: wp-authority-builder
description: Build a premium, AdSense-ready WordPress authority blog (90 articles across 6 categories, custom theme, real authors, legal pages, full SEO/schema, QA) on a Hostinger site from a single niche or title. Use when the user wants to create a new WordPress blog from a niche.
---

# WP Authority Builder

Given a niche or title, drive a blank Hostinger WordPress install (over WP-CLI/SSH, REST fallback)
to a finished, AdSense-ready authority blog. Spec: `docs/superpowers/specs/2026-06-03-wp-authority-builder-design.md`.

## Prerequisites
1. A blank WordPress install is standing on the target domain (user-provisioned).
2. `config/secrets.env` is filled (copy from `config/secrets.env.example`).
3. The venv is created and dependencies installed (`requirements.txt`).

## Phase 1 — Connect & verify (Gate 1)
Run: `python -m scripts.verify_connection`
- Must print `[OK] control channel = wpcli` (or `rest` as graceful fallback) before any build proceeds.

> Phases 0, 0.5, 2–10 are added by Plans B–E. Do not improvise them; follow the plan docs.
