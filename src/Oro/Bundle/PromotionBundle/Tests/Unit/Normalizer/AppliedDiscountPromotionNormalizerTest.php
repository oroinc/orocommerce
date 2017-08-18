<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Normalizer;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Normalizer\AppliedDiscountPromotionNormalizer;
use Oro\Bundle\PromotionBundle\Normalizer\RuleNormalizer;
use Oro\Bundle\PromotionBundle\Normalizer\ScopeNormalizer;
use Oro\Bundle\PromotionBundle\Normalizer\SegmentNormalizer;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class AppliedDiscountPromotionNormalizerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RuleNormalizer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ruleNormalizer;

    /**
     * @var ScopeNormalizer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeNormalizer;

    /**
     * @var SegmentNormalizer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $segmentNormalizer;

    /**
     * @var AppliedDiscountPromotionNormalizer
     */
    private $normalizer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->ruleNormalizer = $this->createMock(RuleNormalizer::class);
        $this->scopeNormalizer = $this->createMock(ScopeNormalizer::class);
        $this->segmentNormalizer = $this->createMock(SegmentNormalizer::class);

        $this->normalizer = new AppliedDiscountPromotionNormalizer(
            $this->ruleNormalizer,
            $this->scopeNormalizer,
            $this->segmentNormalizer
        );
    }

    /**
     * @dataProvider normalizeDataProvider
     *
     * @param Promotion $promotion
     * @param array $expected
     */
    public function testNormalize(
        Promotion $promotion,
        array $expected
    ) {
        $this->ruleNormalizer->expects($this->once())
            ->method('normalize')
            ->with($promotion->getRule())
            ->willReturn([
                'name' => 'Promo',
                'expression' => 'currency = "USD"',
                'sortOrder' => 10,
                'isStopProcessing' => false,
            ]);

        $this->scopeNormalizer->expects($this->exactly(2))
            ->method('normalize')
            ->withConsecutive(...array_chunk($promotion->getScopes()->toArray(), 1))
            ->willReturnOnConsecutiveCalls(...$expected['scopes']);

        $this->segmentNormalizer->expects($this->once())
            ->method('normalize')
            ->with($promotion->getProductsSegment())
            ->willReturn([
                'definition' => '{"filters":[],"columns":[{"name":"sku","label":"sku","sorting":null}]}'
            ]);

        $actual = $this->normalizer->normalize($promotion);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function normalizeDataProvider()
    {
        /** @var Scope $firstScope */
        $firstScope = $this->getEntity(Scope::class, ['id' => 42]);

        /** @var Scope $secondScope */
        $secondScope = $this->getEntity(Scope::class, ['id' => 45]);

        $rule = new Rule();
        $rule->setName('Promo')
            ->setExpression('currency = "USD"')
            ->setSortOrder(10)
            ->setStopProcessing(false);

        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType('order');
        $discountConfiguration->setOptions([
            'discount_type' => 'amount',
            'discount_value' => '10.0000',
            'discount_currency' => 'USD'
        ]);

        $segment = new Segment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));
        $segment->setDefinition('{"filters":[],"columns":[{"name":"sku","label":"sku","sorting":null}]}')
            ->setEntity(Product::class);

        /** @var Promotion $promotion */
        $promotion = $this->getEntity(
            Promotion::class,
            [
                'id' => 42,
                'useCoupons' => true,
                'rule' => $rule,
                'scopes' => [$firstScope, $secondScope],
                'discountConfiguration' => $discountConfiguration,
                'productsSegment' => $segment
            ]
        );

        return [
            [
                'promotion' => $promotion,
                'expected' =>  [
                    'id' => 42,
                    'useCoupons' => true,
                    'rule' => [
                        'name' => 'Promo',
                        'expression' => 'currency = "USD"',
                        'sortOrder' => 10,
                        'isStopProcessing' => false,
                    ],
                    'scopes' => [
                        ['id' => 42],
                        ['id' => 45],
                    ],
                    'productsSegment' => [
                        'definition' => '{"filters":[],"columns":[{"name":"sku","label":"sku","sorting":null}]}'
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider denormalizeDataProvider
     *
     * @param array $promotionData
     * @param Rule $rule
     * @param array|Scope[] $scopes
     * @param Segment $segment
     * @param AppliedPromotion $expected
     */
    public function testDenormalize(
        array $promotionData,
        Rule $rule,
        array $scopes,
        Segment $segment,
        AppliedPromotion $expected
    ) {
        $this->ruleNormalizer->expects($this->once())
            ->method('denormalize')
            ->with($promotionData['rule'])
            ->willReturn($rule);

        $this->scopeNormalizer->expects($this->exactly(2))
            ->method('denormalize')
            ->withConsecutive(...array_chunk($promotionData['scopes'], 1))
            ->willReturnOnConsecutiveCalls(...$scopes);

        $this->segmentNormalizer->expects($this->once())
            ->method('denormalize')
            ->with($promotionData['productsSegment'])
            ->willReturn($segment);

        $actual = $this->normalizer->denormalize($promotionData);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function denormalizeDataProvider()
    {
        /** @var Scope $firstScope */
        $firstScope = $this->getEntity(Scope::class, ['id' => 42]);

        /** @var Scope $secondScope */
        $secondScope = $this->getEntity(Scope::class, ['id' => 45]);

        $segment = new Segment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));
        $segment->setDefinition('{"filters":[],"columns":[{"name":"sku","label":"sku","sorting":null}]}')
            ->setEntity(Product::class);

        $rule = new Rule();
        $rule->setName('Promo')
            ->setExpression('currency = "USD"')
            ->setSortOrder(10)
            ->setStopProcessing(false);

        return [
            [
                'promotionData' => [
                    'id' => 42,
                    'useCoupons' => true,
                    'rule' => [
                        'name' => 'Promo',
                        'expression' => 'currency = "USD"',
                        'sortOrder' => 10,
                        'isStopProcessing' => false,
                    ],
                    'scopes' => [
                        ['id' => 42],
                        ['id' => 45]
                    ],
                    'productsSegment' => [
                        'definition' => '{"filters":[],"columns":[{"name":"sku","label":"sku","sorting":null}]}'
                    ]
                ],
                'rule' => $rule,
                'scopes' => [$firstScope, $secondScope],
                'segment' => $segment,
                'expected' => (new AppliedPromotion())->setId(42)
                    ->setUseCoupons(true)
                    ->addScope($firstScope)
                    ->addScope($secondScope)
                    ->setRule($rule)
                    ->setProductsSegment($segment)
            ]
        ];
    }

    public function testRequiredOptionsException()
    {
        $ruleData = [];

        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage(
            'The required options "id", "rule", "scopes", "useCoupons" are missing.'
        );

        $this->normalizer->denormalize($ruleData);
    }


    public function testInvalidArgumentException()
    {
        $object = new \stdClass();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument promotion should be instance of Promotion entity');

        $this->normalizer->normalize($object);
    }
}
