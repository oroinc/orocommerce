<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListChangeTriggerRepository;

/**
 * @dbIsolation
 */
class PriceListChangeTriggerRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListChangeTrigger'
        ]);
    }

    public function testGetPriceListChangeTriggersIterator()
    {
        $iterator = $this->getRepository()->getPriceListChangeTriggersIterator();
        $allChanges = $this->getRepository()->findAll();
        $this->assertInstanceOf('Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator', $iterator);
        $this->assertCount(count($allChanges), $iterator);
    }

    public function testRemoveAll()
    {
        $repository = $this->getRepository();
        $this->assertGreaterThan(1, count($repository->findAll()));
        $repository->removeAll();

        $this->assertCount(0, $repository->findBy([]));
    }

    /**
     * @return PriceListChangeTriggerRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceListChangeTrigger');
    }
}
