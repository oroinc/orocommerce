<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PriceRuleTest extends RestJsonApiTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadPriceRules::class]);
    }

    public function testGetList()
    {
        $parameters = [
            'filter' => [
                'id' => ['@price_list_1_price_rule_1->id', '@price_list_1_price_rule_2->id'],
            ],
            'sort' => 'id',
        ];

        $response = $this->cget(['entity' => 'pricerules'], $parameters);

        $this->assertResponseContains('price_rule/get_list.yml', $response);
    }

    public function testCreateMixValuesWithExpressions()
    {
        $routeParameters = self::processTemplateData(['entity' => 'pricerules']);
        $parameters = $this->getRequestData('price_rule/create_values_with_expressions.yml');
        $response = $this->post($routeParameters, $parameters, [], false);

        self::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        self::assertStringContainsString(
            'One of fields: Currency, Currency Expression should be blank',
            $response->getContent()
        );
        self::assertStringContainsString(
            'One of fields: Quantity, Quantity Expression should be blank',
            $response->getContent()
        );
        self::assertStringContainsString(
            'One of fields: Product Unit, Product Unit Expression should be blank',
            $response->getContent()
        );
    }

    public function testCreateRequiredFieldsBlank()
    {
        $routeParameters = self::processTemplateData(['entity' => 'pricerules']);
        $parameters = $this->getRequestData('price_rule/create_required_fields_blank.yml');
        $response = $this->post($routeParameters, $parameters, [], false);

        self::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        self::assertStringContainsString(
            'One of fields: Currency, Currency Expression is required',
            $response->getContent()
        );
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => 'pricerules'],
            'price_rule/create.yml'
        );

        $priceRule = $this->getEntityManager()
            ->getRepository(PriceRule::class)
            ->findOneBy(['rule' => 'pricelist[1].prices.value * 0.8']);

        self::assertSame('EUR', $priceRule->getCurrency());
        self::assertNull($priceRule->getCurrencyExpression());
        self::assertEquals(1, $priceRule->getQuantity());
        self::assertNull($priceRule->getQuantityExpression());
        self::assertNull($priceRule->getProductUnitExpression());
        self::assertSame('product.category.id == 1', $priceRule->getRuleCondition());

        self::assertEquals(
            $this->getReference(LoadProductUnits::BOX),
            $priceRule->getProductUnit()
        );

        self::assertEquals(
            $this->getReference(LoadPriceLists::PRICE_LIST_3),
            $priceRule->getPriceList()
        );

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [$priceRule->getPriceList()->getId() => []],
            ]
        );

        $this->assertLexemesCreated($priceRule->getPriceList());
    }

    public function testCreateWithExpressions()
    {
        $this->post(
            ['entity' => 'pricerules'],
            'price_rule/create_with_expressions.yml'
        );

        $priceRule = $this->getEntityManager()
            ->getRepository(PriceRule::class)
            ->findOneBy(['rule' => 'pricelist[1].prices.value * 0.8']);

        self::assertSame('pricelist[1].prices.currency', $priceRule->getCurrencyExpression());
        self::assertEmpty($priceRule->getCurrency());
        self::assertSame('pricelist[1].prices.quantity + 3', $priceRule->getQuantityExpression());
        self::assertNull($priceRule->getQuantity());
        self::assertNull($priceRule->getProductUnit());
        self::assertSame('pricelist[1].prices.unit', $priceRule->getProductUnitExpression());
        self::assertSame('product.category.id == 1', $priceRule->getRuleCondition());

        self::assertEquals(
            $this->getReference(LoadPriceLists::PRICE_LIST_3),
            $priceRule->getPriceList()
        );

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [$priceRule->getPriceList()->getId() => []],
            ]
        );

        $this->assertLexemesCreated($priceRule->getPriceList());
    }

    public function testCreateAsIncludedData()
    {
        $priceListId = $this->getReference('price_list_3')->getId();

        $this->patch(
            ['entity' => 'pricelists', 'id' => (string)$priceListId],
            [
                'data'     => [
                    'type'          => 'pricelists',
                    'id'            => (string)$priceListId,
                    'relationships' => [
                        'priceRules' => [
                            'data' => [
                                ['type' => 'pricerules', 'id' => 'new_rule']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'pricerules',
                        'id'            => 'new_rule',
                        'attributes'    => [
                            'currency' => 'EUR',
                            'quantity' => 1,
                            'priority' => 10,
                            'rule'     => 'pricelist[1].prices.value * 0.8'
                        ],
                        'relationships' => [
                            'productUnit' => [
                                'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.box->code)>']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $priceRule = $this->getEntityManager()
            ->getRepository(PriceRule::class)
            ->findOneBy(['rule' => 'pricelist[1].prices.value * 0.8']);

        self::assertEquals($priceListId, $priceRule->getPriceList()->getId());
        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            ['product' => [$priceRule->getPriceList()->getId() => []]]
        );
        $this->assertLexemesCreated($priceRule->getPriceList());
    }

    public function testDeleteList()
    {
        $priceRuleId1 = $this->getFirstPriceRule()->getId();
        $priceList1 = $this->getFirstPriceRule()->getPriceList();

        $priceRuleId2 = $this->getReference(LoadPriceRules::PRICE_RULE_2)->getId();
        /** @var PriceList $priceList2 */
        $priceList2 = $this->getReference(LoadPriceRules::PRICE_RULE_2)->getPriceList();

        $this->cdelete(
            ['entity' => 'pricerules'],
            [
                'filter' => [
                    'id' => [$priceRuleId1, $priceRuleId2]
                ]
            ]
        );
        $this->assertNull(
            $this->getEntityManager()->find(PriceRule::class, $priceRuleId1)
        );
        $this->assertNull(
            $this->getEntityManager()->find(PriceRule::class, $priceRuleId2)
        );

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [$priceList1->getId() => []],
            ]
        );

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [$priceList2->getId() => []],
            ]
        );
    }

    public function testGet()
    {
        $priceRuleId = $this->getFirstPriceRule()->getId();

        $response = $this->get(
            ['entity' => 'pricerules', 'id' => $priceRuleId]
        );
        $this->assertResponseContains('price_rule/get.yml', $response);
    }

    public function testUpdate()
    {
        $priceRuleId = $this->getFirstPriceRule()->getId();

        $this->patch(
            ['entity' => 'pricerules', 'id' => (string) $priceRuleId],
            'price_rule/update.yml'
        );

        $updatedPriceRule = $this->getEntityManager()
            ->getRepository(PriceRule::class)
            ->find($priceRuleId);

        self::assertNull($updatedPriceRule->getQuantity());
        self::assertSame('pricelist[1].prices.quantity + 4', $updatedPriceRule->getQuantityExpression());
        self::assertSame('product.category.id > 0', $updatedPriceRule->getRuleCondition());
        self::assertSame('pricelist[1].prices.value * 1', $updatedPriceRule->getRule());
        self::assertSame(5, $updatedPriceRule->getPriority());

        self::assertEquals(
            $this->getReference(LoadProductUnits::BOTTLE),
            $updatedPriceRule->getProductUnit()
        );

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $updatedPriceRule->getPriceList()->getId() => []
                ],
            ]
        );
    }

    public function testDelete()
    {
        $priceRuleId = $this->getFirstPriceRule()->getId();
        $priceList = $this->getFirstPriceRule()->getPriceList();

        $this->delete([
            'entity' => 'pricerules',
            'id' => $priceRuleId,
        ]);

        $this->assertNull(
            $this->getEntityManager()->find(PriceRule::class, $priceRuleId)
        );

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [$priceList->getId() => []],
            ]
        );
    }

    public function testGetSubResources()
    {
        $priceRule = $this->getFirstPriceRule();

        $this->assertGetSubResource(
            $priceRule->getId(),
            'priceList',
            $priceRule->getPriceList()->getId()
        );

        $this->assertGetSubResource(
            $priceRule->getId(),
            'productUnit',
            $priceRule->getProductUnit()->getCode()
        );
    }

    public function testGetRelationships()
    {
        $priceRule = $this->getFirstPriceRule();

        $response = $this->getRelationship([
            'entity' => 'pricerules',
            'id' => $priceRule->getId(),
            'association' => 'priceList',
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'pricelists',
                    'id' => (string) $priceRule->getPriceList()->getId(),
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity' => 'pricerules',
            'id' => $priceRule->getId(),
            'association' => 'productUnit',
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => $priceRule->getProductUnit()->getCode(),
                ]
            ],
            $response
        );
    }

    private function getFirstPriceRule(): PriceRule
    {
        return $this->getReference(LoadPriceRules::PRICE_RULE_1);
    }

    private function assertLexemesCreated(PriceList $priceList): void
    {
        $lexeme = $this->getEntityManager()
            ->getRepository(PriceRuleLexeme::class)
            ->findOneBy(['priceList' => $priceList]);

        self::assertNotNull($lexeme);
    }

    private function assertGetSubResource(
        int $entityId,
        string $associationName,
        string $expectedAssociationId
    ): void {
        $response = $this->getSubresource(
            ['entity' => 'pricerules', 'id' => $entityId, 'association' => $associationName]
        );

        $result = self::jsonToArray($response->getContent());

        self::assertEquals($expectedAssociationId, $result['data']['id']);
    }
}
