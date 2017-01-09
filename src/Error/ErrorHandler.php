<?php
namespace ErrorEmail\Error;

use Cake\Error\ErrorHandler as CakeErrorHandler;
use Cake\Error\PHP7ErrorException;
use ErrorEmail\Traits\EmailThrowableTrait;
use Exception;

/**
 * Extend cakes error handler to add email error functionality
 */
class ErrorHandler extends CakeErrorHandler
{
    use EmailThrowableTrait;

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
        $this->_callParent($exception);
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
     * Wrap parent functionality so we can isolate our class for testing
     *
     * @param \Exception $exception Exception instance
     * @return void
     * @throws \Exception when renderer class not found
     * @see http://php.net/manual/en/function.set-exception-handler.php
     */
    protected function _callParent(Exception $exception)
    {
        parent::handleException($exception);
    }
}
