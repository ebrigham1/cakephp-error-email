An exception has been thrown<?php if ($site): ?> on <?= $site ?><?php endif; ?><?php if ($environment): ?> (<?= $environment ?>)<?php endif; ?>

<?php if ($environment): ?><strong>Environment:</strong> <?= $environment ?><?php endif; ?>
Exception Url: <?= $this->Url->build($this->request->getAttribute('here'), true) ?>
Exception Class: <?= get_class($exception) ?>
Exception Message: <?= $exception->getMessage() ?>
Exception Code: <?= $exception->getCode() ?>
Client IP: <?= $this->request->clientIp() ?>

In <?= $exception->getFile() ?> on line <?= $exception->getLine() ?>

Stack Trace:
<?= $exception->getTraceAsString() ?>
