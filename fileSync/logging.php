<?php
#unlink("$base_dir/log.txt");
function lg($str) {
	global $do_log, $base_dir;
	if ($do_log > 0) {
		$hand_lg = fopen("$base_dir/log.txt", "a");
		fwrite($hand_lg, $str."\r\n");
		fclose($hand_lg);
	};
};
?>
