<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'no_trailing_whitespace' => true,
        'single_quote' => true,
        'blank_line_before_statement' => ['statements' => ['return']],
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_order' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha']
    ])
    ->setFinder($finder);
