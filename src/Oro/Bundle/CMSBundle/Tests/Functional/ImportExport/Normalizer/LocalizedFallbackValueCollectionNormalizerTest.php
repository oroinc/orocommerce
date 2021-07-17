<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\ImportExport\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\ImportExport\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

class LocalizedFallbackValueCollectionNormalizerTest extends WebTestCase
{
    use EntityTrait;

    /** @var LocalizedFallbackValueCollectionNormalizer */
    private $normalizer;

    /** {@inheritdoc} */
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->normalizer = new LocalizedFallbackValueCollectionNormalizer(
            $this->getContainer()->get('doctrine'),
            LocalizedFallbackValue::class,
            Localization::class
        );
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(array $actualData, array $expectedData = []): void
    {
        $actualData = $this->convertArrayToEntities($actualData);

        $this->assertEquals($expectedData, $this->normalizer->normalize(new ArrayCollection($actualData)));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeDataProvider(): array
    {
        return [
            'without localization' => [
                [
                    [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'wysiwyg' => 'value',
                            'localization' => null
                        ]
                    ],
                ],
                ['default' => ['fallback' => 'system', 'string' => null, 'text' => null, 'wysiwyg' => 'value']],
            ],
            'localization without name' => [
                [
                    [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'wysiwyg' => 'value',
                            'localization' => ['testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization'],
                        ]
                    ],
                ],
                ['default' => ['fallback' => 'system', 'string' => null, 'text' => null, 'wysiwyg' => 'value']],
            ],
            'localization with name' => [
                [
                    [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'wysiwyg' => 'value',
                            'localization' => [
                                'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization',
                                'testProperties' => ['name' => 'English']
                            ],
                        ]
                    ],
                ],
                ['English' => ['fallback' => 'system', 'string' => null, 'text' => null, 'wysiwyg' => 'value']],
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
                            'wysiwyg' => 'value',
                            'localization' => [
                                'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization',
                                'testProperties' => ['name' => 'French (France)']
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
                    'English' => ['fallback' => 'system', 'string' => null, 'text' => 'value', 'wysiwyg' => null],
                    'English (Canada)' => [
                        'fallback' => 'system',
                        'string' => 'value',
                        'text' => null,
                        'wysiwyg' => null
                    ],
                    'French (France)' => [
                        'fallback' => 'system',
                        'string' => null,
                        'text' => null,
                        'wysiwyg' => 'value'
                    ],
                    'default' => ['fallback' => 'system', 'string' => null, 'text' => 'value', 'wysiwyg' => null],
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
    public function testDenormalizer($actualData, $class, array $expectedData): void
    {
        $expectedData = new ArrayCollection($this->convertArrayToEntities($expectedData));

        $this->assertEquals($expectedData, $this->normalizer->denormalize($actualData, $class));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function denormalizeDataProvider(): array
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
                ['default' => ['fallback' => 'system', 'string' => null, 'text' => null, 'wysiwyg' => 'value']],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                [
                    'default' => [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'wysiwyg' => 'value',
                        ],
                    ],
                ]
            ],
            'localization with name' => [
                ['English' => ['fallback' => 'system', 'string' => null, 'text' => null, 'wysiwyg' => 'value']],
                'ArrayCollection<Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue>',
                [
                    'English' => [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'system',
                            'wysiwyg' => 'value',
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
                    'default' => ['fallback' => 'system', 'string' => 'value', 'text' => null, 'wysiwyg' => null],
                    'English' => ['string' => 'value', 'wysiwyg' => null],
                    'English (Canada)' => ['fallback' => 'parent_localization', 'text' => 'value', 'wysiwyg' => null],
                    'French (France)' => [
                        'fallback' => 'parent_localization',
                        'string' => null,
                        'text' => null,
                        'wysiwyg' => 'value'
                    ],
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
                            'fallback' => 'parent_localization',
                            'text' => 'value',
                            'localization' => [
                                'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization',
                                'testProperties' => ['name' => 'English (Canada)']
                            ],
                        ],
                    ],
                    'French (France)' => [
                        'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue',
                        'testProperties' => [
                            'fallback' => 'parent_localization',
                            'wysiwyg' => 'value',
                            'localization' => [
                                'testEntity' => 'Oro\Bundle\LocaleBundle\Entity\Localization',
                                'testProperties' => ['name' => 'French (France)']
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }
}
