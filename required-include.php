<?php
/**
 * Get the virtual-magazine base directory
 * @return string
 */
function get_vm_dir(){
    return dirname(__FILE__);
}

/**
 * Include pocket-knife files
 */
require get_vm_dir(). '/../pocket-knife/start.php';

/**
 * Set path to autoload
 */
add_include_path(get_vm_dir().'/server/classes');

/**
 * Set log file locations
 */
BasicLog::setFile(get_vm_dir().'/server/logs/error.log', 'error');

