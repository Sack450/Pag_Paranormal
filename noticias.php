<?php
require_once 'site_config.php';
$page_title = 'Noticias Paranormales';
$page_desc  = 'Últimas noticias sobre fenómenos paranormales de todo el mundo.';

$noticias = [];
if ($conexion) {
    $res = $conexion->query("SELECT * FROM noticias ORDER BY fecha DESC");
    if ($res) while ($r = $res->fetch_assoc()) $noticias[] = $r;
}
include '_header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label"><?php echo cfg('sec_noticias_label'); ?></span>
      <h2 class="section-title">NOTICIAS <span class="rojo">PARANORMALES</span></h2>
      <div class="section-line"><i class="fas fa-newspaper"></i></div>
    </div>

    <?php if (empty($noticias)): ?>
      <div class="empty-state">
        <i class="fas fa-satellite"></i>
        <p>Próximamente publicaremos las últimas noticias paranormales.</p>
      </div>
    <?php else: ?>

    <?php $primera = array_shift($noticias); ?>
    <!-- Noticia destacada -->
    <div class="noticia-destacada">
      <?php if ($primera['imagen_ruta']): ?>
        <img src="<?php echo htmlspecialchars($primera['imagen_ruta']); ?>" alt="<?php echo htmlspecialchars($primera['titulo']); ?>" loading="lazy">
      <?php endif; ?>
      <div class="noticia-content">
        <span class="fecha"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($primera['fecha']); ?></span>
        <?php if ($primera['fuente']): ?><span class="inv-tag" style="margin-left:10px;"><?php echo htmlspecialchars($primera['fuente']); ?></span><?php endif; ?>
        <h3><?php echo htmlspecialchars($primera['titulo']); ?></h3>
        <p><?php echo htmlspecialchars(mb_substr(strip_tags($primera['resumen']), 0, 300)) . '...'; ?></p>
      </div>
    </div>

    <?php if (!empty($noticias)): ?>
    <div class="inv-grid" style="margin-top:2rem;">
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
          <p class="inv-card-desc"><?php echo htmlspecialchars(mb_substr(strip_tags($n['resumen']), 0, 130)) . '...'; ?></p>
          <div class="inv-card-meta">
            <span><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($n['fecha']); ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include '_footer.php'; ?>
