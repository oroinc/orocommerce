<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\ImportExport\Serializer;

use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\ImportExport\Serializer\TaxCodeNormalizer;

class TaxCodeNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider normalizeDataProvider
     * @param object $object
     * @param array $context
     * @param array|null $expected
     */
    public function testNormalize($object, array $context = [], array $expected = null)
    {
        $serializer = new TaxCodeNormalizer();
        $this->assertSame($expected, $serializer->normalize($object, null, $context));
    }

    /**
     * @return array
     */
    public function normalizeDataProvider()
    {
        return [
            'full' => [
                (new ProductTaxCode())->setDescription('descr')->setCode('code'),
                [],
                [
                    'code' => 'code',
                    'description' => 'descr'
                ]
            ],
            'short' => [
                (new ProductTaxCode())->setDescription('descr')->setCode('code'),
                ['mode' => 'short'],
                [
                    'code' => 'code',
                ]
            ],
            'unsupported' => [
                new \stdClass(),
                [],
                null
            ]
        ];
    }

    /**
     * @dataProvider denormalizeDataProvider
     * @param array|string $data
     * @param object $expected
     */
    public function testDenormalize($data, $expected)
    {
        $serializer = new TaxCodeNormalizer();
        $this->assertEquals($expected, $serializer->denormalize($data, ProductTaxCode::class));
    }

    /**
     * @return array
     */
    public function denormalizeDataProvider()
    {
        return [
            'full' => [
                [
                    'code' => 'code',
                    'description' => 'descr'
                ],
                (new ProductTaxCode())->setDescription('descr')->setCode('code'),
            ],
            'short' => [
                ['code' => 'code'],
                (new ProductTaxCode())->setCode('code'),
            ],
            'scalar' => [
                'code',
                (new ProductTaxCode())->setCode('code')
            ]
        ];
    }

    public function testSupportsDenormalization()
    {
        $serializer = new TaxCodeNormalizer();
        $this->assertTrue($serializer->supportsDenormalization([], ProductTaxCode::class));
    }

    public function testDoesNotSupportsDenormalization()
    {
        $serializer = new TaxCodeNormalizer();
        $this->assertFalse($serializer->supportsDenormalization([], \stdClass::class));
    }

    public function testSupportsNormalization()
    {
        $serializer = new TaxCodeNormalizer();
        $this->assertTrue($serializer->supportsNormalization(new ProductTaxCode()));
    }

    public function testDoesNotSupportsNormalization()
    {
        $serializer = new TaxCodeNormalizer();
        $this->assertFalse($serializer->supportsNormalization(new \stdClass()));
    }
}
