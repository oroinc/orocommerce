<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class PriceListEntityListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
        ]);
    }

    public function testOnCreate()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository('OroB2BPricingBundle:PriceListChangeTrigger');

        $priceList = $em->getRepository('OroB2BPricingBundle:PriceList')->findOneBy([]);
        $em->remove($priceList);
        $em->flush();

        $actual = $repository->findBy(['force' => true]);

        $this->assertCount(1, $actual);
    }
}
