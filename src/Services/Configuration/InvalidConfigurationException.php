<?php

namespace App\Services\Configuration;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidConfigurationException extends \Exception
{

    public readonly ConstraintViolationListInterface $validation;

    public function __construct(ConstraintViolationListInterface $validation)
    {
        $this->validation = $validation;
        $joinedErrors = [];
        foreach ($validation as $v) {
            $joinedErrors[] = $v->getPropertyPath().": ".$v->getMessage();
        }
        parent::__construct(sprintf("the following errors where found :\n\t%s", implode("\n\t", $joinedErrors)));
    }

}