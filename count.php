<?php
require 'server/pocket-knife/start.php';
add_include_path('server/classes');
$tokens = WebUrl::getTokens();
echo Magazine::countPages(@$tokens[0]);