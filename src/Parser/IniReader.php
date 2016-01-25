<?php

	namespace browserfs\string\Parser;

	class IniReader extends \browserfs\string\Parser {

		protected static $tokens = [
			'COMMENT'     => '/^#([^\\n]+)/',
			'WHITE_SPACE' => '/^[\\s]+/',
			'['           => '/^\\[/',
			']'           => '/^\\]/',
			'='           => '/^=/',
			'IDENTIFIER'  => '/^[\\$a-zA-Z_]([a-zA-Z0-9\\-\\$_]+)?((\\.[\\$a-zA-Z_]([a-zA-Z0-9\\-\\$_]+)?)+)?/',
			'VALUE'       => '/^[^\\n]+/',
			'EXTENDS'     => '/^extends[\\s]+/'
		];

		protected $sections = [];

		public function __construct( $fileName ) {

			if ( !is_string( $fileName ) || !strlen( $fileName ) ) {
				throw new \browserfs\Exception( 'Invalid argument. Expected a non-empty string!' );
			}

			if ( !file_exists( $fileName ) ) {
				throw new \browserfs\Exception( 'File ' . $fileName . ' not found!' );
			}

			if ( !is_readable( $fileName ) ) {
				throw new \browserfs\Exception( 'File ' . $fileName . ' is not readable!' );
			}

			$buffer = file_get_contents( $fileName );

			if ( !is_string( $buffer ) ) {
				throw new \browserfs\Exception( 'Could not read file ' . $fileName . '!' );
			}

			parent::__construct( $buffer );

			$this->setFileName( $fileName );

			$this->parse();

		}

		protected function read( $tokenName ) {
			if ( !is_string( $tokenName ) || !array_key_exists( $tokenName, self::$tokens ) ) {
				throw new \browserfs\Exception('Invalid token ' . json_encode( $tokenName ) );
			}

			$matches = $this->canReadExpression( self::$tokens[ $tokenName ] );

			if ( false !== $matches ) {
				$this->consume( strlen( $matches[0] ) );
				return true;
			} else {
				return false;
			}
		}

		protected function readString( $tokenName, $returnIndex = 0 ) {
			if ( !is_string( $tokenName ) || !array_key_exists( $tokenName, self::$tokens ) ) {
				throw new \browserfs\Exception( 'Invalid token ' . json_encode( $tokenName ) );
			}

			if ( !is_int( $returnIndex ) ) {
				throw new \browserfs\Exception( 'Invalid argument $returnIndex: expected int!' );
			}

			$matches = $this->canReadExpression( self::$tokens[ $tokenName ] );

			if ( false !== $matches ) {

				$this->consume( strlen( $matches[0] ) );

				return isset( $matches[ $returnIndex ] )
					? $matches[ $returnIndex ]
					: '';
			} else {
				return false;
			}
		}

		protected function readWhiteSpaceOrComment() {
			$once = false;

			do {

				$next = $this->read( 'WHITE_SPACE' );

				if ( !$next ) {
					$next = $this->read( 'COMMENT' );
				}

				if ( $next ) {
					$once = true;
				}

			} while ( $next );

			return $once;

		}

		public function sectionExists( $sectionName ) {
			return ( is_string( $sectionName ) && ( strlen( $sectionName ) > 0 ) )
				? ( array_key_exists( $sectionName, $this->sections ) ? true : false )
				: false;
		}

		protected function cloneSection( $sourceSection, $targetSection ) {
			
			$this->createSection( $targetSection );

			if ( isset( $this->sections[ $sourceSection ] ) ) {
				foreach ( $this->sections[ $sourceSection ] as $iniEntry ) {
					$this->sections[ $targetSection ][] = $iniEntry;
				}
			}

		}

		protected function createSection( $sectionName, $extendsSection = null ) {

			if ( !is_string( $sectionName ) ) {
				throw new \browserfs\Exception('Invalid argument $sectionName: string expected!');
			}

			if ( strlen( $sectionName ) == 0 ) {
				throw new \browserfs\Exception('Invalid argument $sectionName: non-empty string expected!');
			}

			if ( null != $extendsSection ) {

				$this->cloneSection( $extendsSection, $sectionName );

			} else {

				$this->sections[ $sectionName ] = [];

			}

		}

		public function addSectionProperty( $sectionName, $propertyName, $propertyValue, $allowDuplicatePropertyNames = false ) {

			if ( !is_string( $sectionName ) ) {
				throw new \browserfs\Exception('Invalid argument $sectionName: string expected!' );
			}

			if ( strlen( $sectionName ) == 0 ) {
				throw new \browserfs\Exception('Invalid argument $sectionName: non-empty string expected!' );
			}

			if ( !is_string( $propertyName ) ) {
				throw new \browserfs\Exception('Invalid argument $propertyName: string expected' );
			}

			if ( strlen( $propertyName ) == 0 ) {
				throw new \browserfs\Exception('Invalid argument $propertyName: non-empty string expected!' );
			}

			if ( !$this->sectionExists( $sectionName ) ) {
				$this->createSection( $sectionName );
			}

			if ( $allowDuplicatePropertyNames ) {

				$this->sections[ $sectionName ][] = [
					'name' => $propertyName,
					'value' => $propertyValue
				];

			} else {

				$found = false;

				foreach ( $this->sections[ $sectionName ] as &$property ) {
					if ( $property['name'] == $propertyName ) {
						$property['value'] = $propertyValue;
						$found = true;
						break;
					}
				}

				if ( !$found ) {
					$this->sections[ $sectionName ][] = [
						'name' => $propertyName,
						'value' => $propertyValue
					];
				}

			}
		}

		public function getProperty( $sectionName, $propertyName, $defaultValue = '' ) {
			if ( $this->sectionExists( $sectionName ) ) {
				foreach ( $this->sections[ $sectionName ] as $property ) {
					if ( $property['name'] == $propertyName ) {
						return $property['value'];
					}
				}
				return $defaultValue;
			} else {
				return $defaultValue;
			}
		}

		public function getPropertyMulti( $sectionName, $propertyName, $defaultValue = [] ) {
			
			if ( $this->sectionExists( $sectionName ) ) {
				
				$result = [];
				$found  = false;

				foreach ( $this->sections[ $sectionName ] as $property ) {
					if ( $property['name'] = $propertyName ) {
						$found = true;
						$result[] = $defaultValue;
					}
				}

				return $found
					? $result
					: $defaultValue;

			} else {

				return $defaultValue;
			
			}
		
		}

		protected function readSection() {

			$this->readWhiteSpaceOrComment();

			$sectionName = $this->readString('IDENTIFIER');

			if ( $sectionName === false ) {
				throw new \browserfs\Exception('Unexpected token "' . $this->nextToken() . '", expected <section_name>, at line ' . $this->line() . ' in file "' . $this->file() . '"' );
			}

			$this->readWhiteSpaceOrComment();

			$extends = null;

			if ( $this->read('EXTENDS') ) {

				$this->readWhiteSpaceOrComment();

				$extends = $this->readString('IDENTIFIER');

				if ( $extends === false ) {
					throw new \browserfs\Exception('Unexpected token "' . $this->nextToken() . '", expected <extends_section_name>, at line ' . $this->line() . ' in file "' . $this->file() . '"' );
				}

				$this->readWhiteSpaceOrComment();

			}

			if ( !$this->read(']') ) {
				throw new \browserfs\Exception('Unexpected token "' . $this->nextToken() . '", expected "]", at line ' . $this->line() . ' in file "' . $this->file() . '"' );
			}

			return [
				'name' => $sectionName,
				'extends' => $extends
			];


		}

		protected function readProperty() {

			$this->readWhiteSpaceOrComment();

			$propertyName = $this->readString('IDENTIFIER');

			if ( $propertyName === false ) {
				throw new \browserfs\Exception('Unexpected token "' . $this->nextToken() . '", expected <identifier>, at line ' . $this->line() . ' in file "' . $this->file() . '"' );
			}

			$this->readWhiteSpaceOrComment();

			if ( !$this->read('=') ) {
				throw new \browserfs\Exception('Unexpected token "' . $this->nextToken() . '", expected "=", at line ' . $this->line() . ' in file "' . $this->file() . '"' );
			}

			$this->readWhiteSpaceOrComment();

			$propertyValue = $this->readString('VALUE');

			if ( $propertyValue === false ) {
				throw new \browserfs\Exception('Unexpected token "' . $this->nextToken() . '", expected <value>, at line ' . $this->line() . ' in file "' . $this->file() . '"' );
			}

			$propertyValue = trim( $propertyValue );

			$result = '';
			$done = false;

			for ( $i=0, $len = strlen( $propertyValue); $i<$len; $i++ ) {

				switch ( $propertyValue[$i] ) {
					case ';':
						$done = true;
						break;
					case '#':
						$done = true;
						break;
					case '\\':
						// yes, we allow escaping in property values

						if ( $i < $len - 1 ) {

							$i++;

							switch ( $propertyValue[$i] ) {
								case 'n':
									$result .= "\n";
									break;
								case 't':
									$result .= "\t";
									break;
								case 'r':
									$result .= "\r";
									break;
								default:
									$result .= $propertyValue[$i];
									break;
							}

						} else {
							$result .= '\\';
						}

						break;
					default;
						$result .= $propertyValue[$i];
						break;
				}

				if ( $done ) {
					break;
				}
			}

			return [
				'name' => $propertyName,
				'value' => trim($result)
			];

		}

		protected function parse() {

			$currentSection = 'main';

			while ( !$this->eof() ) {

				switch ( true ) {

					case $this->readWhiteSpaceOrComment():
						break;

					case $this->read( '[' ):

						$result = $this->readSection();

						$currentSection = $result[ 'name' ];

						$this->createSection( $currentSection, $result['extends'] );

						break;

					default:

						// read section property
						$result = $this->readProperty();

						$this->addSectionProperty( $currentSection, $result['name'], $result['value'] );

						break;
				}

			}

		}

		public static function create( $iniFileName ) {
			return new self( $iniFileName );
		}

		public function getPropertyInt( $sectionName, $propertyName, $defaultValue ) {
			if ( !is_int( $defaultValue ) ) {
				throw new \browserfs\Exception('Invalid argument $defaultValue: int expected!');
			}

			$result = $this->getProperty( $sectionName, $propertyName, null );

			if ( $result === null ) {
				return $defaultValue;
			} else {
				if ( preg_match( '/^(0|(\\-)?[1-9]([0-9]+)?)$/', $result ) ) {
					return (int)$result;
				} else {
					return $defaultValue;
				}
			}
		}

		public function getPropertyBool( $sectionName, $propertyName, $defaultValue ) {
			if ( !is_bool( $defaultValue ) ) {
				throw new \browserfs\Exception('Invalid argument $defaultValue: boolean expected!');
			}

			$result = $this->getProperty( $sectionName, $propertyName, null );

			if ( $result === null ) {
				return $defaultValue;
			} else {
				switch ( true ) {
					case preg_match( '/^(1|y|yes|on)$/i', $result ) ? true : false:
						return true;
						break;
					case preg_match( '/^(0|n|no|off)$/i', $result ) ? true : false:
						return false;
						break;
					default:
						return $defaultValue;
						break;
				}
			}
		}

		/**
		 * Getters are available in format "section_name/property_name"
		 *
		 * e.g.: $this->{"section/property"}
		 */
		public function __get( $propertyName ) {
			switch ( true ) {
				case preg_match( '/^([^\\/]+)\\/(.*)$/', $propertyName, $matches ) ? true : false:
					return $this->getProperty( $matches[1], $matches[2], '' );
					break;
				default:
					return '';
					break;
			}
		}

	}