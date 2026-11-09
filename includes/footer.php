    </main>

    <!-- App Global Footer (Steady Fixed & Perfectly Aligned with Sidebar Footer) -->
    <footer id="app-footer">
      <div class="footer-left">
        <strong>UniCRM Admissions Cloud</strong> &bull; Client Evaluation Prototype v1.0
      </div>
      <div class="footer-right">
        <span>&copy; <?= date('Y') ?> Higher Education CRM Solutions. Built for multi-campus university operations.</span>
      </div>
    </footer>

  </div><!-- /#main-wrapper -->
</div><!-- /#app-layout -->

<!-- Include Global Modals (Quick Lead, Log Call, Stage Advancer, User Guide) -->
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