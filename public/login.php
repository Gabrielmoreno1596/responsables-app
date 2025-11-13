<?php require __DIR__ . '/_bootstrap.php'; ?>
<!DOCTYPE html>
<meta charset="utf-8" />
<base href="<?= htmlspecialchars(BASE_PATH ?: '/', ENT_QUOTES) ?>/" />
<title>CECNSR · Acceso</title>

<link rel="stylesheet" href="assets/css/ui-cecnsr.css" />
<link rel="stylesheet" href="assets/css/theme-cecnsr.css" />
<link rel="stylesheet" href="assets/css/login.cecnsr.css" />

<header class="appbar">
  <img src="assets/img/cecnsr-logo.png" alt="CECNSR" />
  <div>
    <div class="appbar__title">CECNSR · Sistema de Responsables</div>
    <div class="appbar__subtitle">Acceso para personal autorizado</div>
  </div>
</header>

<form id="form-login" autocomplete="off">
  <input name="username" value="admin" required />
  <input type="password" name="password" required />
  <button>Entrar</button>
</form>
<pre id="out">...</pre>

<script>
  const out = document.getElementById('out');
  document.getElementById('form-login').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new URLSearchParams(new FormData(e.target));
    const resp = await fetch('api/login', {
      method: 'POST',
      body: fd,
      credentials: 'include'
    });
    const text = await resp.text();
    out.textContent = text;
    if (resp.ok) location.href = 'buscador'; // se resuelve con el <base>
  });
</script>