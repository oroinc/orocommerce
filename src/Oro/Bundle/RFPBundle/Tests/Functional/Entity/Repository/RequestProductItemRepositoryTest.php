<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\RFPBundle\Entity\Repository\RequestProductItemRepository;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestProductItemData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RequestProductItemRepositoryTest extends WebTestCase
{
    private RequestProductItemRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadRequestProductItemData::class,
        ]);

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(RequestProductItem::class);
    }

    public function testFindByRequestProductItem(): void
    {
        $expected = [
            $this->getReference('request-product-1')->getId() => new ArrayCollection([
                $this->getReference('request-product-1.item1'),
                $this->getReference('request-product-2.item2')
            ]),
            $this->getReference('request-product-2')->getId() => new ArrayCollection([
                $this->getReference('request-product-3.item3')
            ])
        ];

        $requestProductIds = [
            $this->getReference('request-product-1')->getId(),
            $this->getReference('request-product-2')->getId(),
            $this->getReference('request-product-3')->getId()
        ];

        $result = $this->repository->getProductItemsByRequestIds($requestProductIds);
        self::assertEquals($expected, $result);
    }

    public function testFindByRequestProductItemWithInvalidRequestId(): void
    {
        $invalidRequestProductIds = [];
        $result = $this->repository->getProductItemsByRequestIds($invalidRequestProductIds);
        self::assertEmpty($result);
    }
}
