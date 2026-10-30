<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CRM') — ZiTrack CRM</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> 
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<script src="{{url('admin/libraries/js/lucide.js')}}"></script>
    <!-- Bootstrap 5 -->

    <link href="{{url('admin/libraries/css/bootstrap.min.css')}}" rel="stylesheet"> 
    <link href="{{url('admin/libraries/css/dataTables.bootstrap5.min.css')}}" rel="stylesheet">
    <link href="{{url('admin/libraries/css/font-awesome.css')}}" rel="stylesheet">
    <link href="{{url('admin/libraries/css/buttons.bootstrap5.min.css')}}" rel="stylesheet">
    <link href="{{url('admin/libraries/css/select2.min.css')}}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ url('admin/css/admin.css') }}?v={{ filemtime(public_path('admin/css/admin.css')) }}" rel="stylesheet">
	 <link href="{{ url('admin/css/table.css') }}?v={{ filemtime(public_path('admin/css/table.css')) }}" rel="stylesheet">
	
</head>

<body>
    <div class="admin-preloader" id="adminPreloader" role="status" aria-live="polite" aria-atomic="true" hidden>
        <div class="admin-preloader-card">
            <span class="admin-preloader-spinner" aria-hidden="true"></span>
            <span id="adminPreloaderMessage">Loading, please wait...</span>
        </div>
    </div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="brand-icon">
                <i data-lucide="zap"></i>
            </span>
            <div>
                <span class="brand-name">Whitehat</span>
                <span class="brand-sub">Super Admin Console</span>
            </div>
            <button class="sidebar-close-btn d-md-none" onclick="closeSidebar()" aria-label="Close sidebar">
                <i class="bi bi-x"></i>
            </button>
        </div>

        
        <!-- Dynamic Sidebar Navigation -->
        <div id="dynamicSidebar">

            <ul class="sidebar-nav" id="dashboardMenu">
                <li class="nav-item">
                    <span class="nav-link text-muted">
                        Loading menu...
                    </span>
                </li>
            </ul>

            <div class="sidebar-section-label"
                 id="managementLabel"
                 style="display:none;">
                Management
            </div>

            <ul class="sidebar-nav" id="managementMenu"></ul>

        </div>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                <div class="user-details">
                    <div class="user-name">{{ auth()->user()->name ?? '' }}</div>
                    <div class="user-role">{{ auth()->user()->roles->first()?->name ?? 'guest' }}</div>
                </div>
                <form method="get" action="{{ route('logout') }}" style="margin-left: auto;">
                    @csrf
                    <button type="submit" class="logout-btn" title="Logout">
                        <i data-lucide="log-out"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <header class="topbar">
        <div class="topbar-left">
            <button class="btn btn-outline-secondary d-md-none topbar-hamburger" aria-label="Toggle sidebar" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div class="page-title-section">
                <div class="page-title">@yield('page-title', 'Dashboard')</div>
                <div class="page-subtitle">@yield('page-subtitle', '')</div>
            </div>
        </div>

        <div class="topbar-right">
            <!-- Search Bar -->
            <div class="search-container">
                <i data-lucide="search" class="search-icon"></i>
                <input type="text" class="search-input" placeholder="Search...">
            </div>

            <!-- Notifications -->
            <div>
				<a href="{{ route('admin.notifications.index') }}" 
				   class="notification-btn position-relative text-decoration-none">
					<i data-lucide="bell"></i>
					<span class="notification-badge" id="notificationCount"></span>
				</a>
			</div>

            <!-- User Dropdown -->
            <div class="dropdown">
                <a class="user-dropdown-toggle dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <div class="user-dropdown-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                    <div class="user-dropdown-name">{{ auth()->user()->name ?? '' }}</div>
                    <i data-lucide="chevron-down" class="user-dropdown-chevron"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i data-lucide="user" class="me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i data-lucide="settings" class="me-2"></i>Settings</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="get" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i data-lucide="log-out" class="me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="main-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4 fade-in"
                role="alert">
                <i data-lucide="check-circle" class="me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4 fade-in" role="alert">
                <i data-lucide="alert-circle" class="me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Scripts -->
    <script src="{{url('admin/libraries/js/jquery-3.7.1.min.js')}}"></script>
    <script src="{{url('admin/libraries/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{url('admin/libraries/js/jquery-3.7.1.min.js')}}"></script>
    <script src="{{url('admin/libraries/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{url('admin/libraries/js/font-awesome.js')}}"></script>
    <script src="{{url('admin/libraries/js/dataTables.bootstrap5.min.js')}}"></script>
    <script src="{{url('admin/libraries/js/pdfmake.min.js')}}"></script>
    <script src="{{url('admin/libraries/js/vfs_fonts.js')}}"></script>
    <script src="{{url('admin/libraries/js/dataTables.buttons.min.js')}}"></script>
    <script src="{{url('admin/libraries/js/buttons.bootstrap5.min.js')}}"></script>
    <script src="{{url('admin/libraries/js/buttons.html5.min.js')}}"></script>
    <script src="{{url('admin/libraries/js/buttons.print.min.js')}}"></script>
    <script src="{{url('admin/libraries/js/buttons.colVis.min.js')}}"></script>
    <script src="{{url('admin/libraries/js/select2.min.js')}}"></script>
    <script src="{{ url('admin/libraries/js/sweetalert2@11.js') }}?v={{ filemtime(public_path('admin/libraries/js/sweetalert2@11.js')) }}"></script>
	

    <script>
        // Initialize Lucide icons
        lucide.createIcons();


    // ============================================
    // Dynamic Sidebar - Fetch menu from Laravel API
    // ============================================

    async function loadDynamicSidebar() {

        const dashboardMenu = document.getElementById('dashboardMenu');
        const managementMenu = document.getElementById('managementMenu');
        const managementLabel = document.getElementById('managementLabel');

        if (!dashboardMenu || !managementMenu || !managementLabel) {
            console.error('Sidebar HTML elements not found');
            return;
        }

        // Normalize URL paths for active menu highlighting
        function normalizePath(path) {
            return path.replace(/\/+$/, '') || '/';
        }

        // Allow only safe, same-origin navigation URLs
        function safeMenuUrl(url) {
            if (typeof url !== 'string' || !url.trim()) {
                return null;
            }

            try {
                const parsed = new URL(url, window.location.origin);

                if (
                    parsed.origin !== window.location.origin ||
                    !['http:', 'https:'].includes(parsed.protocol)
                ) {
                    return null;
                }

                return parsed.href;
            } catch (error) {
                return null;
            }
        }

        // Identify the currently active menu
		
		//added
		

function isActive(menu) {
    const children = Array.isArray(menu.children)
        ? menu.children
        : [];

    if (children.some(isActive)) {
        return true;
    }

    const url = safeMenuUrl(menu.url);

    if (!url) {
        return false;
    }

    const menuUrl = new URL(url);
    const currentPath = normalizePath(
        window.location.pathname
    );

    if (normalizePath(menuUrl.pathname) !== currentPath) {
        return false;
    }

    if (menu.view) {
        const selectedView =
            sessionStorage.getItem('selectedLeadView')
            || 'total';

        return menu.view === selectedView;
    }

    return true;
}

//...

        // Create a Lucide icon element
        function createIcon(iconName) {
            const icon = document.createElement('i');

            icon.setAttribute(
                'data-lucide',
                typeof iconName === 'string' && iconName
                    ? iconName
                    : 'circle'
            );

            return icon;
        }

        // Create each sidebar menu item
        function createMenuItem(menu, itemId) {

            if (!menu || typeof menu.label !== 'string') {
                return null;
            }

            const li = document.createElement('li');
            li.className = 'nav-item';

            const children = Array.isArray(menu.children)
                ? menu.children
                : [];

            const active = isActive(menu);

            // Menu with submenus
            if (children.length > 0) {

                const collapseId = 'dynamicSidebarMenu' + itemId;

                const toggle = document.createElement('a');

                toggle.href = '#' + collapseId;
                toggle.setAttribute('data-bs-toggle', 'collapse');
                toggle.setAttribute('role', 'button');
                toggle.setAttribute('aria-controls', collapseId);
                toggle.setAttribute('aria-expanded', String(active));

                if (active) {
                    toggle.classList.add('active');
                }

                toggle.appendChild(createIcon(menu.icon));
                toggle.appendChild(
                    document.createTextNode(' ' + menu.label)
                );

                const chevron = document.createElement('i');
                chevron.className = 'bi bi-chevron-down ms-auto';
                chevron.style.fontSize = '0.75rem';

                toggle.appendChild(chevron);

                const collapse = document.createElement('div');

                collapse.id = collapseId;
                collapse.className = active
                    ? 'collapse show'
                    : 'collapse';

                const childList = document.createElement('ul');
                childList.className = 'sidebar-nav';

                children.forEach(function(child, childIndex) {
                    const childItem = createMenuItem(
                        child,
                        itemId + '-' + childIndex
                    );

                    if (childItem) {
                        childList.appendChild(childItem);
                    }
                });

                if (!childList.children.length) {
                    return null;
                }

                collapse.appendChild(childList);
                li.appendChild(toggle);
                li.appendChild(collapse);

            } else {

                // Menu without submenus
                const url = safeMenuUrl(menu.url);

                if (!url) {
                    return null;
                }

                const link = document.createElement('a');

                link.href = url;
				//added
				if (menu.view) {
    link.dataset.leadView = menu.view;
}

                if (active) {
                    link.classList.add('active');
                    link.setAttribute('aria-current', 'page');
                }

                link.appendChild(createIcon(menu.icon));
                link.appendChild(
                    document.createTextNode(' ' + menu.label)
                );

                li.appendChild(link);
            }

            return li;
        }

        try {

            // Fetch from the existing authenticated web endpoint
            const response = await fetch(
                "{{ route('admin.sidebar.menu') }}",
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(
                    'Sidebar API failed. HTTP status: ' +
                    response.status
                );
            }

            const result = await response.json();

            if (
                result.status !== true ||
                !result.data ||
                !Array.isArray(result.data.menus)
            ) {
                throw new Error('Invalid sidebar API response');
            }

            // Build menu elements before updating the DOM
            const dashboardItems = document.createDocumentFragment();
            const managementItems = document.createDocumentFragment();

            result.data.menus.forEach(function(menu, index) {

                const menuItem = createMenuItem(menu, String(index));

                if (!menuItem) {
                    return;
                }

                if (menu.label === 'Dashboard') {
                    dashboardItems.appendChild(menuItem);
                } else {
                    managementItems.appendChild(menuItem);
                }
            });

            dashboardMenu.replaceChildren(dashboardItems);
            managementMenu.replaceChildren(managementItems);

            // Show Management heading only if menu items exist
            managementLabel.style.display =
                managementMenu.children.length > 0
                    ? ''
                    : 'none';

            // Refresh Lucide icons after adding sidebar HTML
            if (window.lucide) {
                lucide.createIcons();
            }

            console.log('Dynamic sidebar loaded successfully');

        } catch (error) {

            console.error('Dynamic sidebar error:', error);

            dashboardMenu.replaceChildren();
            managementMenu.replaceChildren();

            managementLabel.style.display = 'none';

            const errorItem = document.createElement('li');
            errorItem.className = 'nav-item';

            const retryButton = document.createElement('button');
            retryButton.type = 'button';
            retryButton.className = 'btn btn-link text-light';
            retryButton.textContent = 'Retry loading sidebar';

            retryButton.addEventListener('click', loadDynamicSidebar);

            errorItem.appendChild(retryButton);
            dashboardMenu.appendChild(errorItem);
        }
    }

    // Load sidebar when the page is ready
    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            loadDynamicSidebar
        );
    } else {
        loadDynamicSidebar();
    }
//added

document.addEventListener('click', function (event) {
    const link = event.target.closest('a[data-lead-view]');

    if (!link || event.defaultPrevented ||
        event.button !== 0 || event.ctrlKey ||
        event.metaKey || event.shiftKey ||
        event.altKey) {
        return;
    }

    event.preventDefault();

    const view = link.dataset.leadView;

    sessionStorage.setItem('selectedLeadView', view);

    const destination = new URL(link.href);

    const currentPath = window.location.pathname;

    // Navigate to the clean URL if on another page
    if (currentPath !== destination.pathname) {
        window.location.assign(destination.href);
        return;
    }

    // Remove any old ?view= parameter
    if (window.location.search) {
        history.replaceState(
            history.state,
            '',
            destination.pathname
        );
    }

    // Update sidebar highlighting
    document
        .querySelectorAll('a[data-lead-view]')
        .forEach(function (item) {
            const selected = item.dataset.leadView === view;

            item.classList.toggle('active', selected);

            if (selected) {
                item.setAttribute('aria-current', 'page');
            } else {
                item.removeAttribute('aria-current');
            }
        });

    // Change lead table without refreshing the page
    if (typeof window.setLeadView === 'function') {
        window.setLeadView(view);
    }
});
//...
        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.remove('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = event.target.closest('button[onclick="toggleSidebar()"]');
            const closeBtn = event.target.closest('button[onclick="closeSidebar()"]');

            if (!sidebar.contains(event.target) && !toggleBtn && !closeBtn && window.innerWidth <= 768) {
                sidebar.classList.remove('show');
            }
        });

        // Initialize select2
        $('.select2').select2();

        if ($.fn.dataTable) {
            $.extend(true, $.fn.dataTable.defaults, {
                scrollX: true,
                autoWidth: false
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            

            // Search bar Enter key submit (behavior fallback)
            var searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('keypress', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        var query = searchInput.value.trim();
                        if (query.length > 0) {
                            window.location.href = '{{ url("admin") }}?q=' + encodeURIComponent(query);
                        }
                    }
                });
            }
        });

        let adminPreloaderTimer;
        let adminPreloaderHideTimer;
        const activeAdminRequests = new Set();

        //added
		
function isDataTableRequest(settings) {
    const requestData = settings.data;
    let parameters;

    // FormData is used for normal form submissions,
    // such as Create User. It is not a DataTables request.
    if (requestData instanceof FormData) {
        return false;
    }

    if (requestData && typeof requestData === 'object') {
        parameters = new URLSearchParams($.param(requestData));
    } else {
        const url = new URL(settings.url, window.location.href);

        parameters = new URLSearchParams(
            typeof requestData === 'string'
                ? requestData
                : url.search
        );
    }

    return parameters.has('draw') &&
           parameters.has('start') &&
           parameters.has('length');
}
//...

        function showAdminPreloader(message = 'Loading, please wait...') {
            const preloader = document.getElementById('adminPreloader');
            document.getElementById('adminPreloaderMessage').textContent = message;
            window.clearTimeout(adminPreloaderTimer);
            window.clearTimeout(adminPreloaderHideTimer);
            adminPreloaderTimer = window.setTimeout(() => {
                preloader.hidden = false;
                requestAnimationFrame(() => preloader.classList.add('is-visible'));
            }, 120);
        }

        function hideAdminPreloader() {
            const preloader = document.getElementById('adminPreloader');
            window.clearTimeout(adminPreloaderTimer);
            preloader.classList.remove('is-visible');
            adminPreloaderHideTimer = window.setTimeout(() => {
                if (!preloader.classList.contains('is-visible')) {
                    preloader.hidden = true;
                }
            }, 180);
        }

        $(document)
            .on('ajaxSend.adminPreloader', function (event, xhr, settings) {
                if (isDataTableRequest(settings)) {
                    return;
                }

                activeAdminRequests.add(xhr);
                showAdminPreloader('Working, please wait...');
            })
            .on('ajaxComplete.adminPreloader', function (event, xhr) {
                activeAdminRequests.delete(xhr);
                if (!activeAdminRequests.size) {
                    hideAdminPreloader();
                }
            });

        document.addEventListener('click', function (event) {
            const link = event.target.closest('a[href]');
            if (!link || event.defaultPrevented || event.button !== 0
                || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
                || link.target || link.hasAttribute('download')
                || link.getAttribute('href').startsWith('#')
                || link.getAttribute('href').startsWith('javascript:')) {
                return;
            }

            const destination = new URL(link.href, window.location.href);
            if (destination.origin === window.location.origin
                && (destination.pathname !== window.location.pathname
                    || destination.search !== window.location.search)) {
                window.setTimeout(() => {
                    if (!event.defaultPrevented) {
                        showAdminPreloader('Loading page...');
                    }
                }, 0);
            }
        });

        document.addEventListener('submit', function (event) {
            if (event.target.matches('form')) {
                window.setTimeout(() => {
                    if (!event.defaultPrevented) {
                        showAdminPreloader('Saving changes...');
                    }
                }, 0);
            }
        }, true);

        function confirmAdminAction(options) {
            return Swal.fire({
                icon: options.icon || (options.destructive ? 'warning' : 'question'),
                title: options.title,
                text: options.text,
                showCancelButton: true,
                reverseButtons: true,
                focusCancel: true,
                confirmButtonText: options.confirmButtonText || 'Confirm',
                cancelButtonText: 'Cancel',
                confirmButtonColor: options.destructive ? '#dc3545' : '#5548d9',
                cancelButtonColor: '#667085',
                customClass: {
                    popup: 'admin-swal-popup',
                    confirmButton: 'admin-swal-confirm',
                    cancelButton: 'admin-swal-cancel'
                }
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                if (options.onConfirm) {
                    options.onConfirm();
                    return;
                }

                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: options.url,
                    type: options.method || 'GET',
                    data: options.data
                }).done((data) => {
                    if (!data || !data.status) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Action failed',
                            text: data?.message || data?.msg || 'The server could not complete this action.'
                        });
                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: options.successTitle || 'Done',
                        text: data.message || options.successMessage || 'The action completed successfully.',
                        confirmButtonColor: '#5548d9',
                        customClass: { popup: 'admin-swal-popup' }
                    }).then(() => {
                        if (options.reloadOnSuccess === false) {
                            return;
                        }

                        const $ajaxTables = $('.dataTable').filter(function () {
                            if (!$.fn.DataTable.isDataTable(this)) {
                                return false;
                            }
                            return Boolean($(this).DataTable().ajax.url());
                        });

                        if ($ajaxTables.length) {
                            $ajaxTables.each(function () {
                                $(this).DataTable().ajax.reload(null, false);
                            });
                        } else {
                            window.location.reload();
                        }
                    });
                }).fail((xhr) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request failed',
                        text: xhr.responseJSON?.message || 'The server could not complete this action. Please try again.'
                    });
                });
            });
        }

        function confirmAdminForm(form, title, text) {
            confirmAdminAction({
                title: title,
                text: text,
                confirmButtonText: 'Yes, continue',
                onConfirm: () => form.requestSubmit()
            });
            return false;
        }

        function myConfirm(action) {
            const cleanAction = String(action).replace(/^\/+/, '');
            const segments = cleanAction.split('/').filter(Boolean);
            const operation = segments[segments.length - 2];
            const actions = {
                delete: {
                    title: 'Delete this item?',
                    text: 'This action cannot be undone.',
                    confirmButtonText: 'Yes, delete it',
                    successTitle: 'Deleted',
                    destructive: true
                },
                status: {
                    title: 'Change this status?',
                    text: 'The status will be updated.',
                    confirmButtonText: 'Yes, change status',
                    successTitle: 'Status updated'
                },
                trending: {
                    title: 'Mark this item as trending?',
                    text: 'This item will be marked as trending.',
                    confirmButtonText: 'Yes, continue',
                    successTitle: 'Updated'
                }
            };
            const config = actions[operation];

            if (!config) {
                return Swal.fire({
                    icon: 'error',
                    title: 'Action unavailable',
                    text: 'This action is not supported.'
                });
            }

            return confirmAdminAction({
                ...config,
                url: new URL(cleanAction, '{{ url("/") }}/').toString(),
                method: 'GET'
            });
        }



    </script>

    <script>
        // Notification Logic
        function fetchNotifications() {
            $.ajax({
                url: "{{ route('admin.notifications.list') }}",
                type: "GET",
                global: false,
                success: function(response) {
                    if (response.status) {
                        updateNotificationUI(response);
                    }
                }
            });
        }

        let currentUnreadCount = 0;

        function updateNotificationUI(response) {
            const $count = $('#notificationCount');
            const $unreadOnly = $('.unread-only-count');
            
            if (typeof response.unread_count !== 'undefined') {
                currentUnreadCount = parseInt(response.unread_count) || 0;
            }

            // Update counts
            if (currentUnreadCount > 0) {
                $count.text(currentUnreadCount > 99 ? '99+' : currentUnreadCount).addClass('show');
                if ($unreadOnly.length) $unreadOnly.text(currentUnreadCount + ' New').show();
            } else {
                $count.removeClass('show');
                if ($unreadOnly.length) $unreadOnly.hide();
            }
        }

        function decrementUnreadCount() {
            if (currentUnreadCount > 0) {
                currentUnreadCount--;
            }
            updateNotificationUI({ unread_count: currentUnreadCount });
        }

        function handleNotificationClick(id, element) {
            // Mark as read via API
            let url = "{{ route('admin.notifications.read', ':id') }}";
            url = url.replace(':id', id);

            $.ajax({
                url: url,
                type: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status) {
                        $(element).removeClass('unread');
                        fetchNotifications(); // Refresh count
                    }
                }
            });
        }

        // Fetch when the tab gains focus
        $(window).on('focus', function() {
            fetchNotifications();
        });

        // Fetch when an action is performed that might trigger a notification
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.type !== 'GET' && settings.url.indexOf("{{ route('admin.notifications.list') }}") === -1) {
                fetchNotifications();
            }
        });

        // Initial fetch only
        $(document).ready(function() {
            fetchNotifications();
        });
    </script>
    @stack('scripts')

</body>

</html>
