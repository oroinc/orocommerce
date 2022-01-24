<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributePriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PriceAttributeProductPriceTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadPriceAttributeProductPrices::class]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'priceattributeproductprices'],
            ['filter' => ['product' => ['@product-1->id']], 'sort' => 'id']
        );

        $this->assertResponseContains('price_attribute_product_price/get_list.yml', $response);
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => 'priceattributeproductprices'],
            'price_attribute_product_price/create.yml'
        );

        $price = $this->getEntityManager()
            ->getRepository(PriceAttributeProductPrice::class)
            ->findOneBy([
                'priceList' => $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_2),
                'product'   => $this->getReference(LoadProductData::PRODUCT_3),
                'unit'      => $this->getReference(LoadProductUnits::LITER)
            ]);

        self::assertEquals(24.57, $price->getPrice()->getValue());
        self::assertSame('USD', $price->getPrice()->getCurrency());
    }

    public function testTryToCreateDuplicate()
    {
        $routeParameters = ['entity' => 'priceattributeproductprices'];
        $parameters = $this->getRequestData('price_attribute_product_price/create.yml');

        $this->post($routeParameters, $parameters);

        $response = $this->post($routeParameters, $parameters, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'unique entity constraint',
                'detail' => 'This value is used. Unique constraint: product,priceList,unit,currency,quantity'
            ],
            $response
        );
    }

    public function testTryToCreateEmptyValue()
    {
        $data = $this->getRequestData('price_attribute_product_price/create.yml');
        $data['data']['attributes']['value'] = '';
        $response = $this->post(
            ['entity' => 'priceattributeproductprices'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'Price value should not be blank.',
                'source' => ['pointer' => '/data/attributes/value']
            ],
            $response
        );
    }

    public function testTryToCreateEmptyCurrency()
    {
        $data = $this->getRequestData('price_attribute_product_price/create.yml');
        $data['data']['attributes']['currency'] = '';
        $response = $this->post(
            ['entity' => 'priceattributeproductprices'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/currency']
            ],
            $response
        );
    }

    public function testTryToCreateWrongValue()
    {
        $data = $this->getRequestData('price_attribute_product_price/create.yml');
        $data['data']['attributes']['value'] = 'test';
        $response = $this->post(
            ['entity' => 'priceattributeproductprices'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'type constraint',
                'detail' => 'This value should be of type numeric.',
                'source' => ['pointer' => '/data/attributes/value']
            ],
            $response
        );
    }

    public function testTryToCreateWrongCurrency()
    {
        $data = $this->getRequestData('price_attribute_product_price/create.yml');
        $data['data']['attributes']['currency'] = 'EUR';
        $response = $this->post(
            ['entity' => 'priceattributeproductprices'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'product price currency constraint',
                'detail' => 'Currency "EUR" is not valid for current price list.',
                'source' => ['pointer' => '/data/attributes/currency']
            ],
            $response
        );
    }

    public function testTryToCreateWrongProductUnit()
    {
        $data = $this->getRequestData('price_attribute_product_price/create.yml');
        $data['data']['relationships']['unit']['data']['id'] = '<toString(@product_unit.box->code)>';
        $response = $this->post(
            ['entity' => 'priceattributeproductprices'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'product price allowed units constraint',
                'detail' => 'Unit "box" is not allowed for product "product-3".',
                'source' => ['pointer' => '/data/relationships/unit/data']
            ],
            $response
        );
    }

    public function testDeleteList()
    {
        $priceId1 = $this->getFirstPrice()->getId();
        $priceId2 = $this->getReference('price_attribute_product_price.2')->getId();

        $this->cdelete(
            ['entity' => 'priceattributeproductprices'],
            ['filter' => ['id' => [$priceId1, $priceId2]]]
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

        $response = $this->get(
            ['entity' => 'priceattributeproductprices', 'id' => $priceId]
        );

        $this->assertResponseContains('price_attribute_product_price/get.yml', $response);
    }

    public function testUpdate()
    {
        $priceId = $this->getFirstPrice()->getId();

        $this->patch(
            ['entity' => 'priceattributeproductprices', 'id' => (string)$priceId],
            'price_attribute_product_price/update.yml'
        );

        $price = $this->getEntityManager()
            ->getRepository(PriceAttributeProductPrice::class)
            ->find($priceId);

        self::assertEquals(78.95, $price->getPrice()->getValue());
        self::assertEquals('EUR', $price->getPrice()->getCurrency());
        self::assertEquals(
            $this->getReference('product_unit.milliliter'),
            $price->getProductUnit()
        );
    }

    public function testTryToUpdateToDuplicate()
    {
        $priceId = $this->getFirstPrice()->getId();

        $response = $this->patch(
            ['entity' => 'priceattributeproductprices', 'id' => (string)$priceId],
            'price_attribute_product_price/update_duplicate.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'unique entity constraint',
                'detail' => 'This value is used. Unique constraint: product,priceList,unit,currency,quantity'
            ],
            $response
        );
    }

    public function testDelete()
    {
        $priceId = $this->getFirstPrice()->getId();

        $this->delete(
            ['entity' => 'priceattributeproductprices', 'id' => $priceId]
        );

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
            'entity'      => 'priceattributeproductprices',
            'id'          => $price->getId(),
            'association' => 'priceList'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'priceattributepricelists',
                    'id'   => (string)$price->getPriceList()->getId()
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity'      => 'priceattributeproductprices',
            'id'          => $price->getId(),
            'association' => 'product'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id'   => (string)$price->getProduct()->getId()
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity'      => 'priceattributeproductprices',
            'id'          => $price->getId(),
            'association' => 'unit'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productunits',
                    'id'   => (string)$price->getProductUnit()->getCode()
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
                'entity'      => 'priceattributeproductprices',
                'id'          => $price->getId(),
                'association' => 'priceList'
            ],
            [
                'data' => [
                    'type' => 'priceattributepricelists',
                    'id'   => (string)$this->getReference('price_attribute_price_list_2')->getId()
                ]
            ]
        );

        $this->patchRelationship(
            [
                'entity'      => 'priceattributeproductprices',
                'id'          => $price->getId(),
                'association' => 'unit'
            ],
            [
                'data' => [
                    'type' => 'productunits',
                    'id'   => (string)$this->getReference('product_unit.milliliter')->getCode()
                ]
            ]
        );

        $this->patchRelationship(
            [
                'entity'      => 'priceattributeproductprices',
                'id'          => $price->getId(),
                'association' => 'product'
            ],
            [
                'data' => [
                    'type' => 'products',
                    'id'   => (string)$this->getReference('product-4')->getId()
                ]
            ]
        );

        $updatedPrice = $this->getEntityManager()
            ->getRepository(PriceAttributeProductPrice::class)
            ->find($price->getId());

        self::assertEquals(
            $this->getReference('price_attribute_price_list_2'),
            $updatedPrice->getPriceList()
        );

        self::assertEquals(
            $this->getReference('product_unit.milliliter'),
            $updatedPrice->getProductUnit()
        );

        self::assertEquals(
            $this->getReference('product-4'),
            $updatedPrice->getProduct()
        );
    }

    private function assertGetSubResource(int $entityId, string $associationName, string $associationId): void
    {
        $response = $this->getSubresource([
            'entity'      => 'priceattributeproductprices',
            'id'          => $entityId,
            'association' => $associationName
        ]);

        $result = self::jsonToArray($response->getContent());

        self::assertEquals($associationId, $result['data']['id']);
    }

    private function getFirstPrice(): PriceAttributeProductPrice
    {
        return $this->getReference('price_attribute_product_price.1');
    }
}
