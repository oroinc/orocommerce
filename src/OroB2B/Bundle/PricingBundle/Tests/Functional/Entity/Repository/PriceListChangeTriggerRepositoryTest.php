<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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

    public function testFindBuildAllForceTrigger()
    {
        $trigger = $this->getRepository()->findBuildAllForceTrigger();
        $this->assertEmpty($trigger->getWebsite());
        $this->assertEmpty($trigger->getAccountGroup());
        $this->assertEmpty($trigger->getAccount());
        $this->assertTrue($trigger->isForce());
    }

    public function testGetPriceListChangeTriggersIterator()
    {
        $iterator = $this->getRepository()->getPriceListChangeTriggersIterator();
        $allChanges = $this->getRepository()->findAll();
        $this->assertInstanceOf('Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator', $iterator);
        $this->assertCount(count($allChanges), $iterator);
    }

    public function testDeleteAll()
    {
        $this->assertNotEmpty($this->getRepository()->findAll());
        $this->getRepository()->deleteAll();
        $this->assertEmpty($this->getRepository()->findAll());
    }

    /**
     * @return PriceListChangeTriggerRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceListChangeTrigger');
    }
}
