<?php

require 'server/pocket-knife/start.php';
require 'server/classes/Magazine.php';
require 'server/classes/Library.php';
require 'server/classes/Link.php';
require 'server/classes/Page.php';

try {
    // validate ID
    $tokens = WebUrl::getTokens();
    $id = @$tokens[0];
    if (!$id) {
        throw new Error('No ID defined in URL like "/print.php/[id]', 400);
    }
    // get magazine
    $magazine = new Magazine($id);
    if (!$magazine->getStorage()->exists($id)) {
        throw new Error("Magazine '{$id}' does not exist.", 404);
    }
    $data = $magazine->GET();
    // 
} catch (Error $e) {
    // send error as HTML always
    $e->send('text/html');
    exit();
}

// include embed
ob_start();
include 'server/ui/magazine-embed.php';
$content = ob_get_clean();

// output
echo htmlentities($content);