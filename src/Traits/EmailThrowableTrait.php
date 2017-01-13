<?php
namespace ErrorEmail\Traits;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Error\FatalErrorException;
use Cake\Mailer\Email;
use Error;
use ErrorEmail\Exception\ConfigurationException;
use Exception;

/**
 * Trait to hold functionality to email throwables to the dev team
 *
 * Once you use this trait just call $this->_emailThrowable($throwable)
 * to handle emailing throwables.
 */
trait EmailThrowableTrait
{
    /**
     * Handles emailing the throwable
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    public function emailThrowable($throwable)
    {
        // Check if we should skip emailing for any reason
        if ($this->_skipEmail($throwable)) {
            return false;
        }
        // If we made it here its time to to email the error
        $email = $this->_getMailer();
        $email = $this->_setupEmail($email, $throwable);
        $email->send();

        return true;
    }

    /**
     * Get instance of the mailer
     *
     * @return Cake\Mailer\Email
     */
    protected function _getMailer()
    {
        return new Email(Configure::read('ErrorEmail.emailDeliveryProfile'));
    }

    /**
     * Setup the email including what template to use, the subject, what variables to assign
     * who the email is to and from.
     *
     * Override this function to provide more advanced functionalty
     * such as using different email templates per app exception/error type or sending to different addresses
     * per exception/error type
     *
     * @param Cake\Mailer\Email $email Mailer instance
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return Cake\Mailer\Email
     */
    protected function _setupEmail(Email $email, $throwable)
    {
        // Switch template and variables assigned per throwable class to customize feedback
        // Can also potentially change to and from adresses to send it to different teams to handle
        switch (true) {
            // Send a different template for fatal errors
            // Error catches php7 fatal errors FatalErrorException catches php5 fatal errors
            case $throwable instanceof Error || $throwable instanceof FatalErrorException:
                $email->template('ErrorEmail.error', 'ErrorEmail.default')
                    ->subject($this->_setupSubjectWithSiteAndEnv('A fatal error has been thrown'))
                    ->viewVars([
                        'error' => $throwable,
                        'environment' => Configure::read('ErrorEmail.environment'),
                        'site' => Configure::read('ErrorEmail.siteName')
                    ]);
                break;
            // If its an exception use the default email
            case $throwable instanceof Exception:
                // Break omitted intentionally
            default:
                $email->template('ErrorEmail.exception', 'ErrorEmail.default')
                    ->subject($this->_setupSubjectWithSiteAndEnv('An exception has been thrown'))
                    ->viewVars([
                        'exception' => $throwable,
                        'environment' => Configure::read('ErrorEmail.environment'),
                        'site' => Configure::read('ErrorEmail.siteName')
                    ]);
                break;
        }
        $email->emailFormat('both');
        // Use toEmailAddress if we have it
        if (Configure::read('ErrorEmail.toEmailAddress')) {
            $email->to(Configure::read('ErrorEmail.toEmailAddress'));
        }
        // Use fromEmailAddress if we have it
        if (Configure::read('ErrorEmail.fromEmailAddress')) {
            $email->from(Configure::read('ErrorEmail.fromEmailAddress'));
        }

        return $email;
    }

    /**
     * Setup the subject of the email with site and environment information
     * if available
     *
     * @param string $subject The email subject
     * @return string
     */
    protected function _setupSubjectWithSiteAndEnv($subject)
    {
        // If site is conigured add it to the subject
        if (Configure::read('ErrorEmail.siteName')) {
            $subject .= ' on ' . Configure::read('ErrorEmail.siteName');
        }
        // If environment is configured add it to the subject
        if (Configure::read('ErrorEmail.environment')) {
            $subject .= ' (' . Configure::read('ErrorEmail.environment') . ')';
        }

        return $subject;
    }

    /**
     * Check if we should skip emailing the given throwable
     * if its in the skipLog list, the skipEmail list or if it
     * needs to be throttled
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance.
     * @return bool
     */
    protected function _skipEmail($throwable)
    {
        switch (true) {
            // If config says don't email
            case empty(Configure::read('ErrorEmail.email')):
                // Break omitted intentionally
            // If we are misconfigured
            case $throwable instanceof ConfigurationException:
                // Break omitted intentionally
            // If the throwable is in any of the skip lists we check
            case $this->_inSkipEmailLists($throwable):
                // Break omitted intentionally
            // If the throwable should be skipped for any other reason
            case $this->_appSpecificSkipEmail($throwable):
                // Break omitted intentionally
            // Check if we should rate limit the throwable to prevent spam
            case $this->_throttle($throwable):
                return true;
            default:
                // If we made it here we shouldn't skip emailing
                return false;
        }
    }

    /**
     * Override this function to give your app the ability to
     * skip emailing based on more than just the throwable class
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance.
     * @return bool
     */
    protected function _appSpecificSkipEmail($throwable)
    {
        // Add any logic here to skip throwables that requires more complicated checking
        // than instanceof class provided by _inSkipList
        return false;
    }

    /**
     * Check if we should rate limit the throwable. This is done by determining if we've
     * already seen the throwable in the last x minutes determined by the rateLimit config variable.
     * Throwables are determined to be unique by looking at throwable class + throwable message + throwable code
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance.
     * @return bool true if email should be skipped due to throttling, false if it shouldn't
     */
    protected function _throttle($throwable)
    {
        switch (true) {
            // Check the config first to see if we should even try to throttle
            case empty(Configure::read('ErrorEmail.throttle')):
                // Break omitted intentionally
            // Check the throttle skip list to see if we should skip throttling
            case $this->_inSkipList('ErrorEmail.skipThrottle', $throwable):
                // Break omitted intentionally
            // If the throwable should skip throttling for any other reason
            case $this->_appSpecificSkipThrottle($throwable):
                return false;
            default:
                // This throwable shouldn't skip throttling if we made it this far so check the cache to see if we need to throttle.
                // The cache key is a composite of the throwable class, message, and code
                $cacheKey = preg_replace("/[^A-Za-z0-9]/", '', get_class($throwable) . $throwable->getMessage() . $throwable->getCode());
                if (Cache::read($cacheKey, Configure::read('ErrorEmail.throttleCache')) !== false) {
                    return true;
                }
                // The throwable wasn't in the cache add it to the cache now
                Cache::add($cacheKey, true, Configure::read('ErrorEmail.throttleCache'));
                // Since it wasn't in the cache don't throttle it
                return false;
        }
    }

    /**
     * Override this function to give your app the ability to
     * skip throttling based on more than just the throwable class
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance.
     * @return bool true if we shouldn't try to throttle this throwable, false if we should
     */
    protected function _appSpecificSkipThrottle($throwable)
    {
        // Add any logic here to skip throwables that requires more complicated checking
        // than instanceof class provided by _inSkipList
        return false;
    }

    /**
     * Check if the throwable is in any skip email lists.
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance.
     * @return bool
     */
    protected function _inSkipEmailLists($throwable)
    {
        // If it should skip the log it should also skip emailing
        if ($this->_inSkipList('Error.skipLog', $throwable)) {
            return true;
        }
        // If its in the skipEmail list don't bother emailing
        if ($this->_inSkipList('ErrorEmail.skipEmail', $throwable)) {
            return true;
        }
        // If we made it here the throwable isn't in the skip lists so return false
        return false;
    }

    /**
     * Check if the given throwable is in the skip list given by $listName
     *
     * @param string $listName Name of the skip list to check.
     * @param \Throwable (php5 \Exception) $throwable Throwable inastance.
     * @return bool
     */
    protected function _inSkipList($listName, $throwable)
    {
        // Check to make sure we don't have an empty list
        if (!empty(Configure::read($listName))) {
            // Parse the list one by one and check instanceof
            foreach ((array)Configure::read($listName) as $class) {
                // If we find an instanceof they are in the skip list
                if ($throwable instanceof $class) {
                    return true;
                }
            }
        }
        // If we made it all the way here they aren't in the skip list
        return false;
    }
}
