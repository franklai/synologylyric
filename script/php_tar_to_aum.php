<?php

define('MODULE_ROOT', '../modules');
define('ARCHIVE_ROOT', '../archive');
define('INCLUDE_ROOT', '../include');
define('INFO_FILE', 'INFO');
define('COMMON_FILE', 'fujirou_common.php');

function DoTar($moduleName) {
    $infoPath = implode('/', array(MODULE_ROOT, $moduleName, INFO_FILE));

    if (!file_exists($infoPath)) {
        return FALSE;
    }

    $infoContent = file_get_contents($infoPath);
    $json = json_decode($infoContent, TRUE);

    if (!array_key_exists('module', $json) || !array_key_exists('version', $json)) {
        return FALSE;
    }

    $moduleDir = implode('/', array(MODULE_ROOT, $moduleName));
    $moduleFile = $json['module'];
    $moduleVersion = $json['version'];
    $phpPath = implode('/', array($moduleDir, $moduleFile));
    $commonPath = implode('/', array(INCLUDE_ROOT, COMMON_FILE));
    $commonTemp = implode('/', array($moduleDir, COMMON_FILE));

    $listFiles = sprintf("%s %s", INFO_FILE, $moduleFile);

    $commonExists = file_exists($commonPath);

    if ($commonExists) {
        $listFiles .= "  ".COMMON_FILE;

        // copy common php to module dir for
        copy($commonPath, $commonTemp);

        // set owner/group, permission the same as original
        chown($commonTemp, fileowner($commonPath));
        chgrp($commonTemp, filegroup($commonPath));
        chmod($commonTemp, fileperms($commonPath));
    }

    $archiveFile = sprintf("%s-%s.aum", $moduleName, $moduleVersion);
    $archivePath = implode('/', array(ARCHIVE_ROOT, $archiveFile));

    printf(" *** The following files will be including in %s *** \n", $archiveFile);
    system("cd $moduleDir; ls  $listFiles");

    $cmd = sprintf(
        "COPYFILE_DISABLE=1 tar zcf %s -C %s  %s",
        $archivePath, $moduleDir, $listFiles
    );
    system($cmd);

    if ($commonExists) {
        unlink($commonTemp);
    }
}

function PrintUsage() {
    printf("Usage:\n");
    printf("  php -d safe_mode_exec_dir= -d open_basedir= %s [module_name\n]", basename(__FILE__));

    exit(-1);
}

if (!debug_backtrace()) {
    if (empty($_SERVER['argv']) || count($_SERVER['argv']) < 2) {
        PrintUsage();
    }

    $moduleName = basename($_SERVER['argv'][1]);

    DoTar($moduleName);
}

// vim: expandtab ts=4
?>
