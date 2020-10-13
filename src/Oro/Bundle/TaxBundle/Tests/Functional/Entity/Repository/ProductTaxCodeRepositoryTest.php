<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
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

    public function testFindByCodes()
    {
        /** @var ProductTaxCode $taxCode1 */
        $taxCode1 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_1);

        /** @var ProductTaxCode $taxCode2 */
        $taxCode2 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_2);

        /** @var ProductTaxCode $taxCode3 */
        $taxCode3 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_3);

        $this->assertEquals([
            $taxCode1,
            $taxCode2,
            $taxCode3,
        ], $this->getRepository()->findByCodes([
            TaxFixture::TAX_1,
            TaxFixture::TAX_2,
            TaxFixture::TAX_3,
        ]));
    }

    public function testFindByCodesAndOrganization()
    {
        /** @var ProductTaxCode $taxCode3 */
        $taxCode3 = $this->getReference(TaxFixture::REFERENCE_PREFIX . '.' . TaxFixture::TAX_3);

        $organizationAcme = $this->getReference('acme_organization');

        $this->assertEquals([
            $taxCode3
        ], $this->getRepository()->findByCodes([
            TaxFixture::TAX_1,
            TaxFixture::TAX_2,
            TaxFixture::TAX_3,
        ], $organizationAcme));
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
