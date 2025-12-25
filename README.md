# 🎬 Watch That Show

**Watch That Show** is a vanilla PHP movie search and discovery application powered by the **TMDB (The Movie Database) API**.  
The project emphasizes clean architecture, progressive enhancement, and real-world development practices — without relying on a PHP framework.

---

## 📌 Project Goals

- Build a fully functional movie database app using **plain PHP**
- Integrate a real-world third-party API (TMDB)
- Demonstrate clean separation of concerns
- Implement progressive enhancement (works with and without JavaScript)
- Avoid scope creep while still delivering a polished UX

---

## ✨ Features

### 🔍 Movie Search

- Server-side search using PHP (fallback / non-JS)
- Client-side **live search** with debouncing
- Abortable fetch requests
- Protection against stale responses
- Results dynamically update without page reloads

### 🧠 Fuzzy Search Ranking

- Client-side result re-ranking using:
  - Token overlap
  - Prefix & substring matching
  - Levenshtein distance (typo tolerance)
  - Weighted scoring
- Improves relevance over TMDB’s default ordering

### 🎥 Movie Details

- Movie poster and metadata
- Release year, runtime, rating
- Overview and tagline
- Cast list with actor headshots
- Efficient API usage via `append_to_response=credits`

### 🧑‍🎤 Actor / Person Pages

- Actor profile image
- Biography and birthplace
- Complete filmography
- Movies sorted by release date
- Seamless navigation back to movie pages

### ⚡ Performance & Stability

- File-based API caching with TTL
- Reduced API calls
- Faster page loads
- TMDB rate-limit friendly

---

## 🏗️ Architecture Overview

watch-that-show-php/
├── public/
│ ├── index.php # Search page
│ ├── movie.php # Movie details
│ ├── person.php # Actor details
│ └── js/
│ └── app.js # Live search + fuzzy ranking
│
├── src/
│ ├── bootstrap.php # App initialization
│ ├── tmdb.php # TMDB service layer
│ ├── helpers.php # Generic helper functions
│ ├── config.php # App configuration
│ └── views/
│ ├── header.php
│ └── footer.php
│
├── cache/
│ └── tmdb/ # Cached API responses
│
├── .env.example
└── README.md

---

## 🧱 Key Design Decisions

### ✅ Vanilla PHP (No Framework)

- Full control over execution flow
- Transparent logic (no hidden framework magic)
- Demonstrates core PHP skills

### ✅ Central Bootstrap

`src/bootstrap.php` handles:

- Environment loading
- Dependency wiring
- Global helpers and services

This mirrors professional PHP architectures (Laravel-style bootstrapping).

### ✅ Environment Variables (`.env`)

- API keys stored securely
- No secrets committed to source control
- Easy local vs production configuration

### ✅ Dedicated TMDB Service Layer

All TMDB logic lives in `src/tmdb.php`:

- API calls
- Image URL helpers
- Caching
- Error handling

This avoids duplicated logic and keeps responsibilities clear.

### ✅ Progressive Enhancement

- PHP renders initial results
- JavaScript enhances UX
- App works even if JS is disabled

---

## 🔐 Setup Instructions

### 1. Clone the repository

```bash
git clone https://github.com/your-username/watch-that-show-php.git
cd watch-that-show-php
```
