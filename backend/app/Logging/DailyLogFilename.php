<?php

namespace App\Logging;

use Monolog\Handler\RotatingFileHandler;

class DailyLogFilename
{
    public function __invoke($logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                $handler->setFilenameFormat('{filename}_{date}', 'd_m_Y');
            }
        }
    }
}
