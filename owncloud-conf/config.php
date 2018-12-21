<?php
$CONFIG = array (
  'datadirectory' => '/mnt/data/files',
  'apps_paths' => 
  array (
    0 => 
    array (
      'path' => '/var/www/owncloud/apps',
      'url' => '/apps',
      'writable' => false,
    ),
    1 => 
    array (
      'path' => '/var/www/owncloud/custom',
      'url' => '/custom',
      'writable' => true,
    ),
  ),
  'dbtype' => 'mysql',
  'dbhost' => 'db:3306',
  'dbname' => 'owncloud',
  'dbuser' => 'owncloud',
  'dbpassword' => 'guessme',
  'dbtableprefix' => 'oc_',
  'trusted_domains' => 
  array (
    0 => 'drive.example.com',
  ),
  'onlyoffice' => 
  array (
    'verify_peer_off' => true,
  ),
  'mysql.utf8mb4' => true,
  'passwordsalt' => 'guessme',
  'secret' => 'guessme',
  'overwrite.cli.url' => 'http://172.16.46.2/',
  'version' => '9.1.8.2',
  'logtimezone' => 'UTC',
  'installed' => true,
  'instanceid' => 'oc6v87jdhtme',
  'updatechecker' => 'false',
  'upgrade.disable-web' => 'true',
  'redis' => 
  array (
    'host' => 'redis',
    'port' => 6379,
  ),
  'memcache.distributed' => '\\OC\\Memcache\\Redis',
  'memcache.locking' => '\\OC\\Memcache\\Redis',
  'filelocking.enabled' => 'true',
  'memcache.local' => '\\OC\\Memcache\\APCu',
  'maintenance' => false,
  'loglevel' => '2',
  'default_language' => 'en',
  'htaccess.RewriteBase' => '/',
  'mail_smtpmode' => 'smtp',
);
