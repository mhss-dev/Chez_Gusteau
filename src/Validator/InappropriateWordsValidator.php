<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class InappropriateWordsValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var InappropriateWords $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        //on met d'abord le mot introduit dans le formulaire en minuscule
        //on fait ensuite une boucle pour verifié que le mot intruduit n'est pas dans notre liste
        //si il est dans notre liste, il envoi une violation avec notre message définit dans InappropriateWords.php
        $value = strtolower($value);
        foreach($constraint->listWords as $inappropriateWord){
            if(str_contains($value, $inappropriateWord)){
                $this->context->buildViolation($constraint->message)
                ->setParameter('{{ inappropriateWord }}', $inappropriateWord)
                ->addViolation();
            }
        }
    }
}
