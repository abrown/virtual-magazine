<?php
require 'required-include.php';
$tokens = WebUrl::getTokens();
echo Magazine::countPages(@$tokens[0]);