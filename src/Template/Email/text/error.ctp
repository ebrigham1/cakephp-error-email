An error has been thrown<?php if ($site): ?> on <?= $site ?><?php endif; ?><?php if ($environment): ?> (<?= $environment ?>)<?php endif; ?>

<?php if ($environment): ?>Environment: <?= $environment ?><?php endif; ?>
Error Url: <?= $this->Url->build($this->request->getAttribute('here'), true) ?>
Error Class: <?= get_class($error) ?>
Error Message: <?= $error->getMessage() ?>
Error Code: <?= $error->getCode() ?>
Client IP: <?= $this->request->clientIp() ?>

In <?= $error->getFile() ?> on line <?= $error->getLine() ?>

Stack Trace:
<?= $error->getTraceAsString() ?>
