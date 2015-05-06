<?php
require_once 'common.php';

copy('/opt/cgminer.conf','/Angstrom/Cointerra/cgminer.conf');

?>
<?php echo $head; /*common.php */?>

	<?php echo $menu; /*common.php */?>
<table border=1 cellspacing=0 cellpadding=2>
	<tr>
		<td valign='top'>
			<div id='main'><h3>CGMiner config file has been reset to default settings.</h3></div>
		</td>
	</tr>
</table>

<?php echo $foot; /*common.php */?>
