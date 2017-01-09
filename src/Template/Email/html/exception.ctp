There has been an exception thrown<?php if ($site): ?> on <?= $site ?><?php endif; ?><br><br> 

<?php if ($environment): ?><strong>Environment:</strong> <?= $environment ?><br><?php endif; ?>
<strong>Exception Url:</strong> <?= $this->Url->build($this->request->here, true) ?><br>
<strong>Exception Class:</strong> <?= get_class($exception) ?><br>
<strong>Exception Message:</strong> <?= $exception->getMessage() ?><br>
<strong>Exception Code:</strong> <?= $exception->getCode() ?><br><br>

In <?= $exception->getFile() ?> on line <?= $exception->getLine() ?><br><br>

<strong>Stack Trace:</strong><br>
<?= nl2br($exception->getTraceAsString()) ?>
