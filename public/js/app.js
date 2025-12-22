document.addEventListener("DOMContentLoaded", () => {
  const input = document.querySelector("#q");
  const grid = document.querySelector("#resultsGrid");
  const status = document.querySelector("#liveStatus");

  if (!input || !grid || !status) return;

  let timer = null;
  let controller = null;

  const escapeHtml = (s) =>
    String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");

  const posterUrl = (posterPath) =>
    posterPath ? `https://image.tmdb.org/t/p/w500${posterPath}` : "";

  function renderMessage(msg) {
    grid.innerHTML = `<p class="subtle">${escapeHtml(msg)}</p>`;
  }

  function renderResults(items) {
    if (!Array.isArray(items) || items.length === 0) {
      renderMessage("No results found.");
      return;
    }

    grid.innerHTML = items
      .map((m) => {
        const id = Number(m.id);
        const title = escapeHtml(m.title || "Untitled");
        const year = (m.release_date || "").slice(0, 4);
        const poster = posterUrl(m.poster_path);

        const posterHtml = poster
          ? `<img loading="lazy" src="${poster}" alt="${title} poster">`
          : `<div class="poster-fallback">No Image</div>`;

        return `
          <a class="card" href="movie.php?id=${id}">
            <div class="poster">${posterHtml}</div>
            <div class="card-body">
              <div class="title">${title}</div>
              <div class="meta">${year ? escapeHtml(year) : "—"}</div>
            </div>
          </a>
        `;
      })
      .join("");
  }

  // ---------------------------
  // Fuzzy ranking helpers
  // ---------------------------

  function normalize(str) {
    // lowercase, remove accents, keep alphanumerics/spaces
    return String(str ?? "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "") // diacritics
      .replace(/[^a-z0-9\s]/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  }

  function tokens(str) {
    const n = normalize(str);
    return n ? n.split(" ") : [];
  }

  // Levenshtein distance (small + safe for short strings)
  function levenshtein(a, b) {
    a = normalize(a);
    b = normalize(b);
    const alen = a.length;
    const blen = b.length;
    if (alen === 0) return blen;
    if (blen === 0) return alen;

    // Use two rows to save memory
    const prev = new Array(blen + 1);
    const curr = new Array(blen + 1);

    for (let j = 0; j <= blen; j++) prev[j] = j;

    for (let i = 1; i <= alen; i++) {
      curr[0] = i;
      const ca = a.charCodeAt(i - 1);
      for (let j = 1; j <= blen; j++) {
        const cost = ca === b.charCodeAt(j - 1) ? 0 : 1;
        curr[j] = Math.min(
          prev[j] + 1, // deletion
          curr[j - 1] + 1, // insertion
          prev[j - 1] + cost // substitution
        );
      }
      for (let j = 0; j <= blen; j++) prev[j] = curr[j];
    }

    return prev[blen];
  }

  function similarity(a, b) {
    const na = normalize(a);
    const nb = normalize(b);
    const maxLen = Math.max(na.length, nb.length);
    if (maxLen === 0) return 1;
    const dist = levenshtein(na, nb);
    return 1 - dist / maxLen; // 0..1
  }

  function fuzzyScore(query, title) {
    const q = normalize(query);
    const t = normalize(title);

    if (!q || !t) return 0;

    // Strong boosts
    if (t === q) return 100;
    if (t.startsWith(q)) return 90;
    if (t.includes(q)) return 75;

    // Token overlap boosts (good for multi-word queries)
    const qt = tokens(q);
    const tt = tokens(t);
    let overlap = 0;
    const ttSet = new Set(tt);
    for (const tok of qt) {
      if (ttSet.has(tok)) overlap++;
    }
    const overlapRatio = qt.length ? overlap / qt.length : 0; // 0..1

    // Typo tolerance via similarity
    // Compare query to full title and also to best-matching title token
    const fullSim = similarity(q, t); // 0..1
    let bestTokenSim = 0;
    for (const tok of tt) {
      // don’t overwork the CPU on very long titles
      if (tok.length < 2) continue;
      const s = similarity(q, tok);
      if (s > bestTokenSim) bestTokenSim = s;
    }

    // Weighted score (tuned for “feels right”)
    // - overlap helps multi-word partials
    // - fullSim helps typos
    // - bestTokenSim helps single-word typos
    return overlapRatio * 40 + fullSim * 45 + bestTokenSim * 15;
  }

  function rerankResults(query, results) {
    const q = query;

    return [...results]
      .map((m) => {
        const title = m.title || "";
        const score = fuzzyScore(q, title);

        // Light boost for higher-rated items if fuzzy score ties
        const rating = Number(m.vote_average ?? 0);

        return { ...m, _fuzzy: score, _rating: rating };
      })
      .sort((a, b) => {
        if (b._fuzzy !== a._fuzzy) return b._fuzzy - a._fuzzy;
        return b._rating - a._rating;
      })
      .map(({ _fuzzy, _rating, ...rest }) => rest);
  }

  // ---------------------------
  // Live search
  // ---------------------------

  async function liveSearch(q) {
    if (controller) controller.abort();
    controller = new AbortController();

    const apiUrl = new URL("api/search.php", window.location.href);
    apiUrl.searchParams.set("q", q);

    status.textContent = "Searching…";

    try {
      const res = await fetch(apiUrl.toString(), {
        signal: controller.signal,
        headers: { Accept: "application/json" },
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok) {
        console.error("API error:", data);
        status.textContent = data?.error
          ? `Error: ${data.error}`
          : "Error searching.";
        renderMessage("Could not load results.");
        return;
      }

      const reranked = rerankResults(q, data.results || []);

      status.textContent = `Showing results for “${data.query}”`;
      renderResults(reranked);
    } catch (err) {
      if (err.name === "AbortError") return;
      console.error("Fetch failed:", err);
      status.textContent = "Network error.";
      renderMessage("Network error.");
    }
  }

  input.addEventListener("input", () => {
    const q = input.value.trim();
    clearTimeout(timer);

    if (q.length < 2) {
      status.textContent = "Type at least 2 characters…";
      if (controller) controller.abort();
      grid.innerHTML = "";
      return;
    }

    timer = setTimeout(() => liveSearch(q), 250);
  });

  // No initial message here; PHP renders initial status.
});
