<?php
namespace ErrorEmail\TestCase\Error;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Error\FatalErrorException;
use Cake\Mailer\Email;
use Cake\TestSuite\TestCase;
use Cake\View\ViewBuilder;
use ErrorEmail\Exception\ConfigurationException;
use ErrorEmail\Exception\DeprecatedException;
use ErrorEmail\Exception\NoticeException;
use ErrorEmail\Exception\StrictException;
use ErrorEmail\Exception\WarningException;
use ErrorEmail\Traits\EmailThrowableTrait;
use Exception;

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
        Configure::write('ErrorEmail.emailLevels', ['exception']);
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
        Configure::write('ErrorEmail.emailLevels', ['exception']);
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
        Configure::write('ErrorEmail.emailLevels', ['exception']);
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
        Configure::write('ErrorEmail.emailLevels', ['exception']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(4))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->exactly(2))
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->exactly(2))
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->exactly(2))
            ->method('setSubject')
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
        Configure::write('ErrorEmail.emailLevels', ['exception']);
        Configure::write('ErrorEmail.throttle', true);
        Configure::write('ErrorEmail.throttleCache', '_error_email_');
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(2))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->once())
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->once())
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->once())
            ->method('setSubject')
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
        Configure::write('ErrorEmail.emailLevels', ['exception']);
        Configure::write('ErrorEmail.throttle', true);
        Configure::write('ErrorEmail.throttleCache', '_error_email_');
        Configure::write('ErrorEmail.skipThrottle', [Exception::class]);
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(4))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->exactly(2))
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->exactly(2))
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->exactly(2))
            ->method('setSubject')
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
        Configure::write('ErrorEmail.emailLevels', ['exception']);
        Configure::write('ErrorEmail.throttle', false);
        Configure::write('ErrorEmail.environment', 'local');
        Configure::write('ErrorEmail.siteName', 'site');
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(2))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->once())
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->once())
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->once())
            ->method('setSubject')
            ->with('An exception has been thrown on site (local)')
            ->will($this->returnSelf());
        $mailerMock->expects($this->once())
            ->method('setViewVars')
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
        Configure::write('ErrorEmail.emailLevels', ['exception']);
        Configure::write('ErrorEmail.throttle', false);
        Configure::write('ErrorEmail.toEmailAddress', 'to@localhost');
        Configure::write('ErrorEmail.fromEmailAddress', 'from@localhost');
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(2))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->once())
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->once())
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->once())
            ->method('setSubject')
            ->will($this->returnSelf());
        $mailerMock->expects($this->once())
            ->method('setTo')
            ->with('to@localhost');
        $mailerMock->expects($this->once())
            ->method('setFrom')
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
        Configure::write('ErrorEmail.emailLevels', ['error']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new FatalErrorException('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(2))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->once())
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->once())
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->once())
            ->method('setSubject')
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

    /**
     * Tests the emailError method when we are misconfigured
     *
     * @return void
     */
    public function testEmailThrowableMisconfigured()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['exception', 'error']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new ConfigurationException('Misconfigured');
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return false
        $this->assertFalse($return);
    }

    /**
     * Tests the emailError method when the email level exception is on and an exception is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelExceptionOn()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['exception']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(2))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->once())
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->once())
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->once())
            ->method('setSubject')
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

    /**
     * Tests the emailError method when the email level exception is off and an exception is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelExceptionOff()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['error', 'warning', 'notice', 'strict', 'deprecated']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return false
        $this->assertFalse($return);
    }

    /**
     * Tests the emailError method when the email level error is on and an error level exception is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelErrorOn()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['error']);
        // Setup objects used in test
        $exception = new FatalErrorException('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(2))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->once())
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->once())
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->once())
            ->method('setSubject')
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

    /**
     * Tests the emailError method when the email level error is off and an error is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelErrorOff()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['exception', 'warning', 'notice', 'strict', 'deprecated']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new FatalErrorException('Test Exception');
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return false
        $this->assertFalse($return);
    }

    /**
     * Tests the emailError method when the email level warning is on and a warning is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelWarningOn()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['warning']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new WarningException('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(2))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->once())
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->once())
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->once())
            ->method('setSubject')
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

    /**
     * Tests the emailError method when the email level warning is off and an warning is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelWarningOff()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['exception', 'error', 'notice', 'strict', 'deprecated']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new WarningException('Test Exception');
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return false
        $this->assertFalse($return);
    }

    /**
     * Tests the emailError method when the email level notice is on and a notice is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelNoticeOn()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['notice']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new NoticeException('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(2))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->once())
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->once())
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->once())
            ->method('setSubject')
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

    /**
     * Tests the emailError method when the email level notice is off and an notice is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelNoticeOff()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['exception', 'error', 'warning', 'strict', 'deprecated']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new NoticeException('Test Exception');
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return false
        $this->assertFalse($return);
    }

    /**
     * Tests the emailError method when the email level strict is on and a strict is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelStrictOn()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['strict']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new StrictException('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(2))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->once())
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->once())
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->once())
            ->method('setSubject')
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

    /**
     * Tests the emailError method when the email level strict is off and an strict is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelStrictOff()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['exception', 'error', 'warning', 'notice', 'deprecated']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new StrictException('Test Exception');
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return false
        $this->assertFalse($return);
    }

    /**
     * Tests the emailError method when the email level deprecated is on and a deprecated is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelDeprecatedOn()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['deprecated']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new DeprecatedException('Test Exception');
        // Mock mailer
        $mailerMock = $this->createMock(Email::class);
        // Mock view builder
        $viewBuilderMock = $this->createMock(ViewBuilder::class);
        $mailerMock->expects($this->exactly(2))
            ->method('viewBuilder')
            ->willReturn($viewBuilderMock);
        $viewBuilderMock->expects($this->once())
            ->method('setTemplate')
            ->willReturn($mailerMock);
        $viewBuilderMock->expects($this->once())
            ->method('setLayout')
            ->willReturn($mailerMock);
        $mailerMock->expects($this->once())
            ->method('setSubject')
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

    /**
     * Tests the emailError method when the email level deprecated is off and an deprecated is thrown
     *
     * @return void
     */
    public function testEmailThrowableConfigEmailLevelDeprecatedOff()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.emailLevels', ['exception', 'error', 'warning', 'notice', 'strict']);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new DeprecatedException('Test Exception');
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return false
        $this->assertFalse($return);
    }

    /**
     * Tests the emailError method when there are no emailLevels
     *
     * @return void
     */
    public function testEmailThrowableConfigNoEmailLevels()
    {
        // Setup the config
        Configure::write('ErrorEmail.email', true);
        Configure::write('ErrorEmail.throttle', false);
        // Setup objects used in test
        $exception = new Exception('Test Exception');
        $return = $this->emailThrowableTraitMock->emailThrowable($exception);
        // Should return false
        $this->assertFalse($return);
    }
}
