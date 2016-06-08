<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @dbIsolation
 */
class LocalizedFallbackValueCollectionNormalizerTest extends WebTestCase
{
    use EntityTrait;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * @param array $actualData
     * @param array $expectedData
     *
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(array $actualData, array $expectedData = [])
    {
        $actualData = $this->convertArrayToEntities($actualData);

        /** @var LocalizedFallbackValueCollectionNormalizer $normalizer */
        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->getParameter('oro_locale.entity.localized_fallback_value.class'),
            $this->getContainer()->getParameter('oro_locale.entity.localization.class')
        );

        $this->assertEquals(
            $expectedData,
            $normalizer->normalize(new ArrayCollection($actualData))
        );
    }

    /**
     * @return array
     */
    public function normalizeDataProvider()
    {
        return [
            'without localization' => [
                [
                    [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'string' => 'value',
                            'localization' => null
                        ]
                    ],
                ],
                ['default' => ['fallback' => 'system', 'string' => 'value', 'text' => null]],
            ],
            'localization without name' => [
                [
                    [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'text' => 'value',
                            'localization' => ['testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization'],
                        ]
                    ],
                ],
                ['default' => ['fallback' => 'system', 'string' => null, 'text' => 'value']],
            ],
            'localization with name' => [
                [
                    [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'text' => 'value',
                            'localization' => [
                                'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization',
                                'testProperties' => ['name' => 'English']
                            ],
                        ]
                    ],
                ],
                ['English' => ['fallback' => 'system', 'string' => null, 'text' => 'value']],
            ],
            'mixed' => [
                [
                    [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'text' => 'value',
                            'localization' => [
                                'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization',
                                'testProperties' => ['name' => 'English']
                            ],
                        ],
                    ],
                    [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'string' => 'value',
                            'localization' => [
                                'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization',
                                'testProperties' => ['name' => 'English (Canada)']
                            ],
                        ],
                    ],
                    [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'text' => 'value',
                            'localization' => [
                                'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization',
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
    public function testDenormalizer($actualData, $class, array $expectedData)
    {
        $expectedData = new ArrayCollection($this->convertArrayToEntities($expectedData));

        /** @var LocalizedFallbackValueCollectionNormalizer $normalizer */
        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->getParameter('oro_locale.entity.localized_fallback_value.class'),
            $this->getContainer()->getParameter('oro_locale.entity.localization.class')
        );

        $this->assertEquals($expectedData, $normalizer->denormalize($actualData, $class));
    }

    /**
     * @return array
     */
    public function denormalizeDataProvider()
    {
        return [
            'not and array' => [
                'value',
                'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                [],
            ],
            'wrong type' => [
                [],
                'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
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
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'string' => 'value',
                        ],
                    ],
                ]
            ],
            'localization with name' => [
                ['English' => ['fallback' => 'system', 'string' => 'value']],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                [
                    'English' => [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'string' => 'value',
                            'localization' => [
                                'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization',
                                'testProperties' => ['name' => 'English']
                            ],
                        ],
                    ],
                ]
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
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'string' => 'value',
                        ],
                    ],
                    'English' => [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'string' => 'value',
                            'localization' => [
                                'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization',
                                'testProperties' => ['name' => 'English']
                            ],
                        ],
                    ],
                    'English (Canada)' => [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'parent_locale',
                            'text' => 'value',
                            'localization' => [
                                'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization',
                                'testProperties' => ['name' => 'English (Canada)']
                            ],
                        ],
                    ],
                ]
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
    public function testSupportsNormalization($data, $expected, array $context = [])
    {
        if (!$this->getContainer()->hasParameter('orob2b_product.entity.product.class')) {
            $this->markTestSkipped('ProductBundle required');
        }

        /** @var LocalizedFallbackValueCollectionNormalizer $normalizer */
        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->getParameter('oro_locale.entity.localized_fallback_value.class'),
            $this->getContainer()->getParameter('oro_locale.entity.localization.class')
        );

        $this->assertEquals($expected, $normalizer->supportsNormalization($data, [], $context));

        // trigger caches
        $this->assertEquals($expected, $normalizer->supportsNormalization($data, [], $context));
    }

    /**
     * @return array
     */
    public function supportsNormalizationDataProvider()
    {
        return [
            'not a collection' => [[], false],
            'collection' => [new ArrayCollection(), false],
            'not existing collection field' => [
                new ArrayCollection(),
                false,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names1'],
            ],
            'not supported field' => [
                new ArrayCollection(),
                false,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'unitPrecisions'],
            ],
            'supported field' => [
                new ArrayCollection(),
                true,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names'],
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
    public function testSupportsDenormalization($data, $class, $expected, array $context = [])
    {
        if (!$this->getContainer()->hasParameter('orob2b_product.entity.product.class')) {
            $this->markTestSkipped('ProductBundle required');
        }

        /** @var LocalizedFallbackValueCollectionNormalizer $normalizer */
        $normalizer = new LocalizedFallbackValueCollectionNormalizer(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->getParameter('oro_locale.entity.localized_fallback_value.class'),
            $this->getContainer()->getParameter('oro_locale.entity.localization.class')
        );

        $this->assertEquals($expected, $normalizer->supportsDenormalization($data, $class, [], $context));

        // trigger caches
        $this->assertEquals($expected, $normalizer->supportsDenormalization($data, $class, [], $context));
    }

    /**
     * @return array
     */
    public function supportsdeDenormalizationDataProvider()
    {
        return [
            'not a collection' => [new ArrayCollection(), 'OroB2B\Bundle\ProductBundle\Entity\Product', false],
            'not existing collection field' => [
                new ArrayCollection(),
                'ArrayCollection<OroB2B\Bundle\ProductBundle\Entity\Product>',
                false,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names1'],
            ],
            'not supported field' => [
                new ArrayCollection(),
                'ArrayCollection<OroB2B\Bundle\ProductBundle\Entity\Product>',
                false,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'unitPrecisions'],
            ],
            'supported field' => [
                new ArrayCollection(),
                'ArrayCollection<OroB2B\Bundle\ProductBundle\Entity\Product>',
                true,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names'],
            ],
            'namespace' => [
                new ArrayCollection(),
                'Doctrine\Common\Collections\ArrayCollection<OroB2B\Bundle\ProductBundle\Entity\Product>',
                true,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names'],
            ],
            'not supported class' => [
                new ArrayCollection(),
                'Doctrine\Common\Collections\ArrayCollection<OroB2B\Bundle\ProductBundle\Entity\ProductUnit>',
                true,
                ['entityName' => 'OroB2B\Bundle\ProductBundle\Entity\Product', 'fieldName' => 'names'],
            ],
        ];
    }
}
