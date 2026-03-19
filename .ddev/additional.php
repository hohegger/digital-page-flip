<?php

$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] = array_merge(
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'] ?? [],
    [
        'host' => 'db',
        'port' => 3306,
        'dbname' => 'db',
        'user' => 'db',
        'password' => 'db',
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*\\.ddev\\.site';

// Add webp to allowed media file extensions (not in TYPO3 v12 default)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] = 'gif,jpg,jpeg,bmp,png,pdf,svg,ai,mp3,wav,mp4,ogg,flac,opus,webm,webp,youtube,vimeo';
