<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Async\Topics;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;

/**
 * @dbIsolation
 */
class PriceListEntityListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductPrices::class
        ]);
        $this->topic = Topics::CALCULATE_RULE;
    }

    public function testPreRemove()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repository = $em->getRepository('OroB2BPricingBundle:PriceListChangeTrigger');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $em->remove($priceList);
        $em->flush();

        $actual = $repository->findBy(['force' => true]);

        $this->assertCount(1, $actual);
    }

    public function testPreUpdate()
    {
        $this->cleanQueueMessageTraces();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        $priceList->setProductAssignmentRule('product.id > 10');
        $em->persist($priceList);
        $em->flush();

        $traces = $this->getQueueMessageTraces();
        $this->assertCount(1, $traces);
        $this->assertEquals($priceList->getId(), $this->getPriceListIdFromTrace($traces[0]));
    }

    public function testPreUpdateAssignmentNotChanged()
    {
        $this->cleanQueueMessageTraces();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        $priceList->setName('TEST123');
        $em->persist($priceList);
        $em->flush();

        $this->assertEmpty($this->getQueueMessageTraces());
    }
}
