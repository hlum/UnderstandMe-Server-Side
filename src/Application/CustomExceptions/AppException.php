<?php

namespace App\Application\CustomExceptions;

use Exception;

abstract class AppException extends Exception {
    abstract public function getStatusCode(): int;
}


class NotFoundException extends AppException {
    public function getStatusCode(): int {
        return 404;
    }
}


class ValidationException extends AppException {
    public function getStatusCode(): int {
        return 422;
    }
}


class UnAuthorizedException extends AppException {
    public function getStatusCode(): int {
        return 401;
    }
}


 