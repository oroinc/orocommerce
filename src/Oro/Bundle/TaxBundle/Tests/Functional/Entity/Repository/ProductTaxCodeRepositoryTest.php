<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodesWithAdditionalOrganization as TaxFixture;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductTaxCodeRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([TaxFixture::class]);
    }

    public function testFindManyByEntitiesWithEmptyProducts()
    {
        $this->assertEmpty($this->getRepository()->findManyByEntities([]));
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
            $this->getRepository()->findManyByEntities($products)
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
            $this->getRepository()->findManyByEntities($products)
        );
    }

    protected function getRepository(): ProductTaxCodeRepository
    {
        return $this->getContainer()->get('oro_tax.repository.product_tax_code');
    }
}
