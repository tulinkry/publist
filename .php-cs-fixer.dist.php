<?php

// Minimal, opinionated-as-little-as-possible style gate: PSR-12 (which
// already requires braces on every control structure) plus a few explicit
// rules for the specific inconsistencies this codebase had - spaces inside
// parentheses, spaces around "->", unary operators glued to their operand.
// Run `composer lint:fix` to autofix, `composer lint` to check without
// writing (used as the build-phase gate, see Dockerfile).

$finder = (new PhpCsFixer\Finder())
	->in([__DIR__ . '/app', __DIR__ . '/tests'])
	->name('*.php')
	->name('*.phpt');

return (new PhpCsFixer\Config())
	->setRules([
		'@PSR12' => true,
		'object_operator_without_whitespace' => true,
		'spaces_inside_parentheses' => ['space' => 'none'],
		'unary_operator_spaces' => true,
		'method_argument_space' => ['on_multiline' => 'ignore'],
	])
	->setRiskyAllowed(false)
	// This codebase indents with tabs; PSR-12's indentation rules default to
	// spaces, which would otherwise turn every line of every file into a diff.
	->setIndent("\t")
	->setFinder($finder);
