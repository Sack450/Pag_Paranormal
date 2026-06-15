<?php
require_once 'site_config.php';
$page_title = 'Exploraciones Paranormales';
$page_desc  = 'Exploraciones en lugares malditos y embrujados.';

$items = [];
if ($conexion) {
    $res = $conexion->query("SELECT * FROM exploraciones ORDER BY fecha DESC");
    if ($res) while ($r = $res->fetch_assoc()) $items[] = $r;
}
include '_header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label"><?php echo cfg('sec_exploraciones_label'); ?></span>
      <h2 class="section-title">EXPLORACIONES <span class="rojo">PARANORMALES</span></h2>
      <div class="section-line"><i class="fas fa-video"></i></div>
    </div>

    <?php if (empty($items)): ?>
      <div class="empty-state"><i class="fas fa-ghost"></i><p>Próximamente se publicarán exploraciones...</p></div>
    <?php else: ?>
    <div class="inv-grid">
      <?php foreach ($items as $e): ?>
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
          <p class="inv-card-desc"><?php echo htmlspecialchars(mb_substr(strip_tags($e['descripcion']), 0, 150)) . '...'; ?></p>
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
  </div>
</section>

<?php include '_footer.php'; ?>
