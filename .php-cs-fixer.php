<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules([
        '@PhpCsFixer' => true,
    ]);