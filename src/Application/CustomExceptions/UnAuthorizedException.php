<?php

namespace Application\CustomExceptions;

class UnAuthorizedException extends AppException {
    public function getStatusCode(): int {
        return 401;
    }
    
    public function getErrorType(): string {
        return 'auth_error';
    }
}