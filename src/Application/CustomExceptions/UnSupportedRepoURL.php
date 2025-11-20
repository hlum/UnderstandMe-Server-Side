<?php
namespace Application\CustomExceptions;

class UnSupportedRepoURL extends AppException {
    public function getStatusCode(): int {
        return 422;
    }
    
    public function getErrorType(): string {
        return 'unsupported_repo_url';
    }
}