<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
	->setRiskyAllowed(true)
	->setIndent("\t")
	->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
	->setRules([
		'@PER-CS3x0' => true,
		'@PER-CS3x0:risky' => true,
		'cast_spaces' => ['space' => 'none'],
		'declare_strict_types' => true,
		'yoda_style' => [
			'equal' => false,
			'identical' => false,
			'less_and_greater' => false,
		],
	])
	->setFinder(
		(new PhpCsFixer\Finder())
			->in(__DIR__)
			->exclude('tests/Fixtures'),
	);
