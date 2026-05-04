<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@PHP82Migration' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'global_namespace_import' => ['import_classes' => true, 'import_functions' => false],
        'phpdoc_align' => ['align' => 'left'],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'yoda_style' => false,
    ])
    ->setFinder($finder);
