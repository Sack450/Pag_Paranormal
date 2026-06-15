<?php
require_once 'site_config.php';
$page_title = 'Donaciones';
$page_desc  = 'Apoya la investigación paranormal con tu donación.';

include '_header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label"><?php echo cfg('sec_donaciones_label'); ?></span>
      <h2 class="section-title">DONACIONES <span class="rojo">PARANORMALES</span></h2>
      <div class="section-line"><i class="fas fa-heart"></i></div>
    </div>

    <?php if (!cfgRaw('paypal_link') && !cfgRaw('patreon_link') && !cfgRaw('amazon_link')): ?>
      <div class="empty-state"><i class="fas fa-heart"></i><p>Próximamente habrá opciones de donación disponibles.</p></div>
    <?php else: ?>
    <div class="donaciones-grid">
      <?php if (cfgRaw('paypal_link')): ?>
      <div class="donacion-card">
        <i class="fab fa-paypal" style="font-size:3rem;color:var(--rojo);display:block;margin-bottom:1rem;"></i>
        <h3>PayPal</h3>
        <p><?php echo cfg('donacion_paypal_texto'); ?></p>
        <a href="<?php echo htmlspecialchars(cfgRaw('paypal_link')); ?>" target="_blank" class="btn-primary"><i class="fab fa-paypal"></i> Donar con PayPal</a>
      </div>
      <?php endif; ?>
      <?php if (cfgRaw('patreon_link')): ?>
      <div class="donacion-card">
        <i class="fab fa-patreon" style="font-size:3rem;color:var(--rojo);display:block;margin-bottom:1rem;"></i>
        <h3>Patreon</h3>
        <p><?php echo cfg('donacion_patreon_texto'); ?></p>
        <a href="<?php echo htmlspecialchars(cfgRaw('patreon_link')); ?>" target="_blank" class="btn-primary"><i class="fab fa-patreon"></i> Apoyar en Patreon</a>
      </div>
      <?php endif; ?>
      <?php if (cfgRaw('amazon_link')): ?>
      <div class="donacion-card">
        <i class="fab fa-amazon" style="font-size:3rem;color:var(--rojo);display:block;margin-bottom:1rem;"></i>
        <h3>Lista de Amazon</h3>
        <p><?php echo cfg('donacion_amazon_texto'); ?></p>
        <a href="<?php echo htmlspecialchars(cfgRaw('amazon_link')); ?>" target="_blank" class="btn-primary"><i class="fab fa-amazon"></i> Ver Lista de Amazon</a>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include '_footer.php'; ?>
