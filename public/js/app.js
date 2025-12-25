// public/js/app.js

document.addEventListener("DOMContentLoaded", () => {
  const input = document.querySelector("#q");
  const grid = document.querySelector("#resultsGrid");
  const status = document.querySelector("#liveStatus");

  if (!input || !grid || !status) return;

  let timer = null;
  let controller = null;

  // ✅ NEW: protect against stale responses overwriting newer UI
  let lastRequestId = 0;

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
    return String(str ?? "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9\s]/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  }

  function tokens(str) {
    const n = normalize(str);
    return n ? n.split(" ") : [];
  }

  function levenshtein(a, b) {
    a = normalize(a);
    b = normalize(b);
    const alen = a.length;
    const blen = b.length;
    if (alen === 0) return blen;
    if (blen === 0) return alen;

    const prev = new Array(blen + 1);
    const curr = new Array(blen + 1);

    for (let j = 0; j <= blen; j++) prev[j] = j;

    for (let i = 1; i <= alen; i++) {
      curr[0] = i;
      const ca = a.charCodeAt(i - 1);
      for (let j = 1; j <= blen; j++) {
        const cost = ca === b.charCodeAt(j - 1) ? 0 : 1;
        curr[j] = Math.min(prev[j] + 1, curr[j - 1] + 1, prev[j - 1] + cost);
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
    return 1 - dist / maxLen;
  }

  function fuzzyScore(query, title) {
    const q = normalize(query);
    const t = normalize(title);

    if (!q || !t) return 0;

    if (t === q) return 100;
    if (t.startsWith(q)) return 90;
    if (t.includes(q)) return 75;

    const qt = tokens(q);
    const tt = tokens(t);
    let overlap = 0;
    const ttSet = new Set(tt);
    for (const tok of qt) {
      if (ttSet.has(tok)) overlap++;
    }
    const overlapRatio = qt.length ? overlap / qt.length : 0;

    const fullSim = similarity(q, t);
    let bestTokenSim = 0;
    for (const tok of tt) {
      if (tok.length < 2) continue;
      const s = similarity(q, tok);
      if (s > bestTokenSim) bestTokenSim = s;
    }

    return overlapRatio * 40 + fullSim * 45 + bestTokenSim * 15;
  }

  function rerankResults(query, results) {
    const q = query;

    return [...results]
      .map((m) => {
        const title = m.title || "";
        const score = fuzzyScore(q, title);
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

  async function liveSearch(rawQ) {
    const q = String(rawQ ?? "").trim();
    const requestId = ++lastRequestId;

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

      // ✅ Ignore stale responses
      if (requestId !== lastRequestId) return;

      if (!res.ok) {
        console.error("API error:", data);
        status.textContent = data?.error
          ? `Error: ${data.error}`
          : "Error searching.";
        renderMessage("Could not load results.");
        return;
      }

      const reranked = rerankResults(q, data.results || []);

      // ✅ Use current query, not data.query (prevents weird resets)
      status.textContent = `Showing results for “${q}”`;
      renderResults(reranked);
    } catch (err) {
      if (err.name === "AbortError") return;

      // ✅ Ignore stale errors too
      if (requestId !== lastRequestId) return;

      console.error("Fetch failed:", err);
      status.textContent = "Network error.";
      renderMessage("Network error.");
    }
  }

  input.addEventListener("input", () => {
    // ✅ Don’t trim here (typing spaces shouldn’t feel like “disappearing”)
    const q = input.value;
    clearTimeout(timer);

    if (q.trim().length < 2) {
      status.textContent =
        q.trim().length === 0
          ? "Start typing to search."
          : "Type at least 2 characters…";
      if (controller) controller.abort();
      grid.innerHTML = "";
      return;
    }

    timer = setTimeout(() => liveSearch(q), 250);
  });

  // Prevent Enter from triggering a full page navigation when JS is active
  const form = input.closest("form");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const q = input.value;
      if (q.trim().length >= 2) liveSearch(q);
    });
  }

  // No initial message here; PHP renders initial status.
});

// Show more cast
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.querySelector("#toggleCast");
  if (!btn) return;

  const hiddenItems = Array.from(
    document.querySelectorAll(".cast-card.is-hidden")
  );
  if (hiddenItems.length === 0) return;

  let expanded = false;

  btn.addEventListener("click", () => {
    expanded = !expanded;
    hiddenItems.forEach((li) => li.classList.toggle("is-hidden", !expanded));
    btn.textContent = expanded ? "Show less cast" : "Show more cast";
  });
});
