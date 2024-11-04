<?php

namespace Oro\Bundle\VisibilityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityScopeProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Loads CustomerGroupProductVisibility entity from the database by its ID.
 */
class LoadCustomerGroupProductVisibility extends AbstractLoadVisibility
{
    private VisibilityScopeProvider $visibilityScopeProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AclHelper $aclHelper,
        WebsiteManager $websiteManager,
        VisibilityScopeProvider $visibilityScopeProvider
    ) {
        parent::__construct($doctrineHelper, $aclHelper, $websiteManager);
        $this->visibilityScopeProvider = $visibilityScopeProvider;
    }

    #[\Override]
    protected function getVisibilityEntityClass(): string
    {
        return CustomerGroupProductVisibility::class;
    }

    #[\Override]
    protected function getVisibilityAssociationName(): string
    {
        return 'product';
    }

    #[\Override]
    protected function getScopeId(array $visibilityId): ?int
    {
        return $this->visibilityScopeProvider->findCustomerGroupProductVisibilityScopeId(
            $this->getReference(CustomerGroup::class, $this->getId($visibilityId, 'scope.customerGroup.id')),
            $this->getWebsite($visibilityId)
        );
    }
}
