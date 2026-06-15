<?php
/**
 * _footer.php
 * Pie de página reutilizable con datos dinámicos del sitio.
 */
?>
<footer>
  <div class="container">
    <div class="footer-content">
      <?php render_logo('footer-logo', 'index.php'); ?>
      <div class="footer-social">
        <?php if(cfgRaw('redes_youtube'))   echo '<a href="'.htmlspecialchars(cfgRaw('redes_youtube')).'"   target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a>'; ?>
        <?php if(cfgRaw('redes_instagram')) echo '<a href="'.htmlspecialchars(cfgRaw('redes_instagram')).'" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>'; ?>
        <?php if(cfgRaw('redes_facebook'))  echo '<a href="'.htmlspecialchars(cfgRaw('redes_facebook')).'"  target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>'; ?>
        <?php if(cfgRaw('redes_spotify'))   echo '<a href="'.htmlspecialchars(cfgRaw('redes_spotify')).'"   target="_blank" title="Spotify"><i class="fab fa-spotify"></i></a>'; ?>
        <?php if(cfgRaw('redes_tiktok'))    echo '<a href="'.htmlspecialchars(cfgRaw('redes_tiktok')).'"    target="_blank" title="TikTok"><i class="fab fa-tiktok"></i></a>'; ?>
      </div>
      <p class="footer-text"><?php echo cfg('footer_texto'); ?></p>
      <p class="copyright"><?php echo cfg('footer_copyright'); ?></p>
    </div>
  </div>
</footer>
</body>
</html>
