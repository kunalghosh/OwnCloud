<?php
#unlink("$base_dir/log.txt");
function lg($str) {
	global $do_log, $base_dir;
	if ($do_log > 0) {
		$hand_lg = fopen("$base_dir/log.txt", "a");
		$str_new = implode(" ",$str); #just to see if this logs arrays embedded in strings or not.
		fwrite($hand_lg, $str_new."\r\n");
		fclose($hand_lg);
	};
};
?>
