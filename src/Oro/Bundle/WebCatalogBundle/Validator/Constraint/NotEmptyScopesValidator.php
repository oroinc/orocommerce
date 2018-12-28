<?php

namespace Oro\Bundle\WebCatalogBundle\Validator\Constraint;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\ScopeWebCatalogProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate that non-default content variants contains non-default scopes.
 */
class NotEmptyScopesValidator extends ConstraintValidator
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @param ScopeManager $scopeManager
     */
    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param NotEmptyScopes $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Collection || $value->isEmpty()) {
            return;
        }

        /** @var ContentVariant $firstVariant */
        $firstVariant = $value->first();
        $defaultScope = $this->scopeManager->findOrCreate(
            'web_content',
            [ScopeWebCatalogProvider::WEB_CATALOG => $firstVariant->getNode()->getWebCatalog()]
        );
        /** @var ContentVariant $contentVariant */
        foreach ($value as $index => $contentVariant) {
            if ($contentVariant->isDefault()) {
                continue;
            }

            $contentVariant->getScopes()->removeElement($defaultScope);
            if ($contentVariant->getScopes()->isEmpty()) {
                $path = sprintf('[%d].scopes', $index);
                $this->context->buildViolation($constraint->message)
                    ->atPath($path)
                    ->addViolation();
            }
        }
    }
}
