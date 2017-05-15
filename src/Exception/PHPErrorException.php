<?php
namespace ErrorEmail\Exception;

use Cake\Core\Exception\Exception;

/**
 * Base exception for php warnings, notices, strict, and deprecated errors
 */
class PHPErrorException extends Exception
{
    /**
     * Constructor
     *
     * @param string $message Message string.
     * @param int $code Code.
     * @param string|null $file File name.
     * @param int|null $line Line number.
     */
    public function __construct($message, $code = 500, $file = null, $line = null)
    {
        // Use parents constructor
        parent::__construct($message, $code);
        // Also set file and line if we have them
        if ($file) {
            $this->file = $file;
        }
        if ($line) {
            $this->line = $line;
        }
    }
}
