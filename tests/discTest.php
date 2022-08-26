<?php

declare(strict_types=1);

/*
In __root__

./vendor/bin/phpunit ./local/disc/tests

./vendor/bin/phpunit ./local/disc/tests/discTest.php

*/

use dmyers\disc\disc;
use PHPUnit\Framework\TestCase;
use dmyers\disc\exceptions\DiscException;
use dmyers\disc\exceptions\FileException;
use dmyers\disc\exceptions\DirectoryException;

final class discTest extends TestCase
{
	public function setUp(): void
	{
		if (!defined('__ROOT__')) {
			define('__ROOT__', realpath(__DIR__ . '/../../../'));
		}

		disc::root(__ROOT__);
	}

	public function tearDown(): void
	{
		disc::directory('/loca/disc/tests/test_working')->removeContents();
	}

	public function testGetRoot(): void
	{
		$this->assertEquals(__ROOT__, disc::getRoot());
	}

	public function testResolve(): void
	{
		$this->assertEquals(__ROOT__ . '/abc/123', disc::resolve('/abc/123'));
		$this->assertEquals(__ROOT__ . '/abc/123', disc::resolve('/abc/123', false, false));

		$this->expectException(DiscException::class);
		disc::resolve('/abc/123', true, true);
	}
} /* end class */
