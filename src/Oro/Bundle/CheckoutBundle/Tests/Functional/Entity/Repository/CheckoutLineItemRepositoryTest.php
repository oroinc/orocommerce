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

    /** @var CheckoutLineItemRepository */
    private $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->setCurrentWebsite();
        $this->loadFixtures(
            [
                LoadShoppingListsCheckoutsData::class,
            ]
        );

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(CheckoutLineItem::class);
    }

    /**
     * @after
     */
    public function afterFrontendTest(): void
    {
        if (null !== $this->client) {
            $this->getWebsiteManagerStub()->disableStub();
        }
    }

    public function testCanBeGrouped(): void
    {
        $id = $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1)->getId();

        $this->assertFalse($this->repository->canBeGrouped($id));
    }
}
