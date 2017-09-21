<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItems;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedDiscountRepository;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadAppliedPromotionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AppliedDiscountRepositoryTest extends WebTestCase
{
    /**
     * @var AppliedDiscountRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadAppliedPromotionData::class,
                LoadOrderLineItems::class,
            ]
        );
        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(AppliedDiscount::class)
            ->getRepository(AppliedDiscount::class);
    }

    public function testIsRepositoryConnectedToEntity()
    {
        $this->assertInstanceOf(AppliedDiscountRepository::class, $this->repository);
    }

    public function testFindPromotionByOrderLineItem()
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getReference(LoadOrderLineItems::ITEM_1);

        $appliedDiscounts = $this->repository->findByLineItem($lineItem);
        $this->assertNotEmpty($appliedDiscounts);
    }
}
