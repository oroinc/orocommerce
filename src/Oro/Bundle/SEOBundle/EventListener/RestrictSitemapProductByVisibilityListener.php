<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\VisibilityBundle\Visibility\ProductVisibilityTrait;

class RestrictSitemapProductByVisibilityListener
{
    use ProductVisibilityTrait;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * @param RestrictSitemapEntitiesEvent $event
     */
    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        $queryBuilder = $event->getQueryBuilder();
        $website = $event->getWebsite();

        $productVisibilityTerm = $this->getProductVisibilityResolvedTermByWebsite(
            $queryBuilder,
            $website
        );
        $anonymousGroupVisibilityTerm = implode('+', [
            $productVisibilityTerm,
            $this->getCustomerGroupProductVisibilityResolvedTermByWebsite(
                $queryBuilder,
                $this->getAnonymousCustomerGroup(),
                $website
            )
        ]);

        $queryBuilder->andWhere($queryBuilder->expr()->gt($anonymousGroupVisibilityTerm, 0));
    }

    /**
     * @return CustomerGroup|null
     */
    private function getAnonymousCustomerGroup()
    {
        $anonymousGroupId = $this->configManager->get('oro_customer.anonymous_customer_group');

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->doctrineHelper
            ->getEntityRepository(CustomerGroup::class)
            ->find($anonymousGroupId);

        return $customerGroup;
    }
}
