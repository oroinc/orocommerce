<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueScopeValidator extends ConstraintValidator
{
    /**
     * @param UniqueScope $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Collection || $value->isEmpty()) {
            return;
        }

        $usedScopes = new ArrayCollection();
        foreach ($value as $index => $contentVariant) {
            if (!$contentVariant->isDefault()) {
                foreach ($contentVariant->getScopes() as $scopeIdx => $variantScope) {
                    if ($usedScopes->contains($variantScope)) {
                        $path = sprintf('[%d].scopes[%d]', $index, $scopeIdx);
                        $this->context->buildViolation($constraint->message)
                            ->atPath($path)
                            ->addViolation();
                    } else {
                        $usedScopes->add($variantScope);
                    }
                }
            }
        }
    }
}
