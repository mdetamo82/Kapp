<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database Backup & Restore Controller
 *
 * Authorization model:
 *
 * view_backup      -> index()
 * create_backup    -> create()
 * download_backup  -> download()
 * delete_backup    -> delete()
 * restore_backup   -> restore()
 * restore_backup   -> restore_from_file()
 *
 * cron_backup() is a machine/cron endpoint and is intentionally
 * separate from normal user authorization.
 */
class Backup extends CI_Controller
{
    /**
     * Backup directory.
     *
     * @var string
     */
    private $backup_path;


    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('authorization');

        $this->load->dbutil();
        $this->load->helper(['file', 'download']);
        $this->load->model('Backup_model');

        $this->backup_path = FCPATH . 'backups/';
    }


    /**
     * Display available backups.
     *
     * Permission:
     *     view_backup
     *
     * @return void
     */
    public function index()
    {
        $this->authorization
            ->require_permission('view_backup');

        if (!is_dir($this->backup_path)) {
            $backups = [];
        } else {
            $files = array_diff(
                scandir($this->backup_path),
                ['.', '..']
            );

            $backups = [];

            foreach ($files as $file) {

                $path = $this->backup_path . $file;

                /*
                 * Only list regular files.
                 */
                if (!is_file($path)) {
                    continue;
                }

                $backups[] = (object) [
                    'file_name'  => $file,
                    'file_size'  => filesize($path),
                    'created_at' => date(
                        'Y-m-d H:i:s',
                        filemtime($path)
                    ),
                ];
            }
        }

        $data = [
            'title'   => 'Database Backup & Restore',
            'backups' => $backups,
        ];

        $this->template->render(
            'admin/backup/index',
            $data
        );
    }


    /**
     * Create a database backup.
     *
     * Permission:
     *     create_backup
     *
     * @return void
     */
    public function create()
    {
        $this->authorization
            ->require_permission('create_backup');

        /*
         * Ensure backup directory exists.
         */
        if (!is_dir($this->backup_path)) {
            if (!mkdir($this->backup_path, 0755, true)) {
                alert_error(
                    'Unable to create backup directory.'
                );

                redirect('admin/backup');
                return;
            }
        }

        $prefs = [
            'format'   => 'zip',
            'filename' => 'db-backup.sql',
        ];

        $backup = $this->dbutil->backup($prefs);

        if ($backup === false || $backup === '') {
            alert_error(
                'Database backup could not be created.'
            );

            redirect('admin/backup');
            return;
        }

        $name = 'backup-' . date('Ymd_His') . '.zip';

        $path = $this->backup_path . $name;

        if (!write_file($path, $backup)) {
            alert_error(
                'Unable to save database backup.'
            );

            redirect('admin/backup');
            return;
        }

        $this->Backup_model->log_backup(
            $name,
            strlen($backup),
            'Manual',
            $this->ion_auth->get_user_id()
        );

        alert_success(
            'Backup created successfully.'
        );

        redirect('admin/backup');
    }


    /**
     * Download a backup file.
     *
     * Permission:
     *     download_backup
     *
     * @param string $file
     * @return void
     */
    public function download($file)
    {
        $this->authorization
            ->require_permission('download_backup');

        /*
         * Prevent path traversal.
         */
        $file = basename(
            urldecode($file)
        );

        $path = $this->backup_path . $file;

        if (!is_file($path)) {
            alert_error(
                'Backup file not found.'
            );

            redirect('admin/backup');
            return;
        }

        force_download(
            $path,
            null
        );
    }


    /**
     * Delete a backup file.
     *
     * Permission:
     *     delete_backup
     *
     * @param string $encoded_file
     * @return void
     */
    public function delete($encoded_file)
    {
        $this->authorization
            ->require_permission('delete_backup');

        /*
         * Decode URL encoding first.
         */
        $encoded_file = urldecode(
            $encoded_file
        );

        /*
         * Decode Base64.
         */
        $file = base64_decode(
            $encoded_file,
            true
        );

        if ($file === false) {
            alert_error(
                'Invalid backup file reference.'
            );

            redirect('admin/backup');
            return;
        }

        /*
         * Prevent path traversal.
         */
        $file = basename($file);

        $path = $this->backup_path . $file;

        if (!is_file($path)) {
            alert_error(
                'Backup file was already deleted or does not exist.'
            );

            redirect('admin/backup');
            return;
        }

        if (!unlink($path)) {
            alert_error(
                'Unable to delete backup file.'
            );

            redirect('admin/backup');
            return;
        }

        alert_success(
            'Backup deleted successfully.'
        );

        redirect('admin/backup');
    }


    /**
     * Restore database from uploaded backup.
     *
     * Permission:
     *     restore_backup
     *
     * @return void
     */
    public function restore()
    {
        $this->authorization
            ->require_permission('restore_backup');

        if (
            !isset($_FILES['backup_file']) ||
            $_FILES['backup_file']['error'] !==
                UPLOAD_ERR_OK
        ) {
            alert_error(
                'No file uploaded or file upload error.'
            );

            redirect('admin/backup');
            return;
        }

        $zip = new ZipArchive;

        $tmp_path =
            $_FILES['backup_file']['tmp_name'];

        if ($zip->open($tmp_path) !== true) {
            alert_error(
                'Invalid backup file.'
            );

            redirect('admin/backup');
            return;
        }

        $sql_content =
            $zip->getFromName('db-backup.sql');

        $zip->close();

        if ($sql_content === false) {
            alert_error(
                'SQL file not found in backup.'
            );

            redirect('admin/backup');
            return;
        }

        /*
         * Restore database.
         */
        $this->db->trans_start();

        $this->db->query(
            'SET FOREIGN_KEY_CHECKS=0'
        );

        $queries = explode(
            ";\n",
            $sql_content
        );

        foreach ($queries as $query) {

            $query = trim($query);

            if ($query === '') {
                continue;
            }

            $this->db->query($query);
        }

        $this->db->query(
            'SET FOREIGN_KEY_CHECKS=1'
        );

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            alert_error(
                'Database restore failed during execution.'
            );
        } else {
            alert_success(
                'Database restored successfully.'
            );
        }

        redirect('admin/backup');
    }


    /**
     * Restore an existing backup from the backup directory.
     *
     * Permission:
     *     restore_backup
     *
     * @param string $file
     * @return void
     */
    public function restore_from_file($file)
    {
        $this->authorization
            ->require_permission('restore_backup');

        /*
         * Prevent path traversal.
         */
        $file = basename(
            urldecode($file)
        );

        $path = $this->backup_path . $file;

        if (!is_file($path)) {
            alert_error(
                'Backup file not found.'
            );

            redirect('admin/backup');
            return;
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            alert_error(
                'Invalid backup file.'
            );

            redirect('admin/backup');
            return;
        }

        $sql_content =
            $zip->getFromName('db-backup.sql');

        $zip->close();

        if ($sql_content === false) {
            alert_error(
                'SQL content not found in backup.'
            );

            redirect('admin/backup');
            return;
        }

        /*
         * Restore database.
         */
        $this->db->trans_start();

        $this->db->query(
            'SET FOREIGN_KEY_CHECKS=0'
        );

        $queries = explode(
            ";\n",
            $sql_content
        );

        foreach ($queries as $query) {

            $query = trim($query);

            if ($query === '') {
                continue;
            }

            $this->db->query($query);
        }

        $this->db->query(
            'SET FOREIGN_KEY_CHECKS=1'
        );

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            alert_error(
                'Database restore failed during execution.'
            );
        } else {
            alert_success(
                'Database restored from backup.'
            );
        }

        redirect('admin/backup');
    }


    /**
     * Automated cron backup.
     *
     * This endpoint is NOT a normal user authorization boundary.
     *
     * It uses its own secret token mechanism.
     *
     * @param string $token
     * @return void
     */
    public function cron_backup($token = '')
    {
        if ($token !== 'secure-token-here') {
            show_404();
            return;
        }

        if (!is_dir($this->backup_path)) {
            if (!mkdir($this->backup_path, 0755, true)) {
                log_message(
                    'error',
                    'Cron backup: unable to create backup directory.'
                );

                return;
            }
        }

        $prefs = [
            'format'   => 'zip',
            'filename' => 'db-backup.sql',
        ];

        $backup = $this->dbutil->backup($prefs);

        if ($backup === false || $backup === '') {
            log_message(
                'error',
                'Cron backup: database backup failed.'
            );

            return;
        }

        $name =
            'cron-backup-' .
            date('Ymd_His') .
            '.zip';

        $path =
            $this->backup_path .
            $name;

        if (!write_file($path, $backup)) {
            log_message(
                'error',
                'Cron backup: unable to write backup file.'
            );

            return;
        }

        $this->Backup_model->log_backup(
            $name,
            strlen($backup),
            'Cron',
            null
        );
    }
}
