<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductImageRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\ProductImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductImageRepositoryTest extends WebTestCase
{
    private ProductImageRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([ProductImageData::class]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(ProductImage::class);
    }

    public function testGetAllProductImagesIterator(): void
    {
        $iterator = $this->repository->getAllProductImagesIterator();

        self::assertInstanceOf(\Iterator::class, $iterator);

        $ids = [];
        foreach ($iterator as $row) {
            self::assertArrayHasKey('id', $row);
            $ids[] = $row['id'];
        }

        self::assertCount(4, $ids);
        self::assertCount(4, array_unique($ids));
    }

    public function testGetAllProductImagesIteratorReturnsOnlyIds(): void
    {
        $iterator = $this->repository->getAllProductImagesIterator();

        foreach ($iterator as $row) {
            self::assertCount(1, $row);
            self::assertArrayHasKey('id', $row);
            self::assertIsInt($row['id']);
        }
    }
}
