<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductTaxCodeControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures([LoadProductTaxCodes::class]);
    }

    public function testPatchAction()
    {
        /** @var ProductTaxCode $taxCode */
        $taxCode = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_3);
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $this->assertFalse($taxCode->getProducts()->contains($product));

        $this->client->request(
            'PATCH',
            $this->getUrl('oro_api_patch_product_tax_code', [
                'id' => $product->getId(),
                'taxCode' => $taxCode->getId(),
            ])
        );

        $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertTrue($taxCode->getProducts()->contains($product));
    }

    public function testPatchActionWithNotFoundProduct()
    {
        $this->client->request(
            'PATCH',
            $this->getUrl('oro_api_patch_product_tax_code', [
                'id' => 100500
            ])
        );

        $this->getJsonResponseContent($this->client->getResponse(), 404);
    }

    public function testPatchActionWithoutTaxCode()
    {
        /** @var ProductTaxCode $taxCode */
        $taxCode = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);
        $product = $this->getReference(LoadProductData::PRODUCT_2);

        $this->assertTrue($taxCode->getProducts()->contains($product));

        $this->client->request(
            'PATCH',
            $this->getUrl('oro_api_patch_product_tax_code', [
                'id' => $product->getId(),
                'taxCode' => ''
            ])
        );

        $this->getJsonResponseContent($this->client->getResponse(), 200);

        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');

        /** @var ProductTaxCodeRepository $taxCodeRepository */
        $taxCodeRepository = $doctrineHelper->getEntityRepositoryForClass(ProductTaxCode::class);
        $taxCodeFromDb = $taxCodeRepository->find($taxCode->getId());
        $this->assertFalse($taxCodeFromDb->getProducts()->contains($product));
    }
}
