<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;

/**
 * Helper methods for checking Combined Price List connections
 */
class CombinedPriceListRelationHelper implements CombinedPriceListRelationHelperInterface
{
    public const RELATIONS = [
        'cpl2c' => CombinedPriceListToCustomer::class,
        'cpl2cg' => CombinedPriceListToCustomerGroup::class,
        'cpl2w' => CombinedPriceListToWebsite::class
    ];

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    public function isFullChainCpl(CombinedPriceList $cpl): bool
    {
        if ($this->isConfigFullChainCpl($cpl)) {
            return true;
        }

        foreach (self::RELATIONS as $relation) {
            $qb = $this->doctrineHelper->createQueryBuilder($relation, 'r')
                ->select('r.id')
                ->where('r.fullChainPriceList = :cpl')
                ->setMaxResults(1)
                ->setParameter('cpl', $cpl);
            $result = $qb->getQuery()->getOneOrNullResult();
            if ($result) {
                return true;
            }
        }

        return false;
    }

    private function isConfigFullChainCpl(CombinedPriceList $cpl): bool
    {
        $configCplId = $this->configManager->get('oro_pricing.full_combined_price_list');

        return $cpl->getId() && $cpl->getId() == $configCplId;
    }
}
