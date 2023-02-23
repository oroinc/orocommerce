<?php

namespace Oro\Bundle\VisibilityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\VisibilityBundle\Api\VisibilityIdHelper;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Provider\VisibilityScopeProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Loads CustomerProductVisibility entity from the database by its ID.
 */
class LoadCustomerProductVisibility extends AbstractLoadVisibility
{
    private VisibilityScopeProvider $visibilityScopeProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AclHelper $aclHelper,
        WebsiteManager $websiteManager,
        VisibilityIdHelper $visibilityIdHelper,
        VisibilityScopeProvider $visibilityScopeProvider
    ) {
        parent::__construct($doctrineHelper, $aclHelper, $websiteManager, $visibilityIdHelper);
        $this->visibilityScopeProvider = $visibilityScopeProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function getVisibilityEntityClass(): string
    {
        return CustomerProductVisibility::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getVisibilityAssociationName(): string
    {
        return 'product';
    }

    /**
     * {@inheritDoc}
     */
    protected function getScopeId(array $visibilityId): ?int
    {
        return $this->visibilityScopeProvider->findCustomerProductVisibilityScopeId(
            $this->getReference(Customer::class, $this->getId($visibilityId, 'scope.customer.id')),
            $this->getWebsite($visibilityId)
        );
    }
}
