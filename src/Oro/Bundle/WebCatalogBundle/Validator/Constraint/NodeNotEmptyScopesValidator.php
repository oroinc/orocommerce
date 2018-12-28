<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validate that non-default content variants contains non-default scopes.
 */
class NodeNotEmptyScopesValidator extends ConstraintValidator
{
    /**
     * @param NodeNotEmptyScopes $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof ContentNode) {
            throw new UnexpectedTypeException($value, ContentNode::class);
        }

        if ($value->getScopesConsideringParent()->isEmpty()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('scopes')
                ->addViolation();
        }
    }
}
