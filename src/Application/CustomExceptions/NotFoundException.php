<?php

namespace Application\CustomExceptions;

class NotFoundException extends AppException {
    public function getStatusCode(): int {
        return 404;
    }
}


 