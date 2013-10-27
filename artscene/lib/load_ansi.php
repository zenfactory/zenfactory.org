<?php

# Pull request variable
$input = trim(urlencode($_REQUEST['input']));

# Point googlge bot to new url 
header("Location: http://ansi.zenfactory.org/lib/load_ansi.php?input=$input");
exit();

?>
