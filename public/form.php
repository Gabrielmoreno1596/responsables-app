<?php require __DIR__ . '/_bootstrap.php'; ?>
<!DOCTYPE html>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<base href="<?= htmlspecialchars(BASE_PATH ?: '/', ENT_QUOTES) ?>/" />
<title>Registro de datos para facturación</title>

<link rel="stylesheet" href="assets/css/theme-cecnsr.css" />
<link rel="stylesheet" href="assets/css/form.cecnsr.css" />

<div class="wrap">
  <h1>Registro de datos para facturación</h1>
  <p>Completa los datos. Los campos con * son obligatorios.</p>

  <!-- Este SÍ envía al endpoint correcto gracias al <base> -->
  <form id="form-publico" class="card" action="api/registro-publico" method="POST" autocomplete="off" novalidate>
    <!-- Honeypot -->
    <input type="text" name="website" tabindex="-1" autocomplete="off" style="display:none" />

    <fieldset>
      <legend><strong>Estudiante</strong></legend>
      <label>Nombre completo *</label>
      <input name="estudiante_nombre" required placeholder="Ej. Ana Sofía López" />
      <label>Grado</label>
      <select name="grado">
        <option value="">Seleccione…</option>
        <option>1°</option>
        <option>2°</option>
        <option>3°</option>
        <option>4°</option>
        <option>5°</option>
        <option>6°</option>
        <option>7°</option>
        <option>8°</option>
        <option>9°</option>
      </select>
    </fieldset>

    <fieldset>
      <legend><strong>Responsable</strong></legend>
      <label>Nombre *</label>
      <input name="responsable_nombre" required placeholder="Ej. María López" />
      <label>DUI</label>
      <input name="dui" placeholder="00000000-0" pattern="\d{8}-\d" />
      <label>Teléfono</label>
      <input name="telefono" placeholder="7000-0000" />
      <label>Correo</label>
      <input type="email" name="correo" placeholder="correo@ejemplo.com" />
      <label>Dirección</label>
      <input name="direccion" placeholder="Calle/avenida y número" />
      <label>Municipio</label>
      <input name="municipio" placeholder="Municipio" />
      <label>Departamento</label>
      <input name="departamento" placeholder="Departamento" />
    </fieldset>

    <div class="actions">
      <button class="primary" type="submit">Guardar</button>
      <span id="msg" class="muted"></span>
    </div>
  </form>

  <p class="ok" id="ok" hidden>¡Registro enviado correctamente!</p>
  <p class="err" id="err" hidden>Ocurrió un error al guardar.</p>
</div>

<script>
  // En producción, el <base> resuelve "api/..." a /responsables-app/public/api/...
  // En local, lo resuelve a /api/...
  document.getElementById('form-publico')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const msg = document.getElementById('msg');
    msg.className = 'muted';
    msg.textContent = 'Guardando…';

    try {
      const fd = new FormData(form);
      const res = await fetch(form.action, {
        method: 'POST',
        body: fd,
        credentials: 'include'
      });
      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch {}
      if (res.ok) {
        msg.className = 'ok';
        msg.textContent = `Registro creado (ID ${data?.id ?? '¿?'}).`;
        form.reset();
      } else {
        msg.className = 'err';
        msg.textContent = `${res.status}: ${data?.error || data?.message || text}`;
      }
    } catch (err) {
      msg.className = 'err';
      msg.textContent = 'Error de red: ' + err.message;
    }
  });
</script>