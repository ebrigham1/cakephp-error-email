There has been a fatal error<?php if ($site): ?> on <?= $site ?><?php endif; ?>

<?php if ($environment): ?>Environment: <?= $environment ?><?php endif; ?>
Error Url: <?= $this->Url->build($this->request->here, true) ?>
Error Class: <?= get_class($error) ?>
Error Message: <?= $error->getMessage() ?>
Error Code: <?= $error->getCode() ?>

In <?= $error->getFile() ?> on line <?= $error->getLine() ?>

Stack Trace:
<?= $error->getTraceAsString() ?>
