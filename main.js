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

// ═══════════════════════════ FORMULARIO DE EVIDENCIAS Y SUBIDAS ═══════════════════════════

document.addEventListener('DOMContentLoaded', () => {
  const formEvidencia = document.getElementById('formEvidencia');
  if (!formEvidencia) return; // Si no estamos en la página que contiene el formulario, salir

  const dropArea = document.getElementById('dropArea');
  const fileInput = document.getElementById('evidenciaArchivo');
  const fileInfo = document.getElementById('fileInfo');
  const fileName = document.getElementById('fileName');
  const removeFileBtn = document.getElementById('removeFileBtn');
  const formAlerta = document.getElementById('formAlerta');
  const btnEnviar = document.getElementById('btnEnviarEvidencia');
  const uploadText = document.getElementById('uploadText');

  // Límite máximo de tamaño del archivo en bytes (15 Megabytes)
  const maxFileSizeBytes = 15 * 1024 * 1024; 

  // 1. GESTIÓN DE DRAG AND DROP (ARRASTRAR Y SOLTAR)
  
  // Evitar comportamientos por defecto del navegador para estos eventos
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, preventDefaults, false);
  });

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  // Resaltar área cuando el archivo se arrastra sobre ella
  ['dragenter', 'dragover'].forEach(eventName => {
    dropArea.addEventListener(eventName, () => dropArea.classList.add('dragover'), false);
  });

  // Quitar el resalto cuando se arrastra fuera del área
  ['dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'), false);
  });

  // Manejar el evento drop (cuando se suelta el archivo)
  dropArea.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
      handleFileSelection(files[0]);
    }
  }, false);

  // Permitir hacer click en el contenedor dragover para seleccionar un archivo
  dropArea.addEventListener('click', () => {
    fileInput.click();
  });

  // Capturar el archivo cuando se selecciona de forma tradicional
  fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
      handleFileSelection(e.target.files[0]);
    }
  });

  // 2. LOGICA DE VALIDACIÓN Y MOSTRAR DETALLES DE ARCHIVO
  
  function handleFileSelection(file) {
    // Validar extensión del archivo
    const fileExt = file.name.split('.').pop().toLowerCase();
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp3', 'wav', 'ogg', 'pdf', 'txt'];
    
    if (!allowedExtensions.includes(fileExt)) {
      mostrarAlerta('Error: Formato de archivo no permitido. Selecciona una imagen, audio o relato (.txt, .pdf).', 'error');
      clearFileSelection();
      return;
    }

    // Validar el tamaño del archivo en el cliente para ahorrar ancho de banda
    if (file.size > maxFileSizeBytes) {
      mostrarAlerta('Error: El archivo excede el tamaño máximo permitido de 15MB.', 'error');
      clearFileSelection();
      return;
    }

    // Asignar el archivo al input de tipo file de forma programática (solo si vino de drag and drop)
    const container = new DataTransfer();
    container.items.add(file);
    fileInput.files = container.files;

    // Mostrar caja con detalles del archivo seleccionado
    fileName.textContent = `${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`;
    fileInfo.style.display = 'flex';
    dropArea.style.display = 'none'; // Ocultar zona de drop temporalmente
    ocultarAlerta();
  }

  // Quitar el archivo seleccionado
  removeFileBtn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    clearFileSelection();
  });

  function clearFileSelection() {
    fileInput.value = '';
    fileName.textContent = '';
    fileInfo.style.display = 'none';
    dropArea.style.display = 'block';
  }

  // 3. ENVÍO DE FORMULARIO POR FETCH API (AJAX)

  formEvidencia.addEventListener('submit', (e) => {
    e.preventDefault(); // Evitar recarga de página estándar

    // Obtener campos de texto para validación local
    const nombre = document.getElementById('nombreCliente').value.trim();
    const email = document.getElementById('emailCliente').value.trim();
    const titulo = document.getElementById('tituloCaso').value.trim();
    const tipo = document.getElementById('tipoEvidencia').value;
    const descripcion = document.getElementById('descripcionCaso').value.trim();

    // Comprobación de campos requeridos
    if (!nombre || !email || !titulo || !tipo || !descripcion) {
      mostrarAlerta('Por favor, completa todos los campos requeridos (*).', 'error');
      return;
    }

    // Comprobación de formato de email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      mostrarAlerta('Por favor, introduce un correo electrónico válido.', 'error');
      return;
    }

    // Activar spinner y deshabilitar botón para evitar envíos múltiples
    setLoadingState(true);
    ocultarAlerta();

    // Crear un objeto FormData con los datos del formulario y del archivo
    const formData = new FormData(formEvidencia);

    // Enviar asíncronamente mediante Fetch API
    fetch('procesar_reporte.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      // Procesar la respuesta HTTP
      if (!response.ok) {
        // Si el estado HTTP es de error (400, 500, etc), lanzar error con JSON si es posible
        return response.json().then(errData => {
          throw new Error(errData.mensaje || 'Error del servidor al procesar la evidencia.');
        });
      }
      return response.json();
    })
    .then(data => {
      if (data.exito) {
        // Mostrar alerta de éxito del servidor indicando el estado "pendiente"
        mostrarAlerta(data.mensaje, 'success');
        // Limpiar el formulario y los archivos seleccionados
        formEvidencia.reset();
        clearFileSelection();
      } else {
        mostrarAlerta(data.mensaje || 'Ocurrió un error inesperado.', 'error');
      }
    })
    .catch(error => {
      console.error('Error al enviar reporte:', error);
      mostrarAlerta(error.message || 'Error de conexión con el servidor. Por favor, intenta de nuevo.', 'error');
    })
    .finally(() => {
      // Restaurar el botón de envío al estado original
      setLoadingState(false);
    });
  });

  // Funciones auxiliares para controlar estados del formulario
  
  function setLoadingState(loading) {
    if (loading) {
      btnEnviar.disabled = true;
      btnEnviar.querySelector('.btn-text').style.display = 'none';
      btnEnviar.querySelector('.btn-spinner').style.display = 'inline-flex';
    } else {
      btnEnviar.disabled = false;
      btnEnviar.querySelector('.btn-text').style.display = 'inline-flex';
      btnEnviar.querySelector('.btn-spinner').style.display = 'none';
    }
  }

  function mostrarAlerta(mensaje, tipo) {
    formAlerta.textContent = mensaje;
    formAlerta.className = `form-alerta ${tipo}`;
    formAlerta.style.display = 'block';
    
    // Desplazar suavemente el scroll hacia la alerta
    formAlerta.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function ocultarAlerta() {
    formAlerta.style.display = 'none';
    formAlerta.textContent = '';
  }
});

