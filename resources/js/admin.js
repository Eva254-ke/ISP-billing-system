/**
 * CloudBridge Networks - Admin JavaScript
 * Production Ready
 */

document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const sidebarToggleClasses = ['sidebar-collapsed', 'sidebar-collapse'];

    function notifyLayoutChanged() {
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
            document.dispatchEvent(new Event('cb:layout-changed'));

            if (window.jQuery) {
                window.jQuery(window).trigger('resize');
            }
        }, 260);
    }

    function setSidebarCollapsed(collapsed) {
        sidebarToggleClasses.forEach(className => body.classList.toggle(className, collapsed));
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', String(!collapsed));
        }
        notifyLayoutChanged();
    }

    function isSidebarCollapsed() {
        return sidebarToggleClasses.some(className => body.classList.contains(className));
    }

    if (window.flatpickr) {
        flatpickr('.date-picker', {
            dateFormat: 'Y-m-d',
            allowInput: true,
        });
    }

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

    document.querySelectorAll('.alert-auto-dismiss').forEach(function (alert) {
        setTimeout(function () {
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    });

    const toggleBtn = document.getElementById('sidebarToggle');
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const isMobile = window.innerWidth <= 992;

            if (isMobile) {
                body.classList.toggle('sidebar-mobile-open');
                overlay.classList.toggle('active');
                toggleBtn.setAttribute('aria-expanded', String(body.classList.contains('sidebar-mobile-open')));
                notifyLayoutChanged();
                return;
            }

            setSidebarCollapsed(!isSidebarCollapsed());
        });
    }

    overlay.addEventListener('click', function () {
        body.classList.remove('sidebar-mobile-open');
        overlay.classList.remove('active');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', 'false');
        }
        notifyLayoutChanged();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 992) {
            overlay.classList.remove('active');
            body.classList.remove('sidebar-mobile-open');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', String(!isSidebarCollapsed()));
            }
        }
    });

    document.querySelectorAll('.btn-close').forEach(button => {
        if (!button.getAttribute('aria-label')) {
            button.setAttribute('aria-label', 'Close');
        }

        if (!button.getAttribute('title')) {
            button.setAttribute('title', 'Close');
        }
    });

    if (window.innerWidth <= 992) {
        document.querySelectorAll('.nav-treeview').forEach(menu => {
            menu.style.display = 'none';
        });

        document.querySelectorAll('.has-treeview > .nav-link').forEach(link => {
            link.addEventListener('click', function (e) {
                const treeview = this.nextElementSibling;

                if (treeview && treeview.classList.contains('nav-treeview')) {
                    e.preventDefault();
                    treeview.style.display = treeview.style.display === 'block' ? 'none' : 'block';
                    this.parentElement.classList.toggle('menu-open');
                }
            });
        });
    }

    document.querySelectorAll('[data-card-widget="collapse"]').forEach(button => {
        button.addEventListener('click', function () {
            const card = this.closest('.card');
            if (!card) return;

            card.classList.toggle('collapsed-card');

            const cardBody = card.querySelector('.card-body');
            const footer = card.querySelector('.card-footer');
            const icon = this.querySelector('i');
            const isCollapsed = card.classList.contains('collapsed-card');

            if (cardBody) cardBody.classList.toggle('d-none');
            if (footer) footer.classList.toggle('d-none');

            if (icon) {
                icon.classList.toggle('fa-minus', !isCollapsed);
                icon.classList.toggle('fa-plus', isCollapsed);
            }

            notifyLayoutChanged();
        });
    });
});
