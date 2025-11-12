<?php

namespace Application\CustomExceptions;

use Exception;

abstract class AppException extends Exception {
    abstract public function getStatusCode(): int;
}
 