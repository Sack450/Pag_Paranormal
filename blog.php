<?php
require_once 'site_config.php';
$page_title = 'Blog Paranormal';
$page_desc  = 'Artículos, análisis y técnicas de investigación paranormal.';

$articulos = [];
if ($conexion) {
    $res = $conexion->query("SELECT * FROM blog_articulos ORDER BY fecha DESC");
    if ($res) while ($r = $res->fetch_assoc()) $articulos[] = $r;
}
include '_header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label"><?php echo cfg('sec_blog_label'); ?></span>
      <h2 class="section-title">BLOG <span class="rojo">PARANORMAL</span></h2>
      <div class="section-line"><i class="fas fa-pen-fancy"></i></div>
    </div>

    <?php if (empty($articulos)): ?>
      <div class="empty-state">
        <i class="fas fa-ghost"></i>
        <p>Todavía no hay artículos publicados. ¡Próximamente!</p>
      </div>
    <?php else: ?>
    <div class="inv-grid">
      <?php foreach ($articulos as $a): ?>
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
          <p class="inv-card-desc"><?php echo htmlspecialchars(mb_substr(strip_tags($a['contenido']), 0, 150)) . '...'; ?></p>
          <div class="inv-card-meta">
            <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($a['fecha']); ?></span>
            <a href="articulo.php?id=<?php echo $a['id']; ?>" class="read-more">Leer más <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include '_footer.php'; ?>
