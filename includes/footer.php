  </div><!-- /.page-content -->
</div><!-- /.main-content -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Quill Editor -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<!-- App JS -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<?php if (!empty($extraScripts)): ?>
  <?php foreach ($extraScripts as $s): ?>
    <script src="<?= APP_URL ?>/<?= e($s) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
