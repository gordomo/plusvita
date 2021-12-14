<?php

namespace App\Validator;

use App\Repository\EvolucionRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class EvolutionTypeValidator extends ConstraintValidator
{
    private $er;
    public function __construct(EvolucionRepository $er) {
        $this->er = $er;
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint \App\Validator\EvolutionType */

        if (null === $value || '' === $value) {
            return;
        }
        if ($value === 'Seleccione una Opción') {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
