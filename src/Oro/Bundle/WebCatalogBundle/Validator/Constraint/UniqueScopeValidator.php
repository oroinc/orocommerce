<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that content variant scopes are unique within a collection.
 *
 * This validator enforces the {@see UniqueScope} constraint by checking that no two non-default content variants
 * share the same scope. It iterates through the collection of content variants and tracks which scopes have been used,
 * adding a validation violation when a duplicate scope is detected.
 */
class UniqueScopeValidator extends ConstraintValidator
{
    /**
     * @param UniqueScope $constraint
     *
     */
    #[\Override]
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
