<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes as TaxFixture;

class ProductTaxCodeRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(['Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes']);
    }

    public function testFindOneByProduct()
    {
        /** @var Product $product5 */
        $product5 = $this->getReference(LoadProductData::PRODUCT_5);
        $this->assertNull($this->getRepository()->findOneByProduct($product5));

        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $expectedTaxCode = $this->getRepository()->findOneByProduct($product1);

        /** @var ProductTaxCode $taxCode1 */
        $taxCode1 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1);
        $this->assertEquals($expectedTaxCode->getId(), $taxCode1->getId());
    }

    public function testFindNewProduct()
    {
        $this->assertEmpty($this->getRepository()->findOneByProduct(new Product()));
    }

    public function testFindByCodes()
    {
        /** @var ProductTaxCode $taxCode1 */
        $taxCode = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1);

        $this->assertEquals([$taxCode], $this->getRepository()->findByCodes([TaxFixture::TAX_1]));
    }

    public function testFindManyByEntitiesWithEmptyProducts()
    {
        $this->assertEmpty($this->getRepository()->findManyByEntities(TaxCodeInterface::TYPE_PRODUCT, []));
    }

    public function testFindManyByEntities()
    {
        $products = [
            $this->getReference(LoadProductData::PRODUCT_1),
            $this->getReference(LoadProductData::PRODUCT_3)
        ];

        $expectedTaxCodes = [
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1),
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_2)
        ];

        $this->assertEquals(
            $expectedTaxCodes,
            $this->getRepository()->findManyByEntities(TaxCodeInterface::TYPE_PRODUCT, $products)
        );
    }

    public function testFindManyByEntitiesWithNewProducts()
    {
        $firstNewProduct = new Product();
        $secondNewProduct = new Product();
        $products = [
            $firstNewProduct,
            $this->getReference(LoadProductData::PRODUCT_1),
            $this->getReference(LoadProductData::PRODUCT_3),
            $secondNewProduct
        ];

        $expectedTaxCodes = [
            null,
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1),
            $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_2),
            null
        ];

        $this->assertEquals(
            $expectedTaxCodes,
            $this->getRepository()->findManyByEntities(TaxCodeInterface::TYPE_PRODUCT, $products)
        );
    }

    /**
     * @return ProductTaxCodeRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('oro_tax.repository.product_tax_code');
    }
}
