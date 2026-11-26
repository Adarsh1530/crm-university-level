    </main>

    <!-- App Global Footer -->
    <footer id="app-footer">
      <div class="footer-left">
        <strong>CRM</strong> &bull; Customer Relationship Management Platform
      </div>
      <div class="footer-right">
        <span>&copy; <?= date('Y') ?> CRM. Core Operations, Regional Segmentation & Lead Routing System.</span>
      </div>
    </footer>

  </div><!-- /#main-wrapper -->
</div><!-- /#app-layout -->

<!-- Include Global Modals (Quick Lead, Log Call, Top Update, User Guide) -->
<?php include __DIR__ . '/global_modals.php'; ?>

<!-- Core Main Interactions Script -->
<script src="<?= base_url('assets/js/main.js?v=' . filemtime(__DIR__ . '/../assets/js/main.js')) ?>"></script>

<!-- Interactive Role-Specific Guided Tutorial Engine -->
<script src="<?= base_url('assets/js/tutorial.js?v=' . filemtime(__DIR__ . '/../assets/js/tutorial.js')) ?>"></script>

<?php if (isset($extraScripts)): ?>
  <?= $extraScripts ?>
<?php endif; ?>

</body>
</html>
