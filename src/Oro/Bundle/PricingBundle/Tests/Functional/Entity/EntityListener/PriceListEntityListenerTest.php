<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
                        PriceListRelationTrigger::WEBSITE => null,
                        PriceListRelationTrigger::ACCOUNT => null,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::FORCE => true,
                    ]
                ],
            ],
            $this->getMessageProducer()->getSentMessages()
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

    public function testPrePersistEmptyAssignmentRule()
    {
        $this->cleanQueueMessageTraces();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = new PriceList();
        $priceList->setName('TEST123');
        $em->persist($priceList);
        $em->flush();

        $this->assertTrue($priceList->isActual());
        $this->assertEmpty($this->getQueueMessageTraces());
    }

    public function testPrePersistWithAssignmentRule()
    {
        $this->cleanQueueMessageTraces();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = new PriceList();
        $priceList->setName('TEST123');
        $priceList->setProductAssignmentRule('TEST123');
        $em->persist($priceList);
        $em->flush();

        $this->assertFalse($priceList->isActual());

        $traces = $this->getQueueMessageTraces();
        $this->assertCount(1, $traces);
        $this->assertEquals($priceList->getId(), $this->getPriceListIdFromTrace($traces[0]));
    }
}
