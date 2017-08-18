<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Normalizer;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\PromotionBundle\Normalizer\RuleNormalizer;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class RuleNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RuleNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->normalizer = new RuleNormalizer();
    }

    public function testNormalize()
    {
        $rule = new Rule();
        $rule->setName('Promo')
            ->setExpression('currency = "USD"')
            ->setSortOrder(10)
            ->setStopProcessing(false);

        $expected = [
            'name' => 'Promo',
            'expression' => 'currency = "USD"',
            'sortOrder' => 10,
            'isStopProcessing' => false,
        ];

        $actual = $this->normalizer->normalize($rule);

        $this->assertEquals($expected, $actual);
    }

    public function testDenormalize()
    {
        $ruleData = [
            'name' => 'Promo',
            'expression' => 'currency = "USD"',
            'sortOrder' => 10,
            'isStopProcessing' => false,
        ];

        $expected = new Rule();
        $expected->setName('Promo')
            ->setExpression('currency = "USD"')
            ->setSortOrder(10)
            ->setStopProcessing(false);

        $actual = $this->normalizer->denormalize($ruleData);

        $this->assertEquals($expected, $actual);
    }

    public function testRequiredOptionsException()
    {
        $ruleData = [];

        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage(
            'The required options "expression", "isStopProcessing", "name", "sortOrder" are missing.'
        );

        $this->normalizer->denormalize($ruleData);
    }

    public function testInvalidArgumentException()
    {
        $object = new \stdClass();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument rule should be instance of Rule entity');

        $this->normalizer->normalize($object);
    }
}
