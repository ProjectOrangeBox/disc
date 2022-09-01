<?php

declare(strict_types=1);

/*
In __root__

./vendor/bin/phpunit ./local/disc/tests

./vendor/bin/phpunit ./local/disc/tests/fileTest.php

*/

use dmyers\disc\disc;
use PHPUnit\Framework\TestCase;
use dmyers\disc\exceptions\DiscException;
use dmyers\disc\exceptions\FileException;
use dmyers\disc\exceptions\DirectoryException;

final class fileTest extends TestCase
{
	private $ini = [
		'section1' => [
			'name' => 'frank',
			'age' => 24,
		],
		'section2' => [
			'name' => 'pete',
			'age' => 28,
		]
	];

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

	public function testExportIni(): void
	{
		$file = disc::file('/local/disc/tests/test_working/test.ini');

		$this->assertEquals(57, $file->export->ini($this->ini));
	}

	public function testImportIni(): void
	{
		$ini = disc::file('/local/disc/tests/test_working/test.ini')->import->ini();

		$this->assertEquals($this->ini, $ini);
	}
} /* end class */
