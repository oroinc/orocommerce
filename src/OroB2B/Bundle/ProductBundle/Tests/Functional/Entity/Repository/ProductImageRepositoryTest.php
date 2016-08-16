<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductImageRepository;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData as ProductFixture;

/**
 * @dbIsolation
 */
class ProductImageRepositoryTest extends WebTestCase
{
    /**
     * @var ProductImageRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductImageData',
        ]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_product.entity.product_image.class')
        );
    }

    public function testFindOneByImage()
    {
        $this->assertNull($this->repository->findOneByImage(new File()));

        $product = $this->getProduct(ProductFixture::PRODUCT_1);
        $expectedProductImage = $product->getImages()->first();
        $image = $expectedProductImage->getImage();

        $this->assertEquals($expectedProductImage, $this->repository->findOneByImage($image));
    }

    /**
     * @param string $sku
     * @return Product
     */
    public function getProduct($sku)
    {
        return $this->getContainer()
            ->get('doctrine')->getRepository(
                $this->getContainer()->getParameter('orob2b_product.entity.product.class')
            )
            ->findOneBySku($sku);
    }
}

