<?php
namespace ErrorEmail\Traits;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Error\FatalErrorException;
use Cake\Mailer\Email;
use Error;
use ErrorEmail\Exception\ConfigurationException;
use ErrorEmail\Exception\DeprecatedException;
use ErrorEmail\Exception\NoticeException;
use ErrorEmail\Exception\StrictException;
use ErrorEmail\Exception\WarningException;
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
            // Send a different template for php errors
            case $this->_isError($throwable):
                // Use the error email template
                $email->setTemplate('ErrorEmail.error')
                    ->setLayout('ErrorEmail.default')
                    ->setSubject($this->_setupSubjectWithSiteAndEnv('An error has been thrown'))
                    ->setViewVars([
                        'error' => $throwable,
                        'environment' => Configure::read('ErrorEmail.environment'),
                        'site' => Configure::read('ErrorEmail.siteName')
                    ]);
                break;
            // If its an exception use the default email
            case $throwable instanceof Exception:
                // Break omitted intentionally
            default:
                // Use the exception email template
                $email->setTemplate('ErrorEmail.exception')
                    ->setLayout('ErrorEmail.default')
                    ->setSubject($this->_setupSubjectWithSiteAndEnv('An exception has been thrown'))
                    ->setViewVars([
                        'exception' => $throwable,
                        'environment' => Configure::read('ErrorEmail.environment'),
                        'site' => Configure::read('ErrorEmail.siteName')
                    ]);
                break;
        }
        $email->setEmailFormat('both');
        // Use toEmailAddress if we have it
        if (Configure::read('ErrorEmail.toEmailAddress')) {
            $email->setTo(Configure::read('ErrorEmail.toEmailAddress'));
        }
        // Use fromEmailAddress if we have it
        if (Configure::read('ErrorEmail.fromEmailAddress')) {
            $email->setFrom(Configure::read('ErrorEmail.fromEmailAddress'));
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
            // If the throwable isn't included in the emailLevels config
            case !$this->_inEmailLevels($throwable):
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
     * @param \Throwable (php5 \Exception) $throwable Throwable instance.
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

    /**
     * Checks if the given throwable represents a php error rather than an exception
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _isError($throwable)
    {
        switch (true) {
            // Php fatal errors
            case $this->_isEmailLevelError($throwable):
                // Break omitted intentionally
            // Php warnings
            case $this->_isEmailLevelWarning($throwable):
                // Break omitted intentionally
            // Php notices
            case $this->_isEmailLevelNotice($throwable):
                // Break omitted intentionally
            // Php strict notices
            case $this->_isEmailLevelStrict($throwable):
                // Break omitted intentionally
            // Php deprecated notices
            case $this->_isEmailLevelDeprecated($throwable):
                // We have an error, return true
                return true;
            default:
                // We don't have an error, return false
                return false;
        }
    }

    /**
     * Check if the given throwable is in the emailLevels config
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _inEmailLevels($throwable)
    {
        // Check to make sure we don't have an empty list
        if (!empty(Configure::read('ErrorEmail.emailLevels'))) {
            switch (true) {
                case $this->_inEmailLevelException($throwable):
                    // Break omitted intentionally
                case $this->_inEmailLevelError($throwable):
                    // Break omitted intentionally
                case $this->_inEmailLevelWarning($throwable):
                    // Break omitted intentionally
                case $this->_inEmailLevelNotice($throwable):
                    // Break omitted intentionally
                case $this->_inEmailLevelStrict($throwable):
                    // Break omitted intentionally
                case $this->_inEmailLevelDeprecated($throwable):
                    return true;
                default:
                    // If we made it here then the throwable isn't in the email levels
                    return false;
            }
        }
        // If we made it all the way here the error isn't in the emailLevels config
        return false;
    }

    /**
     * Check if the throwable belongs to the emailLevels 'exception' key and the key exists
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _inEmailLevelException($throwable)
    {
        // If we have a 'exception' type throwable and the 'exception' value exists on the emailLevels key
        // in the configuration file return true otherwise return false
        if ($this->_isEmailLevelException($throwable) && in_array('exception', (array)Configure::read('ErrorEmail.emailLevels'))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the throwable belongs to the emailLevels 'error' key and the key exists
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _inEmailLevelError($throwable)
    {
        // If we have a 'error' type throwable and the 'error' value exists on the emailLevels key
        // in the configuration file return true otherwise return false
        if ($this->_isEmailLevelError($throwable) && in_array('error', (array)Configure::read('ErrorEmail.emailLevels'))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the throwable belongs to the emailLevels 'warning' key and the key exists
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _inEmailLevelWarning($throwable)
    {
        // If we have a 'warning' type throwable and the 'warning' value exists on the emailLevels key
        // in the configuration file return true otherwise return false
        if ($this->_isEmailLevelWarning($throwable) && in_array('warning', (array)Configure::read('ErrorEmail.emailLevels'))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the throwable belongs to the emailLevels 'notice' key and the key exists
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _inEmailLevelNotice($throwable)
    {
        // If we have a 'notice' type throwable and the 'notice' value exists on the emailLevels key
        // in the configuration file return true otherwise return false
        if ($this->_isEmailLevelNotice($throwable) && in_array('notice', (array)Configure::read('ErrorEmail.emailLevels'))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the throwable belongs to the emailLevels 'strict' key and the key exists
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _inEmailLevelStrict($throwable)
    {
        // If we have a 'strict' type throwable and the 'strict' value exists on the emailLevels key
        // in the configuration file return true otherwise return false
        if ($this->_isEmailLevelStrict($throwable) && in_array('strict', (array)Configure::read('ErrorEmail.emailLevels'))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the throwable belongs to the emailLevels 'deprecated' key and the key exists
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _inEmailLevelDeprecated($throwable)
    {
        // If we have a 'deprecated' type throwable and the 'deprecated' value exists on the emailLevels key
        // in the configuration file return true otherwise return false
        if ($this->_isEmailLevelDeprecated($throwable) && in_array('deprecated', (array)Configure::read('ErrorEmail.emailLevels'))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the throwable belongs to the emailLevels 'exception' key (all exceptions except those
     * that represent php fatal errors, php warnings, php notices, php strict notices, and php deprecated notices)
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _isEmailLevelException($throwable)
    {
        // Any exception that doesn't represent a php error falls under the 'exception' key
        if (!$this->_isError($throwable) && $throwable instanceof Exception) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the throwable belongs to the emailLevels 'error' key (all php fatal errors)
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _isEmailLevelError($throwable)
    {
        // Error catches php7 fatal errors FatalErrorException catches php5 fatal errors
        if ($throwable instanceof Error || $throwable instanceof FatalErrorException) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the throwable belongs to the emailLevels 'warning' key (all php warnings)
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _isEmailLevelWarning($throwable)
    {
        // WarningException catches all php warnings
        if ($throwable instanceof WarningException) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the throwable belongs to the emailLevels 'notice' key (all php notices)
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _isEmailLevelNotice($throwable)
    {
        // NoticeException catches all php notices
        if ($throwable instanceof NoticeException) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the throwable belongs to the emailLevels 'strict' key (all php strict notices)
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _isEmailLevelStrict($throwable)
    {
        // StrictException catches all php strict notices
        if ($throwable instanceof StrictException) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the throwable belongs to the emailLevels 'deprecated' key (all php deprecated notices)
     *
     * @param \Throwable (php5 \Exception) $throwable Throwable instance
     * @return bool
     */
    protected function _isEmailLevelDeprecated($throwable)
    {
        // DeprecatedException catches all php deprecated notices
        if ($throwable instanceof DeprecatedException) {
            return true;
        } else {
            return false;
        }
    }
}
