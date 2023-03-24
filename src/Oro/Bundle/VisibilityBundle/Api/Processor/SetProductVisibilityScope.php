<?php

namespace Oro\Bundle\VisibilityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityScopeProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sets Scope entity to ProductVisibility entity based on submitted data.
 */
class SetProductVisibilityScope extends AbstractSetVisibilityScope
{
    private VisibilityScopeProvider $visibilityProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        WebsiteManager $websiteManager,
        TranslatorInterface $translator,
        VisibilityScopeProvider $visibilityProvider
    ) {
        parent::__construct($doctrineHelper, $websiteManager, $translator);
        $this->visibilityProvider = $visibilityProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function getScope(CustomizeFormDataContext $context, WebsiteInterface $website): Scope
    {
        return $this->visibilityProvider->getProductVisibilityScope($website);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExistingVisibilitySearchCriteria(VisibilityInterface $entity, Scope $scope): array
    {
        return [
            'product' => $entity->getProduct(),
            'scope'   => $scope,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function isExistingVisibilityCheckApplicable(VisibilityInterface $entity): bool
    {
        return null !== $entity->getProduct()?->getId();
    }
}
