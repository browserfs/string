<?php

	/**
     * This class is doing ...
     */

	class IniReaderTest extends PHPUnit_Framework_TestCase {

		protected $ini = null;

		protected function setUp() {
			
			$this->ini = \browserfs\string\Parser\IniReader::create(__DIR__ . '/sample.ini');

		}

		public function testSections() {
			$this->assertEquals(true, $this->ini->sectionExists('development'));
			$this->assertEquals(true, $this->ini->sectionExists('production'));
			$this->assertEquals(true, $this->ini->sectionExists('env'));
			$this->assertEquals(true, $this->ini->sectionExists('database'));
			$this->assertEquals(true, $this->ini->sectionExists('database.production'));
			$this->assertEquals(false, $this->ini->sectionExists('invalidsection'));
		}

	}