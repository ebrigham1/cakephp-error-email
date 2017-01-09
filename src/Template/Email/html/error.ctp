There has been a fatal error<?php if ($site): ?> on <?= $site ?><?php endif; ?><br><br>

<?php if ($environment): ?><strong>Environment:</strong> <?= $environment ?><br><?php endif; ?>
<strong>Error Url:</strong> <?= $this->Url->build($this->request->here, true) ?><br>
<strong>Error Class:</strong> <?= get_class($error) ?><br>
<strong>Error Message:</strong> <?= $error->getMessage() ?><br>
<strong>Error Code:</strong> <?= $error->getCode() ?><br><br>

In <?= $error->getFile() ?> on line <?= $error->getLine() ?><br><br>

<strong>Stack Trace:</strong><br>
<?= nl2br($error->getTraceAsString()) ?>
