/**
 * NexLink UI Interactions - University-Level Student CRM
 * Handles Collapsible Sidebar, Accordion Submenus, Dropdowns, Modals, Tabs, Search
 */

document.addEventListener('DOMContentLoaded', () => {
  const appLayout = document.getElementById('app-layout');
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebar-toggle');

  // 1. Sidebar Desktop Collapse & Mobile Offcanvas Drawer
  if (sidebarToggle && sidebar && appLayout) {
    sidebarToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      if (window.innerWidth > 1024) {
        appLayout.classList.toggle('sidebar-collapsed');
        // Persist preference in localStorage
        const isCollapsed = appLayout.classList.contains('sidebar-collapsed');
        try { localStorage.setItem('unicrm_sidebar_collapsed', isCollapsed ? '1' : '0'); } catch(err){}
      } else {
        sidebar.classList.toggle('mobile-open');
      }
    });

    // Restore desktop preference
    try {
      if (window.innerWidth > 1024 && localStorage.getItem('unicrm_sidebar_collapsed') === '1') {
        appLayout.classList.add('sidebar-collapsed');
      }
    } catch(err){}

    // Close when clicking outside on mobile
    document.addEventListener('click', (e) => {
      if (window.innerWidth <= 1024 && sidebar.classList.contains('mobile-open')) {
        if (!sidebar.contains(e.target) && e.target !== sidebarToggle && !sidebarToggle.contains(e.target)) {
          sidebar.classList.remove('mobile-open');
        }
      }
    });
  }

  // 2. Global Keyboard Shortcut for Search (Ctrl+K or /)
  const searchInput = document.getElementById('global-search-input');
  if (searchInput) {
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey && e.key.toLowerCase() === 'k') || (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA')) {
        e.preventDefault();
        searchInput.focus();
        searchInput.select();
      }
    });
  }

  // 3. Dropdown Menu System
  function setupDropdown(triggerId, menuId) {
    const trigger = document.getElementById(triggerId);
    const menu = document.getElementById(menuId);
    if (!trigger || !menu) return;

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      // Close other dropdowns
      document.querySelectorAll('.dropdown-menu-custom').forEach(d => {
        if (d !== menu) d.classList.remove('show');
      });
      menu.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
      if (!menu.contains(e.target) && !trigger.contains(e.target)) {
        menu.classList.remove('show');
      }
    });
  }

  setupDropdown('notif-bell-btn', 'notif-dropdown-menu');
  setupDropdown('role-switcher-btn', 'role-switcher-menu');
  setupDropdown('user-profile-btn', 'user-profile-menu');

  // 4. Modal Dialog System
  window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('show');
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
  };

  window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }
  };

  document.querySelectorAll('.modal-backdrop, .modal').forEach(backdrop => {
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) {
        closeModal(backdrop.id);
      }
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', function() {
      const modal = this.closest('.modal-backdrop, .modal');
      if (modal) closeModal(modal.id);
    });
  });

  document.querySelectorAll('[data-modal-target]').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const targetId = this.getAttribute('data-modal-target');
      openModal(targetId);
    });
  });

  // Global Escape Key Listener for Modals & Dropdowns
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-backdrop, .modal').forEach(m => {
        closeModal(m.id);
      });
      document.querySelectorAll('.dropdown-menu-custom').forEach(d => {
        d.classList.remove('show');
      });
      document.body.style.overflow = '';
    }
  });

  // 5. Pill Tabs System
  document.querySelectorAll('.nav-tab-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const tabTarget = this.getAttribute('data-tab-target');
      if (!tabTarget) return; // allows standard anchor links
      e.preventDefault();

      const container = this.closest('.tabs-container') || document;
      const parentTabRow = this.closest('.nav-tabs');
      if (parentTabRow) {
        parentTabRow.querySelectorAll('.nav-tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
      }

      container.querySelectorAll('.tab-pane').forEach(pane => {
        pane.style.display = 'none';
      });
      const activePane = container.querySelector(tabTarget);
      if (activePane) {
        activePane.style.display = 'block';
      }
    });
  });

  // 6. Flash Alert Dismissal
  document.querySelectorAll('.alert-close').forEach(btn => {
    btn.addEventListener('click', function() {
      const alert = this.closest('.alert');
      if (alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-6px)';
        alert.style.transition = 'all 0.2s';
        setTimeout(() => alert.remove(), 200);
      }
    });
  });

  // 7. Delete Confirmation Safeguard
  document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const msg = this.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this record? This action cannot be undone.';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  // 8. Auto-submit filter selects
  document.querySelectorAll('.filter-auto-submit').forEach(select => {
    select.addEventListener('change', function() {
      this.closest('form').submit();
    });
  });
});

// 9. Global Accordion Nav Group Toggle for Sidebar
window.toggleNavGroup = function(toggleBtn) {
  const appLayout = document.getElementById('app-layout');
  if (appLayout && appLayout.classList.contains('sidebar-collapsed')) {
    appLayout.classList.remove('sidebar-collapsed');
    try { localStorage.setItem('unicrm_sidebar_collapsed', '0'); } catch(err){}
  }

  const group = toggleBtn.closest('.nav-group');
  if (!group) return;

  const isOpen = group.classList.contains('nav-group-open');
  group.classList.toggle('nav-group-open', !isOpen);
};
