# ErrorEmail plugin for CakePHP 3.x
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.txt)
[![Build Status](https://api.travis-ci.org/ebrigham1/cakephp-error-email.png?branch=master)](https://travis-ci.org/ebrigham1/cakephp-error-email)
[![Coverage Status](https://img.shields.io/codecov/c/github/ebrigham1/cakephp-error-email/master.svg)](https://codecov.io/github/ebrigham1/cakephp-error-email?branch=master)

The ErrorEmail plugin is designed to enhance CakePHP's error handling system by adding the ability to conditionally email the dev team when errors or exceptions are thrown by your application with useful debugging information such as:
* Exception/Error Url
* Exception/Error Class
* Exception/Error Message
* Exception/Error Code
* File and Line Number
* Stack Trace

## Table of Contents
* [Installation](#installation)
* [Configuration](#configuration)
* [Basic Usage](#basic-usage)
* [Advanced Usage](#advanced-usage)
	* [Overriding Views](#overriding-views)
	* [Extending/Overriding Core Functions](#extendingoverriding-core-functions)
		* [Advanced Installation](#advanced-installation)
		* [Adding Arbitrary Logic to Skip Emails](#adding-arbitrary-logic-to-skip-emails)
		* [Adding Arbitrary Logic to Throttle Emails](#adding-arbitrary-logic-to-throttle-emails)
		* [Overriding Emailing Functionality](#overriding-emailing-functionality)
* [Bugs and Feedback](#bugs-and-feedback)
* [License](#license)

## Installation
You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

Run the following command
```sh
composer require ebrigham1/cakephp-error-email
 ```
You can then load the plugin using the shell command:
```sh
bin/cake plugin load -b ErrorEmail
```
Or you can manually add the loading statement in the **config/boostrap.php** file of your application:

```php
Plugin::load('ErrorEmail', ['bootstrap' => true]);
```

In your **config/Bootstrap.php** replace:
```php
use Cake\Error\ErrorHandler;
```
With:
```php
use ErrorEmail\Error\ErrorHandler;
```

In your **src/Application.php** replace:
```php
use Cake\Error\Middleware\ErrorHandlerMiddleware;
```
With:
```php
use ErrorEmail\Middleware\ErrorHandlerMiddleware;
```

## Configuration
Default configuration:
```php
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
'Cache' => [
    '_error_email_' => [
        'className' => 'File',
        'prefix' => 'error_email_',
        'path' => CACHE . 'error_emails/',
        'duration' => '+5 minutes'
    ],
],
```
This configuration is automatically merged with your application specific configuartion preferentially using any keys you define.

* email (bool) - Enable or disable emailing of errors/exceptions
* emailDeliveryProfile (string) - The email delivery profile (defined in config/app.php under the Email key) to use "default" is the default
* skipEmail (array) - An array of exception/error classes that should never be emailed even if they are thrown Ex: [Cake\Network\Exception\NotFoundException::class, Cake\Network\Exception\InvalidCsrfTokenException::class]
* throttle (bool) - Enable or disable throttling of error/exception emails. Throttling is only performed if its been determined the exact same exception/error has already been emailed by checking the cache. Errors/Exceptions are determined to be unique by exception/error class + exception/error message + exception/error code
* throttleCache (string) - The cache configuration to use for throttling emails, the default is using the file cache driver with a 5 minute duration
* skipThrottle (array) - An array of exception/error classes that should never be throttled even if they are thrown more than once within the normal throttling window Ex: ['App\Exception\FullfillmentException']
* toEmailAddress (string) - The email address to send these error/exception emails to, typically the dev team
* fromEmailAddress (string) - The email address these emails should be sent from ex: noreply@yoursite.com
* environment (string) - Optional, with the default template this will be placed in both the subject and the body of the email so its easy to identify what environment the email was sent from Ex: local/staging/production. 
* siteName (string) - Optional, with the default template this will be placed in both the subject and the body of the email so its easy to identify what site the email was sent from.

**Note:** the skipLog key from Error in your **config/app.php** file is also used. Exception/Error classes that are in that list will not be emailed out as it is assumed if they aren't important enough to even log they shouldn't be important enough to receive an email about.

**Important:** If email => true you must provide a valid email delivery profile to the emailDeliveryProfile config key. Typically the default will work fine unless you've renamed your application's default email delivery profile. If your email delivery profile doesn't define a to address and a from address you must also define the toEmailAddress and fromEmailAddress config values. If throttle => true then throttleCache must also be a valid cache configuration. The default should work fine as long as you don't redefine throttleCache in your config.

A configuration exception will be thrown if the config is detected to be invalid with an explination of what is incorrect.

## Basic Usage

Typically you define these keys in your **config/app.php** file:
```php
'ErrorEmail' => [
    'email' => true,
    'skipEmail' => [],
    'throttle' => true,
    'skipThrottle' => [],
    'toEmailAddress' => 'devteam@yoursite.com',
    'fromEmailAddress' => 'noreply@yoursite.com',
    'environment' => production,
    'siteName' => yoursite.com
]
```
With this configuration you would get emails whenever any error or exception happened on your site with detailed debugging information in the email. If say you had an error on a popular page that many users were hitting that error would only be sent to you once every 5 minutes for the duration of the error being in existence. If a different error was thrown as well you would get that error right away the first time but then again it would be throttled to a maximum of once every 5 minutes.

If you found that you were receiving a lot of emails for exceptions/errors that you can not do anything about for instance Cake\Network\Exception\NotFoundException you can simply add it to the skipEmail config and you will no longer be bothered with those exceptions.

If you want to throttle emails in general to avoid spamming your team, but you have some exceptions that you must always receive an email about then you can use the skipThrottle list. For instance maybe a customer has paid for something on your site, but you were unable to fulfill their purchase after they paid because it requires an API call to a service that was temporarilly down. Then you can add the exception you throw in that instance to the skip throttle list. This will result in all exceptions asside from the exceptions you define in the skipThrottle list being throttled to only email once per every 5 minutes while your FullfillmentException will email you every single time it happens.

## Advanced Usage
### Overriding Views
The default plugin email templates can be overridden by creating your own template files at:
* **src/Template/Plugin/ErrorEmail/Email/html/error.ctp**
* **src/Template/Plugin/ErrorEmail/Email/text/error.ctp**
* **src/Template/Plugin/ErrorEmail/Email/html/exception.ctp**
* **src/Template/Plugin/ErrorEmail/Email/text/exception.ctp**

### Extending/Overriding Core Functions
In order to extend/override core functionality of this plugin you will have to create your own classes which extend this plugin's classes.

#### Advanced Installation
Create **src/Traits/EmailThrowableTrait.php**:
```php
<?php
namespace App\Traits;

trait EmailThrowableTrait
{
}
```
Create **src/Error/ErrorHandler.php**:
```php
<?php
namespace App\Error;

use App\Traits\EmailThrowableTrait;
use ErrorEmail\Error\ErrorHandler as ErrorEmailErrorHandler;

class ErrorHandler extends ErrorEmailErrorHandler
{
    use EmailThrowableTrait;
}
```
Create **src/Middleware/ErrorHandlerMiddleware.php**
```php
<?php
namespace App\Middleware;

use App\Traits\EmailThrowableTrait;
use ErrorEmail\Middleware\ErrorHandlerMiddleware as ErrorEmailErrorHandlerMiddleware;

class ErrorHandlerMiddleware extends ErrorEmailErrorHandlerMiddleware
{
    use EmailThrowableTrait;
}
```
Now that you have your own classes you will need to update your application to use them.

In your **config/Bootstrap.php** replace:
```php
use Cake\Error\ErrorHandler; // Or use ErrorEmail\Error\ErrorHandler;
```
With:
```php
use App\Error\ErrorHandler;
```
In your **src/Application.php** replace:
```php
use Cake\Error\Middleware\ErrorHandlerMiddleware; // Or use ErrorEmail\Middleware\ErrorHandlerMiddleware;
```
With:
```php
use App\Middleware\ErrorHandlerMiddleware;
```

#### Adding Arbitrary Logic to Skip Emails
In your **src/Traits/EmailThrowableTrait.php** add this function:
```php
protected function _appSpecificSkipEmail($throwable)
{
    // Add any logic here to skip emailing throwables that requires more complicated checking
    // than instanceof class provided by plugin config, return true to skip emailing, false to not skip emailing
}
```

#### Adding Arbitrary Logic to Throttle Emails
In your **src/Traits/EmailThrowableTrait.php** add this function:
```php
protected function _appSpecificSkipThrottle($throwable)
{
    // Add any logic here to skip throttling throwables that requires more complicated checking
    // than instanceof class provided by plugin config, return true to skip throttling, false to not skip throttling
}
```

#### Overriding Emailing Functionality
In your **src/Traits/EmailThrowableTrait.php** add this function:
```php
protected function _setupEmail(Cake\Mailer\Email $email, $throwable)
{
   // Add logic here to pick the email template, layout,
   // set, the to address, from address, viewVars, ect.
   // Make sure to return your email object at the end of the function
   // so the plugin can send the email.
   return $email;
}
```

## Bugs and Feedback
http://github.com/ebrigham1/cakephp-error-email/issues

## License
Copyright (c) 2017 Ethan Brigham

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
