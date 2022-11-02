<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\TextFilteredIndexDataProvider;

class TextFilteredIndexDataProviderTest extends IndexDataProviderTest
{
    /**
     * {@inheritDoc}
     */
    protected function createIndexDataProvider(): IndexDataProvider
    {
        return new TextFilteredIndexDataProvider(
            $this->eventDispatcher,
            $this->aliasResolver,
            $this->placeholder,
            $this->tagHelper,
            $this->placeholderHelper
        );
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function entitiesDataProvider(): array
    {
        return array_merge(
            parent::entitiesDataProvider(),
            [
                'simple field with html' => [
                    'entityConfig' => ['fields' => [['name' => 'title', 'type' => Query::TYPE_TEXT]]],
                    'indexData' => [
                        [1, 'title', '<p>SKU-01</p>', ['LOCALIZATION_ID' => 5], true],
                    ],
                    'expected' => [1 => [
                        'text' => ['title' => 'SKU-01', 'all_text_5' => 'SKU-01'],
                        'integer' => ['system_entity_id' => 1]
                    ]],
                ],
                'placeholder field' => [
                    'entityConfig' => [
                        'fields' => [
                            [
                                'name' => 'title_WEBSITE_ID',
                                'type' => Query::TYPE_TEXT,
                            ],
                            [
                                'name' => 'custom_PLACEHOLDER_ID',
                                'type' => Query::TYPE_INTEGER,
                            ],
                        ],
                    ],
                    'indexData' => [
                        [1, 'title_WEBSITE_ID', '<p>SKU-01</p>', ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 5], true],
                        [1, 'custom_42', 42, ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 5], true],
                    ],
                    'expected' => [
                        1 => [
                            'text' => [
                                'title_1' => 'SKU-01',
                                'all_text_5' => 'SKU-01',
                            ],
                            'integer' => [
                                'system_entity_id' => 1,
                                'custom_42' => 42,
                            ]
                        ],
                    ],
                ],
                'multiple placeholder field' => [
                    'entityConfig' => [
                        'fields' => [
                            [
                                'name' => 'title_WEBSITE_ID',
                                'type' => Query::TYPE_TEXT,
                            ],
                            [
                                'name' => 'descr_LOCALIZATION_ID',
                                'type' => Query::TYPE_TEXT,
                            ],
                        ],
                    ],
                    'indexData' => [
                        [1, 'title_WEBSITE_ID', '<p>SKU-01</p>', ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 5], true],
                        [1, 'title_WEBSITE_ID', '<p>SKU-01-gb</p>', ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 6], true],
                        [1, 'descr_LOCALIZATION_ID', '<p>en_US</p>', ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 5], true],
                        [1, 'descr_LOCALIZATION_ID', '<p>en_GB</p>', ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 6], true],
                    ],
                    'expected' => [
                        1 => [
                            'text' => [
                                'title_1' => 'SKU-01 SKU-01-gb',
                                'all_text_5' => 'SKU-01 en_US',
                                'all_text_6' => 'SKU-01-gb en_GB',
                                'descr_5' => 'en_US',
                                'descr_6' => 'en_GB',
                            ],
                            'integer' => [
                                'system_entity_id' => 1,
                            ]
                        ],
                    ],
                ],
                'do not drop value in all_text_localization fields, like metadata' => [
                    'entityConfig' => [
                        'fields' => [
                            [
                                'name' => 'title_WEBSITE_ID',
                                'type' => Query::TYPE_TEXT,
                            ],
                            [
                                'name' => 'descr_LOCALIZATION_ID',
                                'type' => Query::TYPE_TEXT,
                            ],
                            [
                                'name' => 'all_text_LOCALIZATION_ID',
                                'type' => Query::TYPE_TEXT,
                            ],
                        ],
                    ],
                    'indexData' => [
                        [1, 'title_WEBSITE_ID', '<p>SKU-01</p>', ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 5], true],
                        [1, 'title_WEBSITE_ID', '<p>SKU-01-gb</p>', ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 6], true],
                        [1, 'descr_LOCALIZATION_ID', '<p>en_US</p>', ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 5], true],
                        [1, 'descr_LOCALIZATION_ID', '<p>en_GB</p>', ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 6], true],
                        [1, 'all_text_LOCALIZATION_ID', 'title5 descr5 keywords5', ['LOCALIZATION_ID' => 5], true],
                        [1, 'all_text_LOCALIZATION_ID', 'title6 descr6 keywords6', ['LOCALIZATION_ID' => 6], true],
                    ],
                    'expected' => [
                        1 => [
                            'text' => [
                                'title_1' => 'SKU-01 SKU-01-gb',
                                'all_text_5' => 'SKU-01 en_US title5 descr5 keywords5',
                                'all_text_6' => 'SKU-01-gb en_GB title6 descr6 keywords6',
                                'descr_5' => 'en_US',
                                'descr_6' => 'en_GB',
                            ],
                            'integer' => [
                                'system_entity_id' => 1,
                            ]
                        ],
                    ],
                ],
                'all_text has long strings' => [
                    'entityConfig' => [
                        'fields' => [
                            [
                                'name' => 'title',
                                'type' => Query::TYPE_TEXT,
                            ],
                            [
                                'name' => 'description',
                                'type' => Query::TYPE_TEXT,
                            ],
                        ],
                    ],
                    'indexData' => [
                        [1, 'title', 'The long entry', true],
                        [
                            1,
                            'description',
                            'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234' .
                            'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234' .
                            'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234' .
                            'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234' .
                            'QJfPB2teh0ukQN46FehTdiMRMMGGlaNvQvB4ymJq49zUWidBOhT9IzqNyPhYvchY1234' .
                            ' ' .
                            'zUWidBOhT9IzqNyPhYvchY QJfPB2teh0ukQ',
                            ['LOCALIZATION_ID' => 5],
                            true
                        ],
                    ],
                    'expected' => [
                        1 => [
                            'text' => [
                                'title' => 'The long entry',
                                'description' =>
                                    'zUWidBOhT9IzqNyPhYvchY QJfPB2teh0ukQ',
                                'all_text_5' => 'zUWidBOhT9IzqNyPhYvchY QJfPB2teh0ukQ The long entry',
                            ],
                            'integer' => [
                                'system_entity_id' => 1,
                            ]
                        ],
                    ],
                ]
            ]
        );
    }
}
