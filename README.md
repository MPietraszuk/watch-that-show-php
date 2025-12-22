# Watch That Show (TMDB API) — Vanilla PHP

A lightweight movie browser built with vanilla PHP, CSS, and JavaScript.
No framework. No database. Designed to demonstrate clean structure, secure API integration,
and production-style organization.

## Features

- Search movies via TMDB
- Movie details page (synopsis, genres, top cast, trailer link)
- Trending movies (today / week)
- Popular movies with pagination
- File-based caching to reduce API calls

## Tech

- PHP (no framework)
- TMDB API
- CSS (custom)
- JavaScript (minimal)

## Project Structure

- `public/` is the web root (pages + static assets)
- `src/` contains application logic (config, API client, helpers)
- `cache/` stores cached JSON responses (gitignored)

## Setup

1. Copy `.env.example` → `.env`
2. Add your `TMDB_API_KEY`
3. Run locally:

```bash
php -S localhost:8000 -t public
```
