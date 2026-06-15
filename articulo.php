<?php
require_once 'site_config.php';

$id = (int)($_GET['id'] ?? 0);
$articulo = null;

if ($id > 0 && $conexion) {
    $stmt = $conexion->prepare("SELECT * FROM blog_articulos WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $articulo = $stmt->get_result()->fetch_assoc();
}

if (!$articulo) {
    header('Location: blog.php');
    exit;
}

$page_title = $articulo['titulo'];
$page_desc  = mb_substr(strip_tags($articulo['contenido']), 0, 160);
include '_header.php';
?>

<section class="section">
  <div class="container">
    <article class="articulo-completo">
      <?php if ($articulo['imagen_ruta']): ?>
        <div class="articulo-hero-img">
          <img src="<?php echo htmlspecialchars($articulo['imagen_ruta']); ?>" alt="<?php echo htmlspecialchars($articulo['titulo']); ?>">
        </div>
      <?php endif; ?>
      <div class="section-header" style="margin-top:2rem;">
        <span class="section-label"><?php echo htmlspecialchars($articulo['categoria']); ?></span>
        <h1 class="section-title" style="font-size:2rem;"><?php echo htmlspecialchars($articulo['titulo']); ?></h1>
        <p style="color:#888;margin-top:0.5rem;"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($articulo['fecha']); ?></p>
        <div class="section-line"><i class="fas fa-pen-fancy"></i></div>
      </div>
      <div class="articulo-contenido" style="max-width:800px;margin:2rem auto;line-height:1.8;">
        <?php echo $articulo['contenido']; ?>
      </div>
      <div style="text-align:center;margin-top:3rem;">
        <a href="blog.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Volver al Blog</a>
      </div>
    </article>
  </div>
</section>

<?php include '_footer.php'; ?>
