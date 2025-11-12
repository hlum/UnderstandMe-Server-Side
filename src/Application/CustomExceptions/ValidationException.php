<?php

namespace Application\CustomExceptions;

class ValidationException extends AppException {
    public function getStatusCode(): int {
        return 422;
    }
}

 