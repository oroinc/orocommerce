<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Normalizer;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Normalizer\SegmentNormalizer;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SegmentNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SegmentNormalizer
     */
    protected $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new SegmentNormalizer();
    }

    public function testNormalize()
    {
        $segment = new Segment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC))
            ->setDefinition('{"filters":[],"columns":[{"name":"sku","label":"sku","sorting":null}]}')
            ->setEntity(Product::class);

        $expected = [
            'definition' => '{"filters":[],"columns":[{"name":"sku","label":"sku","sorting":null}]}'
        ];

        $actual = $this->normalizer->normalize($segment);

        $this->assertEquals($expected, $actual);
    }

    public function testDenormalize()
    {
        $segmentData = [
            'definition' => '{"filters":[],"columns":[{"name":"sku","label":"sku","sorting":null}]}'
        ];

        $expected = new Segment();
        $expected->setType(new SegmentType(SegmentType::TYPE_DYNAMIC))
            ->setDefinition('{"filters":[],"columns":[{"name":"sku","label":"sku","sorting":null}]}')
            ->setEntity(Product::class);

        $actual = $this->normalizer->denormalize($segmentData);

        $this->assertEquals($expected, $actual);
    }

    public function testRequiredOptionsException()
    {
        $ruleData = [];

        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "definition" is missing.');

        $this->normalizer->denormalize($ruleData);
    }

    public function testInvalidArgumentException()
    {
        $object = new \stdClass();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument segment should be instance of Segment entity');

        $this->normalizer->normalize($object);
    }
}
