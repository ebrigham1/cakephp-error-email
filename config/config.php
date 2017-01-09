<?php
return [
    /**
     * Configure the ErrorEmail service
     *
     * - email (bool) - Enable or disable emailing of errors/exceptions
     *
     * - emailDeliveryProfile (string) - The email delivery profile to use "default"
     *   is the default
     *
     * - skipEmail (array) - An array of classes that should never be emailed
     *   even if they are thrown Ex: ['Cake\Network\Exception\NotFoundException']
     *
     * - throttle (bool) - Enable or disable throttling of errors/exceptions
     *
     * - throttleCache (string) - The cache configuration to use for throttling emails,
     *   default is using the file cache driver with a 5 minute duration
     *
     * - skipThrottle (array) - An array of classes that should never be throttled
     *   even if they are thrown more than once within the normal throttling window
     *   Ex: ['App\Exception\FullfillmentException']
     *
     * - toEmailAddress (string) - The email address to send these error emails to,
     *   typically the dev team for the website
     *
     * - fromEmailAddress (string) - The email address these emails should be sent from
     *
     * - environment (string) - Optional with the default template this will be placed
     *   in both the subject and the body of the email so its easy to tell what environment
     *   the email was sent from.
     *
     * - siteName (string) - Optional with the default template this will be placed
     *   in both the subject and the body of the email so its easy to tell what site
     *   the email came from.
     */
    'ErrorEmail' => [
        'email' => false,
        'emailDeliveryProfile' => 'default',
        'skipEmail' => [],
        'throttle' => false,
        'throttleCache' => '_error_email_',
        'skipThrottle' => [],
        'toEmailAddress' => null,
        'fromEmailAddress' => null,
        'environment' => null,
        'siteName' => null
    ],
    /**
     * Default throttle cache if one isn't provided by the app
     *
     * - _error_email_ (array) - name of the cache config to use for throttling
     *
     * - className (string) - Name of cache engine to use
     *
     * - prefix (string) - Prefix to prepend to cache keys
     *
     * - path (string) - Path where the cache should be stored
     *
     * - duration (string) - Length of time items should be kept for
     */
    'Cache' => [
        '_error_email_' => [
            'className' => 'File',
            'prefix' => 'error_email_',
            'path' => CACHE . 'error_emails/',
            'duration' => '+5 minutes'
        ],
    ],
];
