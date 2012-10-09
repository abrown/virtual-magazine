<?php

require 'server/pocket-knife/start.php';

// test writable data
$path = realpath(get_base_dir() . '/../test').'/test.txt';
$length = @file_put_contents($path, '...');
if($length != 3){
    pr('Cannot write to local directory: check file permissions');
}
else{
    unlink($path);
}

// test execution length
$execution_time = ini_get('max_execution_time');
//pr('Max execution time: '.$execution_time);
if( !set_time_limit($execution_time * 2)){
    pr('Cannot change execution time.');
}
$new_execution_time = ini_get('max_execution_time');
//pr('New max execution time: '.$new_execution_time);

// find ghostscript
$whereis = `whereis gs`;
$start = strpos($whereis, ':');
if( $start === false ){
    $start = 0;
}
$whereis = ltrim(substr($whereis, $start+1));
$end = strpos($whereis, ' ');
if( $end !== false ){
    $whereis = substr($whereis, 0, $end);
}
$gs = trim($whereis);

// test ghostscript
$path = realpath(get_base_dir() . '/../test');
$command = "$gs -dNOPAUSE -sDEVICE=jpeg -r300 -sOutputFile=$path/test-%d.jpg $path/test.pdf";
exec($command, $output, $return_value);
if( $return_value !== 0 ){
    pr('Ghostscript failure: ');
    foreach($output as $line){
        pr("\t".$line);
    }
}
else{
    $jpeg = $path.'/test-1.jpg';
    if(!file_exists($jpeg)){
        pr('Could not find file: '.$jpeg);
    }
    unlink($path.'/test-1.jpg');
    unlink($path.'/test-2.jpg');
}

// test GD support
if( !function_exists('gd_info')){
    pr('No GD support installed.');
}

//pr($_SERVER);
//pr(ini_get('user_ini.filename'));
pr(ini_set('upload_max_filesize', 2000000));
pr(ini_get('upload_max_filesize'));
pr(ini_get('post_max_size'));
pr(ini_get('memory_limit'));
