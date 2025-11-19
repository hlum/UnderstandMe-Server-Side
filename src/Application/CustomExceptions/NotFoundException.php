<?php

namespace Application\CustomExceptions;

class NotFoundException extends AppException {
    public function getStatusCode(): int {
        return 404;
    }
    
    public function getErrorType(): string {
        return 'not_found_error';
    }
}


 