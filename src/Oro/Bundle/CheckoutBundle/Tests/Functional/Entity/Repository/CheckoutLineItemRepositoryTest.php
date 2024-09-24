<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutLineItemRepository;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\WebsiteManagerTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CheckoutLineItemRepositoryTest extends WebTestCase
{
    use WebsiteManagerTrait;

    private CheckoutLineItemRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->setCurrentWebsite();
        $this->loadFixtures([LoadShoppingListsCheckoutsData::class]);

        $this->repository = self::getContainer()->get('doctrine')->getRepository(CheckoutLineItem::class);
    }

    /**
     * @beforeResetClient
     */
    public static function afterFrontendTest(): void
    {
        self::getWebsiteManagerStub()->disableStub();
    }

    public function testCanBeGrouped(): void
    {
        $id = $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1)->getId();

        $this->assertFalse($this->repository->canBeGrouped($id));
    }
}
