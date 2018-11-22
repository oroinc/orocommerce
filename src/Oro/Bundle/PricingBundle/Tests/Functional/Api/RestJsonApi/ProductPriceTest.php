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

        $this->loadFixtures(
            [
                LoadProductPricesWithRules::class,
            ]
        );
    }

    public function testGetList()
    {
        $parameters = [
            'filter' => [
                'priceList' => ['@price_list_1->id'],
            ],
            'sort' => 'product',
        ];
        $response = $this->cget(['entity' => $this->getEntityName()], $parameters);

        $this->assertResponseContains($this->getAliceFolderName().'/get_list.yml', $response);
    }

    public function testGetListWithTotalCount()
    {
        $parameters = [
            'filter' => [
                'priceList' => ['@price_list_1->id'],
            ],
            'page' => ['size' => 1],
            'sort' => 'product',
        ];
        $response = $this->cget(
            ['entity' => $this->getEntityName()],
            $parameters,
            ['HTTP_X-Include' => 'totalCount']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $this->getEntityName(),
                        'id' => '<(implode("-", [@product_price_with_rule_1->id, @price_list_1->id]))>',
                    ]
                ]
            ],
            $response
        );
        self::assertEquals(2, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListWithoutPriceListFilter()
    {
        $routeParameters = self::processTemplateData(['entity' => $this->getEntityName()]);

        $response = $this->cget($routeParameters, [], [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'priceList filter is required',
            $response->getContent()
        );
    }

    public function testCreate()
    {
        $this->cleanScheduledMessages();

        $response = $this->post(
            ['entity' => $this->getEntityName()],
            $this->getAliceFolderName().'/create.yml'
        );

        $productPrice = $this->getProductPrice('price_list_3');

        static::assertNotNull($productPrice);

        static::assertContains(
            $productPrice->getId().'-'.$productPrice->getPriceList()->getId(),
            $response->getContent()
        );

        $this->assertMessagesSentForCreateRequest('price_list_3');
    }

    public function testCreateDuplicate()
    {
        $routeParameters = self::processTemplateData(['entity' => $this->getEntityName()]);
        $parameters = $this->getRequestData(
            $this->getAliceFolderName().'/create.yml'
        );

        $this->post($routeParameters, $parameters);

        $response = $this->post($routeParameters, $parameters, [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'unique entity constraint',
            $response->getContent()
        );
    }

    public function testCreateWithWrongAttributes()
    {
        $routeParameters = self::processTemplateData(['entity' => $this->getEntityName()]);
        $parameters = $this->getRequestData(
            $this->getAliceFolderName().'/create_wrong.yml'
        );

        $response = $this->post($routeParameters, $parameters, [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'Currency \"EUR\" is not valid for current price list',
            $response->getContent()
        );
        static::assertContains(
            'Unit \"liter\" is not allowed for product',
            $response->getContent()
        );
    }

    public function testDeleteList()
    {
        $this->cleanScheduledMessages();

        $priceList = $this->getReference('price_list_1');

        $this->cdelete(
            ['entity' => $this->getEntityName()],
            [
                'filter' => [
                    'priceList' => $priceList->getId(),
                ]
            ]
        );

        static::assertSame(
            0,
            $this->getEntityManager()->getRepository(ProductPrice::class)->countByPriceList(
                static::getContainer()->get('oro_pricing.shard_manager'),
                $priceList
            )
        );

        $priceListId = $this->getReference('price_list_1')->getId();

        static::assertMessageSent(
            Topics::RESOLVE_COMBINED_PRICES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $priceListId => [
                        $this->getReference('product-1')->getId(),
                    ]
                ],
            ]
        );
        static::assertMessageSent(
            Topics::RESOLVE_COMBINED_PRICES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $priceListId => [
                        $this->getReference('product-2')->getId(),
                    ]
                ],
            ]
        );
    }

    public function testDeleteListWithoutPriceListFilter()
    {
        $routeParameters = self::processTemplateData(
            [
                'entity' => $this->getEntityName(),
            ]
        );

        $response = $this->cdelete($routeParameters, [], [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'At least one filter must be provided.',
            $response->getContent()
        );
    }

    public function testGetWithoutPriceListInId()
    {
        $productPrice = $this->getFirstProductPrice();

        $routeParameters = self::processTemplateData(
            [
                'entity' => $this->getEntityName(),
                'id' => $productPrice->getid(),
            ]
        );

        $response = $this->get($routeParameters, [], [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        static::assertContains(
            'An entity with the requested identifier does not exist.',
            $response->getContent()
        );
    }

    public function testGetWithWrongPriceListInId()
    {
        $productPrice = $this->getFirstProductPrice();

        $routeParameters = self::processTemplateData(
            [
                'entity' => $this->getEntityName(),
                'id' => $productPrice->getId().'-'.$this->getReference('price_list_2')->getId(),
            ]
        );

        $response = $this->get($routeParameters, [], [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        static::assertContains(
            'An entity with the requested identifier does not exist.',
            $response->getContent()
        );
    }

    public function testGetWithNotExistingId()
    {
        $productPrice = $this->getFirstProductPrice();

        $routeParameters = self::processTemplateData(
            [
                'entity' => $this->getEntityName(),
                'id' => $productPrice->getId().'a-'.$this->getReference('price_list_1')->getId(),
            ]
        );

        $response = $this->get($routeParameters, [], [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        static::assertContains(
            'An entity with the requested identifier does not exist.',
            $response->getContent()
        );
    }

    public function testGet()
    {
        $response = $this->get(
            [
                'entity' => $this->getEntityName(),
                'id' => $this->getFirstProductPriceApiId(),
            ]
        );

        $this->assertResponseContains($this->getAliceFolderName().'/get.yml', $response);
    }

    public function testUpdate()
    {
        $this->cleanScheduledMessages();

        $response = $this->patch(
            [
                'entity' => $this->getEntityName(),
                'id' => $this->getFirstProductPriceApiId(),
            ],
            $this->getAliceFolderName().'/update.yml'
        );

        $productPrice = $this->getProductPrice('price_list_1');

        static::assertNotNull($productPrice);

        static::assertContains(
            $productPrice->getId().'-'.$productPrice->getPriceList()->getId(),
            $response->getContent()
        );

        $this->assertMessagesSentForCreateRequest('price_list_1');
    }

    public function testUpdateWithPriceList()
    {
        $routeParameters = self::processTemplateData(
            [
                'entity' => $this->getEntityName(),
                'id' => $this->getFirstProductPriceApiId(),
            ]
        );

        $parameters = $this->getRequestData($this->getAliceFolderName().'/update_with_price_list.yml');

        $response = $this->patch($routeParameters, $parameters, [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'extra fields constraint',
            $response->getContent()
        );
        static::assertContains(
            'This form should not contain extra fields: \"priceList\"',
            $response->getContent()
        );
    }

    public function testUpdateDuplicate()
    {
        $routeParameters = self::processTemplateData(
            [
                'entity' => $this->getEntityName(),
                'id' => $this->getFirstProductPriceApiId(),
            ]
        );

        $parameters = $this->getRequestData($this->getAliceFolderName().'/update_duplicate.yml');

        $response = $this->patch($routeParameters, $parameters, [], false);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'Product has duplication of product prices',
            $response->getContent()
        );
    }

    public function testUpdateResetPriceRule()
    {
        $this->cleanScheduledMessages();

        $this->patch(
            [
                'entity' => $this->getEntityName(),
                'id' => $this->getFirstProductPriceApiId(),
            ],
            $this->getAliceFolderName().'/update_reset_rule.yml'
        );

        $productPrice = $this->findProductPriceByUniqueKey(
            5,
            'USD',
            $this->getReference('price_list_1'),
            $this->getReference('product-1'),
            $this->getReference('product_unit.liter')
        );

        static::assertNull($productPrice->getPriceRule());
    }

    public function testDelete()
    {
        $this->cleanScheduledMessages();

        $this->delete(
            [
                'entity' => $this->getEntityName(),
                'id' => $this->getFirstProductPriceApiId(),
            ]
        );

        $productPrice = $this->findProductPriceByUniqueKey(
            5,
            'USD',
            $this->getReference('price_list_1'),
            $this->getReference('product-1'),
            $this->getReference('product_unit.liter')
        );

        static::assertNull($productPrice);

        static::assertMessageSent(
            Topics::RESOLVE_COMBINED_PRICES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $this->getReference('price_list_1')->getId() => [
                        $this->getReference('product-1')->getId()
                    ]
                ],
            ]
        );
    }

    /**
     * @param string $priceListReferece
     * @return ProductPrice
     */
    private function getProductPrice($priceListReferece)
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
            ->setParameter('priceList', $this->getReference($priceListReferece))
            ->setParameter('product', $this->getReference('product-5'))
            ->setParameter('unit', $this->getReference('product_unit.milliliter'));

        $query = $queryBuilder->getQuery();
        $query->setHint('priceList', $this->getReference($priceListReferece)->getId());
        $query->setHint(
            PriceShardWalker::ORO_PRICING_SHARD_MANAGER,
            static::getContainer()->get('oro_pricing.shard_manager')
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
            static::getContainer()->get('oro_pricing.shard_manager')
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

        static::assertMessageSent(
            Topics::RESOLVE_COMBINED_PRICES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $priceListId => [$productId],
                ]
            ]
        );
        static::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRODUCT => [
                    $priceListId => [$productId],
                ]
            ]
        );
    }

    /**
     * @return string
     */
    private function getFirstProductPriceApiId()
    {
        return $this->getFirstProductPrice()->getId() . '-' . $this->getReference('price_list_1')->getId();
    }

    /**
     * @return ProductPrice
     */
    private function getFirstProductPrice()
    {
        return $this->getReference(LoadProductPricesWithRules::PRODUCT_PRICE_1);
    }

    /**
     * @return string
     */
    private function getEntityName(): string
    {
        return 'productprices';
    }

    /**
     * @return string
     */
    private function getAliceFolderName(): string
    {
        return 'product_price';
    }
}
