<?php
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Mailer\Email;
use Cake\Utility\Hash;
use ErrorEmail\Exception\ConfigurationException;

// Get the configuration engine so we can load our default config file
$engine = new PhpConfig();
// Read our config file
$configValues = $engine->read('ErrorEmail.config');
// Special handling for emailLevels key since hash::merge doesn't handle it correctly
if (Configure::read('ErrorEmail.emailLevels')) {
    unset($configValues['ErrorEmail']['emailLevels']);
}
// Merge our default ErrorEmail config with the apps config ErrorEmail config prefering the apps version
Configure::write(
    'ErrorEmail',
    Hash::merge(
        $configValues['ErrorEmail'],
        Configure::read('ErrorEmail')
    )
);
// If Emailing errors emails is turned on make sure we have the necessary configuration values set
if (Configure::read('ErrorEmail.email')) {
    // Check to make sure emailDeliveryProfile is configured properly
    if (!in_array(Configure::read('ErrorEmail.emailDeliveryProfile'), Email::configured())) {
        throw new ConfigurationException('ErrorEmail plugin misconfigured, please add a valid email delivery profile for key "' . Configure::read('ErrorEmail.emailDeliveryProfile') . '"');
    }
    $email = new Email(Configure::read('ErrorEmail.emailDeliveryProfile'));
    // Check to make sure we have a to address to send the email to
    if (!$email->to() && !Configure::read('ErrorEmail.toEmailAddress')) {
        throw new ConfigurationException('ErrorEmail plugin misconfigured, please add the "ErrorEmail.toEmailAddress" configuration value');
    }
    // Check to make sure we have an address to send the email from
    if (!$email->from() && !Configure::read('ErrorEmail.fromEmailAddress')) {
        throw new ConfigurationException('ErrorEmail plugin misconfigured, please add the "ErrorEmail.fromEmailAddress" configuration value');
    }
}
// If throttling emails is turned on make sure we have the necessary configuration values set
if (Configure::read('ErrorEmail.throttle')) {
    // Use default cache config if app is using default cache config key and hasn't overwrote the config already
    if (Configure::read('ErrorEmail.throttleCache') == key($configValues['Cache']) && !in_array(key($configValues['Cache']), Cache::configured())) {
        Cache::config($configValues['Cache']);
    }
    // Check to make sure the throttleCache is configured properly
    if (!in_array(Configure::read('ErrorEmail.throttleCache'), Cache::configured())) {
        throw new ConfigurationException('ErrorEmail plugin misconfigured, please add a valid cache config for key "' . Configure::read('ErrorEmail.throttleCache') . '"');
    }
}
