<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadApiProductPricesWithRules;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductPriceTest extends RestJsonApiTestCase
{
    use ProductPriceTestTrait;
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_cpl');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_to_product');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_currency');

        $this->loadFixtures([LoadApiProductPricesWithRules::class]);
    }

    private function findProductPrice(int $priceListId): ?ProductPrice
    {
        return $this->getProductPriceRepository()->createQueryBuilder('price')
            ->andWhere('price.quantity = :quantity')
            ->andWhere('price.value = :value')
            ->andWhere('price.currency = :currency')
            ->andWhere('price.priceList = :priceList')
            ->andWhere('price.product = :product')
            ->andWhere('price.unit = :unit')
            ->setParameter('quantity', 250)
            ->setParameter('value', 150)
            ->setParameter('currency', 'CAD')
            ->setParameter('priceList', $priceListId)
            ->setParameter('product', $this->getProduct('product-5'))
            ->setParameter('unit', $this->getProductUnit('product_unit.milliliter'))
            ->getQuery()
            ->setHint('priceList', $priceListId)
            ->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $this->getShardManager())
            ->getOneOrNullResult();
    }

    private function getShardManager(): ShardManager
    {
        return self::getContainer()->get('oro_pricing.shard_manager');
    }

    private function getProductPriceApiId(ProductPrice $productPrice, PriceList $priceList): string
    {
        return $productPrice->getId() . '-' . $priceList->getId();
    }

    private function getPriceList(string $priceListReference): PriceList
    {
        return $this->getReference($priceListReference);
    }

    private function getProduct(string $productReference): Product
    {
        return $this->getReference($productReference);
    }

    private function getProductPrice(string $productPriceReference): ProductPrice
    {
        return $this->getReference($productPriceReference);
    }

    private function getProductUnit(string $productUnitReference): ProductUnit
    {
        return $this->getReference($productUnitReference);
    }

    private function getProductPriceRepository(): ProductPriceRepository
    {
        return $this->getDoctrineHelper()->getEntityRepositoryForClass(ProductPrice::class);
    }

    private function assertMessagesSentForCreateRequest(int $priceListId): void
    {
        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $priceListId => [$this->getProduct('product-5')->getId()]
                ]
            ]
        );
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'productprices'],
            ['filter' => ['priceList' => ['@price_list_1->id']], 'sort' => 'product']
        );

        $this->assertResponseContains('product_price/get_list.yml', $response);
    }

    public function testGetListWithTotalCount(): void
    {
        $response = $this->cget(
            ['entity' => 'productprices'],
            ['filter' => ['priceList' => ['@price_list_1->id']], 'page' => ['size' => 1], 'sort' => 'product'],
            ['HTTP_X-Include' => 'totalCount']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productprices',
                        'id' => '<(implode("-", [@product_price_with_rule_1->id, @price_list_1->id]))>',
                    ]
                ]
            ],
            $response
        );
        self::assertEquals(2, $response->headers->get('X-Include-Total-Count'));
    }

    public function testTryToGetListWithoutPriceListFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'productprices'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The "priceList" filter is required.'
            ],
            $response
        );
    }

    public function testGetListWhenPriceListFilterContainsIdOfNotExistingPriceList(): void
    {
        if ($this->getShardManager()->isShardingEnabled()) {
            self::markTestSkipped('Skip to avoid "current transaction is aborted" error.');
        }

        $response = $this->cget(
            ['entity' => 'productprices'],
            ['filter' => ['priceList' => '9999']]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testTryToGetListWhenPriceListFilterContainsNotIntegerValue(): void
    {
        $response = $this->cget(
            ['entity' => 'productprices'],
            ['filter' => ['priceList' => 'invalid']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'Expected integer value. Given "invalid".',
                'source' => ['parameter' => 'filter[priceList]']
            ],
            $response
        );
    }

    public function testCreate(): void
    {
        $response = $this->post(
            ['entity' => 'productprices'],
            'product_price/create.yml'
        );

        $priceListId = $this->getPriceList('price_list_3')->getId();
        $productPrice = $this->findProductPrice($priceListId);
        self::assertNotNull($productPrice);

        self::assertEquals(
            $this->getProductPriceApiId($productPrice, $productPrice->getPriceList()),
            $this->getResourceId($response)
        );

        $this->assertMessagesSentForCreateRequest($priceListId);
    }

    public function testTryToCreateDuplicate(): void
    {
        $this->post(
            ['entity' => 'productprices'],
            'product_price/create.yml'
        );

        $response = $this->post(
            ['entity' => 'productprices'],
            'product_price/create.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'unique entity constraint',
                'detail' => 'Product has duplication of product prices.'
                    . ' Set of fields "PriceList", "Quantity" , "Unit" and "Currency" should be unique.'
            ],
            $response
        );
    }

    public function testTryToCreateEmptyValue(): void
    {
        $data = $this->getRequestData('product_price/create.yml');
        $data['data']['attributes']['value'] = '';
        $response = $this->post(
            ['entity' => 'productprices'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'Price value should not be blank.',
                'source' => ['pointer' => '/data/attributes/value']
            ],
            $response
        );
    }

    public function testTryToCreateEmptyCurrency(): void
    {
        $data = $this->getRequestData('product_price/create.yml');
        $data['data']['attributes']['currency'] = '';
        $response = $this->post(
            ['entity' => 'productprices'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/currency']
            ],
            $response
        );
    }

    public function testTryToCreateWrongValue(): void
    {
        $data = $this->getRequestData('product_price/create.yml');
        $data['data']['attributes']['value'] = 'test';
        $response = $this->post(
            ['entity' => 'productprices'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title' => 'type constraint',
                'detail' => 'This value should be of type numeric.',
                'source' => ['pointer' => '/data/attributes/value']
            ],
            $response
        );
    }

    public function testTryToCreateWrongCurrency(): void
    {
        $data = $this->getRequestData('product_price/create.yml');
        $data['data']['attributes']['currency'] = 'EUR';
        $response = $this->post(
            ['entity' => 'productprices'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title' => 'product price currency constraint',
                'detail' => 'Currency "EUR" is not valid for current price list.',
                'source' => ['pointer' => '/data/attributes/currency']
            ],
            $response
        );
    }

    public function testTryToCreateWrongProductUnit(): void
    {
        $data = $this->getRequestData('product_price/create.yml');
        $data['data']['relationships']['unit']['data']['id'] = '<toString(@product_unit.liter->code)>';
        $response = $this->post(
            ['entity' => 'productprices'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'product price allowed units constraint',
                'detail' => 'Unit "liter" is not allowed for product "product-5".',
                'source' => ['pointer' => '/data/relationships/unit/data']
            ],
            $response
        );
    }

    public function testCreateTogetherWithPriceList(): void
    {
        $response = $this->post(
            ['entity' => 'productprices'],
            'product_price/create_with_priceList.yml'
        );

        $content = self::jsonToArray($response->getContent());
        $priceListId = (int)$content['data']['relationships']['priceList']['data']['id'];
        $productPrice = $this->findProductPrice($priceListId);
        self::assertNotNull($productPrice);

        self::assertEquals(
            $this->getProductPriceApiId($productPrice, $productPrice->getPriceList()),
            $this->getResourceId($response)
        );

        $this->assertMessagesSentForCreateRequest($priceListId);
    }

    public function testCreateWithPriceListAndProduct(): void
    {
        $response = $this->post(
            ['entity' => 'productprices'],
            'product_price/create_with_priceList_and_product.yml'
        );

        $this->assertResponseContains(
            'product_price/get_with_priceList_and_product.yml',
            $response
        );
    }

    public function testCreateTogetherWithPriceListViaPriceListCreate(): void
    {
        $response = $this->post(
            ['entity' => 'pricelists'],
            'product_price/create_with_priceList_via_createPriceList.yml'
        );

        $priceListId = (int)$this->getResourceId($response);
        $productPrice = $this->findProductPrice($priceListId);
        self::assertNotNull($productPrice);

        $content = self::jsonToArray($response->getContent());
        $productPriceId = $content['included'][0]['id'];
        self::assertEquals(
            $this->getProductPriceApiId($productPrice, $productPrice->getPriceList()),
            $productPriceId
        );

        $this->assertMessagesSentForCreateRequest($priceListId);
    }

    public function testDeleteList(): void
    {
        $priceList = $this->getPriceList('price_list_1');
        $priceListId = $priceList->getId();
        $product1Id = $this->getProduct('product-1')->getId();
        $product2Id = $this->getProduct('product-2')->getId();

        $this->cdelete(
            ['entity' => 'productprices'],
            ['filter' => ['priceList' => (string)$priceListId]]
        );

        self::assertSame(
            0,
            $this->getProductPriceRepository()->countByPriceList($this->getShardManager(), $priceList)
        );

        $message = self::getSentMessage(ResolveCombinedPriceByPriceListTopic::getName());
        self::assertIsArray($message);
        self::assertArrayHasKey('product', $message);
        self::assertArrayHasKey($priceListId, $message['product']);
        $productIds = $message['product'][$priceListId];
        sort($productIds);
        self::assertEquals([$product1Id, $product2Id], $productIds);
    }

    public function testDeleteListWhenPriceListFilterContainsIdOfNotExistingPriceList(): void
    {
        $this->cdelete(
            ['entity' => 'productprices'],
            ['filter' => ['priceList' => '9999']]
        );

        self::assertEmptyMessages(ResolveCombinedPriceByPriceListTopic::getName());
    }

    public function testTryToDeleteListWithoutPriceListFilter(): void
    {
        $response = $this->cdelete(
            ['entity' => 'productprices'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'At least one filter must be provided.'
            ],
            $response
        );
    }

    public function testTryToGetWithoutPriceListInId(): void
    {
        $response = $this->get(
            ['entity' => 'productprices', 'id' => $this->getProductPrice('product_price_with_rule_1')->getId()],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetWithWrongPriceListId(): void
    {
        $productPriceApiId = $this->getProductPriceApiId(
            $this->getProductPrice('product_price_with_rule_1'),
            $this->getPriceList('price_list_2')
        );
        $response = $this->get(
            ['entity' => 'productprices', 'id' => $productPriceApiId],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetWhenProductListDoesNotExist(): void
    {
        if ($this->getShardManager()->isShardingEnabled()) {
            self::markTestSkipped('Skip to avoid "current transaction is aborted" error.');
        }

        $productPriceApiId = $this->getProductPrice('product_price_with_rule_1')->getId() . '-9999';
        $response = $this->get(
            ['entity' => 'productprices', 'id' => $productPriceApiId],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetWhenProductPriceIdIsNotGuid(): void
    {
        $productPriceApiId = 'invalid-' . $this->getPriceList('price_list_1')->getId();
        $response = $this->get(
            ['entity' => 'productprices', 'id' => $productPriceApiId],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetWhenProductListIdIsNotInteger(): void
    {
        $productPriceApiId = $this->getProductPrice('product_price_with_rule_1')->getId() . '-invalid';
        $response = $this->get(
            ['entity' => 'productprices', 'id' => $productPriceApiId],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetWhenIdIsInvalid(): void
    {
        $response = $this->get(
            ['entity' => 'productprices', 'id' => 'invalid'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testGet(): void
    {
        $productPriceApiId = $this->getProductPriceApiId(
            $this->getProductPrice('product_price_with_rule_1'),
            $this->getPriceList('price_list_1')
        );
        $response = $this->get(
            ['entity' => 'productprices', 'id' => $productPriceApiId]
        );

        $this->assertResponseContains('product_price/get.yml', $response);
    }

    public function testUpdate(): void
    {
        $productPriceApiId = $this->getProductPriceApiId(
            $this->getProductPrice('product_price_with_rule_1'),
            $this->getPriceList('price_list_1')
        );
        $response = $this->patch(
            ['entity' => 'productprices', 'id' => $productPriceApiId],
            'product_price/update.yml'
        );

        $priceList1Id = $this->getPriceList('price_list_1')->getId();
        $productPrice = $this->findProductPrice($priceList1Id);
        self::assertNotNull($productPrice);

        self::assertEquals(
            $this->getProductPriceApiId($productPrice, $productPrice->getPriceList()),
            $this->getResourceId($response)
        );

        $priceList2Id = $this->getPriceList('price_list_2')->getId();
        $product1Id = $this->getProduct('product-1')->getId();
        $product5Id = $this->getProduct('product-5')->getId();
        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $priceList1Id => [$product5Id],
                    $priceList2Id => [$product1Id, $product5Id]
                ]
            ]
        );
    }

    public function testUpdateValueOnly(): void
    {
        $productPriceApiId = $this->getProductPriceApiId(
            $this->getProductPrice('product_price_with_rule_2'),
            $this->getPriceList('price_list_1')
        );
        $data = [
            'data' => [
                'type' => 'productprices',
                'id' => $productPriceApiId,
                'attributes' => [
                    'value' => '15.0000'
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'productprices', 'id' => $productPriceApiId], $data);

        $this->assertResponseContains($data, $response);
        $priceList2Id = $this->getPriceList('price_list_2')->getId();
        $product2Id = $this->getProduct('product-2')->getId();
        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            ['product' => [$priceList2Id => [$product2Id]]]
        );
    }

    public function testTryToUpdateWithPriceList(): void
    {
        $productPrice = $this->getProductPrice('product_price_with_rule_1');
        $priceListId = $productPrice->getPriceList()->getId();
        $productPriceApiId = $this->getProductPriceApiId($productPrice, $this->getPriceList('price_list_1'));
        $response = $this->patch(
            ['entity' => 'productprices', 'id' => $productPriceApiId],
            [
                'data' => [
                    'type' => 'productprices',
                    'id' => $productPriceApiId,
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => '<toString(@price_list_3->id)>']
                        ]
                    ]
                ]
            ]
        );

        $data['data']['relationships']['priceList']['data']['id'] = (string)$priceListId;
        $this->assertResponseContains($data, $response);
    }

    public function testTryToUpdateDuplicate(): void
    {
        $productPriceApiId = $this->getProductPriceApiId(
            $this->getProductPrice('product_price_with_rule_1'),
            $this->getPriceList('price_list_1')
        );
        $response = $this->patch(
            ['entity' => 'productprices', 'id' => $productPriceApiId],
            'product_price/update_duplicate.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'unique entity constraint',
                'detail' => 'Product has duplication of product prices.'
                    . ' Set of fields "PriceList", "Quantity" , "Unit" and "Currency" should be unique.'
            ],
            $response
        );
    }

    public function testUpdateResetPriceRule(): void
    {
        $priceList1 = $this->getPriceList('price_list_1');
        $priceList2 = $this->getPriceList('price_list_2');
        $productPrice1 = $this->getProductPrice('product_price_with_rule_1');
        $productPrice1ApiId = $this->getProductPriceApiId($productPrice1, $priceList1);
        $product1 = $this->getProduct('product-1');
        $data = [
            'data' => [
                'type' => 'productprices',
                'id' => $productPrice1ApiId,
                'attributes' => [
                    'value' => '150.0000'
                ]
            ]
        ];
        $this->patch(
            ['entity' => 'productprices', 'id' => $productPrice1ApiId],
            $data
        );

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $priceList2->getId() => [
                        $product1->getId()
                    ]
                ]
            ]
        );

        $productPrice = $this->findProductPriceByUniqueKey(
            5,
            'USD',
            $priceList1,
            $product1,
            $this->getProductUnit('product_unit.liter')
        );

        self::assertNull($productPrice->getPriceRule());
    }

    public function testDelete(): void
    {
        $priceList = $this->getPriceList('price_list_1');
        $product = $this->getProduct('product-1');
        $productPriceApiId = $this->getProductPriceApiId(
            $this->getProductPrice('product_price_with_rule_1'),
            $this->getPriceList('price_list_1')
        );
        $this->delete(
            ['entity' => 'productprices', 'id' => $productPriceApiId]
        );

        $productPrice = $this->findProductPriceByUniqueKey(
            5,
            'USD',
            $priceList,
            $product,
            $this->getProductUnit('product_unit.liter')
        );

        self::assertNull($productPrice);
        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $this->getPriceList('price_list_2')->getId() => [$this->getProduct('product-1')->getId()]
                ]
            ]
        );
    }
}
