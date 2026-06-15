<?php
require_once 'site_config.php';
$page_title = 'Tienda Oxlack';
$page_desc  = 'Productos oficiales del Investigador Oxlack.';

$productos = [];
if ($conexion) {
    $res = $conexion->query("SELECT * FROM tienda_productos ORDER BY fecha_registro DESC");
    if ($res) while ($r = $res->fetch_assoc()) $productos[] = $r;
}
include '_header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label"><?php echo cfg('sec_tienda_label'); ?></span>
      <h2 class="section-title">TIENDA <span class="rojo">OXLACK</span></h2>
      <div class="section-line"><i class="fas fa-shopping-bag"></i></div>
    </div>

    <?php if (empty($productos)): ?>
      <div class="empty-state"><i class="fas fa-box-open"></i><p>Próximamente habrá productos disponibles...</p></div>
    <?php else: ?>
    <div class="inv-grid">
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
          <div class="articulo-contenido" style="font-size:0.95rem;"><?php echo $pr['descripcion']; ?></div>
          <div class="inv-card-meta">
            <span style="color:var(--rojo);font-weight:bold;font-size:1.2rem;">$<?php echo number_format((float)$pr['precio'], 2); ?></span>
            <?php if ($pr['link_compra']): ?>
            <a href="<?php echo htmlspecialchars($pr['link_compra']); ?>" target="_blank" class="read-more">Comprar <i class="fas fa-shopping-cart"></i></a>
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
