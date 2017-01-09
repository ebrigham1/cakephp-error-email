<?php
namespace ErrorEmail\TestCase\Error;

use Cake\Error\PHP7ErrorException;
use Cake\TestSuite\TestCase;
use ErrorEmail\Error\ErrorHandler;
use Exception;

/**
 * ErrorHandler Test
 *
 * @coversDefaultClass ErrorHandler
 */
class ErrorHandlerTest extends TestCase
{
    /**
     * @var Error handler mock object
     */
    protected $errorHandlerMock;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->errorHandlerMock = $this->getMockBuilder(ErrorHandler::class)
            ->setMethods(['emailThrowable', '_callParent'])
            ->getMock();
    }

    /**
     * Tests the handleException method
     *
     * @return void
     */
    public function testHandleException()
    {
        $exception = new Exception('Test Exception');
        // We expect to try and email the throwable
        $this->errorHandlerMock->expects($this->once())
            ->method('emailThrowable')
            ->with($exception);
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParent')
            ->with($exception);
        $return = $this->errorHandlerMock->handleException($exception);
        // Should return null
        $this->assertNull($return);
    }

    /**
     * Tests the handleException method when given a PHP7ErrorException
     *
     * @return void
     */
    public function testHandleExceptionPHP7ErrorException()
    {
        // Mock exception so we can control what getError returns and test it
        $exceptionMock = $this->createMock(PHP7ErrorException::class);
        $exceptionMock->expects($this->once())
            ->method('getError')
            ->will($this->returnValue(true));
        // We expect it to try and email the throwable with what PHP7ErrorException returns from getError()
        $this->errorHandlerMock->expects($this->once())
            ->method('emailThrowable')
            ->with(true);
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParent')
            ->with($exceptionMock);
        $return = $this->errorHandlerMock->handleException($exceptionMock);
        // Should return null
        $this->assertNull($return);
    }
}
