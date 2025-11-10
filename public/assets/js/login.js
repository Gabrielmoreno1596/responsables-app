document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#login-form');
    if (!form) return;

    const errorEl = document.querySelector('#login-error');

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        if (errorEl) { errorEl.hidden = true; errorEl.textContent = ''; }

        const fd = new FormData(form);
        const payload = Object.fromEntries(fd.entries());

        try {
            const res = await fetch(form.action || '/api/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                const target = form.dataset.redirect || '/buscador';
                window.location.assign(target);
            } else {
                const msg = (data && (data.error || data.message)) || 'Error de autenticación';
                if (errorEl) { errorEl.textContent = msg; errorEl.hidden = false; }
                else alert(msg);
            }
        } catch (e) {
            if (errorEl) { errorEl.textContent = 'Error de red. Intenta de nuevo.'; errorEl.hidden = false; }
            else alert('Error de red. Intenta de nuevo.');
        }
    });
});
