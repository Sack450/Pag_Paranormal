<?php
require_once 'site_config.php';
$page_title = 'Podcast Paranormal';
$page_desc  = 'Episodios del podcast de investigación paranormal.';

$episodios = [];
if ($conexion) {
    $res = $conexion->query("SELECT * FROM podcasts ORDER BY fecha DESC");
    if ($res) while ($r = $res->fetch_assoc()) $episodios[] = $r;
}
include '_header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label"><?php echo cfg('sec_podcast_label'); ?></span>
      <h2 class="section-title">PODCAST <span class="rojo">PARANORMAL</span></h2>
      <div class="section-line"><i class="fas fa-microphone"></i></div>
    </div>

    <?php if (empty($episodios)): ?>
      <div class="empty-state"><i class="fas fa-headphones"></i><p>Próximamente se publicarán episodios...</p></div>
    <?php else: ?>
    <div class="inv-grid">
      <?php foreach ($episodios as $p): ?>
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
          <p class="inv-card-desc"><?php echo htmlspecialchars(mb_substr(strip_tags($p['descripcion']), 0, 150)) . '...'; ?></p>
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
  </div>
</section>

<?php include '_footer.php'; ?>
