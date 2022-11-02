<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractCombinedPriceListRelationRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadCombinedPriceLists::class,
            ]
        );
    }

    /**
     * @param BaseCombinedPriceListRelation[] $combinedPriceListRelations
     * @param CombinedPriceList $combinedPriceList
     * @return null|BaseCombinedPriceListRelation
     */
    protected function getRelationByPriceList($combinedPriceListRelations, CombinedPriceList $combinedPriceList)
    {
        foreach ($combinedPriceListRelations as $combinedPriceListRelation) {
            if ($combinedPriceListRelation->getPriceList()->getId() === $combinedPriceList->getId()) {
                return $combinedPriceListRelation;
            }
        }

        return null;
    }
}
