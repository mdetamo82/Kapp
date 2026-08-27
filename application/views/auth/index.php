<div class="container-fluid">

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">
        <i class="fas fa-users mr-1"></i>
        <?= lang('index_heading') ?>
      </h3>
      <div class="card-tools">
	 
        <a href="<?= site_url('auth/create_user') ?>" class="btn btn-primary btn-sm">
          <i class="fas fa-user-plus"></i> <?= lang('index_create_user_link') ?>
        </a>
        <a href="<?= site_url('auth/create_group') ?>" class="btn btn-secondary btn-sm">
          <i class="fas fa-users-cog"></i> <?= lang('index_create_group_link') ?>
        </a>
     
      </div>
    </div>
    
    <div class="card-body">
      <table id="usersTable" class="table table-bordered table-striped">
        <thead>
          <tr>
            <th><i class="fas fa-user mr-1"></i> <?= lang('index_fname_th') ?></th>
            <th><i class="fas fa-user mr-1"></i> <?= lang('index_lname_th') ?></th>
            <th><i class="fas fa-envelope mr-1"></i> <?= lang('index_email_th') ?></th>
            <th><i class="fas fa-users-cog mr-1"></i> <?= lang('index_groups_th') ?></th>
            <th><i class="fas fa-info-circle mr-1"></i> <?= lang('index_status_th') ?></th>
            <th><i class="fas fa-cog mr-1"></i> <?= lang('index_action_th') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><?= htmlspecialchars($user->first_name, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($user->last_name, ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <?php foreach ($user->groups as $group): ?>
                  <a href="<?= site_url("auth/edit_group/" . $group->id) ?>" class="badge badge-info mb-1">
                    <?= htmlspecialchars($group->name, ENT_QUOTES, 'UTF-8') ?>
                  </a>
                <?php endforeach; ?>
              </td>
              <td>
                <?php if ($user->active): ?>
                  <span class="badge badge-success">
                    <i class="fas fa-check-circle mr-1"></i> <?= lang('index_active_link') ?>
                  </span>
                <?php else: ?>
                  <span class="badge badge-danger">
                    <i class="fas fa-times-circle mr-1"></i> <?= lang('index_inactive_link') ?>
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-flex">
                  <a href="<?= site_url("auth/edit_user/" . $user->id) ?>" class="btn btn-sm btn-default action-btn" title="Edit">
                    <i class="fas fa-edit text-info"></i>
                  </a>
                  <?php if ($user->active): ?>
                    <a href="<?= site_url("auth/deactivate/" . $user->id) ?>" class="btn btn-sm btn-default action-btn deactivate-btn" title="Deactivate">
                      <i class="fas fa-user-slash text-warning"></i>
                    </a>
                  <?php else: ?>
                    <a href="<?= site_url("auth/activate/" . $user->id) ?>" class="btn btn-sm btn-default action-btn activate-btn" title="Activate">
                      <i class="fas fa-user-check text-success"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
$(function () {
  // Initialize DataTable
  $("#usersTable").DataTable({
    "responsive": true, 
    "lengthChange": false, 
    "autoWidth": false,
    "buttons": ["copy", "csv", "excel", "pdf", "print"]
  }).buttons().container().appendTo('#usersTable_wrapper .col-md-6:eq(0)');

  // Deactivation confirmation
  $('.deactivate-btn').click(function(e) {
    e.preventDefault();
    const deactivateUrl = $(this).attr('href');
    const userName = $(this).closest('tr').find('td:first').text();
    
    Swal.fire({
      title: '<?= lang("deactivate_confirmation_title") ?>',
      html: '<?= lang("deactivate_confirmation_text") ?> <b>' + userName + '</b>?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: '<?= lang("deactivate_confirm_btn") ?>',
      cancelButtonText: '<?= lang("cancel_btn") ?>',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = deactivateUrl;
      }
    });
  });

  // Activation confirmation
  $('.activate-btn').click(function(e) {
    e.preventDefault();
    const activateUrl = $(this).attr('href');
    const userName = $(this).closest('tr').find('td:first').text();
    
    Swal.fire({
      title: '<?= lang("activate_confirmation_title") ?>',
      html: '<?= lang("activate_confirmation_text") ?> <b>' + userName + '</b>?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: '<?= lang("activate_confirm_btn") ?>',
      cancelButtonText: '<?= lang("cancel_btn") ?>',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = activateUrl;
      }
    });
  });
});
</script>