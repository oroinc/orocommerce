<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributePriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PriceAttributeProductPriceTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadPriceAttributeProductPrices::class,
        ]);
    }

    public function testGetList()
    {
        $parameters = [
            'filter' => [
                'product' => ['@product-1->id'],
            ],
            'sort' => 'id',
        ];
        $response = $this->cget(['entity' => $this->getEntityApiName()], $parameters);

        $this->assertResponseContains($this->getAliceFilesFolderName() . '/get_list.yml', $response);
    }

    public function testCreateDuplicate()
    {
        $routeParameters = self::processTemplateData(['entity' => $this->getEntityApiName()]);
        $parameters = $this->getRequestData(
            $this->getAliceFilesFolderName() . '/create.yml'
        );

        $this->post($routeParameters, $parameters);

        $response = $this->post($routeParameters, $parameters, [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'unique entity constraint',
            $response->getContent()
        );
    }

    public function testCreateWrongCurrency()
    {
        $routeParameters = self::processTemplateData(['entity' => $this->getEntityApiName()]);
        $parameters = $this->getRequestData(
            $this->getAliceFilesFolderName() . '/create_wrong_currency.yml'
        );

        $response = $this->post($routeParameters, $parameters, [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'Currency \"EUR\" is not valid for current price list',
            $response->getContent()
        );
    }

    public function testCreateWrongProductUnit()
    {
        $routeParameters = self::processTemplateData(['entity' => $this->getEntityApiName()]);
        $parameters = $this->getRequestData(
            $this->getAliceFilesFolderName() . '/create_wrong_unit.yml'
        );

        $response = $this->post($routeParameters, $parameters, [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'Unit \"box\" is not allowed for product',
            $response->getContent()
        );
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => $this->getEntityApiName()],
            $this->getAliceFilesFolderName() . '/create.yml'
        );

        $price = $this->getEntityManager()
            ->getRepository(PriceAttributeProductPrice::class)
            ->findOneBy([
                'priceList' => $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_2),
                'product' => $this->getReference(LoadProductData::PRODUCT_3),
                'unit' => $this->getReference(LoadProductUnits::LITER),
            ]);

        static::assertEquals(24.57, $price->getPrice()->getValue());
        static::assertSame('USD', $price->getPrice()->getCurrency());
    }

    public function testDeleteList()
    {
        $priceId1 = $this->getFirstPrice()->getId();
        $priceId2 = $this->getReference('price_attribute_product_price.2')->getId();

        $this->cdelete(
            ['entity' => $this->getEntityApiName()],
            [
                'filter' => [
                    'id' => [$priceId1, $priceId2]
                ]
            ]
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceAttributeProductPrice::class, $priceId1)
        );

        $this->assertNull(
            $this->getEntityManager()->find(PriceAttributeProductPrice::class, $priceId2)
        );
    }

    public function testGet()
    {
        $priceId = $this->getFirstPrice()->getId();

        $response = $this->get([
            'entity' => $this->getEntityApiName(),
            'id' => $priceId
        ]);

        $this->assertResponseContains($this->getAliceFilesFolderName() . '/get.yml', $response);
    }

    public function testUpdate()
    {
        $priceId = $this->getFirstPrice()->getId();

        $this->patch(
            ['entity' => $this->getEntityApiName(), 'id' => (string)$priceId],
            $this->getAliceFilesFolderName() . '/update.yml'
        );

        $price = $this->getEntityManager()
            ->getRepository(PriceAttributeProductPrice::class)
            ->find($priceId);

        static::assertEquals(78.95, $price->getPrice()->getValue());
        static::assertEquals('EUR', $price->getPrice()->getCurrency());
        static::assertEquals(
            $this->getReference('product_unit.milliliter'),
            $price->getProductUnit()
        );
    }

    public function testUpdateToDuplicate()
    {
        $priceId = $this->getFirstPrice()->getId();

        $routeParameters = self::processTemplateData([
            'entity' => $this->getEntityApiName(),
            'id' => (string)$priceId
        ]);

        $parameters = $this->getRequestData(
            $this->getAliceFilesFolderName() . '/update_duplicate.yml'
        );

        $response = $this->patch($routeParameters, $parameters, [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'unique entity constraint',
            $response->getContent()
        );
    }

    public function testDelete()
    {
        $priceId = $this->getFirstPrice()->getId();

        $this->delete([
            'entity' => $this->getEntityApiName(),
            'id' => $priceId,
        ]);

        $this->assertNull(
            $this->getEntityManager()->find(PriceAttributeProductPrice::class, $priceId)
        );
    }

    public function testGetSubResources()
    {
        $price = $this->getFirstPrice();

        $this->assertGetSubResource($price->getId(), 'priceList', $price->getPriceList()->getId());
        $this->assertGetSubResource($price->getId(), 'product', $price->getProduct()->getId());
        $this->assertGetSubResource($price->getId(), 'unit', $price->getProductUnit()->getCode());
    }

    public function testGetRelationships()
    {
        $price = $this->getFirstPrice();

        $response = $this->getRelationship([
            'entity' => $this->getEntityApiName(),
            'id' => $price->getId(),
            'association' => 'priceList',
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'priceattributepricelists',
                    'id' => (string)$price->getPriceList()->getId(),
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity' => $this->getEntityApiName(),
            'id' => $price->getId(),
            'association' => 'product',
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => (string)$price->getProduct()->getId(),
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity' => $this->getEntityApiName(),
            'id' => $price->getId(),
            'association' => 'unit',
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => (string)$price->getProductUnit()->getCode(),
                ]
            ],
            $response
        );
    }

    public function testPatchRelationships()
    {
        $price = $this->getReference('price_attribute_product_price.8');

        $this->patchRelationship(
            [
                'entity' => $this->getEntityApiName(),
                'id' => $price->getId(),
                'association' => 'priceList',
            ],
            [
                'data' => [
                    'type' => 'priceattributepricelists',
                    'id' => (string)$this->getReference('price_attribute_price_list_2')->getId(),
                ]
            ]
        );

        $this->patchRelationship(
            [
                'entity' => $this->getEntityApiName(),
                'id' => $price->getId(),
                'association' => 'unit',
            ],
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => (string)$this->getReference('product_unit.milliliter')->getCode(),
                ]
            ]
        );

        $this->patchRelationship(
            [
                'entity' => $this->getEntityApiName(),
                'id' => $price->getId(),
                'association' => 'product',
            ],
            [
                'data' => [
                    'type' => 'products',
                    'id' => (string)$this->getReference('product-4')->getId(),
                ]
            ]
        );

        $updatedPrice = $this->getEntityManager()
            ->getRepository(PriceAttributeProductPrice::class)
            ->find($price->getId());

        static::assertEquals(
            $this->getReference('price_attribute_price_list_2'),
            $updatedPrice->getPriceList()
        );

        static::assertEquals(
            $this->getReference('product_unit.milliliter'),
            $updatedPrice->getProductUnit()
        );

        static::assertEquals(
            $this->getReference('product-4'),
            $updatedPrice->getProduct()
        );
    }

    /**
     * @param int    $entityId
     * @param string $associationName
     * @param string $associationId
     */
    protected function assertGetSubResource(int $entityId, string $associationName, string $associationId)
    {
        $response = $this->getSubresource([
            'entity' => $this->getEntityApiName(),
            'id' => $entityId,
            'association' => $associationName
        ]);

        $result = json_decode($response->getContent(), true);

        self::assertEquals($associationId, $result['data']['id']);
    }

    /**
     * @return string
     */
    protected function getEntityApiName(): string
    {
        return 'priceattributeproductprices';
    }

    /**
     * @return string
     */
    protected function getAliceFilesFolderName(): string
    {
        return 'price_attribute_product_price';
    }

    /**
     * @return PriceAttributeProductPrice
     */
    protected function getFirstPrice()
    {
        return $this->getReference('price_attribute_product_price.1');
    }
}
