<?php
$root = dirname(__DIR__);
foreach (['listennotes', 'tests', 'examples'] as $directory) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory)) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            passthru(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()), $status);
            if ($status !== 0) { exit($status); }
        }
    }
}
