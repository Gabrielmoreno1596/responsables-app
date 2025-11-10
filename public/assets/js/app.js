document.addEventListener("DOMContentLoaded", async () => {
    const buscador = document.getElementById("buscador");
    const container = document.getElementById("datos-container");

    // Cargar el JSON una sola vez y almacenarlo en una variable global
    let estudiantes = [];

    try {
        const response = await fetch("estudiantes.json");
        estudiantes = await response.json();
    } catch (error) {
        console.error("Error al cargar los datos:", error);
        container.innerHTML = "<p>Error al cargar los datos</p>";
        return;
    }

    // Evento del buscador con debounce
    buscador.addEventListener(
        "input",
        debounce(() => {
            const query = buscador.value.trim().toLowerCase();
            if (query.length > 0) {
                buscarEstudiantes(query);
            } else {
                container.innerHTML = ""; // Limpia los resultados si no hay búsqueda
            }
        }, 300)
    );

    function buscarEstudiantes(query) {
        const resultados = estudiantes.filter(estudiante =>
            (estudiante.estudiante || "").toLowerCase().includes(query) ||
            (estudiante.email || "").toLowerCase().includes(query)
        );
        mostrarDatos(resultados);
    }

    function mostrarDatos(estudiantes) {
        container.innerHTML = "";

        if (estudiantes.length === 0) {
            container.innerHTML = "<p>No se encontraron resultados</p>";
            return;
        }

        const fragment = document.createDocumentFragment();

        estudiantes.forEach(estudiante => {
            const estudianteDiv = document.createElement("div");
            estudianteDiv.classList.add("estudiante");

            estudianteDiv.innerHTML = `
        <h3>${estudiante.estudiante || "Nombre no disponible"}</h3>
        <p><strong>Grado:</strong> ${estudiante.grado || "No especificado"}</p>
        <p><strong>Responsable:</strong> ${estudiante.responsable || "No especificado"}</p>
        <p><strong>DUI:</strong> ${estudiante.dui || estudiante.numeroDUI || "No especificado"}</p>
        <p><strong>Dirección:</strong> ${estudiante.direccion || "No especificado"}</p>
        <p><strong>Teléfono:</strong> ${estudiante.telefono || "No especificado"}</p>
        <p><strong>Correo:</strong> ${estudiante.email || estudiante.correoElectronico || "No especificado"}</p>        
      `;

            fragment.appendChild(estudianteDiv);
        });

        container.appendChild(fragment);
    }
});

// Función debounce para evitar demasiadas búsquedas en poco tiempo
function debounce(func, delay) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
    };
}
document.addEventListener("DOMContentLoaded", async () => {
    const buscador = document.getElementById("buscador");
    const container = document.getElementById("datos-container");
    const usernameInput = document.getElementById("username");
    const passwordInput = document.getElementById("password");
    const loginButton = document.getElementById("login-button");

    let estudiantes = [];

    try {
        const response = await fetch("estudiantes.json");
        estudiantes = await response.json();
    } catch (error) {
        console.error("Error al cargar los datos:", error);
        container.innerHTML = "<p>Error al cargar los datos</p>";
        return;
    }

    usernameInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            passwordInput.focus();
        }
    });

    passwordInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            verificarCredenciales();
        }
    });

    buscador.addEventListener(
        "input",
        debounce(() => {
            const query = buscador.value.trim().toLowerCase();
            if (query.length > 0) {
                buscarEstudiantes(query);
            } else {
                container.innerHTML = "";
            }
        }, 300)
    );
});

function togglePasswordVisibility() {
    const passwordInput = document.getElementById("password");
    const toggleIcon = document.getElementById("toggle-password");

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleIcon.src = "boton-de-visibilidad.png"; // Cambia a icono de ojo abierto
    } else {
        passwordInput.type = "password";
        toggleIcon.src = "ojo-cerrado.png"; // Cambia a icono de ojo tachado
    }
}

async function hashPassword(password) {
    const encoder = new TextEncoder();
    const data = encoder.encode(password);
    const hashBuffer = await crypto.subtle.digest("SHA-256", data);
    return Array.from(new Uint8Array(hashBuffer))
        .map(b => b.toString(16).padStart(2, "0"))
        .join("");
}

const USUARIO_CORRECTO = "CECNSR";
const HASH_CORRECTO = "3c82c4c31011decb275ea6ea85f1c5c6e969482f2859b5c27985bdecb3b7c998";

async function verificarCredenciales() {
    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;
    const errorMessage = document.getElementById("login-error");

    const hashedPassword = await hashPassword(password);

    if (username === USUARIO_CORRECTO && hashedPassword === HASH_CORRECTO) {
        document.getElementById("login-container").style.display = "none";
        document.getElementById("content").classList.remove("hidden");
    } else {
        errorMessage.textContent = "Usuario o contraseña incorrectos.";
    }
}


// LOGIN
async function doLogin(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const resp = await fetch('/responsables-app/public/api/login', {
        method: 'POST',
        body: new URLSearchParams(fd),
        credentials: 'include' // importante para cookie de sesión
    });
    const data = await resp.json();
    if (resp.ok) {
        window.location.href = '/responsables-app/public/buscador';
    } else {
        alert(data.error || 'Error de login');
    }
}

// BUSCADOR
let t;
async function buscar(q) {
    const url = '/responsables-app/public/api/estudiantes?q=' + encodeURIComponent(q);
    const resp = await fetch(url, { credentials: 'include' });
    const data = await resp.json();
    if (!resp.ok) return alert(data.error || 'No autorizado');

    // TODO: pinta tarjetas con data.data
}

document.addEventListener('DOMContentLoaded', () => {
    const fLogin = document.querySelector('#form-login');
    if (fLogin) fLogin.addEventListener('submit', doLogin);

    const inputQ = document.querySelector('#input-buscar');
    if (inputQ) {
        inputQ.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => buscar(inputQ.value.trim()), 300);
        });
        buscar(''); // primera carga
    }
});
