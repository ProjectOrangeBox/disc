<?php

declare(strict_types=1);

/*
In __root__

./vendor/bin/phpunit ./local/disc/tests

./vendor/bin/phpunit ./local/disc/tests/directoryTest.php

*/

use dmyers\disc\disc;
use PHPUnit\Framework\TestCase;
use dmyers\disc\exceptions\DiscException;
use dmyers\disc\exceptions\FileException;
use dmyers\disc\exceptions\DirectoryException;

final class directoryTest extends TestCase
{
	public function setUp(): void
	{
		if (!defined('__ROOT__')) {
			define('__ROOT__', realpath(__DIR__ . '/support'));
		}

		disc::root(__ROOT__);
	}

	public function tearDown(): void
	{
		disc::directory('/working')->removeContents();
	}
} /* end class */
