<?php
include('Net/SSH2.php');
error_reporting(E_ALL);  //this is a dev version
ini_set('display_errors', '1');  //dev version


$ssh = new Net_SSH2('miner3');
if (!$ssh->login('user', 'live')) {
    exit('Login Failed');
}

echo "<pre>";
echo $ssh->exec('hostname');
echo $ssh->exec('pwd');
echo $ssh->exec('whoami');
echo $ssh->exec('ls -l');
echo $ssh->exec('sudo init 6');



//echo $ssh->exec('./cgminer-2.2.1-x86_64-built/cgminer -c myconf.m1.conf');


echo "</pre>";



?>
