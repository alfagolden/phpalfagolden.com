<?php
header('Content-Type: text/plain; charset=utf-8');
echo "SAPI=".php_sapi_name()."\n";
echo "PHP_VERSION=".PHP_VERSION."\n";
echo "LOADED_INI=".php_ini_loaded_file()."\n";
echo "SCANNED_INI=".php_ini_scanned_files()."\n";
echo "upload_max_filesize=".ini_get('upload_max_filesize')."\n";
echo "post_max_size=".ini_get('post_max_size')."\n";
echo "file_uploads=".ini_get('file_uploads')."\n";
