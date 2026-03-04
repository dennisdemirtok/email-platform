<?php

namespace App\ThirdParty\LoggerHandlers;

use CodeIgniter\Log\Handlers\HandlerInterface;

class StdoutHandler implements HandlerInterface {
    public function handle(array $logEntry): bool {
        $message = $logEntry['message'];
        file_put_contents('php://stdout', $message . PHP_EOL);
        return true;
    }
}
