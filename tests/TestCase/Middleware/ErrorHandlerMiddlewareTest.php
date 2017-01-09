<?php
namespace ErrorEmail\TestCase\Error;

use Cake\TestSuite\TestCase;
use ErrorEmail\Middleware\ErrorHandlerMiddleware;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * ErrorHandlerMiddleware Test
 *
 * @coversDefaultClass ErrorHandlerMiddleware
 */
class ErrorHandlerMiddlewareTest extends TestCase
{
    /**
     * Tests the handleException method
     *
     * @return void
     */
    public function testHandleException()
    {
        // Setup objects used in test
        $request = $this->createMock(ResponseInterface::class);
        $response = $this->createMock(ServerRequestInterface::class);
        $exception = new Exception('Test Exception');
        $errorHandlerMiddlewareMock = $this->getMockBuilder(ErrorHandlerMiddleware::class)
            ->setMethods(['emailThrowable', '_callParent'])
            ->getMock();
        // We expect to try and email the throwable
        $errorHandlerMiddlewareMock->expects($this->once())
            ->method('emailThrowable')
            ->with($exception);
        // We expect the parent to handle the rest
        $errorHandlerMiddlewareMock->expects($this->once())
            ->method('_callParent')
            ->with($exception, $request, $response)
            ->will($this->returnValue(true));
        $return = $errorHandlerMiddlewareMock->handleException($exception, $request, $response);
        // Should return true since we told the parent call to return true
        $this->assertTrue($return);
    }
}
