<?php

namespace Tests\Support\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class ThrowingMonologHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        throw new \RuntimeException('Simulated log sink failure.');
    }
}
