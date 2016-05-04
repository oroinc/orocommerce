<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;

abstract class AbstractCombinedPriceListRelationRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
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
