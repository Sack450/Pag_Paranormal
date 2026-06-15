<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ── Conexión ──────────────────────────────────────────────
if (file_exists('conexion.php')) {
    require_once 'conexion.php';
} else {
    $conexion = null;
    $conexion_error = "Archivo 'conexion.php' no encontrado.";
}
if (!isset($conexion))       { $conexion = null; }
if (!isset($conexion_error)) { $conexion_error = null; }

$autenticado = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// ═══════════════════════════════════════════════════════════
//  API AJAX
// ═══════════════════════════════════════════════════════════
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    if (!$autenticado) { echo json_encode(['success'=>false,'message'=>'No autenticado']); exit; }

    $action = $_GET['action'] ?? ($_POST['action'] ?? '');

    // ── Helpers ────────────────────────────────────────────
    function subir_imagen($file_key, $b64_key) {
        $dir = 'uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        if (!empty($_POST[$b64_key])) {
            $data = $_POST[$b64_key];
            if (strpos($data, ',') !== false) [,$data] = explode(',', $data);
            $bytes = base64_decode($data);
            $path  = $dir . time() . '_' . uniqid() . '.png';
            file_put_contents($path, $bytes);
            return $path;
        }
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $name = time() . '_' . basename($_FILES[$file_key]['name']);
            $path = $dir . $name;
            if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $path)) return $path;
        }
        return '';
    }

    function resp($ok, $msg='', $data=null) {
        $r = ['success'=>$ok, 'message'=>$msg];
        if ($data !== null) $r['data'] = $data;
        echo json_encode($r); exit;
    }

    if (!$conexion) resp(false, 'Sin conexión a BD');

    // ── GET: listar ────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows = [];
        switch ($action) {
            case 'get_blog':
                $r = $conexion->query("SELECT id,titulo,categoria,fecha,imagen_ruta FROM blog_articulos ORDER BY fecha DESC");
                while($f=$r->fetch_assoc()) $rows[]=$f; break;
            case 'get_noticias':
                $r = $conexion->query("SELECT id,titulo,fuente,fecha,imagen_ruta FROM noticias ORDER BY fecha DESC");
                while($f=$r->fetch_assoc()) $rows[]=$f; break;
            case 'get_galeria':
                $r = $conexion->query("SELECT id,categoria,descripcion,imagen_ruta FROM galeria ORDER BY id DESC");
                while($f=$r->fetch_assoc()) $rows[]=$f; break;
            case 'get_podcast':
                $r = $conexion->query("SELECT id,episodio,titulo,fecha,imagen_ruta,link_audio FROM podcasts ORDER BY fecha DESC");
                while($f=$r->fetch_assoc()) $rows[]=$f; break;
            case 'get_exploraciones':
                $r = $conexion->query("SELECT id,titulo,ubicacion,fecha,link_video FROM exploraciones ORDER BY fecha DESC");
                while($f=$r->fetch_assoc()) $rows[]=$f; break;
            case 'get_tienda':
                $r = $conexion->query("SELECT id,nombre,precio,imagen_ruta,link_compra,fecha_registro FROM tienda_productos ORDER BY fecha_registro DESC");
                while($f=$r->fetch_assoc()) $rows[]=$f; break;
            case 'get_evidencias':
                $r = $conexion->query("SELECT id,nombre_cliente,titulo_caso,tipo_evidencia,estado,fecha_registro FROM reportes_evidencia ORDER BY fecha_registro DESC");
                while($f=$r->fetch_assoc()) $rows[]=$f; break;
            case 'get_config':
                $r = $conexion->query("SELECT clave,valor FROM configuracion_sitio");
                $cfg = [];
                while($f=$r->fetch_assoc()) $cfg[$f['clave']] = $f['valor'];
                resp(true, '', $cfg); break;
            case 'get_stats':
                $stats = [];
                foreach(['blog_articulos','noticias','galeria','podcasts','exploraciones','tienda_productos','reportes_evidencia'] as $t) {
                    $res = $conexion->query("SELECT COUNT(*) as c FROM `$t`");
                    $stats[$t] = $res ? (int)$res->fetch_assoc()['c'] : 0;
                }
                resp(true, '', $stats); break;
            case 'get_item':
                $table = $_GET['table'] ?? '';
                $id    = (int)($_GET['id'] ?? 0);
                $allowed = ['blog_articulos','noticias','galeria','podcasts','exploraciones','tienda_productos','reportes_evidencia'];
                if (!in_array($table, $allowed) || $id<=0) resp(false,'Petición inválida');
                $stmt = $conexion->prepare("SELECT * FROM `$table` WHERE id=? LIMIT 1");
                $stmt->bind_param('i',$id);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();
                resp($item ? true : false, '', $item); break;
        }
        resp(true, '', $rows);
    }

    // ── POST: insertar / actualizar / eliminar / config ────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tipo = $_POST['form_type'] ?? '';

        // — Eliminar ——————————————————————————————————————
        if ($action === 'delete') {
            $table = $_POST['table'] ?? '';
            $id    = (int)($_POST['id'] ?? 0);
            $allowed = ['blog_articulos','noticias','galeria','podcasts','exploraciones','tienda_productos','reportes_evidencia'];
            if (!in_array($table, $allowed) || $id<=0) resp(false,'Petición inválida');
            $stmt = $conexion->prepare("DELETE FROM `$table` WHERE id=?");
            $stmt->bind_param('i',$id);
            resp($stmt->execute(), $stmt->execute() ? 'Eliminado' : $conexion->error);
        }

        // — Guardar configuración del sitio ——————————————
        if ($action === 'save_config') {
            $campos = [
                'site_title','site_tagline','site_description','hero_titulo','hero_subtitulo',
                'hero_tag','hero_btn_explorar','hero_btn_contactar',
                'footer_texto','footer_copyright','logo_texto','logo_mostrar_texto',
                'color_primario','color_acento','color_fondo','color_nav',
                'redes_youtube','redes_instagram','redes_facebook','redes_spotify','redes_tiktok',
                'redes_whatsapp','redes_twitter',
                'paypal_link','patreon_link','amazon_link',
                'sec_blog_label','sec_noticias_label','sec_podcast_label','sec_exploraciones_label',
                'sec_galeria_label','sec_tienda_label','sec_donaciones_label',
                'donacion_paypal_texto','donacion_patreon_texto','donacion_amazon_texto',
                'sobremi_label','sobremi_nombre','sobremi_parrafo1','sobremi_parrafo2',
                'sobremi_stat1_num','sobremi_stat1_label','sobremi_stat2_num','sobremi_stat2_label',
                'sobremi_stat3_num','sobremi_stat3_label',
                'contacto_label','contacto_titulo',
                'contacto_youtube_texto','contacto_instagram_texto','contacto_facebook_texto',
                'contacto_tiktok_texto','contacto_twitter_texto','contacto_whatsapp_texto',
                'evidencias_titulo','evidencias_intro',
                'logo_imagen','favicon','hero_imagen','config_version'
            ];

            $img_logo = subir_imagen('imagen_logo', 'imagen_base64_logo');
            if ($img_logo) $_POST['logo_imagen'] = $img_logo;
            $img_favicon = subir_imagen('imagen_favicon', 'imagen_base64_favicon');
            if ($img_favicon) $_POST['favicon'] = $img_favicon;
            $img_hero = subir_imagen('imagen_hero', 'imagen_base64_hero');
            if ($img_hero) $_POST['hero_imagen'] = $img_hero;
            $img_sobremi = subir_imagen('imagen_sobremi', 'imagen_base64_sobremi');
            if ($img_sobremi) $_POST['sobremi_foto'] = $img_sobremi;
            if (isset($_POST['sobremi_foto']) && !in_array('sobremi_foto', $campos, true)) {
                $campos[] = 'sobremi_foto';
            }

            $_POST['logo_mostrar_texto'] = (!empty($_POST['logo_mostrar_texto']) && $_POST['logo_mostrar_texto'] !== '0') ? '1' : '0';
            $_POST['config_version'] = (string)time();

            require_once __DIR__ . '/_theme.php';
            foreach (['color_primario','color_acento','color_fondo','color_nav'] as $ck) {
                if (isset($_POST[$ck])) {
                    $_POST[$ck] = theme_hex_normalize($_POST[$ck]);
                }
            }

            $ok = true;
            foreach ($campos as $c) {
                if (!isset($_POST[$c])) continue;
                $val  = $_POST[$c];
                $stmt = $conexion->prepare("INSERT INTO configuracion_sitio (clave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?");
                $stmt->bind_param('sss', $c, $val, $val);
                if (!$stmt->execute()) { $ok = false; break; }
            }
            resp($ok, $ok ? 'Cambios aplicados en tiempo real.' : 'Error al guardar: '.$conexion->error, [
                'version' => $_POST['config_version'],
                'logo_imagen' => $_POST['logo_imagen'] ?? '',
                'favicon' => $_POST['favicon'] ?? '',
                'hero_imagen' => $_POST['hero_imagen'] ?? '',
            ]);
        }

        // — Cambiar estado de evidencia ———————————————————
        if ($action === 'update_estado') {
            $id     = (int)($_POST['id'] ?? 0);
            $estado = $_POST['estado'] ?? '';
            $notas  = $_POST['notas_admin'] ?? '';
            if (!in_array($estado, ['pendiente','aceptado','rechazado']) || $id<=0) resp(false,'Datos inválidos');
            $stmt = $conexion->prepare("UPDATE reportes_evidencia SET estado=?, notas_admin=? WHERE id=?");
            $stmt->bind_param('ssi',$estado,$notas,$id);
            resp($stmt->execute(), 'Estado actualizado.');
        }

        // — Insertar / Actualizar contenido ——————————————
        $id_edit = (int)($_POST['id_edit'] ?? 0);

        if ($tipo === 'blog') {
            $tit = $_POST['titulo']    ?? '';
            $cat = $_POST['categoria'] ?? '';
            $fec = $_POST['fecha']     ?? '';
            $con = $_POST['contenido'] ?? '';
            $img = subir_imagen('imagen','imagen_base64_blog');

            if ($id_edit > 0) {
                if ($img) {
                    $stmt = $conexion->prepare("UPDATE blog_articulos SET titulo=?,categoria=?,fecha=?,contenido=?,imagen_ruta=? WHERE id=?");
                    $stmt->bind_param('sssssi',$tit,$cat,$fec,$con,$img,$id_edit);
                } else {
                    $stmt = $conexion->prepare("UPDATE blog_articulos SET titulo=?,categoria=?,fecha=?,contenido=? WHERE id=?");
                    $stmt->bind_param('ssssi',$tit,$cat,$fec,$con,$id_edit);
                }
            } else {
                $stmt = $conexion->prepare("INSERT INTO blog_articulos (titulo,categoria,fecha,imagen_ruta,contenido) VALUES (?,?,?,?,?)");
                $stmt->bind_param('sssss',$tit,$cat,$fec,$img,$con);
            }
            resp($stmt->execute(), $stmt->execute() ? ($id_edit?'Artículo actualizado.':'Artículo publicado.') : 'Error: '.$conexion->error);
        }

        if ($tipo === 'noticias') {
            $tit = $_POST['titulo']  ?? '';
            $fue = $_POST['fuente']  ?? '';
            $fec = $_POST['fecha']   ?? '';
            $res = $_POST['resumen'] ?? '';
            $img = subir_imagen('imagen','imagen_base64_noticias');

            if ($id_edit > 0) {
                if ($img) {
                    $stmt = $conexion->prepare("UPDATE noticias SET titulo=?,fuente=?,fecha=?,resumen=?,imagen_ruta=? WHERE id=?");
                    $stmt->bind_param('sssssi',$tit,$fue,$fec,$res,$img,$id_edit);
                } else {
                    $stmt = $conexion->prepare("UPDATE noticias SET titulo=?,fuente=?,fecha=?,resumen=? WHERE id=?");
                    $stmt->bind_param('ssssi',$tit,$fue,$fec,$res,$id_edit);
                }
            } else {
                $stmt = $conexion->prepare("INSERT INTO noticias (titulo,fuente,fecha,imagen_ruta,resumen) VALUES (?,?,?,?,?)");
                $stmt->bind_param('sssss',$tit,$fue,$fec,$img,$res);
            }
            resp($stmt->execute(), $id_edit?'Noticia actualizada.':'Noticia publicada.');
        }

        if ($tipo === 'galeria') {
            $cat = $_POST['categoria']   ?? '';
            $des = $_POST['descripcion'] ?? '';
            $img = subir_imagen('imagen','imagen_base64_galeria');

            if ($id_edit > 0) {
                if ($img) {
                    $stmt = $conexion->prepare("UPDATE galeria SET categoria=?,descripcion=?,imagen_ruta=? WHERE id=?");
                    $stmt->bind_param('sssi',$cat,$des,$img,$id_edit);
                } else {
                    $stmt = $conexion->prepare("UPDATE galeria SET categoria=?,descripcion=? WHERE id=?");
                    $stmt->bind_param('ssi',$cat,$des,$id_edit);
                }
            } else {
                if (!$img) resp(false,'Debes subir una imagen para la galería.');
                $stmt = $conexion->prepare("INSERT INTO galeria (categoria,descripcion,imagen_ruta) VALUES (?,?,?)");
                $stmt->bind_param('sss',$cat,$des,$img);
            }
            resp($stmt->execute(), $id_edit?'Imagen actualizada.':'Imagen subida.');
        }

        if ($tipo === 'podcast') {
            $ep  = $_POST['episodio']    ?? '';
            $tit = $_POST['titulo']      ?? '';
            $fec = $_POST['fecha']       ?? '';
            $des = $_POST['descripcion'] ?? '';
            $url = $_POST['audioLink']   ?? '';
            $img = subir_imagen('imagen','imagen_base64_podcast');

            if ($id_edit > 0) {
                if ($img) {
                    $stmt = $conexion->prepare("UPDATE podcasts SET episodio=?,titulo=?,fecha=?,descripcion=?,link_audio=?,imagen_ruta=? WHERE id=?");
                    $stmt->bind_param('ssssssi',$ep,$tit,$fec,$des,$url,$img,$id_edit);
                } else {
                    $stmt = $conexion->prepare("UPDATE podcasts SET episodio=?,titulo=?,fecha=?,descripcion=?,link_audio=? WHERE id=?");
                    $stmt->bind_param('sssssi',$ep,$tit,$fec,$des,$url,$id_edit);
                }
            } else {
                $stmt = $conexion->prepare("INSERT INTO podcasts (episodio,titulo,fecha,imagen_ruta,descripcion,link_audio) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param('ssssss',$ep,$tit,$fec,$img,$des,$url);
            }
            resp($stmt->execute(), $id_edit?'Podcast actualizado.':'Podcast publicado.');
        }

        if ($tipo === 'exploraciones') {
            $tit = $_POST['titulo']      ?? '';
            $ubi = $_POST['ubicacion']   ?? '';
            $fec = $_POST['fecha']       ?? '';
            $des = $_POST['descripcion'] ?? '';
            $vid = $_POST['videoLink']   ?? '';
            $img = subir_imagen('imagen','imagen_base64_exploraciones');

            if ($id_edit > 0) {
                if ($img) {
                    $stmt = $conexion->prepare("UPDATE exploraciones SET titulo=?,ubicacion=?,fecha=?,descripcion=?,link_video=?,imagen_ruta=? WHERE id=?");
                    $stmt->bind_param('ssssssi',$tit,$ubi,$fec,$des,$vid,$img,$id_edit);
                } else {
                    $stmt = $conexion->prepare("UPDATE exploraciones SET titulo=?,ubicacion=?,fecha=?,descripcion=?,link_video=? WHERE id=?");
                    $stmt->bind_param('sssssi',$tit,$ubi,$fec,$des,$vid,$id_edit);
                }
            } else {
                $stmt = $conexion->prepare("INSERT INTO exploraciones (titulo,ubicacion,fecha,descripcion,link_video,imagen_ruta) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param('ssssss',$tit,$ubi,$fec,$des,$vid,$img);
            }
            resp($stmt->execute(), $id_edit?'Exploración actualizada.':'Exploración publicada.');
        }

        if ($tipo === 'tienda') {
            $nom = $_POST['nombre']      ?? '';
            $pre = $_POST['precio']      ?? '';
            $lnk = $_POST['link_compra'] ?? '';
            $des = $_POST['descripcion'] ?? '';
            $img = subir_imagen('imagen','imagen_base64_tienda');

            if ($id_edit > 0) {
                if ($img) {
                    $stmt = $conexion->prepare("UPDATE tienda_productos SET nombre=?,precio=?,descripcion=?,link_compra=?,imagen_ruta=? WHERE id=?");
                    $stmt->bind_param('sdsssi',$nom,$pre,$des,$lnk,$img,$id_edit);
                } else {
                    $stmt = $conexion->prepare("UPDATE tienda_productos SET nombre=?,precio=?,descripcion=?,link_compra=? WHERE id=?");
                    $stmt->bind_param('sdssi',$nom,$pre,$des,$lnk,$id_edit);
                }
            } else {
                $stmt = $conexion->prepare("INSERT INTO tienda_productos (nombre,precio,imagen_ruta,descripcion,link_compra) VALUES (?,?,?,?,?)");
                $stmt->bind_param('sdsss',$nom,$pre,$img,$des,$lnk);
            }
            resp($stmt->execute(), $id_edit?'Producto actualizado.':'Producto publicado.');
        }

        resp(false, 'Formulario no reconocido.');
    }
    resp(false, 'Método no permitido.');
}

// ═══════════════════════════════════════════════════════════
//  LOGIN / LOGOUT
// ═══════════════════════════════════════════════════════════
$error_login = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (!$conexion) {
        $error_login = 'Error BD: ' . ($conexion_error ?? 'Sin conexión');
    } else {
        $pwd = $_POST['password'] ?? '';
        try {
            $r = $conexion->query("SELECT password_admin FROM administradores LIMIT 1");
            if ($r && $r->num_rows > 0) {
                $row = $r->fetch_assoc();
                if ($pwd === $row['password_admin']) {
                    $_SESSION['admin_logged_in'] = true;
                    header('Location: admin.php'); exit;
                } else {
                    $error_login = 'Contraseña incorrecta.';
                }
            } else {
                $error_login = 'No hay administrador configurado. Importa database.sql primero.';
            }
        } catch (Exception $e) {
            $error_login = 'Importa primero el archivo database.sql.';
        }
    }
}
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: admin.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel | Investigador Oxlack</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700;900&family=Cinzel:wght@400;600;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<!-- Quill WYSIWYG -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<?php $admin_css_ver = file_exists(__DIR__ . '/admin.css') ? filemtime(__DIR__ . '/admin.css') : time(); ?>
<link rel="stylesheet" href="admin.css?v=<?php echo $admin_css_ver; ?>">
<style id="admin-critical">
  html, body { margin: 0; padding: 0; }
  body.admin-login-body, body.admin-body {
    background: #0a0a0a;
    color: #f0f0f0;
    font-family: Inter, system-ui, -apple-system, sans-serif;
    min-height: 100vh;
  }
  body.admin-login-body {
    min-height: 100vh;
    padding: 0;
  }
  .admin-login-logo {
    max-height: 72px !important;
    max-width: 200px !important;
    width: auto !important;
    height: auto !important;
    object-fit: contain !important;
    display: block !important;
    margin: 0 auto !important;
  }
  .login-glass-card {
    width: 100%;
    max-width: 420px;
  }
</style>
</head>
<body class="<?php echo $autenticado ? 'admin-body' : 'admin-login-body'; ?>">

<?php if ($conexion_error && !$autenticado): ?>
  <div class="admin-login-page">
    <div class="glass-card login-glass-card">
      <div class="login-brand">
        <p class="login-brand-sub">Panel de Administración</p>
      </div>
      <h2 class="login-title">Acceso Admin</h2>
      <p class="db-error-msg"><strong>Error de base de datos:</strong><br><?php echo htmlspecialchars($conexion_error); ?></p>
      <p class="login-hint">Verifica las credenciales en conexion.php</p>
    </div>
    <p class="login-footer">Investigador Oxlack · Acceso restringido</p>
  </div>

<?php elseif (!$autenticado): ?>
  <div class="admin-login-page">
    <div class="glass-card login-glass-card">
      <div class="login-brand">
        <div class="login-logo-wrap">
          <img src="imagenes/logos/logo oxlack png.png" alt="Oxlack Logo" class="admin-login-logo">
        </div>
        <p class="login-brand-sub">Panel de Administración</p>
      </div>
      <h2 class="login-title">Acceso Admin</h2>
      <form method="POST" action="admin.php" class="login-form">
        <input type="hidden" name="action" value="login">
        <div class="form-group login-field">
          <label class="login-label" for="admin-password">Contraseña</label>
          <div class="login-input-wrap">
            <i class="fas fa-lock login-input-icon" aria-hidden="true"></i>
            <input type="password" id="admin-password" name="password" class="form-control login-input" placeholder="Ingresa tu contraseña" required autofocus>
          </div>
        </div>
        <button type="submit" class="btn-submit w-100 login-submit-btn">
          <i class="fas fa-sign-in-alt"></i> Ingresar
        </button>
      </form>
      <?php if ($error_login): ?>
        <p class="login-error-text"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_login); ?></p>
      <?php endif; ?>
    </div>
    <p class="login-footer">Investigador Oxlack · Acceso restringido</p>
  </div>

<?php else: ?>
<div class="admin-layout">

  <!-- ── Sidebar ── -->
  <aside class="admin-sidebar">
    <div class="admin-logo">
      <img src="imagenes/logos/logo oxlack png.png" alt="Oxlack Logo">
    </div>
    <nav class="admin-nav">
      <button class="admin-nav-btn active" onclick="showPanel('dashboard',this)"><i class="fas fa-tachometer-alt"></i> Dashboard</button>
      <button class="admin-nav-btn" onclick="showPanel('blog',this)"><i class="fas fa-pen-nib"></i> Blog</button>
      <button class="admin-nav-btn" onclick="showPanel('noticias',this)"><i class="fas fa-newspaper"></i> Noticias</button>
      <button class="admin-nav-btn" onclick="showPanel('galeria',this)"><i class="fas fa-images"></i> Galería</button>
      <button class="admin-nav-btn" onclick="showPanel('podcast',this)"><i class="fas fa-podcast"></i> Podcast</button>
      <button class="admin-nav-btn" onclick="showPanel('exploraciones',this)"><i class="fas fa-ghost"></i> Exploraciones</button>
      <button class="admin-nav-btn" onclick="showPanel('tienda',this)"><i class="fas fa-shopping-cart"></i> Tienda</button>
      <button class="admin-nav-btn" onclick="showPanel('evidencias',this)"><i class="fas fa-envelope-open-text"></i> Evidencias</button>
      <button class="admin-nav-btn" onclick="showPanel('config',this)"><i class="fas fa-cog"></i> Configuración</button>
    </nav>
    <div class="admin-logout-container">
      <button class="admin-logout-btn" onclick="window.location.href='admin.php?action=logout'"><i class="fas fa-sign-out-alt"></i> Salir</button>
    </div>
  </aside>

  <!-- ── Main ── -->
  <main class="admin-main">
    <div id="ajax-alert" class="ajax-alert-box" style="display:none;"></div>

    <!-- ══ DASHBOARD ══════════════════════════════════════════ -->
    <section id="panel-dashboard" class="admin-panel-content active">
      <div class="section-header">
        <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
        <p>Resumen general del sitio en tiempo real.</p>
      </div>
      <div class="stats-grid" id="stats-grid">
        <!-- cargado por JS -->
      </div>
      <div class="glass-card">
        <h3 class="card-section-title"><i class="fas fa-info-circle"></i> Guía Rápida</h3>
        <div class="quick-guide-grid">
          <div class="quick-item"><i class="fas fa-plus-circle"></i><span>Usa el <strong>formulario superior</strong> de cada sección para agregar contenido.</span></div>
          <div class="quick-item"><i class="fas fa-edit"></i><span>Haz clic en <strong>Editar</strong> en cualquier fila de la tabla para modificar un elemento.</span></div>
          <div class="quick-item"><i class="fas fa-trash-alt"></i><span>Haz clic en <strong>Eliminar</strong> para borrar un elemento (pedirá confirmación).</span></div>
          <div class="quick-item"><i class="fas fa-cog"></i><span>Ve a <strong>Configuración</strong> para editar textos de portada, Sobre Mí, Contacto, Donaciones y colores.</span></div>
          <div class="quick-item"><i class="fas fa-globe"></i><span>Todo el contenido público se actualiza automáticamente — no necesitas tocar código.</span></div>
        </div>
      </div>
    </section>

    <!-- ══ BLOG ═══════════════════════════════════════════════ -->
    <section id="panel-blog" class="admin-panel-content">
      <div class="section-header">
        <h2>Gestión de Blog</h2>
        <p>Crea y administra tus artículos esotéricos y paranormales.</p>
      </div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-form-blog"><div class="spinner"></div><div class="loader-text">Publicando...</div></div>
        <h3 class="card-section-title"><i class="fas fa-plus"></i> <span id="form-blog-title">Nuevo Artículo</span></h3>
        <form id="form-blog" class="ajax-form" data-type="blog">
          <input type="hidden" name="form_type" value="blog">
          <input type="hidden" name="id_edit" value="0">
          <input type="hidden" name="imagen_base64_blog" id="base64-blog">
          <div class="form-group">
            <label>Título del Artículo</label>
            <input type="text" name="titulo" class="form-control" required>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Categoría</label>
              <select name="categoria" class="form-control" required>
                <option value="tecnicas">Técnicas</option>
                <option value="teoria">Teoría</option>
                <option value="experiencias">Experiencias</option>
                <option value="equipo">Equipo</option>
                <option value="historia">Historia</option>
              </select>
            </div>
            <div class="form-group">
              <label>Fecha de Publicación</label>
              <input type="date" name="fecha" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Imagen Destacada</label>
            <div class="upload-area" id="uploadArea-blog" onclick="document.getElementById('inputImg-blog').click()">
              <div class="upload-placeholder" id="placeholder-blog"><i class="fas fa-crop-alt"></i><p>Haz clic para subir imagen</p></div>
              <div class="image-preview" id="preview-blog"><img src="" id="imgShow-blog" alt="Preview"><div class="change-img-btn">Cambiar Imagen</div></div>
              <input type="file" name="imagen" id="inputImg-blog" accept="image/*" onchange="initImageEditor(this,'blog')">
            </div>
          </div>
          <div class="form-group">
            <label>Contenido</label>
            <div id="quill-blog" class="quill-editor"></div>
            <input type="hidden" name="contenido" id="contenido-blog">
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Publicar Artículo</button>
            <button type="button" class="btn-sec" id="cancel-blog" onclick="cancelEdit('blog')" style="display:none">Cancelar Edición</button>
          </div>
        </form>
      </div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-table-blog"><div class="spinner"></div></div>
        <h3 class="card-section-title"><i class="fas fa-list"></i> Artículos Publicados</h3>
        <table class="data-table">
          <thead><tr><th>Imagen</th><th>Título</th><th>Categoría</th><th>Fecha</th><th>Acciones</th></tr></thead>
          <tbody id="tbody-blog"></tbody>
        </table>
      </div>
    </section>

    <!-- ══ NOTICIAS ════════════════════════════════════════════ -->
    <section id="panel-noticias" class="admin-panel-content">
      <div class="section-header"><h2>Gestión de Noticias</h2><p>Mantén a tu audiencia informada.</p></div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-form-noticias"><div class="spinner"></div><div class="loader-text">Publicando...</div></div>
        <h3 class="card-section-title"><i class="fas fa-plus"></i> <span id="form-noticias-title">Nueva Noticia</span></h3>
        <form id="form-noticias" class="ajax-form" data-type="noticias">
          <input type="hidden" name="form_type" value="noticias">
          <input type="hidden" name="id_edit" value="0">
          <input type="hidden" name="imagen_base64_noticias" id="base64-noticias">
          <div class="form-group"><label>Título</label><input type="text" name="titulo" class="form-control" required></div>
          <div class="form-row">
            <div class="form-group"><label>Fuente</label><input type="text" name="fuente" class="form-control" placeholder="Ej: Paranormal News"></div>
            <div class="form-group"><label>Fecha</label><input type="date" name="fecha" class="form-control" required value="<?php echo date('Y-m-d'); ?>"></div>
          </div>
          <div class="form-group">
            <label>Imagen</label>
            <div class="upload-area" onclick="document.getElementById('inputImg-noticias').click()">
              <div class="upload-placeholder" id="placeholder-noticias"><i class="fas fa-camera"></i><p>Subir imagen</p></div>
              <div class="image-preview" id="preview-noticias"><img src="" id="imgShow-noticias" alt="Preview"><div class="change-img-btn">Cambiar</div></div>
              <input type="file" name="imagen" id="inputImg-noticias" accept="image/*" onchange="initImageEditor(this,'noticias')">
            </div>
          </div>
          <div class="form-group">
            <label>Resumen / Contenido</label>
            <div id="quill-noticias" class="quill-editor"></div>
            <input type="hidden" name="resumen" id="contenido-noticias">
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-submit"><i class="fas fa-bullhorn"></i> Publicar Noticia</button>
            <button type="button" class="btn-sec" id="cancel-noticias" onclick="cancelEdit('noticias')" style="display:none">Cancelar Edición</button>
          </div>
        </form>
      </div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-table-noticias"><div class="spinner"></div></div>
        <h3 class="card-section-title"><i class="fas fa-list"></i> Noticias Publicadas</h3>
        <table class="data-table">
          <thead><tr><th>Imagen</th><th>Título</th><th>Fuente</th><th>Fecha</th><th>Acciones</th></tr></thead>
          <tbody id="tbody-noticias"></tbody>
        </table>
      </div>
    </section>

    <!-- ══ GALERÍA ═════════════════════════════════════════════ -->
    <section id="panel-galeria" class="admin-panel-content">
      <div class="section-header"><h2>Galería de Imágenes</h2><p>Sube imágenes de tus investigaciones y eventos.</p></div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-form-galeria"><div class="spinner"></div><div class="loader-text">Subiendo...</div></div>
        <h3 class="card-section-title"><i class="fas fa-plus"></i> <span id="form-galeria-title">Nueva Imagen</span></h3>
        <form id="form-galeria" class="ajax-form" data-type="galeria">
          <input type="hidden" name="form_type" value="galeria">
          <input type="hidden" name="id_edit" value="0">
          <input type="hidden" name="imagen_base64_galeria" id="base64-galeria">
          <div class="form-row">
            <div class="form-group">
              <label>Categoría</label>
              <select name="categoria" class="form-control" required>
                <option value="fans">Fotos con Fans</option>
                <option value="investigaciones">Investigaciones de Campo</option>
                <option value="eventos">Eventos Especiales</option>
              </select>
            </div>
            <div class="form-group"><label>Descripción / Etiqueta</label><input type="text" name="descripcion" class="form-control" placeholder="Ej: Exploración en hospital..."></div>
          </div>
          <div class="form-group">
            <label>Imagen</label>
            <div class="upload-area" onclick="document.getElementById('inputImg-gal').click()">
              <div class="upload-placeholder" id="placeholder-gal"><i class="fas fa-images"></i><p>Seleccionar y Editar Imagen</p></div>
              <div class="image-preview" id="preview-gal"><img src="" id="imgShow-gal" alt="Preview"><div class="change-img-btn">Cambiar</div></div>
              <input type="file" name="imagen" id="inputImg-gal" accept="image/*" onchange="initImageEditor(this,'galeria')">
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-submit"><i class="fas fa-upload"></i> Subir a Galería</button>
            <button type="button" class="btn-sec" id="cancel-galeria" onclick="cancelEdit('galeria')" style="display:none">Cancelar Edición</button>
          </div>
        </form>
      </div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-table-galeria"><div class="spinner"></div></div>
        <h3 class="card-section-title"><i class="fas fa-list"></i> Imágenes Subidas</h3>
        <table class="data-table">
          <thead><tr><th>Vista</th><th>ID</th><th>Categoría</th><th>Descripción</th><th>Acciones</th></tr></thead>
          <tbody id="tbody-galeria"></tbody>
        </table>
      </div>
    </section>

    <!-- ══ PODCAST ═════════════════════════════════════════════ -->
    <section id="panel-podcast" class="admin-panel-content">
      <div class="section-header"><h2>Episodios de Podcast</h2><p>Añade nuevos episodios a tu canal de audio.</p></div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-form-podcast"><div class="spinner"></div><div class="loader-text">Publicando...</div></div>
        <h3 class="card-section-title"><i class="fas fa-plus"></i> <span id="form-podcast-title">Nuevo Episodio</span></h3>
        <form id="form-podcast" class="ajax-form" data-type="podcast">
          <input type="hidden" name="form_type" value="podcast">
          <input type="hidden" name="id_edit" value="0">
          <input type="hidden" name="imagen_base64_podcast" id="base64-podcast">
          <div class="form-row">
            <div class="form-group"><label>Número/ID de Episodio</label><input type="text" name="episodio" class="form-control" placeholder="Ej: Ep. 45" required></div>
            <div class="form-group"><label>Fecha</label><input type="date" name="fecha" class="form-control" required value="<?php echo date('Y-m-d'); ?>"></div>
          </div>
          <div class="form-group"><label>Título del Episodio</label><input type="text" name="titulo" class="form-control" required></div>
          <div class="form-group">
            <label>Portada del Episodio</label>
            <div class="upload-area" onclick="document.getElementById('inputImg-pod').click()">
              <div class="upload-placeholder" id="placeholder-pod"><i class="fas fa-headphones"></i><p>Subir Portada</p></div>
              <div class="image-preview" id="preview-pod"><img src="" id="imgShow-pod" alt="Preview"><div class="change-img-btn">Cambiar</div></div>
              <input type="file" name="imagen" id="inputImg-pod" accept="image/*" onchange="initImageEditor(this,'podcast')">
            </div>
          </div>
          <div class="form-group">
            <label>Descripción</label>
            <div id="quill-podcast" class="quill-editor"></div>
            <input type="hidden" name="descripcion" id="contenido-podcast">
          </div>
          <div class="form-group"><label>Link de Audio (Spotify / YouTube)</label><input type="url" name="audioLink" class="form-control" placeholder="https://" required></div>
          <div class="form-actions">
            <button type="submit" class="btn-submit"><i class="fas fa-broadcast-tower"></i> Publicar Episodio</button>
            <button type="button" class="btn-sec" id="cancel-podcast" onclick="cancelEdit('podcast')" style="display:none">Cancelar Edición</button>
          </div>
        </form>
      </div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-table-podcast"><div class="spinner"></div></div>
        <h3 class="card-section-title"><i class="fas fa-list"></i> Podcasts</h3>
        <table class="data-table">
          <thead><tr><th>Portada</th><th>Episodio</th><th>Título</th><th>Fecha</th><th>Acciones</th></tr></thead>
          <tbody id="tbody-podcast"></tbody>
        </table>
      </div>
    </section>

    <!-- ══ EXPLORACIONES ══════════════════════════════════════ -->
    <section id="panel-exploraciones" class="admin-panel-content">
      <div class="section-header"><h2>Registro de Exploraciones</h2><p>Documenta tus investigaciones en lugares abandonados.</p></div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-form-exploraciones"><div class="spinner"></div><div class="loader-text">Publicando...</div></div>
        <h3 class="card-section-title"><i class="fas fa-plus"></i> <span id="form-exploraciones-title">Nueva Exploración</span></h3>
        <form id="form-exploraciones" class="ajax-form" data-type="exploraciones">
          <input type="hidden" name="form_type" value="exploraciones">
          <input type="hidden" name="id_edit" value="0">
          <input type="hidden" name="imagen_base64_exploraciones" id="base64-exploraciones">
          <div class="form-group"><label>Título de la Exploración</label><input type="text" name="titulo" class="form-control" required></div>
          <div class="form-row">
            <div class="form-group"><label>Ubicación</label><input type="text" name="ubicacion" class="form-control" required></div>
            <div class="form-group"><label>Fecha</label><input type="date" name="fecha" class="form-control" required value="<?php echo date('Y-m-d'); ?>"></div>
          </div>
          <div class="form-group">
            <label>Imagen Destacada</label>
            <div class="upload-area" onclick="document.getElementById('inputImg-exp').click()">
              <div class="upload-placeholder" id="placeholder-exp"><i class="fas fa-map-marked-alt"></i><p>Subir Portada</p></div>
              <div class="image-preview" id="preview-exp"><img src="" id="imgShow-exp" alt="Preview"><div class="change-img-btn">Cambiar</div></div>
              <input type="file" name="imagen" id="inputImg-exp" accept="image/*" onchange="initImageEditor(this,'exploraciones')">
            </div>
          </div>
          <div class="form-group">
            <label>Descripción detallada</label>
            <div id="quill-exploraciones" class="quill-editor"></div>
            <input type="hidden" name="descripcion" id="contenido-exploraciones">
          </div>
          <div class="form-group"><label>Link del Video Documental (Opcional)</label><input type="url" name="videoLink" class="form-control" placeholder="https://"></div>
          <div class="form-actions">
            <button type="submit" class="btn-submit"><i class="fas fa-video"></i> Publicar Exploración</button>
            <button type="button" class="btn-sec" id="cancel-exploraciones" onclick="cancelEdit('exploraciones')" style="display:none">Cancelar Edición</button>
          </div>
        </form>
      </div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-table-exploraciones"><div class="spinner"></div></div>
        <h3 class="card-section-title"><i class="fas fa-list"></i> Exploraciones</h3>
        <table class="data-table">
          <thead><tr><th>Título</th><th>Ubicación</th><th>Fecha</th><th>Acciones</th></tr></thead>
          <tbody id="tbody-exploraciones"></tbody>
        </table>
      </div>
    </section>

    <!-- ══ TIENDA ══════════════════════════════════════════════ -->
    <section id="panel-tienda" class="admin-panel-content">
      <div class="section-header"><h2>Gestión de Tienda</h2><p>Agrega productos, libros o merchandising oficial.</p></div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-form-tienda"><div class="spinner"></div><div class="loader-text">Guardando...</div></div>
        <h3 class="card-section-title"><i class="fas fa-plus"></i> <span id="form-tienda-title">Nuevo Producto</span></h3>
        <form id="form-tienda" class="ajax-form" data-type="tienda">
          <input type="hidden" name="form_type" value="tienda">
          <input type="hidden" name="id_edit" value="0">
          <input type="hidden" name="imagen_base64_tienda" id="base64-tienda">
          <div class="form-row">
            <div class="form-group"><label>Nombre del Producto</label><input type="text" name="nombre" class="form-control" required></div>
            <div class="form-group"><label>Precio</label><input type="number" step="0.01" name="precio" class="form-control" required></div>
          </div>
          <div class="form-group">
            <label>Imagen del Producto</label>
            <div class="upload-area" onclick="document.getElementById('inputImg-tienda').click()">
              <div class="upload-placeholder" id="placeholder-tienda"><i class="fas fa-box-open"></i><p>Subir Imagen</p></div>
              <div class="image-preview" id="preview-tienda"><img src="" id="imgShow-tienda" alt="Preview"><div class="change-img-btn">Cambiar</div></div>
              <input type="file" name="imagen" id="inputImg-tienda" accept="image/*" onchange="initImageEditor(this,'tienda')">
            </div>
          </div>
          <div class="form-group">
            <label>Descripción</label>
            <div id="quill-tienda" class="quill-editor"></div>
            <input type="hidden" name="descripcion" id="contenido-tienda">
          </div>
          <div class="form-group"><label>Enlace de Compra (Checkout externo)</label><input type="url" name="link_compra" class="form-control" required></div>
          <div class="form-actions">
            <button type="submit" class="btn-submit"><i class="fas fa-shopping-cart"></i> Publicar Producto</button>
            <button type="button" class="btn-sec" id="cancel-tienda" onclick="cancelEdit('tienda')" style="display:none">Cancelar Edición</button>
          </div>
        </form>
      </div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-table-tienda"><div class="spinner"></div></div>
        <h3 class="card-section-title"><i class="fas fa-list"></i> Productos en Tienda</h3>
        <table class="data-table">
          <thead><tr><th>Imagen</th><th>Producto</th><th>Precio</th><th>Fecha</th><th>Acciones</th></tr></thead>
          <tbody id="tbody-tienda"></tbody>
        </table>
      </div>
    </section>

    <!-- ══ EVIDENCIAS ══════════════════════════════════════════ -->
    <section id="panel-evidencias" class="admin-panel-content">
      <div class="section-header"><h2>Evidencias de la Comunidad</h2><p>Revisa y modera los reportes enviados por tus seguidores.</p></div>
      <div class="glass-card">
        <div class="loader-overlay" id="loader-table-evidencias"><div class="spinner"></div></div>
        <h3 class="card-section-title"><i class="fas fa-list"></i> Reportes Recibidos</h3>
        <table class="data-table">
          <thead><tr><th>Caso</th><th>Tipo</th><th>Remitente</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody id="tbody-evidencias"></tbody>
        </table>
      </div>
    </section>

    <!-- ══ CONFIGURACIÓN DEL SITIO ════════════════════════════ -->
    <section id="panel-config" class="admin-panel-content">
      <div class="section-header">
        <h2><i class="fas fa-cog"></i> Configuración del Sitio</h2>
        <p>Administra todos los textos, colores y redes sociales de tu página sin programar.</p>
      </div>
      <div class="loader-overlay" id="loader-config"><div class="spinner"></div><div class="loader-text">Guardando...</div></div>

      <!-- Vista previa en tiempo real -->
      <div class="glass-card config-live-preview-wrap">
        <h3 class="card-section-title"><i class="fas fa-eye"></i> Vista Previa en Tiempo Real</h3>
        <p class="config-preview-hint">Los cambios se ven al instante aquí y se guardan automáticamente al soltar el color o tras 1.5 segundos.</p>
        <div class="config-preview-grid">
          <div id="config-live-preview" class="config-live-preview">
            <div class="preview-nav" id="preview-nav">
              <div class="preview-logo" id="preview-logo">Oxlack</div>
            </div>
            <div class="preview-hero" id="preview-hero">
              <p class="preview-tag" id="preview-tag">Investigador Paranormal</p>
              <h4 id="preview-title">INVESTIGADOR OXLACK</h4>
              <p class="preview-sub" id="preview-sub">Subtítulo del sitio</p>
              <div class="preview-btns">
                <span class="preview-btn-primary" id="preview-btn1">Explorar</span>
                <span class="preview-btn-secondary" id="preview-btn2">Contactar</span>
              </div>
            </div>
            <div class="preview-section" id="preview-section">
              <span class="preview-label" id="preview-label">Sección de ejemplo</span>
              <div class="preview-card"><div class="preview-card-bar"></div><div class="preview-card-body"></div></div>
            </div>
          </div>
          <div class="config-preview-actions">
            <div id="config-save-status" class="config-save-status"><i class="fas fa-check-circle"></i> Sincronizado</div>
            <button type="button" class="btn-submit" onclick="saveConfig(false)"><i class="fas fa-save"></i> Guardar Ahora</button>
            <a href="index.php" target="_blank" class="btn-sec" id="btn-ver-sitio"><i class="fas fa-external-link-alt"></i> Ver Sitio en Vivo</a>
          </div>
        </div>
      </div>

      <form id="form-config">

        <!-- Identidad del sitio -->
        <div class="glass-card">
          <h3 class="card-section-title"><i class="fas fa-globe"></i> Identidad del Sitio</h3>
          <div class="form-row">
            <div class="form-group"><label>Título del Sitio</label><input type="text" name="site_title" id="cfg-site_title" class="form-control" placeholder="Investigador Oxlack"></div>
            <div class="form-group"><label>Tagline / Eslogan</label><input type="text" name="site_tagline" id="cfg-site_tagline" class="form-control"></div>
          </div>
          <div class="form-group"><label>Descripción SEO (meta description)</label><textarea name="site_description" id="cfg-site_description" class="form-control" rows="2"></textarea></div>
          <div class="form-group"><label>Texto del Logo (si no usas imagen, o como texto alternativo)</label><input type="text" name="logo_texto" id="cfg-logo_texto" class="form-control"></div>
          <div class="form-group">
            <label>Imagen del Logo (PNG/JPG recomendado, fondo transparente)</label>
            <input type="hidden" name="logo_imagen" id="cfg-logo_imagen">
            <input type="hidden" name="imagen_base64_logo" id="base64-logo">
            <div class="upload-area" onclick="document.getElementById('inputImg-logo').click()">
              <div class="upload-placeholder" id="placeholder-logo"><i class="fas fa-image"></i><p>Subir Logo</p></div>
              <div class="image-preview" id="preview-logo" style="display:none"><img src="" id="imgShow-logo" alt="Logo"><div class="change-img-btn">Cambiar</div></div>
              <input type="file" name="imagen_logo" id="inputImg-logo" accept="image/*" onchange="initImageEditor(this,'logo')">
            </div>
            <label class="checkbox-inline" style="margin-top:0.75rem;display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
              <input type="checkbox" name="logo_mostrar_texto" id="cfg-logo_mostrar_texto" value="1">
              Mostrar texto junto a la imagen del logo
            </label>
          </div>
          <div class="form-group">
            <label>Favicon (icono de la pestaña del navegador)</label>
            <input type="hidden" name="favicon" id="cfg-favicon">
            <input type="hidden" name="imagen_base64_favicon" id="base64-favicon">
            <div class="upload-area" onclick="document.getElementById('inputImg-favicon').click()">
              <div class="upload-placeholder" id="placeholder-favicon"><i class="fas fa-star"></i><p>Subir Favicon</p></div>
              <div class="image-preview" id="preview-favicon" style="display:none"><img src="" id="imgShow-favicon" alt="Favicon"><div class="change-img-btn">Cambiar</div></div>
              <input type="file" name="imagen_favicon" id="inputImg-favicon" accept="image/*" onchange="initImageEditor(this,'favicon')">
            </div>
          </div>
        </div>

        <!-- Hero (portada) -->
        <div class="glass-card">
          <h3 class="card-section-title"><i class="fas fa-star"></i> Portada (Hero)</h3>
          <div class="form-group"><label>Etiqueta superior (ej: Investigador Paranormal)</label><input type="text" name="hero_tag" id="cfg-hero_tag" class="form-control"></div>
          <div class="form-group"><label>Título Principal del Hero</label><input type="text" name="hero_titulo" id="cfg-hero_titulo" class="form-control"></div>
          <div class="form-group"><label>Subtítulo / Frase del Hero</label><input type="text" name="hero_subtitulo" id="cfg-hero_subtitulo" class="form-control"></div>
          <div class="form-row">
            <div class="form-group"><label>Texto botón "Explorar"</label><input type="text" name="hero_btn_explorar" id="cfg-hero_btn_explorar" class="form-control"></div>
            <div class="form-group"><label>Texto botón "Contactar"</label><input type="text" name="hero_btn_contactar" id="cfg-hero_btn_contactar" class="form-control"></div>
          </div>
          <div class="form-group">
            <label>Imagen de fondo del Hero (portada)</label>
            <input type="hidden" name="hero_imagen" id="cfg-hero_imagen">
            <input type="hidden" name="imagen_base64_hero" id="base64-hero">
            <div class="upload-area" onclick="document.getElementById('inputImg-hero').click()">
              <div class="upload-placeholder" id="placeholder-hero"><i class="fas fa-panorama"></i><p>Subir Fondo Hero</p></div>
              <div class="image-preview" id="preview-hero" style="display:none"><img src="" id="imgShow-hero" alt="Hero"><div class="change-img-btn">Cambiar</div></div>
              <input type="file" name="imagen_hero" id="inputImg-hero" accept="image/*" onchange="initImageEditor(this,'hero')">
            </div>
          </div>
        </div>

        <!-- Textos de secciones en portada -->
        <div class="glass-card">
          <h3 class="card-section-title"><i class="fas fa-th-large"></i> Etiquetas de Secciones (Portada)</h3>
          <p style="color:#aaa;font-size:0.9rem;margin-bottom:1rem;">Estos textos aparecen sobre cada sección en la página principal.</p>
          <div class="form-row">
            <div class="form-group"><label>Blog</label><input type="text" name="sec_blog_label" id="cfg-sec_blog_label" class="form-control"></div>
            <div class="form-group"><label>Noticias</label><input type="text" name="sec_noticias_label" id="cfg-sec_noticias_label" class="form-control"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Podcast</label><input type="text" name="sec_podcast_label" id="cfg-sec_podcast_label" class="form-control"></div>
            <div class="form-group"><label>Exploraciones</label><input type="text" name="sec_exploraciones_label" id="cfg-sec_exploraciones_label" class="form-control"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Galería</label><input type="text" name="sec_galeria_label" id="cfg-sec_galeria_label" class="form-control"></div>
            <div class="form-group"><label>Tienda</label><input type="text" name="sec_tienda_label" id="cfg-sec_tienda_label" class="form-control"></div>
          </div>
          <div class="form-group"><label>Donaciones</label><input type="text" name="sec_donaciones_label" id="cfg-sec_donaciones_label" class="form-control"></div>
        </div>

        <!-- Footer -->
        <div class="glass-card">
          <h3 class="card-section-title"><i class="fas fa-align-center"></i> Pie de Página</h3>
          <div class="form-group"><label>Texto principal del footer</label><input type="text" name="footer_texto" id="cfg-footer_texto" class="form-control"></div>
          <div class="form-group"><label>Copyright</label><input type="text" name="footer_copyright" id="cfg-footer_copyright" class="form-control"></div>
        </div>

        <!-- Colores -->
        <div class="glass-card">
          <h3 class="card-section-title"><i class="fas fa-palette"></i> Colores del Tema</h3>
          <p class="config-preview-hint">Mueve los selectores de color: la vista previa arriba se actualiza al instante y se guarda solo.</p>
          <div class="form-row">
            <div class="form-group">
              <label>Color Primario (botones, acentos)</label>
              <div class="color-input-wrap">
                <input type="color" id="cfg-color_primario" class="color-picker" value="#8b0000">
                <input type="text" name="color_primario" id="cfg-color_primario_text" class="form-control" placeholder="#8b0000">
              </div>
            </div>
            <div class="form-group">
              <label>Color de Acento (brillos, hover)</label>
              <div class="color-input-wrap">
                <input type="color" id="cfg-color_acento" class="color-picker" value="#ff0000">
                <input type="text" name="color_acento" id="cfg-color_acento_text" class="form-control" placeholder="#ff0000">
              </div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Color de Fondo (páginas)</label>
              <div class="color-input-wrap">
                <input type="color" id="cfg-color_fondo" class="color-picker" value="#e8e4dc">
                <input type="text" name="color_fondo" id="cfg-color_fondo_text" class="form-control" placeholder="#e8e4dc">
              </div>
            </div>
            <div class="form-group">
              <label>Color de Navegación (barra superior)</label>
              <div class="color-input-wrap">
                <input type="color" id="cfg-color_nav" class="color-picker" value="#0a0a0a">
                <input type="text" name="color_nav" id="cfg-color_nav_text" class="form-control" placeholder="#0a0a0a">
              </div>
            </div>
          </div>
        </div>

        <!-- Redes Sociales -->
        <div class="glass-card">
          <h3 class="card-section-title"><i class="fas fa-share-alt"></i> Redes Sociales</h3>
          <div class="form-row">
            <div class="form-group"><label><i class="fab fa-youtube"></i> YouTube</label><input type="url" name="redes_youtube" id="cfg-redes_youtube" class="form-control" placeholder="https://youtube.com/@..."></div>
            <div class="form-group"><label><i class="fab fa-instagram"></i> Instagram</label><input type="url" name="redes_instagram" id="cfg-redes_instagram" class="form-control" placeholder="https://instagram.com/..."></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label><i class="fab fa-facebook"></i> Facebook</label><input type="url" name="redes_facebook" id="cfg-redes_facebook" class="form-control" placeholder="https://facebook.com/..."></div>
            <div class="form-group"><label><i class="fab fa-spotify"></i> Spotify / Podcast</label><input type="url" name="redes_spotify" id="cfg-redes_spotify" class="form-control" placeholder="https://open.spotify.com/..."></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label><i class="fab fa-tiktok"></i> TikTok</label><input type="url" name="redes_tiktok" id="cfg-redes_tiktok" class="form-control" placeholder="https://tiktok.com/@..."></div>
            <div class="form-group"><label><i class="fab fa-x-twitter"></i> Twitter / X</label><input type="url" name="redes_twitter" id="cfg-redes_twitter" class="form-control" placeholder="https://x.com/..."></div>
          </div>
          <div class="form-group"><label><i class="fab fa-whatsapp"></i> WhatsApp (wa.me/...)</label><input type="url" name="redes_whatsapp" id="cfg-redes_whatsapp" class="form-control" placeholder="https://wa.me/521234567890"></div>
        </div>

        <!-- Donaciones -->
        <div class="glass-card">
          <h3 class="card-section-title"><i class="fas fa-heart"></i> Links de Donación</h3>
          <div class="form-row">
            <div class="form-group"><label><i class="fab fa-paypal"></i> PayPal</label><input type="url" name="paypal_link" id="cfg-paypal_link" class="form-control" placeholder="https://paypal.me/..."></div>
            <div class="form-group"><label><i class="fab fa-patreon"></i> Patreon</label><input type="url" name="patreon_link" id="cfg-patreon_link" class="form-control" placeholder="https://patreon.com/..."></div>
          </div>
          <div class="form-group"><label><i class="fab fa-amazon"></i> Lista de Amazon</label><input type="url" name="amazon_link" id="cfg-amazon_link" class="form-control" placeholder="https://amazon.com.mx/..."></div>
          <hr style="border-color:#333;margin:1.5rem 0;">
          <p style="color:#aaa;font-size:0.9rem;margin-bottom:1rem;">Textos que aparecen en las tarjetas de donación:</p>
          <div class="form-group"><label>Descripción PayPal</label><textarea name="donacion_paypal_texto" id="cfg-donacion_paypal_texto" class="form-control" rows="2"></textarea></div>
          <div class="form-group"><label>Descripción Patreon</label><textarea name="donacion_patreon_texto" id="cfg-donacion_patreon_texto" class="form-control" rows="2"></textarea></div>
          <div class="form-group"><label>Descripción Amazon</label><textarea name="donacion_amazon_texto" id="cfg-donacion_amazon_texto" class="form-control" rows="2"></textarea></div>
        </div>

        <!-- Sobre Mí -->
        <div class="glass-card">
          <h3 class="card-section-title"><i class="fas fa-user"></i> Página "Sobre Mí"</h3>
          <div class="form-group"><label>Etiqueta de sección</label><input type="text" name="sobremi_label" id="cfg-sobremi_label" class="form-control"></div>
          <div class="form-group"><label>Nombre del investigador</label><input type="text" name="sobremi_nombre" id="cfg-sobremi_nombre" class="form-control"></div>
          <div class="form-group"><label>Primer párrafo</label><textarea name="sobremi_parrafo1" id="cfg-sobremi_parrafo1" class="form-control" rows="3"></textarea></div>
          <div class="form-group"><label>Segundo párrafo</label><textarea name="sobremi_parrafo2" id="cfg-sobremi_parrafo2" class="form-control" rows="3"></textarea></div>
          <div class="form-row">
            <div class="form-group"><label>Estadística 1 — Número</label><input type="text" name="sobremi_stat1_num" id="cfg-sobremi_stat1_num" class="form-control" placeholder="200+"></div>
            <div class="form-group"><label>Estadística 1 — Etiqueta</label><input type="text" name="sobremi_stat1_label" id="cfg-sobremi_stat1_label" class="form-control" placeholder="Casos"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Estadística 2 — Número</label><input type="text" name="sobremi_stat2_num" id="cfg-sobremi_stat2_num" class="form-control"></div>
            <div class="form-group"><label>Estadística 2 — Etiqueta</label><input type="text" name="sobremi_stat2_label" id="cfg-sobremi_stat2_label" class="form-control"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Estadística 3 — Número</label><input type="text" name="sobremi_stat3_num" id="cfg-sobremi_stat3_num" class="form-control"></div>
            <div class="form-group"><label>Estadística 3 — Etiqueta</label><input type="text" name="sobremi_stat3_label" id="cfg-sobremi_stat3_label" class="form-control"></div>
          </div>
          <div class="form-group">
            <label>Foto de perfil</label>
            <input type="hidden" name="imagen_base64_sobremi" id="base64-sobremi">
            <input type="hidden" name="sobremi_foto" id="cfg-sobremi_foto">
            <div class="upload-area" onclick="document.getElementById('inputImg-sobremi').click()">
              <div class="upload-placeholder" id="placeholder-sobremi"><i class="fas fa-user"></i><p>Subir Foto</p></div>
              <div class="image-preview" id="preview-sobremi" style="display:none"><img src="" id="imgShow-sobremi" alt="Preview"><div class="change-img-btn">Cambiar</div></div>
              <input type="file" name="imagen_sobremi" id="inputImg-sobremi" accept="image/*" onchange="initImageEditor(this,'sobremi')">
            </div>
          </div>
        </div>

        <!-- Contacto -->
        <div class="glass-card">
          <h3 class="card-section-title"><i class="fas fa-address-book"></i> Página "Contacto"</h3>
          <div class="form-row">
            <div class="form-group"><label>Etiqueta de sección</label><input type="text" name="contacto_label" id="cfg-contacto_label" class="form-control"></div>
            <div class="form-group"><label>Título principal</label><input type="text" name="contacto_titulo" id="cfg-contacto_titulo" class="form-control"></div>
          </div>
          <p style="color:#aaa;font-size:0.9rem;margin-bottom:1rem;">Descripción de cada red social (solo se muestra si configuraste el link arriba):</p>
          <div class="form-group"><label>YouTube</label><input type="text" name="contacto_youtube_texto" id="cfg-contacto_youtube_texto" class="form-control"></div>
          <div class="form-group"><label>Instagram</label><input type="text" name="contacto_instagram_texto" id="cfg-contacto_instagram_texto" class="form-control"></div>
          <div class="form-group"><label>Facebook</label><input type="text" name="contacto_facebook_texto" id="cfg-contacto_facebook_texto" class="form-control"></div>
          <div class="form-group"><label>TikTok</label><input type="text" name="contacto_tiktok_texto" id="cfg-contacto_tiktok_texto" class="form-control"></div>
          <div class="form-group"><label>Twitter / X</label><input type="text" name="contacto_twitter_texto" id="cfg-contacto_twitter_texto" class="form-control"></div>
          <div class="form-group"><label>WhatsApp</label><input type="text" name="contacto_whatsapp_texto" id="cfg-contacto_whatsapp_texto" class="form-control"></div>
        </div>

        <!-- Evidencias -->
        <div class="glass-card">
          <h3 class="card-section-title"><i class="fas fa-envelope-open-text"></i> Página "Enviar Evidencia"</h3>
          <div class="form-group"><label>Título de la página</label><input type="text" name="evidencias_titulo" id="cfg-evidencias_titulo" class="form-control"></div>
          <div class="form-group"><label>Texto introductorio</label><textarea name="evidencias_intro" id="cfg-evidencias_intro" class="form-control" rows="2"></textarea></div>
        </div>

        <div class="form-actions" style="padding: 0 0 2rem 0;">
          <button type="button" class="btn-submit" onclick="saveConfig(false)"><i class="fas fa-save"></i> Guardar Toda la Configuración</button>
        </div>
      </form>
    </section>

  </main>
</div>

<!-- ── Modal Imagen (Cropper) ──────────────────────────────── -->
<div id="cropperModal" class="cropper-modal">
  <div class="cropper-modal-content">
    <div class="cropper-header">
      <h3>Editar Imagen</h3>
      <span class="close-modal" onclick="closeCropper()">&times;</span>
    </div>
    <div class="img-container"><img id="imageToCrop" src=""></div>
    <div class="cropper-actions">
      <button type="button" class="btn-sec" onclick="closeCropper()">Cancelar</button>
      <button type="button" class="btn-submit" onclick="cropAndSave()">Recortar y Aplicar</button>
    </div>
  </div>
</div>

<!-- ── Modal de Confirmación ──────────────────────────────── -->
<div id="confirmModal" class="confirm-modal">
  <div class="confirm-box">
    <div class="confirm-icon"><i class="fas fa-exclamation-triangle"></i></div>
    <h3>¿Confirmar eliminación?</h3>
    <p id="confirm-msg">Esta acción no se puede deshacer.</p>
    <div class="confirm-actions">
      <button class="btn-sec" onclick="closeConfirm()">Cancelar</button>
      <button class="btn-danger" id="confirm-ok-btn">Sí, eliminar</button>
    </div>
  </div>
</div>

<!-- ── Modal de Edición de Evidencia ────────────────────────── -->
<div id="evidModal" class="confirm-modal">
  <div class="confirm-box" style="max-width:520px;width:95%">
    <div class="confirm-header">
      <h3 id="evid-titulo">Revisión de Evidencia</h3>
      <span class="close-modal" onclick="closeEvidModal()">&times;</span>
    </div>
    <div class="form-group" style="margin-top:1rem">
      <label>Estado</label>
      <select id="evid-estado" class="form-control">
        <option value="pendiente">Pendiente</option>
        <option value="aceptado">Aceptado</option>
        <option value="rechazado">Rechazado</option>
      </select>
    </div>
    <div class="form-group">
      <label>Notas del Admin</label>
      <textarea id="evid-notas" class="form-control" rows="3" placeholder="Notas internas..."></textarea>
    </div>
    <div class="confirm-actions">
      <button class="btn-sec" onclick="closeEvidModal()">Cancelar</button>
      <button class="btn-submit" id="evid-save-btn">Guardar Cambios</button>
    </div>
    <input type="hidden" id="evid-id">
  </div>
</div>

<!-- ── Scripts ──────────────────────────────────────────────── -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.7/quill.js"></script>
<script>
// ──────────────────────────────────────────────────────────────
//  QUILL — inicializar editores
// ──────────────────────────────────────────────────────────────
const quillEditors = {};
const quillFields  = ['blog','noticias','podcast','exploraciones','tienda'];
const quillToolbar = [
  [{ header: [2, 3, false] }],
  ['bold','italic','underline','strike'],
  [{ color: [] },{ background: [] }],
  [{ list: 'ordered' },{ list: 'bullet' }],
  ['link','blockquote'],
  ['clean']
];

quillFields.forEach(f => {
  quillEditors[f] = new Quill(`#quill-${f}`, {
    theme: 'snow',
    placeholder: 'Escribe el contenido aquí...',
    modules: { toolbar: quillToolbar }
  });
  quillEditors[f].on('text-change', () => {
    document.getElementById(`contenido-${f}`).value = quillEditors[f].root.innerHTML;
  });
});

// ──────────────────────────────────────────────────────────────
//  INIT
// ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  document.querySelectorAll('.ajax-form').forEach(form => {
    form.addEventListener('submit', e => { e.preventDefault(); submitAjaxForm(form); });
  });
});

// ──────────────────────────────────────────────────────────────
//  NAV / PANELS
// ──────────────────────────────────────────────────────────────
function showPanel(id, btn) {
  document.querySelectorAll('.admin-panel-content').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.admin-nav-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('panel-' + id).classList.add('active');
  btn.classList.add('active');
  if (id !== 'dashboard' && id !== 'config') loadTableData(id);
  if (id === 'dashboard') loadStats();
  if (id === 'config')    loadConfig();
}

// ──────────────────────────────────────────────────────────────
//  ALERTAS AJAX
// ──────────────────────────────────────────────────────────────
function showAlert(msg, ok) {
  const box = document.getElementById('ajax-alert');
  box.className = 'ajax-alert-box ' + (ok ? 'alert-success' : 'alert-error');
  box.innerHTML = `<i class="fas fa-${ok ? 'check-circle' : 'exclamation-triangle'}"></i> ${msg}`;
  box.style.display = 'flex';
  box.style.opacity = '1';
  clearTimeout(box._t);
  box._t = setTimeout(() => { box.style.opacity = '0'; setTimeout(()=>box.style.display='none',400); }, 4500);
}

// ──────────────────────────────────────────────────────────────
//  DASHBOARD — estadísticas
// ──────────────────────────────────────────────────────────────
function loadStats() {
  fetch('admin.php?ajax=1&action=get_stats')
    .then(r => r.json())
    .then(res => {
      if (!res.success) return;
      const d = res.data;
      const labels = {
        blog_articulos:     ['Artículos de Blog',   'fas fa-pen-nib'],
        noticias:           ['Noticias',             'fas fa-newspaper'],
        galeria:            ['Imágenes en Galería',  'fas fa-images'],
        podcasts:           ['Episodios Podcast',    'fas fa-podcast'],
        exploraciones:      ['Exploraciones',        'fas fa-ghost'],
        tienda_productos:   ['Productos en Tienda',  'fas fa-shopping-cart'],
        reportes_evidencia: ['Evidencias Recibidas', 'fas fa-envelope-open-text']
      };
      const g = document.getElementById('stats-grid');
      g.innerHTML = '';
      for (const [key, [label, icon]] of Object.entries(labels)) {
        g.innerHTML += `
          <div class="stat-card">
            <div class="stat-icon"><i class="${icon}"></i></div>
            <div class="stat-info">
              <div class="stat-number">${d[key] ?? 0}</div>
              <div class="stat-label">${label}</div>
            </div>
          </div>`;
      }
    });
}

// ──────────────────────────────────────────────────────────────
//  SUBMIT AJAX FORM
// ──────────────────────────────────────────────────────────────
function submitAjaxForm(form) {
  const type   = form.dataset.type;
  const loader = document.getElementById('loader-form-' + type);
  if (loader) loader.classList.add('active');

  // Sincronizar Quill si existe
  if (quillEditors[type]) {
    document.getElementById('contenido-' + type).value = quillEditors[type].root.innerHTML;
  }

  const fd = new FormData(form);
  fetch('admin.php?ajax=1', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (loader) loader.classList.remove('active');
      showAlert(res.message, res.success);
      if (res.success) {
        cancelEdit(type);  // limpia form y botones
        loadTableData(type);
        loadStats();
      }
    })
    .catch(() => {
      if (loader) loader.classList.remove('active');
      showAlert('Error de conexión al guardar.', false);
    });
}

// ──────────────────────────────────────────────────────────────
//  CARGAR TABLAS
// ──────────────────────────────────────────────────────────────
function loadTableData(type) {
  const tbody  = document.getElementById('tbody-' + type);
  const loader = document.getElementById('loader-table-' + type);
  if (!tbody) return;
  if (loader) loader.classList.add('active');

  fetch(`admin.php?ajax=1&action=get_${type}`)
    .then(r => r.json())
    .then(res => {
      if (loader) loader.classList.remove('active');
      if (res.success) renderTable(type, tbody, res.data);
    })
    .catch(() => { if (loader) loader.classList.remove('active'); });
}

// ──────────────────────────────────────────────────────────────
//  RENDER TABLA
// ──────────────────────────────────────────────────────────────
function renderTable(type, tbody, data) {
  tbody.innerHTML = '';
  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="7" class="empty-msg"><i class="fas fa-inbox"></i> No hay registros todavía.</td></tr>`;
    return;
  }

  const tableMap = {
    blog:        'blog_articulos',
    noticias:    'noticias',
    galeria:     'galeria',
    podcast:     'podcasts',
    exploraciones:'exploraciones',
    tienda:      'tienda_productos',
    evidencias:  'reportes_evidencia'
  };

  data.forEach(item => {
    let img = '';
    if (item.imagen_ruta) img = `<img src="${item.imagen_ruta}" class="table-thumb" alt="">`;

    let cols = '';
    const editBtn   = `<button class="btn-action edit"   onclick="editItem('${type}',${item.id})"><i class="fas fa-edit"></i></button>`;
    const deleteBtn = `<button class="btn-action delete" onclick="confirmDelete('${tableMap[type]}',${item.id},'${esc(item.titulo||item.nombre||item.descripcion||'este elemento')}')"><i class="fas fa-trash"></i></button>`;
    const actions   = `<div class="action-btns">${editBtn}${deleteBtn}</div>`;

    switch(type) {
      case 'blog':
        cols = `<td>${img}</td><td><span class="item-title">${esc(item.titulo)}</span></td><td><span class="badge">${esc(item.categoria)}</span></td><td><span class="item-meta">${item.fecha}</span></td><td>${actions}</td>`;
        break;
      case 'noticias':
        cols = `<td>${img}</td><td><span class="item-title">${esc(item.titulo)}</span></td><td><span class="badge">${esc(item.fuente)}</span></td><td><span class="item-meta">${item.fecha}</span></td><td>${actions}</td>`;
        break;
      case 'galeria':
        cols = `<td>${img}</td><td><span class="item-title">IMG-${item.id}</span></td><td><span class="badge">${esc(item.categoria)}</span></td><td><span class="item-meta">${esc(item.descripcion)}</span></td><td>${actions}</td>`;
        break;
      case 'podcast':
        cols = `<td>${img}</td><td><span class="badge">${esc(item.episodio)}</span></td><td><span class="item-title">${esc(item.titulo)}</span></td><td><span class="item-meta">${item.fecha}</span></td><td>${actions}</td>`;
        break;
      case 'exploraciones':
        cols = `<td><span class="item-title">${esc(item.titulo)}</span></td><td><span class="item-meta">${esc(item.ubicacion)}</span></td><td><span class="item-meta">${item.fecha}</span></td><td>${actions}</td>`;
        break;
      case 'tienda':
        cols = `<td>${img}</td><td><span class="item-title">${esc(item.nombre)}</span></td><td><span class="badge neon">$${item.precio}</span></td><td><span class="item-meta">${(item.fecha_registro||'').split(' ')[0]}</span></td><td>${actions}</td>`;
        break;
      case 'evidencias': {
        const est  = (item.estado||'pendiente').toLowerCase();
        const cls  = est==='aceptado'?'badge neon status-green':(est==='rechazado'?'badge status-red':'badge neon status-orange');
        const revBtn = `<button class="btn-action edit" title="Revisar" onclick="openEvidModal(${item.id},'${esc(item.titulo_caso)}','${est}')"><i class="fas fa-eye"></i> Revisar</button>`;
        cols = `<td><span class="item-title">${esc(item.titulo_caso)}</span></td><td><span class="badge">${esc(item.tipo_evidencia)}</span></td><td><span class="item-meta">${esc(item.nombre_cliente)}</span></td><td><span class="item-meta">${(item.fecha_registro||'').split(' ')[0]}</span></td><td><span class="${cls}">${est.toUpperCase()}</span></td><td><div class="action-btns">${revBtn}</div></td>`;
        break;
      }
    }

    const tr = document.createElement('tr');
    tr.className = 'data-row';
    tr.innerHTML = cols;
    tbody.appendChild(tr);
  });
}

// ──────────────────────────────────────────────────────────────
//  EDITAR ITEM
// ──────────────────────────────────────────────────────────────
const tableMap2 = {
  blog:'blog_articulos', noticias:'noticias', galeria:'galeria',
  podcast:'podcasts', exploraciones:'exploraciones', tienda:'tienda_productos'
};

function editItem(type, id) {
  fetch(`admin.php?ajax=1&action=get_item&table=${tableMap2[type]}&id=${id}`)
    .then(r => r.json())
    .then(res => {
      if (!res.success || !res.data) { showAlert('No se pudo cargar el elemento.', false); return; }
      const d = res.data;
      const form = document.getElementById('form-' + type);
      form.querySelector('[name="id_edit"]').value = id;

      // Rellenar campos comunes
      const set = (n, v) => { const el = form.querySelector(`[name="${n}"]`); if(el) el.value = v||''; };
      switch(type) {
        case 'blog':
          set('titulo', d.titulo); set('categoria', d.categoria); set('fecha', d.fecha);
          quillEditors.blog.root.innerHTML = d.contenido||'';
          document.getElementById('contenido-blog').value = d.contenido||'';
          if(d.imagen_ruta) showExistingImg('blog', d.imagen_ruta);
          break;
        case 'noticias':
          set('titulo', d.titulo); set('fuente', d.fuente); set('fecha', d.fecha);
          quillEditors.noticias.root.innerHTML = d.resumen||'';
          document.getElementById('contenido-noticias').value = d.resumen||'';
          if(d.imagen_ruta) showExistingImg('noticias', d.imagen_ruta);
          break;
        case 'galeria':
          set('categoria', d.categoria); set('descripcion', d.descripcion);
          if(d.imagen_ruta) showExistingImg('galeria', d.imagen_ruta);
          break;
        case 'podcast':
          set('episodio', d.episodio); set('titulo', d.titulo); set('fecha', d.fecha);
          set('audioLink', d.link_audio);
          quillEditors.podcast.root.innerHTML = d.descripcion||'';
          document.getElementById('contenido-podcast').value = d.descripcion||'';
          if(d.imagen_ruta) showExistingImg('podcast', d.imagen_ruta);
          break;
        case 'exploraciones':
          set('titulo', d.titulo); set('ubicacion', d.ubicacion); set('fecha', d.fecha);
          set('videoLink', d.link_video);
          quillEditors.exploraciones.root.innerHTML = d.descripcion||'';
          document.getElementById('contenido-exploraciones').value = d.descripcion||'';
          if(d.imagen_ruta) showExistingImg('exploraciones', d.imagen_ruta);
          break;
        case 'tienda':
          set('nombre', d.nombre); set('precio', d.precio); set('link_compra', d.link_compra);
          quillEditors.tienda.root.innerHTML = d.descripcion||'';
          document.getElementById('contenido-tienda').value = d.descripcion||'';
          if(d.imagen_ruta) showExistingImg('tienda', d.imagen_ruta);
          break;
      }

      // Actualizar UI del formulario
      const titleSpan = document.getElementById('form-' + type + '-title');
      if (titleSpan) titleSpan.textContent = 'Editando elemento #' + id;
      const cancelBtn = document.getElementById('cancel-' + type);
      if (cancelBtn) cancelBtn.style.display = 'inline-flex';

      // Scroll al formulario
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

function showExistingImg(type, path) {
  const shortType = {galeria:'gal', podcast:'pod', exploraciones:'exp'}[type] || type;
  document.getElementById('placeholder-' + shortType).style.display = 'none';
  document.getElementById('preview-'    + shortType).style.display  = 'block';
  document.getElementById('imgShow-'    + shortType).src = path;
}

function cancelEdit(type) {
  const form = document.getElementById('form-' + type);
  if (!form) return;
  form.reset();
  form.querySelector('[name="id_edit"]').value = 0;
  if (quillEditors[type]) quillEditors[type].root.innerHTML = '';
  const titleSpan = document.getElementById('form-' + type + '-title');
  const titles = {blog:'Nuevo Artículo', noticias:'Nueva Noticia', galeria:'Nueva Imagen', podcast:'Nuevo Episodio', exploraciones:'Nueva Exploración', tienda:'Nuevo Producto'};
  if (titleSpan) titleSpan.textContent = titles[type]||'Nuevo';
  const cancelBtn = document.getElementById('cancel-' + type);
  if (cancelBtn) cancelBtn.style.display = 'none';
  resetImagePreview(type);
}

// ──────────────────────────────────────────────────────────────
//  ELIMINAR con confirmación
// ──────────────────────────────────────────────────────────────
let pendingDelete = null;
function confirmDelete(table, id, name) {
  pendingDelete = { table, id };
  document.getElementById('confirm-msg').textContent = `¿Eliminar "${name}"? Esta acción no se puede deshacer.`;
  document.getElementById('confirmModal').classList.add('active');
  document.getElementById('confirm-ok-btn').onclick = () => {
    closeConfirm();
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('table',  table);
    fd.append('id',     id);
    fetch('admin.php?ajax=1', { method:'POST', body: fd })
      .then(r => r.json())
      .then(res => {
        showAlert(res.success ? 'Elemento eliminado.' : ('Error: '+res.message), res.success);
        if (res.success) {
          // Reload active panel table
          const typeMap = {
            blog_articulos:'blog', noticias:'noticias', galeria:'galeria',
            podcasts:'podcast', exploraciones:'exploraciones', tienda_productos:'tienda', reportes_evidencia:'evidencias'
          };
          const t = typeMap[table];
          if (t) loadTableData(t);
          loadStats();
        }
      });
  };
}
function closeConfirm() { document.getElementById('confirmModal').classList.remove('active'); }

// ──────────────────────────────────────────────────────────────
//  EVIDENCIAS — Modal de revisión
// ──────────────────────────────────────────────────────────────
function openEvidModal(id, titulo, estado) {
  document.getElementById('evid-id').value     = id;
  document.getElementById('evid-titulo').textContent = titulo;
  document.getElementById('evid-estado').value  = estado;
  document.getElementById('evid-notas').value   = '';
  document.getElementById('evidModal').classList.add('active');
  document.getElementById('evid-save-btn').onclick = () => saveEvidencia(id);
}
function closeEvidModal() { document.getElementById('evidModal').classList.remove('active'); }
function saveEvidencia(id) {
  const estado = document.getElementById('evid-estado').value;
  const notas  = document.getElementById('evid-notas').value;
  const fd = new FormData();
  fd.append('action','update_estado');
  fd.append('id', id);
  fd.append('estado', estado);
  fd.append('notas_admin', notas);
  fetch('admin.php?ajax=1', { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      closeEvidModal();
      showAlert(res.message, res.success);
      if(res.success) loadTableData('evidencias');
    });
}

// ──────────────────────────────────────────────────────────────
//  CONFIGURACIÓN — vista previa en tiempo real + auto-guardado
// ──────────────────────────────────────────────────────────────
let autoSaveTimer = null;
let colorSaveTimer = null;
let colorPairsSynced = new Set();
let configPreviewReady = false;

function normalizeHex(hex, fallback = '#8b0000') {
  if (!hex) return fallback;
  hex = String(hex).trim();
  if (hex[0] !== '#') hex = '#' + hex;
  if (hex.length === 4) hex = '#' + hex[1]+hex[1]+hex[2]+hex[2]+hex[3]+hex[3];
  return /^#[0-9a-fA-F]{6}$/.test(hex) ? hex.toLowerCase() : fallback;
}

function adjustHexBrightness(hex, pct) {
  hex = normalizeHex(hex);
  let r = parseInt(hex.slice(1,3), 16);
  let g = parseInt(hex.slice(3,5), 16);
  let b = parseInt(hex.slice(5,7), 16);
  r = Math.max(0, Math.min(255, Math.round(r + 255 * (pct / 100))));
  g = Math.max(0, Math.min(255, Math.round(g + 255 * (pct / 100))));
  b = Math.max(0, Math.min(255, Math.round(b + 255 * (pct / 100))));
  return '#' + [r,g,b].map(x => x.toString(16).padStart(2,'0')).join('');
}

function setSaveStatus(state) {
  const el = document.getElementById('config-save-status');
  if (!el) return;
  if (state === 'pending') {
    el.innerHTML = '<i class="fas fa-sync fa-spin"></i> Guardando...';
    el.className = 'config-save-status pending';
  } else if (state === 'ok') {
    el.innerHTML = '<i class="fas fa-check-circle"></i> Sincronizado — visible en el sitio';
    el.className = 'config-save-status ok';
  } else {
    el.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error al guardar';
    el.className = 'config-save-status error';
  }
}

function updateLivePreview() {
  const prev = document.getElementById('config-live-preview');
  if (!prev) return;

  const primary = normalizeHex(document.getElementById('cfg-color_primario_text')?.value, '#8b0000');
  const accent  = normalizeHex(document.getElementById('cfg-color_acento_text')?.value, '#ff0000');
  const fondo   = normalizeHex(document.getElementById('cfg-color_fondo_text')?.value, '#e8e4dc');
  const nav     = normalizeHex(document.getElementById('cfg-color_nav_text')?.value, '#0a0a0a');

  prev.style.setProperty('--preview-primary', primary);
  prev.style.setProperty('--preview-accent', accent);
  prev.style.setProperty('--preview-bg', fondo);
  prev.style.setProperty('--preview-nav', nav);
  prev.style.setProperty('--preview-dark', adjustHexBrightness(primary, -35));

  const logoText = document.getElementById('cfg-logo_texto')?.value || 'Logo';
  const logoImg  = document.getElementById('imgShow-logo')?.src || document.getElementById('cfg-logo_imagen')?.value || '';
  const showText = document.getElementById('cfg-logo_mostrar_texto')?.checked;
  const logoEl = document.getElementById('preview-logo');
  if (logoEl) {
    if (logoImg) {
      logoEl.innerHTML = `<img src="${logoImg}" alt="">${showText ? `<span>${esc(logoText)}</span>` : ''}`;
    } else {
      logoEl.textContent = logoText;
    }
  }

  const setTxt = (id, srcId, fallback = '') => {
    const el = document.getElementById(id);
    const src = document.getElementById(srcId);
    if (el && src) el.textContent = src.value || fallback;
  };
  setTxt('preview-tag', 'cfg-hero_tag', 'Etiqueta');
  setTxt('preview-title', 'cfg-hero_titulo', 'Título');
  setTxt('preview-sub', 'cfg-hero_subtitulo', 'Subtítulo');
  setTxt('preview-btn1', 'cfg-hero_btn_explorar', 'Explorar');
  setTxt('preview-btn2', 'cfg-hero_btn_contactar', 'Contactar');
  setTxt('preview-label', 'cfg-sec_blog_label', 'Sección');

  const heroEl = document.getElementById('preview-hero');
  const heroSrc = document.getElementById('imgShow-hero')?.src || document.getElementById('cfg-hero_imagen')?.value || '';
  if (heroEl) {
    heroEl.style.backgroundImage = heroSrc
      ? `linear-gradient(rgba(0,0,0,0.72), rgba(0,0,0,0.72)), url('${heroSrc}')`
      : 'none';
    heroEl.style.backgroundSize = 'cover';
    heroEl.style.backgroundPosition = 'center';
  }

  const sectionEl = document.getElementById('preview-section');
  if (sectionEl) sectionEl.style.background = fondo;
}

function scheduleAutoSave(delay = 1500) {
  setSaveStatus('pending');
  clearTimeout(autoSaveTimer);
  autoSaveTimer = setTimeout(() => saveConfig(true), delay);
}

function scheduleColorSave() {
  updateLivePreview();
  setSaveStatus('pending');
  clearTimeout(colorSaveTimer);
  colorSaveTimer = setTimeout(() => saveConfig(true), 600);
}

function initConfigLivePreview() {
  if (configPreviewReady) return;
  configPreviewReady = true;

  ['logo_texto','hero_tag','hero_titulo','hero_subtitulo','hero_btn_explorar','hero_btn_contactar','sec_blog_label'].forEach(key => {
    const el = document.getElementById('cfg-' + key);
    if (el) el.addEventListener('input', () => { updateLivePreview(); scheduleAutoSave(); });
  });

  document.getElementById('cfg-logo_mostrar_texto')?.addEventListener('change', () => {
    updateLivePreview();
    scheduleAutoSave();
  });

  ['color_primario','color_acento','color_fondo','color_nav'].forEach(syncColorPicker);
  updateLivePreview();
}

function showConfigImage(type, path) {
  if (!path) return;
  const ph = document.getElementById('placeholder-' + type);
  const pr = document.getElementById('preview-' + type);
  const im = document.getElementById('imgShow-' + type);
  if (ph && pr && im) {
    ph.style.display = 'none';
    pr.style.display = 'block';
    im.src = path + (path.includes('?') ? '&' : '?') + 't=' + Date.now();
  }
}

function loadConfig() {
  const loader = document.getElementById('loader-config');
  loader.classList.add('active');
  fetch('admin.php?ajax=1&action=get_config')
    .then(r => r.json())
    .then(res => {
      loader.classList.remove('active');
      if (!res.success) return;
      const cfg = res.data;
      for (const [key, val] of Object.entries(cfg)) {
        const el = document.getElementById('cfg-' + key);
        if (el && el.type !== 'checkbox') el.value = val;
        const txt = document.getElementById('cfg-' + key + '_text');
        if (txt) {
          txt.value = val;
          const picker = document.getElementById('cfg-' + key);
          if (picker && picker.type === 'color' && /^#[0-9a-fA-F]{6}$/i.test(val)) picker.value = val;
        }
      }
      const chk = document.getElementById('cfg-logo_mostrar_texto');
      if (chk) chk.checked = cfg.logo_mostrar_texto === '1';

      if (cfg.logo_imagen) showConfigImage('logo', cfg.logo_imagen);
      if (cfg.favicon) showConfigImage('favicon', cfg.favicon);
      if (cfg.hero_imagen) showConfigImage('hero', cfg.hero_imagen);
      if (cfg.sobremi_foto) showConfigImage('sobremi', cfg.sobremi_foto);

      if (cfg.config_version) {
        const btn = document.getElementById('btn-ver-sitio');
        if (btn) btn.href = 'index.php?v=' + cfg.config_version;
      }

      initConfigLivePreview();
      updateLivePreview();
      setSaveStatus('ok');
    })
    .catch(() => loader.classList.remove('active'));
}

function syncColorPicker(name) {
  if (colorPairsSynced.has(name)) return;
  colorPairsSynced.add(name);
  const picker = document.getElementById('cfg-' + name);
  const text   = document.getElementById('cfg-' + name + '_text');
  if (!picker || !text) return;
  if (text.value && /^#[0-9a-fA-F]{6}$/i.test(text.value)) picker.value = text.value;
  picker.addEventListener('input', () => {
    text.value = picker.value;
    scheduleColorSave();
  });
  text.addEventListener('input', () => {
    if (/^#[0-9a-fA-F]{6}$/i.test(text.value)) picker.value = text.value;
    scheduleColorSave();
  });
}

function saveConfig(silent = false) {
  const loader = document.getElementById('loader-config');
  if (!silent) loader.classList.add('active');
  setSaveStatus('pending');

  const fd = new FormData(document.getElementById('form-config'));
  fd.append('action', 'save_config');
  fd.set('logo_mostrar_texto', document.getElementById('cfg-logo_mostrar_texto')?.checked ? '1' : '0');

  ['color_primario','color_acento','color_fondo','color_nav'].forEach(n => {
    const txt = document.getElementById('cfg-' + n + '_text');
    if (txt && txt.value) fd.set(n, normalizeHex(txt.value));
  });

  fetch('admin.php?ajax=1', { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (!silent) loader.classList.remove('active');
      if (!silent) showAlert(res.message, res.success);
      if (res.success) {
        setSaveStatus('ok');
        if (res.data) {
          if (res.data.version) {
            const btn = document.getElementById('btn-ver-sitio');
            if (btn) btn.href = 'index.php?v=' + res.data.version;
          }
          ['logo_imagen','favicon','hero_imagen'].forEach(k => {
            if (res.data[k]) {
              const hidden = document.getElementById('cfg-' + k);
              if (hidden) hidden.value = res.data[k];
              const type = k === 'logo_imagen' ? 'logo' : k === 'hero_imagen' ? 'hero' : 'favicon';
              showConfigImage(type, res.data[k]);
              const b64 = document.getElementById('base64-' + type);
              if (b64) b64.value = '';
            }
          });
        }
        updateLivePreview();
      } else {
        setSaveStatus('error');
      }
    })
    .catch(() => {
      if (!silent) loader.classList.remove('active');
      setSaveStatus('error');
      if (!silent) showAlert('Error de conexión.', false);
    });
}

// ──────────────────────────────────────────────────────────────
//  CROPPER.JS
// ──────────────────────────────────────────────────────────────
let cropper, currentType;

function initImageEditor(input, typeId) {
  currentType = typeId;
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img = document.getElementById('imageToCrop');
    img.src = e.target.result;
    document.getElementById('cropperModal').style.display = 'flex';
    if (cropper) cropper.destroy();
    cropper = new Cropper(img, {
      aspectRatio: (typeId === 'favicon') ? 1 : NaN,
      viewMode: 1,
      background: false
    });
  };
  reader.readAsDataURL(input.files[0]);
}

function closeCropper() {
  document.getElementById('cropperModal').style.display = 'none';
  if (cropper) cropper.destroy();
}

function cropAndSave() {
  if (!cropper) return;
  const canvas = cropper.getCroppedCanvas({ maxWidth: 1920, maxHeight: 1080 });
  const data   = canvas.toDataURL('image/png');
  const shortMap = { galeria:'gal', podcast:'pod', exploraciones:'exp', sobremi:'sobremi' };
  const s = shortMap[currentType] || currentType;

  document.getElementById('placeholder-' + s).style.display = 'none';
  document.getElementById('preview-'     + s).style.display = 'block';
  document.getElementById('imgShow-'     + s).src = data;
  document.getElementById('base64-'      + currentType).value = data;
  closeCropper();
  if (typeof updateLivePreview === 'function') updateLivePreview();
  if (typeof scheduleAutoSave === 'function') scheduleAutoSave(800);
}

function resetImagePreview(type) {
  const shortMap = { galeria:'gal', podcast:'pod', exploraciones:'exp', sobremi:'sobremi' };
  const s = shortMap[type] || type;
  const ph = document.getElementById('placeholder-' + s);
  const pr = document.getElementById('preview-'     + s);
  const im = document.getElementById('imgShow-'     + s);
  const b  = document.getElementById('base64-'      + type);
  if (ph) ph.style.display = 'block';
  if (pr) pr.style.display = 'none';
  if (im) im.src = '';
  if (b)  b.value = '';
}

// ──────────────────────────────────────────────────────────────
//  UTIL
// ──────────────────────────────────────────────────────────────
function esc(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
</script>
<?php endif; ?>
</body>
</html>