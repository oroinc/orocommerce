<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validate that content node contains only default scopes.
 */
class NodeHasNoRestrictionsValidator extends ConstraintValidator
{
    private const SCOPE_WEBSITE = 'website';

    public function __construct(private ConfigHandler $configHandler)
    {
    }

    /**
     * @param NodeHasNoRestrictions $constraint
     *
     */
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NodeHasNoRestrictions) {
            throw new UnexpectedTypeException($constraint, NodeHasNoRestrictions::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof ContentNode) {
            throw new UnexpectedTypeException($value, ContentNode::class);
        }

        if ($this->isContentNodeHasRestrictions($value)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('contentNode')
                ->addViolation();
        }
    }

    private function isContentNodeHasRestrictions(ContentNode $contentNode): bool
    {
        $scopeEntityName = $this->configHandler->getConfigManager()->getScopeEntityName();

        $scopes = $contentNode->getScopesConsideringParent();
        foreach ($scopes as $scope) {
            if ((
                $scope->getLocalization()
                || $scope->getCustomerGroup()
                || $scope->getCustomer()
            )
                || (
                    self::SCOPE_WEBSITE === $scopeEntityName
                    && $scope->getWebsite()
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
