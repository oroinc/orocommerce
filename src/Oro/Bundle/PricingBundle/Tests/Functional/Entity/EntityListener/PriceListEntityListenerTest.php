<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PriceListEntityListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductPrices::class
        ]);
    }

    public function testPreRemove()
    {
        $this->cleanScheduledMessages();
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $em->remove($priceList);

        $this->sendScheduledMessages();

        self::assertEmptyMessages(Topics::REBUILD_COMBINED_PRICE_LISTS);
    }

    public function testPreUpdate()
    {
        $this->cleanScheduledMessages();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        $priceList->setProductAssignmentRule('product.id > 10');
        $em->persist($priceList);
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                PriceListTriggerFactory::PRODUCT => [$priceList->getId() => []]
            ]
        );
    }

    public function testPreUpdateActive()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        $priceList->setActive(false);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->persist($priceList);
        $em->flush();

        $this->sendScheduledMessages();
        self::assertEmptyMessages(Topics::REBUILD_COMBINED_PRICE_LISTS);

        $priceList->setActive(true);
        $em->flush();

        $this->sendScheduledMessages();
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                'product' => [$priceList->getId() => []]
            ]
        );
    }

    public function testPreUpdateAssignmentNotChanged()
    {
        $this->cleanScheduledMessages();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        $priceList->setName('TEST123');
        $em->persist($priceList);
        $em->flush();

        $this->sendScheduledMessages();

        self::assertEmptyMessages(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS);
    }

    public function testPrePersistEmptyAssignmentRule()
    {
        $this->cleanScheduledMessages();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = new PriceList();
        $priceList->setName('TEST123');
        $em->persist($priceList);
        $em->flush();

        $this->assertTrue($priceList->isActual());

        $this->sendScheduledMessages();

        self::assertEmptyMessages(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS);
    }

    public function testPrePersistWithAssignmentRule()
    {
        $this->cleanScheduledMessages();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var PriceList $priceList */
        $priceList = new PriceList();
        $priceList->setName('TEST123');
        $priceList->setProductAssignmentRule('TEST123');
        $em->persist($priceList);
        $em->flush();

        $this->assertFalse($priceList->isActual());

        $this->sendScheduledMessages();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                PriceListTriggerFactory::PRODUCT => [$priceList->getId() => []]
            ]
        );
    }
}
