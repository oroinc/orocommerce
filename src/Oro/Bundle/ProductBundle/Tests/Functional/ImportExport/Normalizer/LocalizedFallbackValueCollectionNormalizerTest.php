<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

class LocalizedFallbackValueCollectionNormalizerTest extends WebTestCase
{
    use EntityTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(array $actualData, array $expectedData = []): void
    {
        $actualData = $this->convertArrayToEntities($actualData);

        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            self::getContainer()->get('doctrine'),
            LocalizedFallbackValue::class,
            Localization::class
        );

        self::assertEquals(
            $expectedData,
            $normalizer->normalize(new ArrayCollection($actualData))
        );
    }

    /**
     * @return array
     */
    public function normalizeDataProvider(): array
    {
        return [
            'without localization' => [
                [
                    [
                        'testEntity' => LocalizedFallbackValue::class,
                        'testProperties' => [
                            'fallback' => 'system',
                            'string' => 'value',
                            'localization' => null,
                        ],
                    ],
                ],
                ['default' => ['fallback' => 'system', 'string' => 'value', 'text' => null]],
            ],
            'localization without name' => [
                [
                    [
                        'testEntity' => LocalizedFallbackValue::class,
                        'testProperties' => [
                            'fallback' => 'system',
                            'text' => 'value',
                            'localization' => ['testEntity' => Localization::class],
                        ],
                    ],
                ],
                ['default' => ['fallback' => 'system', 'string' => null, 'text' => 'value']],
            ],
            'localization with name' => [
                [
                    [
                        'testEntity' => LocalizedFallbackValue::class,
                        'testProperties' => [
                            'fallback' => 'system',
                            'text' => 'value',
                            'localization' => [
                                'testEntity' => Localization::class,
                                'testProperties' => ['name' => 'English'],
                            ],
                        ],
                    ],
                ],
                ['English' => ['fallback' => 'system', 'string' => null, 'text' => 'value']],
            ],
            'mixed' => [
                [
                    [
                        'testEntity' => LocalizedFallbackValue::class,
                        'testProperties' => [
                            'fallback' => 'system',
                            'text' => 'value',
                            'localization' => [
                                'testEntity' => Localization::class,
                                'testProperties' => ['name' => 'English'],
                            ],
                        ],
                    ],
                    [
                        'testEntity' => LocalizedFallbackValue::class,
                        'testProperties' => [
                            'fallback' => 'system',
                            'string' => 'value',
                            'localization' => [
                                'testEntity' => Localization::class,
                                'testProperties' => ['name' => 'English (Canada)'],
                            ],
                        ],
                    ],
                    [
                        'testEntity' => LocalizedFallbackValue::class,
                        'testProperties' => [
                            'fallback' => 'system',
                            'text' => 'value',
                            'localization' => [
                                'testEntity' => Localization::class,
                            ],
                        ],
                    ],
                ],
                [
                    'English' => ['fallback' => 'system', 'string' => null, 'text' => 'value'],
                    'English (Canada)' => ['fallback' => 'system', 'string' => 'value', 'text' => null],
                    'default' => ['fallback' => 'system', 'string' => null, 'text' => 'value'],
                ],
            ],
        ];
    }

    /**
     * @param mixed $actualData
     * @param string $class
     * @param array $expectedData
     *
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalizer(mixed $actualData, string $class, array $expectedData): void
    {
        $expectedData = new ArrayCollection($this->convertArrayToEntities($expectedData));

        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            self::getContainer()->get('doctrine'),
            LocalizedFallbackValue::class,
            Localization::class
        );

        self::assertEquals($expectedData, $normalizer->denormalize($actualData, $class));
    }

    public function denormalizeDataProvider(): array
    {
        return [
            'not and array' => [
                'value',
                LocalizedFallbackValue::class,
                [],
            ],
            'wrong type' => [
                [],
                LocalizedFallbackValue::class,
                [],
            ],
            'type' => [
                [],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                [],
            ],
            'without localization' => [
                ['default' => ['fallback' => 'system', 'string' => 'value', 'text' => null]],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                [
                    'default' => [
                        'testEntity' => LocalizedFallbackValue::class,
                        'testProperties' => [
                            'fallback' => 'system',
                            'string' => 'value',
                        ],
                    ],
                ],
            ],
            'localization with name' => [
                ['English' => ['fallback' => 'system', 'string' => 'value']],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                [
                    'English' => [
                        'testEntity' => LocalizedFallbackValue::class,
                        'testProperties' => [
                            'fallback' => 'system',
                            'string' => 'value',
                            'localization' => [
                                'testEntity' => Localization::class,
                                'testProperties' => ['name' => 'English'],
                            ],
                        ],
                    ],
                ],
            ],
            'mixed' => [
                [
                    'default' => ['fallback' => 'system', 'string' => 'value', 'text' => null],
                    'English' => ['string' => 'value'],
                    'English (Canada)' => ['fallback' => 'parent_localization', 'text' => 'value'],
                ],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                [
                    'default' => [
                        'testEntity' => LocalizedFallbackValue::class,
                        'testProperties' => [
                            'fallback' => 'system',
                            'string' => 'value',
                        ],
                    ],
                    'English' => [
                        'testEntity' => LocalizedFallbackValue::class,
                        'testProperties' => [
                            'string' => 'value',
                            'localization' => [
                                'testEntity' => Localization::class,
                                'testProperties' => ['name' => 'English'],
                            ],
                        ],
                    ],
                    'English (Canada)' => [
                        'testEntity' => LocalizedFallbackValue::class,
                        'testProperties' => [
                            'fallback' => 'parent_localization',
                            'text' => 'value',
                            'localization' => [
                                'testEntity' => Localization::class,
                                'testProperties' => ['name' => 'English (Canada)'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param mixed $data
     * @param bool $expected
     * @param array $context
     *
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization($data, bool $expected, array $context = []): void
    {
        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            self::getContainer()->get('doctrine'),
            ProductName::class,
            Localization::class
        );

        self::assertEquals($expected, $normalizer->supportsNormalization($data, null, $context));

        // trigger caches
        self::assertEquals($expected, $normalizer->supportsNormalization($data, null, $context));
    }

    /**
     * @return array
     */
    public function supportsNormalizationDataProvider(): array
    {
        return [
            'not a collection' => [[], false],
            'collection' => [new ArrayCollection(), false],
            'not existing collection field' => [
                new ArrayCollection(),
                false,
                ['entityName' => Product::class, 'fieldName' => 'names1'],
            ],
            'not supported field' => [
                new ArrayCollection(),
                false,
                ['entityName' => Product::class, 'fieldName' => 'unitPrecisions'],
            ],
            'supported field' => [
                new ArrayCollection(),
                true,
                ['entityName' => Product::class, 'fieldName' => 'names'],
            ],
        ];
    }

    /**
     * @param mixed $data
     * @param string $class
     * @param bool $expected
     * @param array $context
     *
     * @dataProvider supportsdeDenormalizationDataProvider
     */
    public function testSupportsDenormalization(mixed $data, string $class, bool $expected, array $context = []): void
    {
        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            self::getContainer()->get('doctrine'),
            ProductName::class,
            Localization::class
        );

        self::assertEquals($expected, $normalizer->supportsDenormalization($data, $class, null, $context));

        // trigger caches
        self::assertEquals($expected, $normalizer->supportsDenormalization($data, $class, null, $context));
    }

    public function supportsdeDenormalizationDataProvider(): array
    {
        return [
            'not a collection' => [new ArrayCollection(), Product::class, false],
            'not existing collection field' => [
                new ArrayCollection(),
                'ArrayCollection<Oro\Bundle\ProductBundle\Entity\Product>',
                false,
                ['entityName' => Product::class, 'fieldName' => 'names1'],
            ],
            'not supported field' => [
                new ArrayCollection(),
                'ArrayCollection<Oro\Bundle\ProductBundle\Entity\Product>',
                false,
                ['entityName' => Product::class, 'fieldName' => 'unitPrecisions'],
            ],
            'supported field' => [
                new ArrayCollection(),
                'ArrayCollection<Oro\Bundle\ProductBundle\Entity\Product>',
                true,
                ['entityName' => Product::class, 'fieldName' => 'names'],
            ],
            'namespace' => [
                new ArrayCollection(),
                'Doctrine\Common\Collections\ArrayCollection<Oro\Bundle\ProductBundle\Entity\Product>',
                true,
                ['entityName' => Product::class, 'fieldName' => 'names'],
            ],
            'not supported class' => [
                new ArrayCollection(),
                'Doctrine\Common\Collections\ArrayCollection<Oro\Bundle\ProductBundle\Entity\ProductUnit>',
                true,
                ['entityName' => Product::class, 'fieldName' => 'names'],
            ],
        ];
    }
}
