<?php

$finder = (new PhpCsFixer\Finder())->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setRules([
        'clean_namespace' => true,
        'encoding' => true,
        'full_opening_tag' => true,
        'normalize_index_brace' => true,
        'no_unset_cast' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'octal_notation' => true,
        'simple_to_complex_string_variable' => true,
    ])
    ->setFinder($finder);
