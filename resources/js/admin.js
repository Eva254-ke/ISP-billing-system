/**
 * CloudBridge Networks - Admin JavaScript
 * Production Ready
 */

document.addEventListener('DOMContentLoaded', function () {
    
    // ──────────────────────────────────────────────────────────────────────
    // Flatpickr Date Picker Initialization
    // ──────────────────────────────────────────────────────────────────────
    if (window.flatpickr) {
        flatpickr('.date-picker', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });
    }

    // User menu dropdown fallback (if Bootstrap JS isn't loaded)
    const userDropdownToggle = document.querySelector('.js-user-dropdown-toggle');
    const hasBootstrapDropdown = window.bootstrap && window.bootstrap.Dropdown;
    if (userDropdownToggle && !hasBootstrapDropdown) {
        const dropdownMenu = userDropdownToggle.nextElementSibling;
        const closeUserMenu = () => {
            if (!dropdownMenu) return;
            dropdownMenu.classList.remove('show');
            userDropdownToggle.classList.remove('show');
            userDropdownToggle.setAttribute('aria-expanded', 'false');
        };

        userDropdownToggle.addEventListener('click', function (e) {
            e.preventDefault();
            if (!dropdownMenu) return;
            const isOpen = dropdownMenu.classList.contains('show');
            closeUserMenu();
            if (!isOpen) {
                dropdownMenu.classList.add('show');
                userDropdownToggle.classList.add('show');
                userDropdownToggle.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function (e) {
            if (!dropdownMenu) return;
            if (userDropdownToggle.contains(e.target) || dropdownMenu.contains(e.target)) return;
            closeUserMenu();
        });
    }

    // ──────────────────────────────────────────────────────────────────────
    // Auto-dismiss Alerts
    // ──────────────────────────────────────────────────────────────────────
    document.querySelectorAll('.alert-auto-dismiss').forEach(function (alert) {
        setTimeout(function () {
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    });

    // ──────────────────────────────────────────────────────────────────────
    // Sidebar Toggle Functionality
    // ──────────────────────────────────────────────────────────────────────
    const toggleBtn = document.getElementById('sidebarToggle');
    const body = document.body;
    
    // Create overlay for mobile
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const isMobile = window.innerWidth <= 992;
            
            if (isMobile) {
                // Mobile: toggle overlay + sidebar slide
                body.classList.toggle('sidebar-mobile-open');
                overlay.classList.toggle('active');
            } else {
                // Desktop: toggle sidebar collapse on body class
                body.classList.toggle('sidebar-collapsed');
            }
            
            // Trigger resize for charts/tables after transition
            setTimeout(() => {
                $(window).trigger('resize');
            }, 300);
        });
    }
    
    // Close sidebar when clicking overlay (mobile)
    overlay.addEventListener('click', function () {
        body.classList.remove('sidebar-mobile-open');
        overlay.classList.remove('active');
    });
    
    // Handle window resize
    window.addEventListener('resize', function () {
        if (window.innerWidth > 992) {
            overlay.classList.remove('active');
            body.classList.remove('sidebar-mobile-open');
        }
    });
    
    // Auto-close treeview on mobile
    if (window.innerWidth <= 992) {
        document.querySelectorAll('.nav-treeview').forEach(menu => {
            menu.style.display = 'none';
        });
        
        document.querySelectorAll('.nav-link[data-widget="treeview"]').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const treeview = this.nextElementSibling;
                if (treeview && treeview.classList.contains('nav-treeview')) {
                    treeview.style.display = treeview.style.display === 'block' ? 'none' : 'block';
                    this.parentElement.classList.toggle('menu-open');
                }
            });
        });
    }

    // Card collapse toggle (AdminLTE compatibility)
    document.querySelectorAll('[data-card-widget="collapse"]').forEach(button => {
        button.addEventListener('click', function () {
            const card = this.closest('.card');
            if (!card) return;
            card.classList.toggle('collapsed-card');
            const body = card.querySelector('.card-body');
            const footer = card.querySelector('.card-footer');
            if (body) body.classList.toggle('d-none');
            if (footer) footer.classList.toggle('d-none');
        });
    });

});


