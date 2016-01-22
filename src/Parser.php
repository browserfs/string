<?php

	namespace browserfs\string;

	/**
	 * Base class for parsing strings. Implements basic functionality.
	 */
	class Parser {

		protected $parsed   = '';
		protected $remain   = '';
		protected $lineNum  = 1;
		protected $fileName = '<UNKNOWN>';

		/**
		 * @constructor
		 * @param $buffer string
		 */
		public function __construct( $buffer ) {
			
			if ( !is_string( $buffer ) ) {
				throw new \browserfs\runtime\Exception('String expected!');
			}

			$this->remain = $buffer;
		}

		/**
		 * Removes a number of bytes $bytes starting with the beginning of
		 * the buffer, marks those bytes as "read", and advances in buffer.
		 * @return void
		 * @throws \browserfs\runtime\Exception
		 */
		public function consume( $bytes ) {
			
			if ( !is_int( $bytes ) || $bytes < 0 ) {
				throw new \browserfs\runtime\Exception('Invalid argument!');
			}

			$chunk = substr( $this->remain, 0, $bytes );
			$bytes = strlen( $chunk );

			if ( $bytes === 0 ) {
				return;
			}

			for ( $i=0; $i<$bytes; $i++ ) {

				if ( $chunk[$i] == "\r" ) {
					
					if ( ( $i + 1 < $bytes ) && ( $chunk[$i+1] == "\n" ) ) {
						$i++;
					}

					$this->lineNum++;

				} else
				if ( $chunk[$i] == "\n" ) {
					$this->lineNum++;
				}
			}

			$this->parsed .= $chunk;
			$this->remain = substr( $this->remain, $bytes );

		}

		/**
		 * Returns the number of allready parsed bytes
		 * @return int
		 */
		public function tell() {
			return strlen( $this->parsed );
		}

		/**
		 * Returns the length of the remaining string to be parsed
		 * @return int
		 */
		public function available() {
			return strlen( $this->remain );
		}

		/**
		 * Returns true if the cursor of the parser reached at the end of the string
		 * @return boolean
		 */
		public function eof() {
			return $this->remain == '';
		}

		/**
		 * Returns the current line number of the parser
		 * @return int
		 */
		public function line() {
			return $this->lineNum;
		}

		/**
		 * Returns the name of the file.
		 */
		public function file() {
			return $this->fileName;
		}

		/**
		 * Sets the name of the file. This has nothing to do with filesystem,
		 * the name of the file is only needed for generating usefull parsing
		 * exceptions.
		 */
		public function setFileName( $name = null ) {
			$this->fileName = is_string( $name ) && strlen( $name ) > 0
				? $name
				: '<UNKNOWN>';
		}

		/**
		 * Returns true if the string at current position starts with $str
		 * @return boolean
		 */
		public function canReadString( $str ) {
			return is_string( $str )
				? substr( $this->remain, 0, strlen( $str ) ) == $str
				: false;
		}

		/**
		 * Returns string[] representing the matches,
		 * if the string at current position starts with 
		 * regular expression $str
		 * @return string[] | false
		 */
		public function canReadExpression( $str ) {
			return is_string( $str )
				? ( preg_match( $str, $this->remain, $matches )
					? $matches
					: false 
				)
				: false;
		}

		/**
		 * Returns the remaining buffer as string
		 */
		public function __toString() {
			return $this->remain;
		}

		/**
		 * Returns the next string token
		 */
		public function nextToken() {
			if ( $this->eof() ) {
				return 'END_OF_FILE';
			} else {
				if ( preg_match( '/^([\s]+)?([^\s]+)/', $this->remain, $matches ) ) {
					return $matches[0];
				} else {
					return '';
				}
			}
		}
	}