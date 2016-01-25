<?php

	/**
     * This class is doing ...
     */

	class IniReaderTest extends PHPUnit_Framework_TestCase {

		protected $ini = null;

		protected function setUp() {
			
			$this->ini = \browserfs\string\Parser\IniReader::create(__DIR__ . '/sample.ini');

		}

		public function testIniReader() {

			$this->assertEquals(true, $this->ini->sectionExists('development'));
			$this->assertEquals(true, $this->ini->sectionExists('production'));
			$this->assertEquals(true, $this->ini->sectionExists('env'));
			$this->assertEquals(true, $this->ini->sectionExists('database'));
			$this->assertEquals(true, $this->ini->sectionExists('database.production'));
			$this->assertEquals(false, $this->ini->sectionExists('invalidsection'));

			$this->assertEquals( true, $this->ini->getProperty( 'development', 'php.error_reporting', '' ) === 'on' );
			$this->assertEquals( true, $this->ini->getProperty( 'development', 'php.display_errors',  '' ) === 'on' );
			$this->assertEquals( true, $this->ini->getProperty( 'production',  'php.error_reporting', '' ) === 'off' );
			$this->assertEquals( true, $this->ini->getProperty( 'production',  'php.display_errors',  '' ) === 'off' );
			$this->assertEquals( true, $this->ini->getProperty( 'env',         'path',                '' ) === '/usr/bin::/usr/local/bin' );
			$this->assertEquals( true, $this->ini->getProperty( 'database',    'type',                '' ) === 'mysql' );
			$this->assertEquals( true, $this->ini->getProperty( 'database',    'host',                '' ) === 'localhost' );
			$this->assertEquals( true, $this->ini->getProperty( 'database',    'user',                '' ) === 'root' );
			$this->assertEquals( true, $this->ini->getProperty( 'database',    'password',            '' ) === '12345' );
			$this->assertEquals( true, $this->ini->getProperty( 'database',    'port',                '' ) === '3306' );
			$this->assertEquals( true, $this->ini->getProperty( 'database.production',    'port',     '' ) === '3306' );

			$this->assertEquals( true, $this->ini->{"database.production/port"} === '3306' );
			$this->assertEquals( true, $this->ini->{"database.production/host"} === '127.0.0.1' );

			$this->assertEquals( true, $this->ini->getPropertyBool( 'development', 'php.error_reporting', false ) === true );
			$this->assertEquals( true, $this->ini->getPropertyInt( 'database.production', 'port', 0 ) === 3306 );
		}

	}