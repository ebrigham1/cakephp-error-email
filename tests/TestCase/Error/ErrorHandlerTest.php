<?php
namespace ErrorEmail\TestCase\Error;

use Cake\Error\PHP7ErrorException;
use Cake\TestSuite\TestCase;
use ErrorEmail\Error\ErrorHandler;
use ErrorEmail\Exception\DeprecatedException;
use ErrorEmail\Exception\NoticeException;
use ErrorEmail\Exception\StrictException;
use ErrorEmail\Exception\WarningException;
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
            ->setMethods(['emailThrowable', '_callParentHandleException', '_callParentHandleError'])
            ->getMock();
    }

    /**
     * Tests the handleError with error_reporting 0
     *
     * @return void
     */
    public function testHandleErrorNoReporting()
    {
        $errorReporting = error_reporting();
        error_reporting(0);
        $code = E_PARSE;
        $description = "Parse Error";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        // We expect email throwable not to be called
        $this->errorHandlerMock->expects($this->never())
            ->method('emailThrowable');
        // We expect the parent not to be called
        $this->errorHandlerMock->expects($this->never())
            ->method('_callParentHandleError');
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of false
        $this->assertFalse($return);
        error_reporting($errorReporting);
    }

    /**
     * Tests the handleError with an E_PARSE error
     *
     * @return void
     */
    public function testHandleErrorE_PARSE()
    {
        $code = E_PARSE;
        $description = "Parse Error";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        // We expect to not try and email the throwable since its handled by handleException
        $this->errorHandlerMock->expects($this->never())
            ->method('emailThrowable');
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with an E_ERROR error
     *
     * @return void
     */
    public function testHandleErrorE_ERROR()
    {
        $code = E_ERROR;
        $description = "Error";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        // We expect to not try and email the throwable since its handled by handleException
        $this->errorHandlerMock->expects($this->never())
            ->method('emailThrowable');
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with an E_CORE_ERROR error
     *
     * @return void
     */
    public function testHandleErrorE_CORE_ERROR()
    {
        $code = E_CORE_ERROR;
        $description = "Core Error";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        // We expect to not try and email the throwable since its handled by handleException
        $this->errorHandlerMock->expects($this->never())
            ->method('emailThrowable');
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with a E_COMPILE_ERROR error
     *
     * @return void
     */
    public function testHandleErrorE_COMPILE_ERROR()
    {
        $code = E_COMPILE_ERROR;
        $description = "Compile Error";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        // We expect to not try and email the throwable since its handled by handleException
        $this->errorHandlerMock->expects($this->never())
            ->method('emailThrowable');
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with a E_USER_ERROR error
     *
     * @return void
     */
    public function testHandleErrorE_USER_ERROR()
    {
        $code = E_USER_ERROR;
        $description = "User Error";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        // We expect to not try and email the throwable since its handled by handleException
        $this->errorHandlerMock->expects($this->never())
            ->method('emailThrowable');
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with a E_WARNING error
     *
     * @return void
     */
    public function testHandleErrorE_WARNING()
    {
        $code = E_WARNING;
        $description = "Warning";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        $expectedException = new WarningException($description, $code, $file, $line);
        // We expect to try and email the throwable
        $this->errorHandlerMock->expects($this->once())
            ->method('emailThrowable')
            ->with($this->equalTo($expectedException));
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with a E_USER_WARNING error
     *
     * @return void
     */
    public function testHandleErrorE_USER_WARNING()
    {
        $code = E_USER_WARNING;
        $description = "User Warning";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        $expectedException = new WarningException($description, $code, $file, $line);
        // We expect to try and email the throwable
        $this->errorHandlerMock->expects($this->once())
            ->method('emailThrowable')
            ->with($this->equalTo($expectedException));
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with a E_COMPILE_WARNING error
     *
     * @return void
     */
    public function testHandleErrorE_COMPILE_WARNING()
    {
        $code = E_COMPILE_WARNING;
        $description = "Compile Warning";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        $expectedException = new WarningException($description, $code, $file, $line);
        // We expect to try and email the throwable
        $this->errorHandlerMock->expects($this->once())
            ->method('emailThrowable')
            ->with($this->equalTo($expectedException));
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with a E_RECOVERABLE_ERROR error
     *
     * @return void
     */
    public function testHandleErrorE_RECOVERABLE_ERROR()
    {
        $code = E_RECOVERABLE_ERROR;
        $description = "Recoverable Error";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        $expectedException = new WarningException($description, $code, $file, $line);
        // We expect to try and email the throwable
        $this->errorHandlerMock->expects($this->once())
            ->method('emailThrowable')
            ->with($this->equalTo($expectedException));
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with a E_NOTICE error
     *
     * @return void
     */
    public function testHandleErrorE_NOTICE()
    {
        $code = E_NOTICE;
        $description = "Notice";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        $expectedException = new NoticeException($description, $code, $file, $line);
        // We expect to try and email the throwable
        $this->errorHandlerMock->expects($this->once())
            ->method('emailThrowable')
            ->with($this->equalTo($expectedException));
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with a E_USER_NOTICE error
     *
     * @return void
     */
    public function testHandleErrorE_USER_NOTICE()
    {
        $code = E_USER_NOTICE;
        $description = "User Notice";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        $expectedException = new NoticeException($description, $code, $file, $line);
        // We expect to try and email the throwable
        $this->errorHandlerMock->expects($this->once())
            ->method('emailThrowable')
            ->with($this->equalTo($expectedException));
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with a E_STRICT error
     *
     * @return void
     */
    public function testHandleErrorE_STRICT()
    {
        $code = E_STRICT;
        $description = "Strict";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        $expectedException = new StrictException($description, $code, $file, $line);
        // We expect to try and email the throwable
        $this->errorHandlerMock->expects($this->once())
            ->method('emailThrowable')
            ->with($this->equalTo($expectedException));
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with a E_DEPRECATED error
     *
     * @return void
     */
    public function testHandleErrorE_DEPRECATED()
    {
        $code = E_DEPRECATED;
        $description = "Deprecated";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        $expectedException = new DeprecatedException($description, $code, $file, $line);
        // We expect to try and email the throwable
        $this->errorHandlerMock->expects($this->once())
            ->method('emailThrowable')
            ->with($this->equalTo($expectedException));
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
    }

    /**
     * Tests the handleError with a E_USER_DEPRECATED error
     *
     * @return void
     */
    public function testHandleErrorE_USER_DEPRECATED()
    {
        $code = E_USER_DEPRECATED;
        $description = "User Deprecated";
        $file = "SomeFile.php";
        $line = 1;
        $context = "context";
        $expectedException = new DeprecatedException($description, $code, $file, $line);
        // We expect to try and email the throwable
        $this->errorHandlerMock->expects($this->once())
            ->method('emailThrowable')
            ->with($this->equalTo($expectedException));
        // We expect the parent to handle the rest
        $this->errorHandlerMock->expects($this->once())
            ->method('_callParentHandleError')
            ->with($code, $description, $file, $line, $context)
            ->will($this->returnValue(true));
        $return = $this->errorHandlerMock->handleError($code, $description, $file, $line, $context);
        // Should return the return of _callParentHandleError
        $this->assertTrue($return);
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
            ->method('_callParentHandleException')
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
            ->method('_callParentHandleException')
            ->with($exceptionMock);
        $return = $this->errorHandlerMock->handleException($exceptionMock);
        // Should return null
        $this->assertNull($return);
    }
}
