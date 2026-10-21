<?php
$css = file_get_contents('assets/css/style.css');
$open = substr_count($css, '{');
$close = substr_count($css, '}');
echo "braces open=$open close=$close balanced=" . ($open === $close ? 'YES' : 'NO') . PHP_EOL;
// unclosed comment
echo "comment open count=" . preg_match_all('~/\*~', $css) . " close=" . preg_match_all('~\*/~', $css) . PHP_EOL;
echo "size=" . strlen($css) . PHP_EOL;