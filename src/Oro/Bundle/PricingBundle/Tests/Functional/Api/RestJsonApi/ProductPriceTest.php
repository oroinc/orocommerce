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

    public function testGetListWithoutPriceListFilter()
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
                'detail' => 'priceList filter is required'
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

    public function testCreateDuplicate()
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

    public function testCreateWithWrongAttributes()
    {
        $response = $this->post(
            ['entity' => 'productprices'],
            'product_price/create_wrong.yml',
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'product price currency constraint',
                    'detail' => 'Currency "EUR" is not valid for current price list. Source: price.currency.'
                ],
                [
                    'title'  => 'product price allowed units constraint',
                    'detail' => 'Unit "liter" is not allowed for product "product-5".',
                    'source' => ['pointer' => '/data/relationships/unit/data']
                ]
            ],
            $response
        );
    }

    public function testDeleteList()
    {
        $this->cleanScheduledMessages();

        $priceList = $this->getReference('price_list_1');

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

        self::assertMessageSent(
            Topics::RESOLVE_COMBINED_PRICES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $priceListId => [$this->getReference('product-1')->getId()]
                ]
            ]
        );
        self::assertMessageSent(
            Topics::RESOLVE_COMBINED_PRICES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $priceListId => [$this->getReference('product-2')->getId()]
                ]
            ]
        );
    }

    public function testDeleteListWithoutPriceListFilter()
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

    public function testGetWithoutPriceListInId()
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

    public function testGetWithWrongPriceListInId()
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

    public function testGetWithNotExistingId()
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

    public function testUpdateWithPriceList()
    {
        $response = $this->patch(
            ['entity' => 'productprices', 'id' => $this->getFirstProductPriceApiId()],
            'product_price/update_with_price_list.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'extra fields constraint',
                'detail' => 'This form should not contain extra fields: "priceList".'
            ],
            $response
        );
    }

    public function testUpdateDuplicate()
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
