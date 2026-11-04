<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConstrucKIT · Reporte de equipos</title>
    <style>
        :root {
            --bg: #f4f5f7; --card: #ffffff; --text: #1f2933; --muted: #6b7280;
            --border: #e5e7eb; --accent: #d97706; --accent-dark: #b45309; --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--bg); color: var(--text); }
        header { background: #1f2933; color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { margin: 0; font-size: 18px; font-weight: 600; }
        header small { color: #cbd5e1; }
        main { max-width: 960px; margin: 24px auto; padding: 0 16px; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .card h2 { margin: 0 0 16px; font-size: 16px; }
        form { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end; }
        label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 4px; }
        input, select { width: 100%; padding: 9px 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; }
        button { padding: 10px 14px; border: 0; border-radius: 8px; background: var(--accent); color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; }
        button:hover { background: var(--accent-dark); }
        button.link { background: none; color: #cbd5e1; padding: 0; font-weight: 400; text-decoration: underline; }
        .error { color: var(--danger); font-size: 14px; margin-top: 12px; }
        .hidden { display: none; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .stat { border: 1px solid var(--border); border-radius: 10px; padding: 14px; }
        .stat .label { font-size: 13px; color: var(--muted); }
        .stat .value { font-size: 24px; font-weight: 700; margin: 6px 0 2px; }
        .stat .detail { font-size: 12px; color: var(--muted); }
        .stat.highlight { border-color: var(--accent); }
        .note { margin-top: 12px; font-size: 13px; color: var(--muted); }
    </style>
</head>
<body>
<header>
    <div>
        <h1>ConstrucKIT</h1>
        <small>Reporte de combustible y mantenciones</small>
    </div>
    <button id="logout" class="link hidden" type="button">Cerrar sesión</button>
</header>

<main>
    {{-- 1. Login: obtiene el token Sanctum desde POST /api/login --}}
    <section id="login-card" class="card">
        <h2>Iniciar sesión</h2>
        <form id="login-form">
            <div>
                <label for="email">Correo</label>
                <input id="email" type="email" value="demo@construckit.cl" required>
            </div>
            <div>
                <label for="password">Contraseña</label>
                <input id="password" type="password" value="password" required>
            </div>
            <button type="submit">Ingresar</button>
        </form>
        <p id="login-error" class="error hidden"></p>
    </section>

    {{-- 2. Filtros del reporte: equipo y rango de fechas --}}
    <section id="report-card" class="card hidden">
        <h2>Reporte por equipo</h2>
        <form id="report-form">
            <div>
                <label for="equipment">Equipo</label>
                <select id="equipment" required></select>
            </div>
            <div>
                <label for="from">Desde</label>
                <input id="from" type="date" required>
            </div>
            <div>
                <label for="to">Hasta</label>
                <input id="to" type="date" required>
            </div>
            <button type="submit">Generar reporte</button>
        </form>
        <p id="report-error" class="error hidden"></p>
    </section>

    {{-- 3. Resultado: se llena con la respuesta de GET /api/equipment/{id}/report --}}
    <section id="result-card" class="card hidden">
        <h2 id="result-title"></h2>
        <div class="stats">
            <div class="stat">
                <div class="label">Litros totales</div>
                <div class="value" id="total-liters"></div>
                <div class="detail" id="loads-count"></div>
            </div>
            <div class="stat">
                <div class="label">Gasto en combustible</div>
                <div class="value" id="fuel-cost"></div>
            </div>
            <div class="stat">
                <div class="label">Gasto en mantenciones</div>
                <div class="value" id="maintenance-cost"></div>
                <div class="detail" id="maintenance-count"></div>
            </div>
            <div class="stat highlight">
                <div class="label">Rendimiento promedio</div>
                <div class="value" id="liters-per-hour"></div>
                <div class="detail" id="hours-used"></div>
            </div>
        </div>
        <p id="performance-note" class="note hidden"></p>
    </section>
</main>

<script>
    // El token vive solo en memoria: al recargar la página hay que volver a iniciar sesión.
    // Es la opción más simple y evita guardar credenciales en el navegador.
    let token = null;

    const $ = (id) => document.getElementById(id);
    const clp = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });
    const number = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 2 });

    // Llama a la API con el token y devuelve el JSON. Lanza un Error con un mensaje legible si falla.
    async function api(path, options = {}) {
        const response = await fetch('/api' + path, {
            ...options,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(token ? { 'Authorization': 'Bearer ' + token } : {}),
            },
        });

        if (response.status === 401 && token) {
            showLogin('La sesión expiró. Vuelve a iniciar sesión.');
            throw new Error('No autenticado.');
        }

        const data = response.status === 204 ? null : await response.json();

        if (!response.ok) {
            // Errores de validación (422): se muestran todos los mensajes que envía la API.
            const messages = data && data.errors ? Object.values(data.errors).flat() : [data?.message || 'Error inesperado.'];
            throw new Error(messages.join(' '));
        }

        return data;
    }

    function showError(element, message) {
        element.textContent = message;
        element.classList.toggle('hidden', !message);
    }

    function showLogin(message = '') {
        token = null;
        $('login-card').classList.remove('hidden');
        $('report-card').classList.add('hidden');
        $('result-card').classList.add('hidden');
        $('logout').classList.add('hidden');
        showError($('login-error'), message);
    }

    // Por defecto se propone el mes anterior completo (con el seeder: octubre de 2026).
    function setDefaultDates() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
        const iso = (d) => [d.getFullYear(), String(d.getMonth() + 1).padStart(2, '0'), String(d.getDate()).padStart(2, '0')].join('-');
        $('from').value = iso(firstDay);
        $('to').value = iso(lastDay);
    }

    async function loadEquipment() {
        const response = await api('/equipment');
        const select = $('equipment');
        select.innerHTML = '';
        response.data.forEach((equipment) => {
            const option = document.createElement('option');
            option.value = equipment.id;
            option.textContent = `${equipment.code} · ${equipment.brand} ${equipment.model}`;
            select.appendChild(option);
        });
    }

    $('login-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        showError($('login-error'), '');
        try {
            const response = await api('/login', {
                method: 'POST',
                body: JSON.stringify({ email: $('email').value, password: $('password').value, device_name: 'web' }),
            });
            token = response.token;
            $('login-card').classList.add('hidden');
            $('report-card').classList.remove('hidden');
            $('logout').classList.remove('hidden');
            setDefaultDates();
            await loadEquipment();
        } catch (error) {
            showError($('login-error'), error.message);
        }
    });

    $('report-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        showError($('report-error'), '');
        try {
            const query = new URLSearchParams({ from: $('from').value, to: $('to').value });
            const { data } = await api(`/equipment/${$('equipment').value}/report?${query}`);
            renderReport(data);
        } catch (error) {
            $('result-card').classList.add('hidden');
            showError($('report-error'), error.message);
        }
    });

    $('logout').addEventListener('click', async () => {
        try { await api('/logout', { method: 'POST' }); } catch (error) { /* el token ya no sirve */ }
        showLogin();
    });

    // Pinta el reporte. Se usa textContent (no innerHTML) para no interpretar datos como HTML.
    function renderReport(report) {
        $('result-title').textContent = `${report.equipment.code} · ${report.period.from} al ${report.period.to}`;
        $('total-liters').textContent = number.format(report.fuel.total_liters) + ' L';
        $('loads-count').textContent = `${report.fuel.loads_count} carga(s)`;
        $('fuel-cost').textContent = clp.format(report.fuel.total_cost_clp);
        $('maintenance-cost').textContent = clp.format(report.maintenance.total_cost_clp);
        $('maintenance-count').textContent = `${report.maintenance.count} mantención(es)`;

        const performance = report.performance;
        $('liters-per-hour').textContent = performance.liters_per_hour === null
            ? '—'
            : number.format(performance.liters_per_hour) + ' L/h';
        $('hours-used').textContent = performance.hours_used === null
            ? ''
            : `${number.format(performance.hours_used)} h de uso`;
        showError($('performance-note'), performance.note || '');

        $('result-card').classList.remove('hidden');
    }
</script>
</body>
</html>