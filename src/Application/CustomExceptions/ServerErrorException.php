<?php

namespace Application\CustomExceptions;

class ServerErrorException extends AppException {
    public function getStatusCode(): int {
        return 500;
    }
    
    public function getErrorType(): string {
        return 'server_error';
    }
}
