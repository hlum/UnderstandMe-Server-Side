<?php

namespace Application\CustomExceptions;

 class UnAuthorizedException extends AppException {
    public function getStatusCode(): int {
        return 401;
    }
}