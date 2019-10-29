<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPricesWithRules;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductPriceTest extends RestJsonApiTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadProductPricesWithRules::class]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'productprices'],
            ['filter' => ['priceList' => ['@price_list_1->id']], 'sort' => 'product']
        );

        $this->assertResponseContains('product_price/get_list.yml', $response);
    }

    public function testGetListWithTotalCount()
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
                        'id'   => '<(implode("-", [@product_price_with_rule_1->id, @price_list_1->id]))>',
                    ]
                ]
            ],
            $response
        );
        self::assertEquals(2, $response->headers->get('X-Include-Total-Count'));
    }

    public function testTryToGetListWithoutPriceListFilter()
    {
        $response = $this->cget(
            ['entity' => 'productprices'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The "priceList" filter is required.'
            ],
            $response
        );
    }

    public function testCreate()
    {
        $this->cleanScheduledMessages();

        $response = $this->post(
            ['entity' => 'productprices'],
            'product_price/create.yml'
        );

        $productPrice = $this->getProductPrice('price_list_3');
        self::assertNotNull($productPrice);

        self::assertEquals(
            $productPrice->getId() . '-' . $productPrice->getPriceList()->getId(),
            $this->getResourceId($response)
        );

        $this->assertMessagesSentForCreateRequest('price_list_3');
    }

    public function testTryToCreateDuplicate()
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
                'title'  => 'unique entity constraint',
                'detail' => 'Product has duplication of product prices.'
                    . ' Set of fields "PriceList", "Quantity" , "Unit" and "Currency" should be unique.'
            ],
            $response
        );
    }

    public function testTryToCreateEmptyValue()
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
                'title'  => 'not blank constraint',
                'detail' => 'Price value should not be blank.',
                'source' => ['pointer' => '/data/attributes/value']
            ],
            $response
        );
    }

    public function testTryToCreateEmptyCurrency()
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
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/currency']
            ],
            $response
        );
    }

    public function testTryToCreateWrongValue()
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
                'title'  => 'type constraint',
                'detail' => 'This value should be of type numeric.',
                'source' => ['pointer' => '/data/attributes/value']
            ],
            $response
        );
    }

    public function testTryToCreateWrongCurrency()
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
                'title'  => 'product price currency constraint',
                'detail' => 'Currency "EUR" is not valid for current price list.',
                'source' => ['pointer' => '/data/attributes/currency']
            ],
            $response
        );
    }

    public function testTryToCreateWrongProductUnit()
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
                'title'  => 'product price allowed units constraint',
                'detail' => 'Unit "liter" is not allowed for product "product-5".',
                'source' => ['pointer' => '/data/relationships/unit/data']
            ],
            $response
        );
    }

    public function testDeleteList()
    {
        $this->cleanScheduledMessages();

        $priceList = $this->getReference('price_list_1');
        $product1Id = $this->getReference('product-1')->getId();
        $product2Id = $this->getReference('product-2')->getId();

        $this->cdelete(
            ['entity' => 'productprices'],
            ['filter' => ['priceList' => $priceList->getId()]]
        );

        self::assertSame(
            0,
            $this->getEntityManager()->getRepository(ProductPrice::class)->countByPriceList(
                self::getContainer()->get('oro_pricing.shard_manager'),
                $priceList
            )
        );

        $priceListId = $this->getReference('price_list_1')->getId();

        $message = self::getSentMessage(Topics::RESOLVE_COMBINED_PRICES);
        self::assertIsArray($message);
        self::assertArrayHasKey('product', $message);
        self::assertArrayHasKey($priceListId, $message['product']);
        $productIds = $message['product'][$priceListId];
        sort($productIds);
        self::assertEquals([$product1Id, $product2Id], $productIds);
    }

    public function testTryToDeleteListWithoutPriceListFilter()
    {
        $response = $this->cdelete(
            ['entity' => 'productprices'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'At least one filter must be provided.'
            ],
            $response
        );
    }

    public function testTryToGetWithoutPriceListInId()
    {
        $response = $this->get(
            ['entity' => 'productprices', 'id' => $this->getFirstProductPrice()->getId()],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetWithWrongPriceListInId()
    {
        $response = $this->get(
            ['entity' => 'productprices', 'id' => $this->getFirstProductPriceApiId('price_list_2')],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetWithNotExistingId()
    {
        $notExistingId = $this->getFirstProductPrice()->getId() . 'a-' . $this->getReference('price_list_1')->getId();
        $response = $this->get(
            ['entity' => 'productprices', 'id' => $notExistingId],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'productprices', 'id' => $this->getFirstProductPriceApiId()]
        );

        $this->assertResponseContains('product_price/get.yml', $response);
    }

    public function testUpdate()
    {
        $this->cleanScheduledMessages();

        $response = $this->patch(
            ['entity' => 'productprices', 'id' => $this->getFirstProductPriceApiId()],
            'product_price/update.yml'
        );

        $productPrice = $this->getProductPrice('price_list_1');
        self::assertNotNull($productPrice);

        self::assertEquals(
            $productPrice->getId() . '-' . $productPrice->getPriceList()->getId(),
            $this->getResourceId($response)
        );

        $this->assertMessagesSentForCreateRequest('price_list_1');
    }

    public function testTryToUpdateWithPriceList()
    {
        $productPriceId = LoadProductPricesWithRules::PRODUCT_PRICE_1;
        $priceListId = $this->getReference($productPriceId)->getPriceList()->getId();
        $response = $this->patch(
            ['entity' => 'productprices', 'id' => $this->getFirstProductPriceApiId()],
            [
                'data' => [
                    'type'          => 'productprices',
                    'id'            => $this->getFirstProductPriceApiId(),
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
        self::assertSame(
            $priceListId,
            $this->getReference($productPriceId)->getPriceList()->getId()
        );
    }

    public function testTryToUpdateDuplicate()
    {
        $response = $this->patch(
            ['entity' => 'productprices', 'id' => $this->getFirstProductPriceApiId()],
            'product_price/update_duplicate.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'unique entity constraint',
                'detail' => 'Product has duplication of product prices.'
                    . ' Set of fields "PriceList", "Quantity" , "Unit" and "Currency" should be unique.'
            ],
            $response
        );
    }

    public function testUpdateResetPriceRule()
    {
        $this->cleanScheduledMessages();

        $this->patch(
            ['entity' => 'productprices', 'id' => $this->getFirstProductPriceApiId()],
            'product_price/update_reset_rule.yml'
        );

        $productPrice = $this->findProductPriceByUniqueKey(
            5,
            'USD',
            $this->getReference('price_list_1'),
            $this->getReference('product-1'),
            $this->getReference('product_unit.liter')
        );

        self::assertNull($productPrice->getPriceRule());
    }

    public function testDelete()
    {
        $this->cleanScheduledMessages();

        $this->delete(
            ['entity' => 'productprices', 'id' => $this->getFirstProductPriceApiId()]
        );

        $productPrice = $this->findProductPriceByUniqueKey(
            5,
            'USD',
            $this->getReference('price_list_1'),
            $this->getReference('product-1'),
            $this->getReference('product_unit.liter')
        );

        self::assertNull($productPrice);

        self::assertMessageSent(
            Topics::RESOLVE_COMBINED_PRICES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $this->getReference('price_list_1')->getId() => [$this->getReference('product-1')->getId()]
                ]
            ]
        );
    }

    /**
     * @param string $priceListReference
     *
     * @return ProductPrice
     */
    private function getProductPrice($priceListReference)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository(ProductPrice::class)
            ->createQueryBuilder('price');

        $queryBuilder
            ->andWhere('price.quantity = :quantity')
            ->andWhere('price.value = :value')
            ->andWhere('price.currency = :currency')
            ->andWhere('price.priceList = :priceList')
            ->andWhere('price.product = :product')
            ->andWhere('price.unit = :unit')
            ->setParameter('quantity', 250)
            ->setParameter('value', 150)
            ->setParameter('currency', 'CAD')
            ->setParameter('priceList', $this->getReference($priceListReference))
            ->setParameter('product', $this->getReference('product-5'))
            ->setParameter('unit', $this->getReference('product_unit.milliliter'));

        $query = $queryBuilder->getQuery();
        $query->setHint('priceList', $this->getReference($priceListReference)->getId());
        $query->setHint(
            PriceShardWalker::ORO_PRICING_SHARD_MANAGER,
            self::getContainer()->get('oro_pricing.shard_manager')
        );
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        return $query->getOneOrNullResult();
    }

    /**
     * @param int         $quantity
     * @param string      $currency
     * @param PriceList   $priceList
     * @param Product     $product
     * @param ProductUnit $unit
     *
     * @return ProductPrice|null
     */
    private function findProductPriceByUniqueKey(
        int $quantity,
        string $currency,
        PriceList $priceList,
        Product $product,
        ProductUnit $unit
    ) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository(ProductPrice::class)
            ->createQueryBuilder('price');

        $queryBuilder
            ->andWhere('price.quantity = :quantity')
            ->andWhere('price.currency = :currency')
            ->andWhere('price.priceList = :priceList')
            ->andWhere('price.product = :product')
            ->andWhere('price.unit = :unit')
            ->setParameter('quantity', $quantity)
            ->setParameter('currency', $currency)
            ->setParameter('priceList', $priceList)
            ->setParameter('product', $product)
            ->setParameter('unit', $unit);

        $query = $queryBuilder->getQuery();
        $query->useQueryCache(false);
        $query->setHint('priceList', $this->getReference('price_list_3')->getId());
        $query->setHint(
            PriceShardWalker::ORO_PRICING_SHARD_MANAGER,
            self::getContainer()->get('oro_pricing.shard_manager')
        );
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        return $query->getOneOrNullResult();
    }

    /**
     * @param string $priceListReference
     */
    private function assertMessagesSentForCreateRequest($priceListReference)
    {
        $productId = $this->getReference('product-5')->getId();
        $priceListId = $this->getReference($priceListReference)->getId();

        self::assertMessageSent(
            Topics::RESOLVE_COMBINED_PRICES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $priceListId => [$productId],
                ]
            ]
        );
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $priceListId => [$productId],
                ]
            ]
        );
    }

    /**
     * @param string $priceListReference
     *
     * @return string
     */
    private function getFirstProductPriceApiId($priceListReference = 'price_list_1')
    {
        return $this->getFirstProductPrice()->getId() . '-' . $this->getReference($priceListReference)->getId();
    }

    /**
     * @return ProductPrice
     */
    private function getFirstProductPrice()
    {
        return $this->getReference(LoadProductPricesWithRules::PRODUCT_PRICE_1);
    }
}
