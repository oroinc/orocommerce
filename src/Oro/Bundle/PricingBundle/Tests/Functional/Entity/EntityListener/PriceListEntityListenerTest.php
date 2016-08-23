<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListChangeTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;

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
        $this->cleanQueueMessageTraces();
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $em->remove($priceList);

        $this->assertEquals(
            [
                [
                    'topic' => Topics::REBUILD_PRICE_LISTS,
                    'message' => [
                        PriceListChangeTrigger::WEBSITE => null,
                        PriceListChangeTrigger::ACCOUNT => null,
                        PriceListChangeTrigger::ACCOUNT_GROUP => null,
                        PriceListChangeTrigger::FORCE => true,
                    ],
                    'priority' => 'oro.message_queue.client.normal_message_priority',
                ],
            ],
            $this->getMessageProducer()->getTraces()
        );
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
