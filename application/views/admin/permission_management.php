<!-- In header.php -->
<link rel="stylesheet" href="<?= base_url('assets/dist/css/adminlte.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/plugins/fontawesome-free/css/all.min.css') ?>">
<script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/dist/js/adminlte.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/sweetalert2/sweetalert2.all.min.js') ?>"></script>

<div class="wrapper">
        <section class="content">
            <div class="container-fluid">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-shield mr-2"></i>Permission Management
                        </h3>
                    </div>
                    
                    <div class="card-body">
                        <div class="row">
                            <!-- Group Selection Sidebar -->
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Groups</h4>
                                    </div>
                                    <div class="card-body p-0">
                                        <ul class="nav nav-pills flex-column" id="groupTabs" role="tablist">
                                            <?php foreach ($groups as $index => $group): ?>
                                            <li class="nav-item">
                                                <a class="nav-link <?= $index === 0 ? 'active' : '' ?>" 
                                                   id="group-<?= $group->id ?>-tab" 
                                                   data-toggle="pill" 
                                                   href="#group-<?= $group->id ?>" 
                                                   role="tab">
                                                    <span class="badge <?= $group->name === 'admin' ? 'bg-danger' : 'bg-primary' ?> mr-2">
                                                        <?= strtoupper(substr($group->name, 0, 1)) ?>
                                                    </span>
                                                    <?= safe_html(ucfirst($group->name)) ?>
                                                </a>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Permission Content -->
                            <div class="col-md-9">
                                <div class="tab-content" id="groupTabsContent">
                                    <?php foreach ($groups as $index => $group): 
                                        $grouped_permissions = [];
                                        foreach ($permissions as $perm) {
                                            $grouped_permissions[$perm->controller][] = $perm;
                                        }
                                    ?>
                                    <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" 
                                         id="group-<?= $group->id ?>" 
                                         role="tabpanel">
                                        
                                        <div class="card">
                                            <div class="card-header">
                                                <h4 class="card-title"><?= safe_html(ucfirst($group->name)) ?> Permissions</h4>
                                            </div>
                                            
                                            <div class="card-body">
                                                <form id="form-group-<?= $group->id ?>" class="permission-form">
                                                    <input type="hidden" name="group_id" value="<?= $group->id ?>">
                                                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                                    
                                                    <?php foreach ($grouped_permissions as $controller => $controller_permissions): ?>
                                                    <div class="controller-section mb-4">
                                                        <h5 class="section-title">
                                                            <i class="fas fa-fw fa-cube mr-2"></i>
                                                            <?= ucwords(str_replace('_', ' ', $controller)) ?>
                                                        </h5>
                                                        
                                                        <div class="row">
                                                            <?php foreach ($controller_permissions as $perm): ?>
                                                            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-3">
                                                                <div class="card permission-card">
                                                                    <div class="card-body">
                                                                        <div class="custom-control custom-switch">
                                                                            <input type="checkbox" 
                                                                                   class="custom-control-input" 
                                                                                   id="perm-<?= $group->id ?>-<?= $perm->id ?>" 
                                                                                   name="permission_ids[]" 
                                                                                   value="<?= $perm->id ?>"
                                                                                   <?= in_array($perm->id, $group->assigned_permissions ?? []) ? 'checked' : '' ?>
                                                                                   title="<?= in_array($perm->id, $group->assigned_permissions ?? []) ? 'Enabled' : 'Disabled' ?>">
                                                                            <label class="custom-control-label" 
                                                                                   for="perm-<?= $group->id ?>-<?= $perm->id ?>">
                                                                                <?= ucwords(str_replace('_', ' ', $perm->method)) ?>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                    
                                                    <div class="card-footer text-right">
                                                        <button class="btn btn-success save-permissions" 
                                                                data-group-id="<?= $group->id ?>">
                                                            <i class="fas fa-save mr-2"></i> Save Changes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
</div>

<style>
/* General styles that work with both light and dark mode */
.controller-section {
    margin-bottom: 2rem;
}

.controller-section .section-title {
    font-size: 1.05rem;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.permission-card {
    transition: all 0.2s ease;
    height: 100%;
}

.permission-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

/* Dark mode specific styles */
[data-theme="dark"] .controller-section .section-title {
    border-bottom-color: rgba(255,255,255,0.1);
}

[data-theme="dark"] .permission-card {
    background-color: #343a40;
    border-color: #454d55;
}

[data-theme="dark"] .permission-card:hover {
    background-color: #3d444c;
}

/* Custom switch colors that work in both modes */
.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #28a745;
    border-color: #28a745;
}

.custom-control-input:not(:checked) ~ .custom-control-label::before {
    background-color: #dc3545; /* Red */
    border-color: #dc3545;
}

/* Active tab styling */
.nav-pills .nav-link.active {
    background-color: transparent;
    color: var(--primary);
    border-left: 3px solid var(--primary);
    font-weight: 500;
}

/* Badge styling */
.badge {
    min-width: 24px;
    display: inline-flex;
    justify-content: center;
    align-items: center;
}
</style>

<script>
$(document).ready(function() {
    // Detect dark mode
    function detectDarkMode() {
        if ($('body').hasClass('dark-mode')) {
            $('html').attr('data-theme', 'dark');
        } else {
            $('html').attr('data-theme', 'light');
        }
    }
    
    // Run on load
    detectDarkMode();
    
    // Watch for dark mode changes
    $(document).on('click', '[data-widget="pushmenu"]', function() {
        setTimeout(detectDarkMode, 100);
    });
    
    // Initialize group tabs
    $('#groupTabs a').on('click', function(e) {
        e.preventDefault();
        $(this).tab('show');
    });

    // Update checkbox titles on change
    $(document).on('change', '[type="checkbox"]', function() {
        $(this).attr('title', this.checked ? 'Enabled' : 'Disabled');
    });

    // Save permissions with AJAX
    $('.save-permissions').click(function(e) {
        e.preventDefault();
        var groupId = $(this).data('group-id');
        var form = $('#form-group-' + groupId);
        var submitBtn = $(this);
        var originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');
        
        $.ajax({
            url: '<?= base_url('admin/permission_admin/save') ?>',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json'
        })
        .done(function(response) {
            if (response && response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                submitBtn.html('<i class="fas fa-check mr-1"></i> Saved');
            } else {
                showError(response?.message || 'Operation failed');
            }
        })
        .fail(function(xhr) {
            var errorMsg = 'Request failed';
            try {
                var jsonResponse = JSON.parse(xhr.responseText);
                errorMsg = jsonResponse.message || errorMsg;
            } catch (e) {
                errorMsg = xhr.statusText;
            }
            showError(errorMsg);
        })
        .always(function() {
            setTimeout(function() {
                submitBtn.html(originalText).prop('disabled', false);
            }, 2000);
        });
    });
    
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: message,
            confirmButtonText: 'OK',
            customClass: {
                confirmButton: 'btn btn-danger'
            }
        });
    }
});
</script>