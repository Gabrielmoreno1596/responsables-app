<?php require __DIR__ . '/_bootstrap.php'; ?>
<!DOCTYPE html>
<meta charset="utf-8" />
<title>Buscador</title>

<!-- Base dinámico desde BASE_PATH (igual que login.php y form.php) -->
<base href="<?= htmlspecialchars(BASE_PATH ?: '/', ENT_QUOTES) ?>/" />

<link rel="stylesheet" href="assets/css/theme-cecnsr.css" />
<link rel="stylesheet" href="assets/css/buscador.cecnsr.css" />




<div class="controls">
  <input id="name" placeholder="Nombre / DUI / correo…" style="min-width:280px" />
  <button id="buscar">Buscar</button>
  <span id="badge" class="pill">0 resultados</span>
</div>
<div class="controls">
  <div>
    <button class="pager" id="prev">&laquo; Anterior</button>
    <span id="pageLabel">pág. 1</span>
    <button class="pager" id="next">Siguiente &raquo;</button>
  </div>
  <div id="filters">
    <button class="filter-chip" data-key="grado" data-value="1°">1°</button>
    <button class="filter-chip" data-key="grado" data-value="2°">2°</button>
    <button class="filter-chip" data-key="grado" data-value="3°">3°</button>
    <button class="filter-chip" data-key="dui" data-value="con">Con DUI</button>
    <button class="filter-chip" data-key="dui" data-value="sin">Sin DUI</button>
    <button class="filter-chip" data-key="municipio" data-value="San Salvador">San Salvador</button>
  </div>
</div>
<div id="list"></div>



<script>
  const input = document.querySelector("#name");
  const btnBuscar = document.querySelector("#buscar");
  const list = document.querySelector("#list");
  const badge = document.querySelector("#badge");
  const pageLabel = document.querySelector("#pageLabel");
  const btnPrev = document.querySelector("#prev");
  const btnNext = document.querySelector("#next");

  // Estado global
  let page = 1;
  const size = 10;

  // Filtros en memoria (puedes ampliarlos)
  const FILTERS = new Map(); // p.ej. 'grado' => Set('1°','2°')

  // Pintar fila
  function renderRow(item) {
    const div = document.createElement("div");
    div.className = "row";
    const creado = item.created_at ? new Date(item.created_at).toLocaleString() : "";
    div.innerHTML = `
      <div><strong>${item.alumno}</strong> (${item.grado || "-"})</div>
      <div>Responsable: ${item.responsable || "-"}</div>
      <div>DUI: ${item.dui || "-"}</div>
      <div>Tel: ${item.telefono || "-"}</div>
      <div>Correo: ${item.correo || "-"}</div>
      <div>Municipio: ${item.municipio || "-"}</div>
      <div><small>Registrado: ${creado}</small></div>
    `;
    return div;
  }

  // Aplicar filtros del cliente (ejemplo simple, opcional)
  function applyClientFilters(items) {
    let filtered = items;

    if (FILTERS.has("grado") && FILTERS.get("grado").size) {
      const set = FILTERS.get("grado");
      filtered = filtered.filter((i) => set.has(i.grado));
    }

    if (FILTERS.has("dui") && FILTERS.get("dui").size) {
      const set = FILTERS.get("dui");
      filtered = filtered.filter((i) => {
        const hasDui = !!(i.dui && i.dui.trim());
        if (set.has("con") && hasDui) return true;
        if (set.has("sin") && !hasDui) return true;
        return false;
      });
    }

    if (FILTERS.has("municipio") && FILTERS.get("municipio").size) {
      const set = FILTERS.get("municipio");
      filtered = filtered.filter((i) => set.has(i.municipio));
    }

    return filtered;
  }

  function buildApiParams(base) {
    const p = new URLSearchParams(base || {});
    if (FILTERS.has("grado") && FILTERS.get("grado").size) {
      FILTERS.get("grado").forEach((v) => p.append("grado", v));
    }
    return p;
  }

  async function cargar() {
    const term = (input.value || "").trim();
    const params = buildApiParams({
      q: term,
      page,
      size
    });
    const url = `api/estudiantes?${params.toString()}`;
    const resp = await fetch(url, {
      method: "GET",
      credentials: "include"
    });

    if (resp.status === 401) {
      // No logueado -> mandar al login (ruta Slim /login -> login.php)
      location.href = "login";
      return;
    }
    if (!resp.ok) {
      console.error("Error API:", await resp.text());
      list.innerHTML = "<p>Error al cargar resultados.</p>";
      badge.textContent = "0 resultados";
      pageLabel.textContent = `pág. ${page}`;
      return;
    }

    const data = await resp.json();
    const items = Array.isArray(data.items) ? data.items : [];
    const filtered = applyClientFilters(items);
    const {
      page: curPage,
      total,
      totalPages,
      size: pageSize
    } = data.pagination || {
      page,
      total: filtered.length,
      totalPages: 1,
      size
    };

    list.innerHTML = "";
    filtered.forEach((item) => list.appendChild(renderRow(item)));

    badge.textContent = `${total} resultado${total === 1 ? "" : "s"}`;
    pageLabel.textContent = `pág. ${curPage}`;
    btnPrev.disabled = curPage <= 1;
    btnNext.disabled = curPage >= totalPages;
  }

  btnBuscar.addEventListener("click", () => {
    page = 1;
    cargar();
  });

  input.addEventListener("keydown", (ev) => {
    if (ev.key === "Enter") {
      page = 1;
      cargar();
    }
  });

  document.querySelectorAll("#filters .filter-chip").forEach((chip) => {
    chip.addEventListener("click", () => {
      const key = chip.dataset.key,
        val = chip.dataset.value;
      if (!FILTERS.has(key)) FILTERS.set(key, new Set());
      const set = FILTERS.get(key);
      const isActive = chip.getAttribute("data-active") === "true";
      if (isActive) {
        set.delete(val);
        chip.removeAttribute("data-active");
      } else {
        set.add(val);
        chip.setAttribute("data-active", "true");
      }
      window.page = 1;
      cargar();
    });
  });

  btnPrev.addEventListener("click", () => {
    if (page > 1) {
      page--;
      cargar();
    }
  });

  btnNext.addEventListener("click", () => {
    page++;
    cargar();
  });

  window.addEventListener("DOMContentLoaded", cargar);
</script>