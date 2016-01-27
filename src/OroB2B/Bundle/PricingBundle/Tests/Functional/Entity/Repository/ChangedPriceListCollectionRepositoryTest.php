<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ChangedPriceListCollectionRepository;

/**
 * @dbIsolation
 */
class ChangedPriceListCollectionRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadChangedPriceListCollection'
        ]);
    }

    public function testGetCollectionChangesIterator()
    {
        $iterator = $this->getRepository()->getCollectionChangesIterator();
        $this->assertSame($iterator->count(), 5);
    }

    /**
     * @return ChangedPriceListCollectionRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:ChangedPriceListCollection');
    }
}
