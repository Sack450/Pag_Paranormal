<?php
require_once 'site_config.php';
$page_title = 'Galería Paranormal';
$page_desc  = 'Fotos y momentos capturados durante las investigaciones.';

$items = [];
$categorias = [];
if ($conexion) {
    $res = $conexion->query("SELECT * FROM galeria ORDER BY id DESC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $items[] = $r;
            $cat = $r['categoria'] ?: 'General';
            if (!isset($categorias[$cat])) $categorias[$cat] = [];
            $categorias[$cat][] = $r;
        }
    }
}
include '_header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label"><?php echo cfg('sec_galeria_label'); ?></span>
      <h2 class="section-title">GALERÍA <span class="rojo">PARANORMAL</span></h2>
      <div class="section-line"><i class="fas fa-camera"></i></div>
    </div>

    <?php if (empty($items)): ?>
      <div class="empty-state"><i class="fas fa-images"></i><p>Próximamente habrá imágenes en la galería...</p></div>
    <?php else: ?>
      <?php foreach ($categorias as $cat => $fotos): ?>
      <div class="galeria-categoria">
        <h3 class="categoria-titulo"><i class="fas fa-folder"></i> <?php echo htmlspecialchars(ucfirst($cat)); ?></h3>
        <div class="galeria-grid">
          <?php foreach ($fotos as $g): ?>
          <div class="galeria-item">
            <img src="<?php echo htmlspecialchars($g['imagen_ruta']); ?>" alt="<?php echo htmlspecialchars($g['descripcion'] ?? 'Galería'); ?>" loading="lazy">
            <div class="galeria-overlay"><i class="fas fa-search-plus"></i></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<?php include '_footer.php'; ?>
