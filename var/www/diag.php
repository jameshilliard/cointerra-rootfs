<?php

/* collect diagnostic data */

$commands = array(
    'cat /version.txt',
    'lsusb',
    'dmesg',
    'ifconfig',
    'ps',
    'uptime',
    'pidof cgminer && echo \'{"command":"stats"}\' | nc 127.0.0.1 4028 | tr -d "\0"',
    'cat /Angstrom/Cointerra/logs/cgminer.log',
    'cat /Angstrom/Cointerra/logs/cgminer.log.*.gz | gunzip',
);

function cmdstr($cmd) {
    return sprintf("echo '==== %s ====';%s", $cmd, $cmd);
}

$command = sprintf("(%s)|gzip", implode(';', array_map('cmdstr', $commands)));

date_default_timezone_set('UTC');
$filename = date("YmdHis");
/* this is system specific and will be replaced in the new web interface */
$mac = substr(@file_get_contents('/sys/devices/ocp.3/4a100000.ethernet/net/eth0/address'), 0, 17);
if (strlen($mac) == 17) {
    $filename = sprintf("%s-%s", strtoupper(str_replace(':', '', $mac)), $filename);
}

/* set headers */
header("Content-Disposition: attachment; filename={$filename}.gz");
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
header("Content-Description: File Transfer");

passthru($command);
flush();
