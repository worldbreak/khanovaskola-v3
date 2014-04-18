<?php
/**
 * Ensures all the use are in alphabetical order.
 *
 */
class cs_Sniffs_Formatting_UseInAlphabeticalOrderSniff implements PHP_CodeSniffer_Sniff {

/**
 * Processed files
 *
 * @var array
 */
	protected $_processed = array();

/**
 * Returns an array of tokens this test wants to listen for.
 *
 * @return array
 */
	public function register() {
		return array(T_USE);
	}

/**
 * Processes this test, when one of its tokens is encountered.
 *
 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
 * @param integer $stackPtr The position of the current token in the stack passed in $tokens.
 * @return void
 */
	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
		if (isset($this->_processed[$phpcsFile->getFilename()])) {
			return;
		}

		$tokens = $phpcsFile->getTokens();

		$isClosure = $phpcsFile->findPrevious(
			array(T_CLOSURE),
			($stackPtr - 1),
			null,
			false,
			null,
			true
		);
		if ($isClosure) {
			return;
		}

		// Only one USE declaration allowed per statement.
		$next = $phpcsFile->findNext(array(T_COMMA, T_SEMICOLON), ($stackPtr + 1));
		if ($tokens[$next]['code'] === T_COMMA) {
			$error = 'There must be one USE keyword per declaration';
			$phpcsFile->addError($error, $stackPtr, 'MultipleDeclarations');
		}

		$uses = array();

		$next = $stackPtr;
		while (true) {
			$content = '';

			$end = $phpcsFile->findNext(array(T_SEMICOLON, T_OPEN_CURLY_BRACKET), $next);
			$useTokens = array_slice($tokens, $next, $end - $next, true);
			foreach ($useTokens as $index => $token) {
				if ($token['code'] === T_STRING || $token['code'] === T_NS_SEPARATOR) {
					$content .= $token['content'];
				}
			}

			// Check for class scoping on use. Traits should be
			// ordered independently.
			$scope = 0;
			if (!empty($token['conditions'])) {
				$scope = key($token['conditions']);
			}
			$uses[$scope][$content] = $index;

			$next = $phpcsFile->findNext(T_USE, $end);
			if (!$next) {
				break;
			}
		}

		// Prevent multiple uses in the same file from entering
		$this->_processed[$phpcsFile->getFilename()] = true;

		$ordered = TRUE;
		foreach ($uses as $scope => $used) {
			$defined = $sorted = array_keys($used);

			natcasesort($sorted);
			$sorted = array_values($sorted);
			if ($sorted === $defined) {
				continue;
			}

			foreach ($defined as $i => $name) {
				if ($name !== $sorted[$i]) {
					$ordered = FALSE;
				}
			}
		}
		if (!$ordered) {
			$error = 'Usings must be in alphabetical order';
			$phpcsFile->addError($error, $stackPtr, 'UseInAlphabeticalOrder', array());
		}
	}

}
