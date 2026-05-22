// Navegación móvil
function toggleMenu() {
  document.getElementById('navLinks').classList.toggle('open');
}

// Cerrar el menú al hacer clic en un enlace
document.querySelectorAll('.nav-links a').forEach(link => {
  link.addEventListener('click', () => {
    document.getElementById('navLinks').classList.remove('open');
  });
});

// Pestañas de galería
function switchGallery(tab) {
  document.querySelectorAll('.gallery-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.gallery-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('gallery-' + tab).classList.add('active');
  event.target.classList.add('active');
}

// Pestañas de tienda
function switchShop(tab) {
  document.querySelectorAll('.shop-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.shop-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('shop-' + tab).classList.add('active');
  event.target.classList.add('active');
}

// Formulario de contacto
function handleSubmit(e) {
  e.preventDefault();
  const btn = e.target;
  btn.textContent = '✓ Mensaje enviado — ¡Gracias!';
  btn.style.background = '#1a1a1a';
  btn.style.pointerEvents = 'none';
  setTimeout(() => {
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> &nbsp; Enviar Mensaje';
    btn.style.background = '';
    btn.style.pointerEvents = '';
  }, 4000);
}

// Aparición suave al hacer scroll
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateY(0)';
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.inv-card, .blog-mini, .news-card, .ep-card, .expl-card, .shop-card, .donation-card, .social-card').forEach(el => {
  el.style.opacity = '0';
  el.style.transform = 'translateY(24px)';
  el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
  observer.observe(el);
});
