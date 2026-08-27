<section class="content">
  <div class="container-fluid">
    <div class="card card-primary card-outline shadow-sm">
    <div class="card-header bg-gradient-primary d-flex justify-content-between align-items-center py-3">
        <h3 class="card-title m-0 text-white">
          <i class="fas fa-database mr-2"></i> Database Management Console
        </h3>
        <div>
          <a href="<?= site_url('admin/backup/create') ?>" class="btn btn-light btn-sm elevation-2" data-toggle="tooltip" title="Create new database backup">
            <i class="fas fa-plus-circle mr-1"></i> New Backup
          </a>
        </div>
      </div>

      <div class="card-body">
        <!-- Upload Form -->
        <form action="<?= site_url('admin/backup/restore') ?>" method="post" enctype="multipart/form-data" class="form-inline mb-4">
          <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
          <label class="mr-2 font-weight-bold text-secondary">Upload Backup:</label>
          <input type="file" name="backup_file" required class="form-control form-control-sm mr-2">
          <button class="btn btn-warning btn-sm" data-toggle="tooltip" title="Restore from backup">
            <i class="fas fa-upload"></i> Restore
          </button>
        </form>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover text-sm">
            <thead class="thead-dark">
              <tr>
                <th><i class="fas fa-file-alt mr-1"></i> File</th>
                <th><i class="fas fa-weight mr-1"></i> Size</th>
                <th><i class="fas fa-calendar-alt mr-1"></i> Date</th>
                <th class="text-center"><i class="fas fa-cogs mr-1"></i> Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($backups as $b): ?>
              <tr>
                <td><?= htmlspecialchars($b->file_name) ?></td>
                <td><?= number_format($b->file_size / 1024, 2) ?> KB</td>
                <td><?= $b->created_at ?></td>
                <td class="text-center">
                  <a href="<?= site_url('admin/backup/download/' . $b->file_name) ?>"
                     class="btn btn-info btn-sm" data-toggle="tooltip" title="Download">
                    <i class="fas fa-download"></i>
                  </a>
                  <a href="javascript:void(0);"
                     class="btn btn-danger btn-sm delete-backup-btn"
                     data-url="<?= site_url('admin/backup/delete/' . urlencode(base64_encode($b->file_name))) ?>"
                     data-toggle="tooltip" title="Delete Backup">
                    <i class="fas fa-trash"></i>
                  </a>
                  <a href="javascript:void(0);" 
                    class="btn btn-warning btn-sm restore-backup-btn" 
                    data-url="<?= site_url('admin/backup/restore_from_file/' . rawurlencode($b->file_name)) ?>" 
                    title="Restore Backup">
                    <i class="fas fa-upload"></i>
                    </a>

                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($backups)): ?>
              <tr>
                <td colspan="4" class="text-center text-muted">No backups found.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
<script>
  $(function () {
    $('[data-toggle="tooltip"]').tooltip();

    $('.delete-backup-btn').on('click', function (e) {
      e.preventDefault();
      const url = $(this).data('url');

      Swal.fire({
        title: 'Are you sure?',
        text: 'This backup file will be permanently deleted!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = url;
        }
      });
    });
  });
</script>
<script>
    $('.restore-backup-btn').on('click', function (e) {
    e.preventDefault();
    const url = $(this).data('url');

    Swal.fire({
        title: 'Are you sure?',
        text: 'This will restore the entire database from this backup!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Restore!',
        backdrop: true,
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
});

</script>
