<?php

namespace Application\CustomExceptions;

class UnsupportedFileTypeException extends AppException {
    public function getStatusCode(): int {
        return 400;
    }
    
    public function getErrorType(): string {
        return 'unsupported_file_type';
    }
}
