<?php
namespace ErrorEmail\Error;

use Cake\Error\ErrorHandler as CakeErrorHandler;
use Cake\Error\PHP7ErrorException;
use ErrorEmail\Exception\DeprecatedException;
use ErrorEmail\Exception\NoticeException;
use ErrorEmail\Exception\StrictException;
use ErrorEmail\Exception\WarningException;
use ErrorEmail\Traits\EmailThrowableTrait;
use Exception;

/**
 * Extend cakes error handler to add email error functionality
 */
class ErrorHandler extends CakeErrorHandler
{
    use EmailThrowableTrait;

    /**
     * Set as the default error handler by CakePHP.
     *
     * Use config/error.php to customize or replace this error handler.
     * This function will use Debugger to display errors when debug > 0. And
     * will log errors to Log, when debug == 0.
     *
     * You can use the 'errorLevel' option to set what type of errors will be handled.
     * Stack traces for errors can be enabled with the 'trace' option.
     *
     * @param int $code Code of error
     * @param string $description Error description
     * @param string|null $file File on which error occurred
     * @param int|null $line Line that triggered the error
     * @param array|null $context Context
     * @return bool True if error was handled
     */
    public function handleError($code, $description, $file = null, $line = null, $context = null)
    {
        if (error_reporting() === 0) {
            return false;
        }
        list($error, $log) = static::mapErrorCode($code);
        // Handle emailing warning, notice, strict, and deprecated error is already handled
        // by handleException since cakephp wraps fatal level errors
        switch ($error) {
            case 'Warning':
                $this->emailThrowable(new WarningException($description, $code, $file, $line));
                break;
            case 'Notice':
                $this->emailThrowable(new NoticeException($description, $code, $file, $line));
                break;
            case 'Strict':
                $this->emailThrowable(new StrictException($description, $code, $file, $line));
                break;
            case 'Deprecated':
                $this->emailThrowable(new DeprecatedException($description, $code, $file, $line));
                break;
            default:
                // Do nothing if it isn't one of the handled error types
                break;
        }
        // Call parent handleError functionality
        return $this->_callParentHandleError($code, $description, $file, $line, $context);
    }

    /**
     * Handle uncaught exceptions.
     *
     * Uses a template method provided by subclasses to display errors in an
     * environment appropriate way.
     *
     * @param \Exception $exception Exception instance.
     * @return void
     * @throws \Exception When renderer class not found
     * @see http://php.net/manual/en/function.set-exception-handler.php
     */
    public function handleException(Exception $exception)
    {
        // Add emailing throwable functionality
        $this->emailThrowable($this->_unwrapException($exception));
        // Use parent functionality
        $this->_callParentHandleException($exception);
    }

    /**
     * Unwrap the exception for class if its a PHP7ErrorException
     *
     * @param \Exception $exception Exception instance.
     * @return \Throwable (php5 Exception) A throwable or child class
     */
    protected function _unwrapException(Exception $exception)
    {
        // PHP7ErrorException must be unwrapped to get the actual \Error class
        if ($exception instanceof PHP7ErrorException) {
            return $exception->getError();
        } else {
            return $exception;
        }
    }

    /**
     * Wrap parent handleError functionality so we can isolate our class for testing
     *
     * @param int $code Code of error
     * @param string $description Error description
     * @param string|null $file File on which error occurred
     * @param int|null $line Line that triggered the error
     * @param array|null $context Context
     * @return bool True if error was handled
     */
    protected function _callParentHandleError($code, $description, $file = null, $line = null, $context = null)
    {
        return parent::handleError($code, $description, $file, $line, $context);
    }

    /**
     * Wrap parent handleException functionality so we can isolate our class for testing
     *
     * @param \Exception $exception Exception instance
     * @return void
     * @throws \Exception when renderer class not found
     * @see http://php.net/manual/en/function.set-exception-handler.php
     */
    protected function _callParentHandleException(Exception $exception)
    {
        parent::handleException($exception);
    }
}
