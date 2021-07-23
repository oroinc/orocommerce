<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PriceListEntityListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductPrices::class]);
        $this->enableMessageBuffering();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    public function testPreRemove()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $em = $this->getEntityManager();
        $em->remove($priceList);
        $em->flush();

        self::assertEmptyMessages(Topics::REBUILD_COMBINED_PRICE_LISTS);
    }

    public function testPreUpdate()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        $priceList->setProductAssignmentRule('product.id > 10');

        $em = $this->getEntityManager();
        $em->persist($priceList);
        $em->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                'product' => [$priceList->getId() => []]
            ]
        );
    }

    public function testPreUpdateActive()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        $priceList->setActive(false);

        $em = $this->getEntityManager();
        $em->persist($priceList);
        $em->flush();

        self::assertEmptyMessages(Topics::REBUILD_COMBINED_PRICE_LISTS);

        $priceList->setActive(true);
        $em->flush();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                'product' => [$priceList->getId() => []]
            ]
        );
    }

    public function testPreUpdateAssignmentNotChanged()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_2');
        $priceList->setName('TEST123');

        $em = $this->getEntityManager();
        $em->persist($priceList);
        $em->flush();

        self::assertEmptyMessages(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS);
    }

    public function testPrePersistEmptyAssignmentRule()
    {
        /** @var PriceList $priceList */
        $priceList = new PriceList();
        $priceList->setName('TEST123');

        $em = $this->getEntityManager();
        $em->persist($priceList);
        $em->flush();

        $this->assertTrue($priceList->isActual());

        self::assertEmptyMessages(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS);
    }

    public function testPrePersistWithAssignmentRule()
    {
        /** @var PriceList $priceList */
        $priceList = new PriceList();
        $priceList->setName('TEST123');
        $priceList->setProductAssignmentRule('TEST123');

        $em = $this->getEntityManager();
        $em->persist($priceList);
        $em->flush();

        $this->assertFalse($priceList->isActual());

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS,
            [
                'product' => [$priceList->getId() => []]
            ]
        );
    }
}
