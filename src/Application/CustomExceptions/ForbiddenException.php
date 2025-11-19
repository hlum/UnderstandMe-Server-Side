<?php

namespace Application\CustomExceptions;

class ForbiddenException extends AppException {
    public function getStatusCode(): int {
        return 403;
    }
    
    public function getErrorType(): string {
        return 'forbidden_error';
    }
}
