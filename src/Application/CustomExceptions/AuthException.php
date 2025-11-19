<?php

namespace Application\CustomExceptions;

class AuthException extends AppException {
    public function getStatusCode(): int {
        return 401;
    }
    
    public function getErrorType(): string {
        return 'auth_error';
    }
}
