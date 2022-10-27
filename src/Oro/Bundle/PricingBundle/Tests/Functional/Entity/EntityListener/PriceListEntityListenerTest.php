<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListsSimplified;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
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

    public function testPreRemoveUnusedPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $em = $this->getEntityManager();
        $em->remove($priceList);
        $em->flush();

        self::assertEmptyMessages(MassRebuildCombinedPriceListsTopic::getName());
    }

    public function testPreRemove()
    {
        $this->loadFixtures([
            LoadPriceListRelations::class,
            LoadCombinedPriceListsSimplified::class
        ]);

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $customer = $this->getReference('customer.level_1_1');
        $websiteUS = $this->getReference('US');
        $websiteCA = $this->getReference('Canada');
        $cplId = $this->getReference('1_2_3')->getId();

        $em = $this->getEntityManager();
        $em->remove($priceList);
        $em->flush();

        $this->assertNull($em->find(CombinedPriceList::class, $cplId));

        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'customer' => $customer->getId(),
                        'website' => $websiteCA->getId()
                    ],
                    [
                        'website' => $websiteUS->getId()
                    ]
                ]
            ]
        );
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
            ResolvePriceListAssignedProductsTopic::getName(),
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

        self::assertEmptyMessages(MassRebuildCombinedPriceListsTopic::getName());

        $priceList->setActive(true);
        $em->flush();

        self::assertMessageSent(
            ResolvePriceListAssignedProductsTopic::getName(),
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

        self::assertEmptyMessages(ResolvePriceListAssignedProductsTopic::getName());
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

        self::assertEmptyMessages(ResolvePriceListAssignedProductsTopic::getName());
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
            ResolvePriceListAssignedProductsTopic::getName(),
            [
                'product' => [$priceList->getId() => []]
            ]
        );
    }
}
