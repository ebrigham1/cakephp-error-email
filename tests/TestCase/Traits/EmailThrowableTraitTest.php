<?php
namespace ErrorEmail\TestCase\Error;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Error\FatalErrorException;
use Cake\Mailer\Email;
use Cake\TestSuite\TestCase;
use ErrorEmail\Traits\EmailThrowableTrait;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * EmailThrowableTrait Test
 *
 * @coversDefaultClass EmailThrowableTrait
 */
class EmailThrowableTraitTest extends TestCase
{
    /**
     * @var Email throwable mock object
     */
    protected $emailThrowableTraitMock;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        // Set up the email throwable trait mock object
        $this->emailThrowableTraitMock = $this->getMockForTrait(
            EmailThrowableTrait::class,
            [],
            '',
            true,
            true,
            true,
            [
                '_getMailer',
            ]
        );
    }

    public function tearDown()
    {
        parent::tearDown();
        // Clean up after we're done
        Cache::clear(false, '_error_email_');
    }

    /**
     * Tests the emailError method with email configured to false
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailOff()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', false);
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return false
        $this->assertFalse($return);
    }

    /**
     * Tests the emailError method with exception in skipLog list
     *
     * @return void
     */
    public function testEmailThrowableConfigInSkipLogList()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('Error.skipLog', [Exception::class]);
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return false
        $this->assertFalse($return);
    }

    /**
     * Tests the emailError method with exception in skipEmail list
     *
     * @return void
     */
    public function testEmailThrowableConfigInSkipEmailList()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.skipEmail', [Exception::class]);
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return false
        $this->assertFalse($return);
    }

    /**
     * Tests the emailError method when throttling is off
     *
     * @return void
     */
    public function testEmailThrowableConfigThrottleOff()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        $mailerMock->expects($this->exactly(2))
            ->method('template')
            ->will($this->returnSelf());
        $mailerMock->expects($this->exactly(2))
            ->method('subject')
            ->will($this->returnSelf());
        // We should see email sent twice
        $mailerMock->expects($this->exactly(2))
            ->method('send');
        $this->emailThrowableTraitMock->expects($this->exactly(2))
            ->method('_getMailer')
            ->will($this->returnValue($mailerMock));
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return true
        $this->assertTrue($return);
        // Call it again to test if it will be throttled
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return true
        $this->assertTrue($return);
    }

    /**
     * Tests the emailError method when throttling is off
     *
     * @return void
     */
    public function testEmailThrowableConfigThrottleOn()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.throttle', true);
        Configure::write('ErrorEmail.throttleCache', '_error_email_');
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        $mailerMock->expects($this->once())
            ->method('template')
            ->will($this->returnSelf());
        $mailerMock->expects($this->once())
            ->method('subject')
            ->will($this->returnSelf());
        // We should see email sent once
        $mailerMock->expects($this->once())
            ->method('send');
        $this->emailThrowableTraitMock->expects($this->once())
            ->method('_getMailer')
            ->will($this->returnValue($mailerMock));
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return true
        $this->assertTrue($return);
        // Call it again to test if it will be throttled
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return true
        $this->assertFalse($return);
    }

    /**
     * Tests the emailError method when throttling is off
     *
     * @return void
     */
    public function testEmailThrowableConfigInSkipThrottleList()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.throttle', true);
        Configure::write('ErrorEmail.throttleCache', '_error_email_');
        Configure::write('ErrorEmail.skipThrottle', [Exception::class]);
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        $mailerMock->expects($this->exactly(2))
            ->method('template')
            ->will($this->returnSelf());
        $mailerMock->expects($this->exactly(2))
            ->method('subject')
            ->will($this->returnSelf());
        // We should see email sent twice
        $mailerMock->expects($this->exactly(2))
            ->method('send');
        $this->emailThrowableTraitMock->expects($this->exactly(2))
            ->method('_getMailer')
            ->will($this->returnValue($mailerMock));
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return true
        $this->assertTrue($return);
        // Call it again to test if it will be throttled
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return true
        $this->assertTrue($return);
    }

    /**
     * Tests the emailError method when with subject injection of env and site
     *
     * @return void
     */
    public function testEmailThrowableConfigWithEnvironmentAndSite()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.throttle', false);
        Configure::write('ErrorEmail.environment', 'local');
        Configure::write('ErrorEmail.siteName', 'site');
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        $mailerMock->expects($this->once())
            ->method('template')
            ->will($this->returnSelf());
        $mailerMock->expects($this->once())
            ->method('subject')
            ->with('An exception has been thrown on site (local)')
            ->will($this->returnSelf());
        $mailerMock->expects($this->once())
            ->method('viewVars')
            ->with([
                'exception' => $exception,
                'environment' => 'local',
                'site' => 'site'
            ]);
        // We should see email sent once
        $mailerMock->expects($this->once())
            ->method('send');
        $this->emailThrowableTraitMock->expects($this->once())
            ->method('_getMailer')
            ->will($this->returnValue($mailerMock));
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return true
        $this->assertTrue($return);
    }

    /**
     * Tests the emailError method when with to and from injection from config
     *
     * @return void
     */
    public function testEmailThrowableConfigWithToAndFrom()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.throttle', false);
        Configure::write('ErrorEmail.toEmailAddress', 'to@localhost');
        Configure::write('ErrorEmail.fromEmailAddress', 'from@localhost');
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        $mailerMock->expects($this->once())
            ->method('template')
            ->will($this->returnSelf());
        $mailerMock->expects($this->once())
            ->method('subject')
            ->will($this->returnSelf());
        $mailerMock->expects($this->once())
            ->method('to')
            ->with('to@localhost');
        $mailerMock->expects($this->once())
            ->method('from')
            ->with('from@localhost');
        // We should see email sent once
        $mailerMock->expects($this->once())
            ->method('send');
        $this->emailThrowableTraitMock->expects($this->once())
            ->method('_getMailer')
            ->will($this->returnValue($mailerMock));
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return true
        $this->assertTrue($return);
    }

    /**
     * Tests the emailError method when there is a fatal error
     *
     * @return void
     */
    public function testEmailThrowableConfigWithFatal()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new FatalErrorException('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        $mailerMock->expects($this->once())
            ->method('template')
            ->with('ErrorEmail.error', 'ErrorEmail.default')
            ->will($this->returnSelf());
        $mailerMock->expects($this->once())
            ->method('subject')
            ->will($this->returnSelf());
        // We should see email sent once
        $mailerMock->expects($this->once())
            ->method('send');
        $this->emailThrowableTraitMock->expects($this->once())
            ->method('_getMailer')
            ->will($this->returnValue($mailerMock));
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return true
        $this->assertTrue($return);
    }
}
