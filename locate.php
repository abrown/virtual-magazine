<?php

/**
 * Locates the Ghoscript executable, if possible, and returns it as a string.
 * This is used by configure.php for auto-populating the Ghostcript path.
 */

// for Windows, use a best guess:
if (PHP_OS == 'WINNT') {
    $locations = array('C:/Program Files/Ghostscript/bin/gswin64c.exe', 'C:/Program Files/gs/gs9.06/bin/gswin64c.exe');
    foreach($locations as $file){
        if(is_file($file)){
            echo $file;
            return;
        }
    }
    return;
}

// for Linux, try to locate:
$whereis = `whereis gs`;
$start = strpos($whereis, ':');
if ($start === false) {
    $start = 0;
}
$whereis = ltrim(substr($whereis, $start + 1));
$end = strpos($whereis, ' ');
if ($end !== false) {
    $whereis = substr($whereis, 0, $end);
}
$gs = trim($whereis);
echo $gs;