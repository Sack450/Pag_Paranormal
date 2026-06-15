<?php
require_once 'site_config.php';

$page_title = cfg('site_title');
$page_desc  = cfg('site_description');

// ── Cargar datos desde la BD ──────────────────────────────
function fetchRows($conexion, string $sql, int $limit = 4): array {
    if (!$conexion) return [];
    $res = $conexion->query($sql . " LIMIT $limit");
    $rows = [];
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

$blogs        = fetchRows($conexion, "SELECT * FROM blog_articulos       ORDER BY fecha DESC");
$noticias     = fetchRows($conexion, "SELECT * FROM noticias             ORDER BY fecha DESC");
$podcasts     = fetchRows($conexion, "SELECT * FROM podcasts             ORDER BY fecha DESC");
$exploraciones= fetchRows($conexion, "SELECT * FROM exploraciones        ORDER BY fecha DESC");
$galeria      = fetchRows($conexion, "SELECT * FROM galeria              ORDER BY id DESC", 6);
$productos    = fetchRows($conexion, "SELECT * FROM tienda_productos     ORDER BY fecha_registro DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?php echo $page_desc; ?>">
<title><?php echo $page_title; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Creepster&family=Cinzel+Decorative:wght@400;700;900&family=Cinzel:wght@400;600;900&family=IM+Fell+English:ital@0;1&family=Special+Elite&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="styles.css">
<?php require __DIR__ . '/_theme.php'; ?>
<style>
  .section-5-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1.5rem; }
  .ver-mas-container { text-align:center; margin-top:2.5rem; }
  <?php if (cfgRaw('hero_imagen')): ?>
  #inicio {
    background-image: linear-gradient(rgba(0,0,0,0.72), rgba(0,0,0,0.72)), url('<?php echo theme_asset_url(cfgRaw('hero_imagen')); ?>');
    background-size: cover;
    background-position: center;
  }
  <?php endif; ?>
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <?php render_logo('nav-logo', 'index.php'); ?>
  <div class="nav-social-mini">
    <?php if(cfgRaw('redes_youtube'))   echo '<a href="'.htmlspecialchars(cfgRaw('redes_youtube')).'"   target="_blank"><i class="fab fa-youtube"></i></a>'; ?>
    <?php if(cfgRaw('redes_instagram')) echo '<a href="'.htmlspecialchars(cfgRaw('redes_instagram')).'" target="_blank"><i class="fab fa-instagram"></i></a>'; ?>
    <?php if(cfgRaw('redes_tiktok'))    echo '<a href="'.htmlspecialchars(cfgRaw('redes_tiktok')).'"    target="_blank"><i class="fab fa-tiktok"></i></a>'; ?>
    <?php if(cfgRaw('redes_facebook'))  echo '<a href="'.htmlspecialchars(cfgRaw('redes_facebook')).'"  target="_blank"><i class="fab fa-facebook"></i></a>'; ?>
  </div>
</nav>

<!-- HERO -->
<section id="inicio">
  <div class="hero">
    <div class="hero-content">
      <p class="hero-tag"><?php echo cfg('hero_tag'); ?></p>
      <div class="hero-logo-container">
        <img src="imagenes/logos/INVESTIGADOR OXLACK.png" alt="Investigador Oxlack" class="hero-logo-img">
      </div>
      <p class="hero-subtitle"><?php echo cfg('hero_subtitulo'); ?></p>
      <div class="hero-buttons">
        <a href="#contenido" class="btn-primary"><?php echo cfg('hero_btn_explorar'); ?></a>
        <a href="contacto.php" class="btn-secondary"><?php echo cfg('hero_btn_contactar'); ?></a>
      </div>
    </div>
  </div>
</section>

<div id="contenido">

  <!-- ── BLOG ─────────────────────────────────────────────── -->
  <section class="section bg-alt">
    <div class="container">
      <div class="section-header">
        <span class="section-label"><?php echo cfg('sec_blog_label'); ?></span>
        <h2 class="section-title">BLOG <span class="rojo">PARANORMAL</span></h2>
        <div class="section-line"><i class="fas fa-pen-fancy"></i></div>
      </div>

      <?php if (empty($blogs)): ?>
        <p class="empty-section"><i class="fas fa-ghost"></i> Próximamente se publicarán artículos...</p>
      <?php else: ?>
      <div class="section-5-grid">
        <?php foreach ($blogs as $a): ?>
        <div class="inv-card">
          <div class="inv-img-placeholder">
            <?php if ($a['imagen_ruta']): ?>
              <img src="<?php echo htmlspecialchars($a['imagen_ruta']); ?>" alt="<?php echo htmlspecialchars($a['titulo']); ?>" loading="lazy">
            <?php else: ?>
              <i class="fas fa-pen-nib"></i>
            <?php endif; ?>
          </div>
          <div class="inv-card-body">
            <span class="inv-tag"><?php echo htmlspecialchars($a['categoria']); ?></span>
            <h3 class="inv-card-title"><?php echo htmlspecialchars($a['titulo']); ?></h3>
            <p class="inv-card-desc"><?php echo htmlspecialchars(mb_substr(strip_tags($a['contenido']), 0, 120)) . '...'; ?></p>
            <div class="inv-card-meta">
              <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($a['fecha']); ?></span>
              <a href="articulo.php?id=<?php echo $a['id']; ?>" class="read-more">Leer más <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="ver-mas-container">
        <a href="blog.php" class="btn-primary"><i class="fas fa-arrow-right"></i> Ver todos los artículos</a>
      </div>
    </div>
  </section>

  <!-- ── NOTICIAS ──────────────────────────────────────────── -->
  <section class="section">
    <div class="container">
      <div class="section-header">
        <span class="section-label"><?php echo cfg('sec_noticias_label'); ?></span>
        <h2 class="section-title">NOTICIAS <span class="rojo">PARANORMALES</span></h2>
        <div class="section-line"><i class="fas fa-newspaper"></i></div>
      </div>

      <?php if (empty($noticias)): ?>
        <p class="empty-section"><i class="fas fa-satellite"></i> Próximamente habrá noticias...</p>
      <?php else: ?>
      <div class="section-5-grid">
        <?php foreach ($noticias as $n): ?>
        <div class="inv-card">
          <div class="inv-img-placeholder">
            <?php if ($n['imagen_ruta']): ?>
              <img src="<?php echo htmlspecialchars($n['imagen_ruta']); ?>" alt="<?php echo htmlspecialchars($n['titulo']); ?>" loading="lazy">
            <?php else: ?>
              <i class="fas fa-newspaper"></i>
            <?php endif; ?>
          </div>
          <div class="inv-card-body">
            <?php if ($n['fuente']): ?><span class="inv-tag"><?php echo htmlspecialchars($n['fuente']); ?></span><?php endif; ?>
            <h3 class="inv-card-title"><?php echo htmlspecialchars($n['titulo']); ?></h3>
            <p class="inv-card-desc"><?php echo htmlspecialchars(mb_substr(strip_tags($n['resumen']), 0, 120)) . '...'; ?></p>
            <div class="inv-card-meta">
              <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($n['fecha']); ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="ver-mas-container">
        <a href="noticias.php" class="btn-primary"><i class="fas fa-arrow-right"></i> Ver todas las noticias</a>
      </div>
    </div>
  </section>

  <!-- ── PODCAST ───────────────────────────────────────────── -->
  <section class="section bg-alt">
    <div class="container">
      <div class="section-header">
        <span class="section-label"><?php echo cfg('sec_podcast_label'); ?></span>
        <h2 class="section-title">PODCAST <span class="rojo">PARANORMAL</span></h2>
        <div class="section-line"><i class="fas fa-microphone"></i></div>
      </div>

      <?php if (empty($podcasts)): ?>
        <p class="empty-section"><i class="fas fa-headphones"></i> Próximamente se publicarán episodios...</p>
      <?php else: ?>
      <div class="section-5-grid">
        <?php foreach ($podcasts as $p): ?>
        <div class="inv-card">
          <div class="inv-img-placeholder">
            <?php if ($p['imagen_ruta']): ?>
              <img src="<?php echo htmlspecialchars($p['imagen_ruta']); ?>" alt="<?php echo htmlspecialchars($p['titulo']); ?>" loading="lazy">
            <?php else: ?>
              <i class="fas fa-podcast"></i>
            <?php endif; ?>
          </div>
          <div class="inv-card-body">
            <span class="inv-tag"><?php echo htmlspecialchars($p['episodio']); ?></span>
            <h3 class="inv-card-title"><?php echo htmlspecialchars($p['titulo']); ?></h3>
            <p class="inv-card-desc"><?php echo htmlspecialchars(mb_substr(strip_tags($p['descripcion']), 0, 100)) . '...'; ?></p>
            <div class="inv-card-meta">
              <span><i class="fas fa-headphones"></i> <?php echo htmlspecialchars($p['fecha']); ?></span>
              <?php if ($p['link_audio']): ?>
              <a href="<?php echo htmlspecialchars($p['link_audio']); ?>" target="_blank" class="read-more">Escuchar <i class="fas fa-play"></i></a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="ver-mas-container">
        <a href="podcast.php" class="btn-primary"><i class="fas fa-arrow-right"></i> Ver todos los episodios</a>
      </div>
    </div>
  </section>

  <!-- ── EXPLORACIONES ─────────────────────────────────────── -->
  <section class="section">
    <div class="container">
      <div class="section-header">
        <span class="section-label"><?php echo cfg('sec_exploraciones_label'); ?></span>
        <h2 class="section-title">EXPLORACIONES <span class="rojo">PARANORMALES</span></h2>
        <div class="section-line"><i class="fas fa-video"></i></div>
      </div>

      <?php if (empty($exploraciones)): ?>
        <p class="empty-section"><i class="fas fa-ghost"></i> Próximamente se publicarán exploraciones...</p>
      <?php else: ?>
      <div class="section-5-grid">
        <?php foreach ($exploraciones as $e): ?>
        <div class="inv-card">
          <div class="inv-img-placeholder">
            <?php if (!empty($e['imagen_ruta'])): ?>
              <img src="<?php echo htmlspecialchars($e['imagen_ruta']); ?>" alt="<?php echo htmlspecialchars($e['titulo']); ?>" loading="lazy">
            <?php else: ?>
              <i class="fas fa-ghost"></i>
            <?php endif; ?>
          </div>
          <div class="inv-card-body">
            <span class="inv-tag">Exploración</span>
            <h3 class="inv-card-title"><?php echo htmlspecialchars($e['titulo']); ?></h3>
            <p class="inv-card-desc"><?php echo htmlspecialchars(mb_substr(strip_tags($e['descripcion']), 0, 100)) . '...'; ?></p>
            <div class="inv-card-meta">
              <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($e['ubicacion']); ?></span>
              <?php if ($e['link_video']): ?>
              <a href="<?php echo htmlspecialchars($e['link_video']); ?>" target="_blank" class="read-more">Ver video <i class="fas fa-play-circle"></i></a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="ver-mas-container">
        <a href="exploraciones.php" class="btn-primary"><i class="fas fa-arrow-right"></i> Ver todas las exploraciones</a>
      </div>
    </div>
  </section>

  <!-- ── GALERÍA ───────────────────────────────────────────── -->
  <section class="section bg-alt">
    <div class="container">
      <div class="section-header">
        <span class="section-label"><?php echo cfg('sec_galeria_label'); ?></span>
        <h2 class="section-title">GALERÍA <span class="rojo">PARANORMAL</span></h2>
        <div class="section-line"><i class="fas fa-camera"></i></div>
      </div>

      <?php if (empty($galeria)): ?>
        <p class="empty-section"><i class="fas fa-images"></i> Próximamente habrá imágenes...</p>
      <?php else: ?>
      <div class="galeria-grid" style="margin-bottom:2rem;">
        <?php foreach ($galeria as $g): ?>
        <div class="galeria-item">
          <img src="<?php echo htmlspecialchars($g['imagen_ruta']); ?>" alt="<?php echo htmlspecialchars($g['descripcion'] ?? 'Galería'); ?>" loading="lazy">
          <div class="galeria-overlay"><i class="fas fa-search-plus"></i></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="ver-mas-container">
        <a href="galeria.php" class="btn-primary"><i class="fas fa-images"></i> Ver galería completa</a>
      </div>
    </div>
  </section>

  <!-- ── TIENDA ─────────────────────────────────────────────── -->
  <?php if (!empty($productos)): ?>
  <section class="section">
    <div class="container">
      <div class="section-header">
        <span class="section-label"><?php echo cfg('sec_tienda_label'); ?></span>
        <h2 class="section-title">TIENDA <span class="rojo">OXLACK</span></h2>
        <div class="section-line"><i class="fas fa-shopping-bag"></i></div>
      </div>
      <div class="section-5-grid">
        <?php foreach ($productos as $pr): ?>
        <div class="inv-card">
          <div class="inv-img-placeholder">
            <?php if ($pr['imagen_ruta']): ?>
              <img src="<?php echo htmlspecialchars($pr['imagen_ruta']); ?>" alt="<?php echo htmlspecialchars($pr['nombre']); ?>" loading="lazy">
            <?php else: ?>
              <i class="fas fa-box-open"></i>
            <?php endif; ?>
          </div>
          <div class="inv-card-body">
            <span class="inv-tag">Producto</span>
            <h3 class="inv-card-title"><?php echo htmlspecialchars($pr['nombre']); ?></h3>
            <p class="inv-card-desc"><?php echo htmlspecialchars(mb_substr(strip_tags($pr['descripcion']), 0, 100)) . '...'; ?></p>
            <div class="inv-card-meta">
              <span style="color:var(--rojo);font-weight:bold;">$<?php echo number_format((float)$pr['precio'],2); ?></span>
              <?php if ($pr['link_compra']): ?>
              <a href="<?php echo htmlspecialchars($pr['link_compra']); ?>" target="_blank" class="read-more">Comprar <i class="fas fa-shopping-cart"></i></a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="ver-mas-container">
        <a href="tienda.php" class="btn-primary"><i class="fas fa-shopping-bag"></i> Ver toda la tienda</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── SOBRE MÍ ─────────────────────────────────────────── -->
  <section class="section" id="sobremi">
    <div class="container">
      <div class="section-header">
        <span class="section-label"><?php echo cfg('sobremi_label'); ?></span>
        <h2 class="section-title">SOBRE <span class="rojo">MÍ</span></h2>
        <div class="section-line"><i class="fas fa-user"></i></div>
      </div>

      <div class="sobremi-preview">
        <div class="sobremi-preview-img">
          <?php if (cfgRaw('sobremi_foto')): ?>
            <img src="<?php echo htmlspecialchars(cfgRaw('sobremi_foto')); ?>" alt="<?php echo htmlspecialchars(cfg('sobremi_nombre')); ?>" loading="lazy">
          <?php else: ?>
            <div class="inv-img-placeholder" style="aspect-ratio:1;"><i class="fas fa-user-secret" style="font-size:4rem;"></i></div>
          <?php endif; ?>
        </div>
        <div class="sobremi-preview-text">
          <h3><?php echo cfg('sobremi_nombre'); ?></h3>
          <p><?php echo cfg('sobremi_parrafo1'); ?></p>
          <div class="stats">
            <div class="stat"><span class="num"><?php echo cfg('sobremi_stat1_num'); ?></span><span class="label"><?php echo cfg('sobremi_stat1_label'); ?></span></div>
            <div class="stat"><span class="num"><?php echo cfg('sobremi_stat2_num'); ?></span><span class="label"><?php echo cfg('sobremi_stat2_label'); ?></span></div>
            <div class="stat"><span class="num"><?php echo cfg('sobremi_stat3_num'); ?></span><span class="label"><?php echo cfg('sobremi_stat3_label'); ?></span></div>
          </div>
          <a href="sobremi.php" class="btn-primary"><i class="fas fa-user"></i> Conoce mi historia completa</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ── DONACIONES ────────────────────────────────────────── -->
  <?php if (cfgRaw('paypal_link') || cfgRaw('patreon_link') || cfgRaw('amazon_link')): ?>
  <section class="section bg-alt">
    <div class="container">
      <div class="section-header">
        <span class="section-label"><?php echo cfg('sec_donaciones_label'); ?></span>
        <h2 class="section-title">DONACIONES <span class="rojo">PARANORMALES</span></h2>
        <div class="section-line"><i class="fas fa-heart"></i></div>
      </div>
      <div class="donaciones-grid">
        <?php if(cfgRaw('paypal_link')): ?>
        <div class="donacion-card">
          <i class="fab fa-paypal" style="font-size:3rem;color:var(--rojo);display:block;margin-bottom:1rem;"></i>
          <h3>PayPal</h3>
          <p><?php echo cfg('donacion_paypal_texto'); ?></p>
          <a href="<?php echo htmlspecialchars(cfgRaw('paypal_link')); ?>" target="_blank" class="btn-primary"><i class="fab fa-paypal"></i> Donar con PayPal</a>
        </div>
        <?php endif; ?>
        <?php if(cfgRaw('patreon_link')): ?>
        <div class="donacion-card">
          <i class="fab fa-patreon" style="font-size:3rem;color:var(--rojo);display:block;margin-bottom:1rem;"></i>
          <h3>Patreon</h3>
          <p><?php echo cfg('donacion_patreon_texto'); ?></p>
          <a href="<?php echo htmlspecialchars(cfgRaw('patreon_link')); ?>" target="_blank" class="btn-primary"><i class="fab fa-patreon"></i> Apoyar en Patreon</a>
        </div>
        <?php endif; ?>
        <?php if(cfgRaw('amazon_link')): ?>
        <div class="donacion-card">
          <i class="fab fa-amazon" style="font-size:3rem;color:var(--rojo);display:block;margin-bottom:1rem;"></i>
          <h3>Lista de Amazon</h3>
          <p><?php echo cfg('donacion_amazon_texto'); ?></p>
          <a href="<?php echo htmlspecialchars(cfgRaw('amazon_link')); ?>" target="_blank" class="btn-primary"><i class="fab fa-amazon"></i> Ver Lista de Amazon</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

</div><!-- /contenido -->

<footer>
  <div class="container">
    <div class="footer-content">
      <?php render_logo('footer-logo', 'index.php'); ?>
      <div class="footer-social">
        <?php if(cfgRaw('redes_youtube'))   echo '<a href="'.htmlspecialchars(cfgRaw('redes_youtube')).'"   target="_blank"><i class="fab fa-youtube"></i></a>'; ?>
        <?php if(cfgRaw('redes_instagram')) echo '<a href="'.htmlspecialchars(cfgRaw('redes_instagram')).'" target="_blank"><i class="fab fa-instagram"></i></a>'; ?>
        <?php if(cfgRaw('redes_facebook'))  echo '<a href="'.htmlspecialchars(cfgRaw('redes_facebook')).'"  target="_blank"><i class="fab fa-facebook"></i></a>'; ?>
        <?php if(cfgRaw('redes_spotify'))   echo '<a href="'.htmlspecialchars(cfgRaw('redes_spotify')).'"   target="_blank"><i class="fab fa-spotify"></i></a>'; ?>
        <?php if(cfgRaw('redes_tiktok'))    echo '<a href="'.htmlspecialchars(cfgRaw('redes_tiktok')).'"    target="_blank"><i class="fab fa-tiktok"></i></a>'; ?>
      </div>
      <p class="footer-text"><?php echo cfg('footer_texto'); ?></p>
      <p class="copyright"><?php echo cfg('footer_copyright'); ?></p>
    </div>
  </div>
</footer>

<script src="main.js"></script>
</body>
</html>
