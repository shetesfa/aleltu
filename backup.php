<?php
// backup.php
$backup_file = 'backup/aleltu_backup_' . date('Y-m-d') . '.sql';
$command = "mysqldump --user=root --password= aleltu > " . $backup_file;
exec($command);
echo "Backup created at " . $backup_file;
?>