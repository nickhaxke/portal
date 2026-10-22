<?php

$paths = ['resources', 'config', 'database', 'app'];
$allEmojis = [];

foreach ($paths as $dir) {
    if (! is_dir($dir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'blade', 'js', 'css', 'json'])) {
            $content = file_get_contents($file->getPathname());
            preg_match_all('/[\x{1F300}-\x{1FAD6}\x{2600}-\x{27BF}\x{1F900}-\x{1F9FF}]/u', $content, $matches);
            if (! empty($matches[0])) {
                $unique = array_unique($matches[0]);
                echo $file->getPathname().' => '.implode(' ', $unique)."\n";
            }
        }
    }
}
