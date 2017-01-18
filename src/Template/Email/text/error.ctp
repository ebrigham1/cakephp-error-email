A fatal error has been thrown<?php if ($site): ?> on <?= $site ?><?php endif; ?><?php if ($environment): ?> (<?= $environment ?>)<?php endif; ?>

<?php if ($environment): ?>Environment: <?= $environment ?><?php endif; ?>
Error Url: <?= $this->Url->build($this->request->here, true) ?>
Error Class: <?= get_class($error) ?>
Error Message: <?= $error->getMessage() ?>
Error Code: <?= $error->getCode() ?>

In <?= $error->getFile() ?> on line <?= $error->getLine() ?>

Stack Trace:
<?= $error->getTraceAsString() ?>
