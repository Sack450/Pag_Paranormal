<?php
require_once 'site_config.php';
$page_title = 'Enviar Evidencia';
$page_desc  = 'Comparte tu evidencia paranormal con el Investigador Oxlack.';

include '_header.php';
?>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Comunidad</span>
      <h2 class="section-title"><?php echo cfg('evidencias_titulo'); ?></h2>
      <p style="max-width:600px;margin:1rem auto;color:#666;"><?php echo cfg('evidencias_intro'); ?></p>
      <div class="section-line"><i class="fas fa-envelope-open-text"></i></div>
    </div>

    <div class="glass-card" style="max-width:700px;margin:0 auto;padding:2rem;background:var(--blanco-puro);border:1px solid #ddd;">
      <div id="formAlerta" class="form-alerta" style="display:none;"></div>

      <form id="formEvidencia" enctype="multipart/form-data">
        <div class="form-group" style="margin-bottom:1rem;">
          <label>Tu Nombre *</label>
          <input type="text" name="nombre_cliente" id="nombreCliente" class="form-control" required maxlength="100" style="width:100%;padding:0.75rem;border:1px solid #ccc;">
        </div>
        <div class="form-group" style="margin-bottom:1rem;">
          <label>Correo Electrónico *</label>
          <input type="email" name="email_cliente" id="emailCliente" class="form-control" required style="width:100%;padding:0.75rem;border:1px solid #ccc;">
        </div>
        <div class="form-group" style="margin-bottom:1rem;">
          <label>Título del Caso *</label>
          <input type="text" name="titulo_caso" id="tituloCaso" class="form-control" required maxlength="150" style="width:100%;padding:0.75rem;border:1px solid #ccc;">
        </div>
        <div class="form-group" style="margin-bottom:1rem;">
          <label>Tipo de Evidencia *</label>
          <select name="tipo_evidencia" id="tipoEvidencia" class="form-control" required style="width:100%;padding:0.75rem;border:1px solid #ccc;">
            <option value="">Selecciona...</option>
            <option value="Imagen">Imagen / Foto</option>
            <option value="Audio">Audio / EVP</option>
            <option value="Relato">Relato escrito</option>
            <option value="Otro">Otro</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:1rem;">
          <label>Descripción del Caso *</label>
          <textarea name="descripcion" id="descripcionCaso" class="form-control" required rows="5" maxlength="5000" style="width:100%;padding:0.75rem;border:1px solid #ccc;"></textarea>
        </div>
        <div class="form-group" style="margin-bottom:1.5rem;">
          <label>Archivo Adjunto (opcional, máx. 15MB)</label>
          <div id="dropArea" style="border:2px dashed #ccc;padding:2rem;text-align:center;cursor:pointer;border-radius:8px;">
            <p id="uploadText"><i class="fas fa-cloud-upload-alt"></i> Arrastra un archivo aquí o haz clic para seleccionar</p>
            <input type="file" name="evidencia_archivo" id="evidenciaArchivo" accept=".jpg,.jpeg,.png,.webp,.gif,.mp3,.wav,.ogg,.pdf,.txt" style="display:none;">
          </div>
          <div id="fileInfo" style="display:none;margin-top:0.5rem;align-items:center;">
            <span id="fileName"></span>
            <button type="button" id="removeFileBtn" style="margin-left:1rem;">Quitar</button>
          </div>
        </div>
        <button type="submit" id="btnEnviarEvidencia" class="btn-primary" style="width:100%;">
          <span class="btn-text"><i class="fas fa-paper-plane"></i> Enviar Evidencia</span>
          <span class="btn-spinner" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Enviando...</span>
        </button>
      </form>
    </div>
  </div>
</section>

<script src="main.js"></script>
<?php include '_footer.php'; ?>
