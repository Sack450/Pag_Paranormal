// =============================================
// v3.js — Configuración de Tailwind + Lógica interactiva
// Versión 3 de "ARCHIVE_0" — Página Paranormal
// =============================================

// --- Configuración de Tailwind CSS (heredada de v2) ---
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      "colors": {
        "background": "#fbf9f9", "on-error-container": "#93000a",
        "inverse-primary": "#c6c6c6", "on-tertiary-fixed": "#1b1b1b",
        "tertiary-fixed-dim": "#c6c6c6", "tertiary-fixed": "#e2e2e2",
        "on-secondary-fixed-variant": "#930010", "surface-container-low": "#f5f3f3",
        "secondary": "#b6171e", "error": "#ba1a1a",
        "on-primary-container": "#848484", "surface-variant": "#e3e2e2",
        "on-error": "#ffffff", "primary": "#000000",
        "on-secondary-container": "#fffbff", "on-tertiary": "#ffffff",
        "secondary-container": "#da3433", "inverse-on-surface": "#f2f0f0",
        "surface-container-lowest": "#ffffff", "surface-dim": "#dbdad9",
        "error-container": "#ffdad6", "primary-container": "#1b1b1b",
        "on-surface-variant": "#4c4546", "secondary-fixed-dim": "#ffb3ac",
        "secondary-fixed": "#ffdad6", "on-primary": "#ffffff",
        "primary-fixed-dim": "#c6c6c6", "primary-fixed": "#e2e2e2",
        "on-secondary": "#ffffff", "surface-container-highest": "#e3e2e2",
        "surface-container-high": "#e9e8e7", "surface-bright": "#fbf9f9",
        "on-tertiary-fixed-variant": "#474747", "on-secondary-fixed": "#410003",
        "outline-variant": "#cfc4c5", "surface": "#fbf9f9",
        "tertiary": "#000000", "on-primary-fixed": "#1b1b1b",
        "on-primary-fixed-variant": "#474747", "outline": "#7e7576",
        "inverse-surface": "#303031", "on-surface": "#1b1c1c",
        "tertiary-container": "#1b1b1b", "surface-container": "#efeded",
        "on-tertiary-container": "#848484", "on-background": "#1b1c1c",
        "surface-tint": "#5e5e5e"
      },
      "borderRadius": {
        "DEFAULT": "0.125rem", "lg": "0.25rem", "xl": "0.5rem", "full": "0.75rem"
      },
      "spacing": {
        "unit": "8px", "margin-mobile": "20px", "container-max": "1280px",
        "gutter": "24px", "margin-desktop": "64px"
      },
      "fontFamily": {
        "headline-sm": ["EB Garamond"], "label-caps": ["Hanken Grotesk"],
        "body-md": ["Hanken Grotesk"], "display-lg-mobile": ["EB Garamond"],
        "headline-md": ["EB Garamond"], "display-lg": ["EB Garamond"],
        "body-lg": ["Hanken Grotesk"]
      },
      "fontSize": {
        "headline-sm": ["24px", {"lineHeight": "1.3", "fontWeight": "500"}],
        "label-caps": ["12px", {"lineHeight": "1", "letterSpacing": "0.1em", "fontWeight": "700"}],
        "body-md": ["16px", {"lineHeight": "1.5", "fontWeight": "400"}],
        "display-lg-mobile": ["36px", {"lineHeight": "1.1", "fontWeight": "600"}],
        "headline-md": ["32px", {"lineHeight": "1.2", "fontWeight": "500"}],
        "display-lg": ["48px", {"lineHeight": "1.1", "letterSpacing": "-0.02em", "fontWeight": "600"}],
        "body-lg": ["18px", {"lineHeight": "1.6", "fontWeight": "400"}]
      }
    },
  },
};

// =============================================
// TODA LA LÓGICA INTERACTIVA
// Se ejecuta después de que el DOM esté cargado
// =============================================
document.addEventListener('DOMContentLoaded', function() {

  // =============================================
  // 1. NAVBAR: OCULTAR AL BAJAR / MOSTRAR AL SUBIR
  // Usa un umbral para evitar parpadeos por micro-scrolls
  // =============================================
  const nav = document.getElementById('mainNav');
  let ultimoScroll = 0;
  const umbral = 10;

  window.addEventListener('scroll', function() {
    const actual = window.pageYOffset;
    // Siempre visible en la zona superior
    if (actual <= 100) {
      nav.classList.remove('nav-hidden');
      ultimoScroll = actual;
      return;
    }
    if (Math.abs(actual - ultimoScroll) < umbral) return;
    // Scroll hacia abajo → ocultar | Scroll hacia arriba → mostrar
    if (actual > ultimoScroll) nav.classList.add('nav-hidden');
    else nav.classList.remove('nav-hidden');
    ultimoScroll = actual;
  });

  // =============================================
  // 2. EFECTO GLITCH ALEATORIO EN TÍTULOS
  // Selecciona elementos con clase 'glitch-text' y aplica
  // la animación CSS en intervalos aleatorios (3-8 seg)
  // =============================================
  const elementosGlitch = document.querySelectorAll('.glitch-text');

  function activarGlitch(elemento) {
    elemento.classList.add('glitch-active');
    setTimeout(() => elemento.classList.remove('glitch-active'), 300);
  }

  // Programación recursiva con intervalos aleatorios
  function programarGlitch() {
    const intervalo = Math.random() * 5000 + 3000;
    setTimeout(() => {
      const idx = Math.floor(Math.random() * elementosGlitch.length);
      if (elementosGlitch[idx]) activarGlitch(elementosGlitch[idx]);
      programarGlitch();
    }, intervalo);
  }
  if (elementosGlitch.length > 0) programarGlitch();

  // También activar glitch al pasar el cursor (hover)
  elementosGlitch.forEach(function(el) {
    el.addEventListener('mouseenter', function() { activarGlitch(el); });
  });

  // =============================================
  // 3. EFECTOS DE SONIDO — Web Audio API
  // Genera sonidos sintéticos sin necesitar archivos externos.
  // Se usa un AudioContext perezoso para cumplir con las
  // políticas de autoplay de los navegadores modernos.
  // =============================================
  let audioCtx = null;

  function getAudioContext() {
    if (!audioCtx) {
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    return audioCtx;
  }

  // Sonido de click sutil — oscilador descendente de 50ms
  function reproducirClick() {
    try {
      const ctx = getAudioContext();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(800, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(200, ctx.currentTime + 0.05);
      gain.gain.setValueAtTime(0.04, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.05);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.06);
    } catch (e) { /* Silenciar errores de audio en navegadores restrictivos */ }
  }

  // Sonido de estática — ruido blanco de 80ms a volumen bajo
  function reproducirEstatica() {
    try {
      const ctx = getAudioContext();
      const duracion = 0.08;
      const tamanoBuffer = ctx.sampleRate * duracion;
      const buffer = ctx.createBuffer(1, tamanoBuffer, ctx.sampleRate);
      const datos = buffer.getChannelData(0);
      for (let i = 0; i < tamanoBuffer; i++) {
        datos[i] = (Math.random() * 2 - 1) * 0.03;
      }
      const fuente = ctx.createBufferSource();
      fuente.buffer = buffer;
      const gain = ctx.createGain();
      gain.gain.setValueAtTime(0.03, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duracion);
      fuente.connect(gain);
      gain.connect(ctx.destination);
      fuente.start();
    } catch (e) { /* Silenciar errores de audio */ }
  }

  // Asignar sonidos a elementos interactivos
  document.querySelectorAll('nav a, aside a, #menuMovil a').forEach(function(link) {
    link.addEventListener('click', reproducirClick);
  });
  document.querySelectorAll('button').forEach(function(btn) {
    btn.addEventListener('mouseenter', reproducirEstatica);
  });

  // =============================================
  // 4. MENÚ MÓVIL — DRAWER LATERAL
  // Un panel deslizable desde la izquierda para pantallas
  // pequeñas, con overlay oscuro de fondo.
  // =============================================
  const btnHamburguesa = document.getElementById('btnHamburguesa');
  const menuMovil = document.getElementById('menuMovil');
  const overlayMenu = document.getElementById('overlayMenu');
  const btnCerrarMenu = document.getElementById('btnCerrarMenu');

  function abrirMenu() {
    if (menuMovil) menuMovil.classList.remove('-translate-x-full');
    if (overlayMenu) overlayMenu.classList.remove('hidden');
  }
  function cerrarMenuFn() {
    if (menuMovil) menuMovil.classList.add('-translate-x-full');
    if (overlayMenu) overlayMenu.classList.add('hidden');
  }

  if (btnHamburguesa) btnHamburguesa.addEventListener('click', abrirMenu);
  if (btnCerrarMenu) btnCerrarMenu.addEventListener('click', cerrarMenuFn);
  if (overlayMenu) overlayMenu.addEventListener('click', cerrarMenuFn);

  // Cerrar menú al hacer click en cualquier enlace del drawer
  document.querySelectorAll('#menuMovil a').forEach(function(link) {
    link.addEventListener('click', cerrarMenuFn);
  });

  // =============================================
  // 5. MODAL DE ENVÍO DE EVIDENCIA (FAB)
  // El botón flotante abre un modal con formulario
  // para subir archivos directamente.
  // =============================================
  const btnFab = document.getElementById('btnFab');
  const modalEvidencia = document.getElementById('modalEvidencia');
  const btnCerrarModal = document.getElementById('btnCerrarModal');
  const overlayModal = document.getElementById('overlayModal');

  function abrirModal() {
    if (modalEvidencia) {
      modalEvidencia.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }
  }
  function cerrarModalFn() {
    if (modalEvidencia) {
      modalEvidencia.classList.add('hidden');
      document.body.style.overflow = '';
    }
  }

  if (btnFab) btnFab.addEventListener('click', abrirModal);
  if (btnCerrarModal) btnCerrarModal.addEventListener('click', cerrarModalFn);
  if (overlayModal) overlayModal.addEventListener('click', cerrarModalFn);

  // Cerrar modal con tecla Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      cerrarModalFn();
      cerrarMenuFn();
    }
  });

  // Procesar envío del formulario de evidencia
  const formEvidencia = document.getElementById('formEvidencia');
  if (formEvidencia) {
    formEvidencia.addEventListener('submit', function(e) {
      e.preventDefault();
      const resultado = document.getElementById('resultadoEvidencia');
      if (resultado) {
        resultado.classList.remove('hidden');
        formEvidencia.reset();
        setTimeout(function() {
          resultado.classList.add('hidden');
          cerrarModalFn();
        }, 3000);
      }
    });
  }

  // =============================================
  // 6. FORMULARIO DE CONTACTO ASÍNCRONO (FETCH)
  // Envía datos al backend PHP sin recargar la página.
  // Incluye modo demo para cuando no hay servidor disponible.
  // =============================================
  const formContacto = document.getElementById('formContacto');
  const resultadoContacto = document.getElementById('resultadoContacto');

  if (formContacto) {
    formContacto.addEventListener('submit', async function(e) {
      e.preventDefault();
      const formData = new FormData(formContacto);
      const btnSubmit = formContacto.querySelector('button[type="submit"]');
      const textoOriginal = btnSubmit.textContent;

      // Feedback visual durante el envío
      btnSubmit.textContent = 'ENCRIPTANDO...';
      btnSubmit.disabled = true;

      try {
        const response = await fetch('procesar_transmision.php', {
          method: 'POST',
          body: formData
        });
        const data = await response.json();

        if (data.exito) {
          resultadoContacto.innerHTML = '<p class="text-green-600 font-label-caps text-[11px] mt-4">✓ TRANSMISIÓN ENCRIPTADA Y ENVIADA CON ÉXITO</p>';
        } else {
          resultadoContacto.innerHTML = '<p class="text-error font-label-caps text-[11px] mt-4">✗ ERROR: ' + data.mensaje + '</p>';
        }
      } catch (error) {
        // Modo demo: simular éxito cuando no hay servidor PHP disponible
        resultadoContacto.innerHTML = '<p class="text-secondary dark:text-red-500 font-label-caps text-[11px] mt-4">✓ TRANSMISIÓN ENCRIPTADA Y ENVIADA [MODO DEMO]</p>';
      }

      resultadoContacto.classList.remove('hidden');
      formContacto.reset();
      btnSubmit.textContent = textoOriginal;
      btnSubmit.disabled = false;

      // Ocultar mensaje después de 5 segundos
      setTimeout(function() {
        resultadoContacto.classList.add('hidden');
      }, 5000);
    });
  }

  // =============================================
  // 7. MODO OSCURO + LOCALSTORAGE
  // Alterna la clase 'dark' en <html> y persiste
  // la preferencia del usuario entre sesiones.
  // =============================================
  const btnDarkMode = document.getElementById('btnDarkMode');
  const html = document.documentElement;

  // Restaurar tema guardado al cargar la página
  const temaGuardado = localStorage.getItem('tema-archivo0');
  if (temaGuardado === 'dark') {
    html.classList.remove('light');
    html.classList.add('dark');
  }

  if (btnDarkMode) {
    btnDarkMode.addEventListener('click', function() {
      html.classList.toggle('dark');
      html.classList.toggle('light');
      const esDark = html.classList.contains('dark');
      localStorage.setItem('tema-archivo0', esDark ? 'dark' : 'light');
      // Actualizar icono del botón
      const icono = btnDarkMode.querySelector('.material-symbols-outlined');
      if (icono) icono.textContent = esDark ? 'light_mode' : 'dark_mode';
    });
  }

  // Actualizar icono inicial según el tema actual
  if (btnDarkMode) {
    const iconoInicial = btnDarkMode.querySelector('.material-symbols-outlined');
    if (iconoInicial) {
      iconoInicial.textContent = html.classList.contains('dark') ? 'light_mode' : 'dark_mode';
    }
  }

});
