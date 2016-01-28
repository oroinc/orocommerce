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
        $allChanges = $this->getRepository()->findAll();
        $this->assertInstanceOf('Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator', $iterator);
        $this->assertSame($iterator->count(), count($allChanges));
    }

    /**
     * @return ChangedPriceListCollectionRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:ChangedPriceListCollection');
    }
}
