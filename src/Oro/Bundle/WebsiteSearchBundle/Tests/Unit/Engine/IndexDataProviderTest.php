<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\CollectContextEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Helper\PlaceholderHelper;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class IndexDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var IndexDataProvider */
    protected $indexDataProvider;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var EntityAliasResolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $aliasResolver;

    /** @var PlaceholderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $placeholder;

    /** @var HtmlTagHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $tagHelper;

    /** @var PlaceholderHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $placeholderHelper;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->aliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->placeholder = $this->createMock(PlaceholderInterface::class);
        $this->tagHelper = $this->createMock(HtmlTagHelper::class);

        $this->placeholderHelper = $this->createMock(PlaceholderHelper::class);
        $this->placeholderHelper->expects($this->any())
            ->method('isNameMatch')
            ->willReturnCallback(
                function ($name, $nameValue) {
                    return $name === 'custom_PLACEHOLDER_ID' && $nameValue === 'custom_42';
                }
            );

        $this->indexDataProvider = new IndexDataProvider(
            $this->eventDispatcher,
            $this->aliasResolver,
            $this->placeholder,
            $this->tagHelper,
            $this->placeholderHelper
        );
    }

    public function testCollectContextForWebsite()
    {
        $websiteId = 1;
        $context = [WebsiteIdPlaceholder::NAME => $websiteId];

        $expectedContext = [
            WebsiteIdPlaceholder::NAME => $websiteId,
            AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => $websiteId
        ];

        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            CollectContextEvent::NAME,
            $this->logicalAnd(
                $this->isInstanceOf(CollectContextEvent::class),
                $this->callback(
                    function (CollectContextEvent $event) use ($expectedContext) {
                        $this->assertEquals($expectedContext, $event->getContext());

                        return true;
                    }
                )
            )
        );

        $this->assertEquals($expectedContext, $this->indexDataProvider->collectContextForWebsite($websiteId, $context));
    }

    public function testGetRestrictedEntitiesQueryBuilder()
    {
        $this->aliasResolver->expects($this->once())->method('getAlias')->with(\stdClass::class)->willReturn('std');

        $em = $this->createMock(EntityManagerInterface::class);
        $qb = new QueryBuilder($em);

        $this->assertEmpty($qb->getDQLPart('select'));

        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch')
            ->withConsecutive(
                [
                    RestrictIndexEntityEvent::NAME,
                    $this->isInstanceOf(RestrictIndexEntityEvent::class),
                ],
                [
                    RestrictIndexEntityEvent::NAME.'.std',
                    $this->isInstanceOf(RestrictIndexEntityEvent::class),
                ]
            )
            ->willReturnCallback(
                function () use ($qb) {
                    $qb->select(['something']);
                }
            );

        $this->assertSame($qb, $this->indexDataProvider->getRestrictedEntitiesQueryBuilder(\stdClass::class, $qb, []));
        $this->assertNotEmpty($qb->getDQLPart('select'));
    }

    /**
     * @dataProvider entitiesDataProvider
     * @param array $entityConfig
     * @param array $indexData
     * @param array $expected
     */
    public function testGetEntitiesData(array $entityConfig, array $indexData, array $expected)
    {
        $this->aliasResolver->expects($this->once())->method('getAlias')->with(\stdClass::class)->willReturn('std');
        $this->tagHelper->expects($this->any())->method('stripTags')->willReturnCallback(
            function ($value) {
                return trim(strip_tags($value));
            }
        );
        $this->placeholder->expects($this->any())->method('replace')->willReturnCallback(
            function ($string, array $values) {
                return str_replace(array_keys($values), array_values($values), $string);
            }
        );

        $this->eventDispatcher->expects($this->at(0))->method('dispatch')
            ->with(IndexEntityEvent::NAME, $this->isInstanceOf(IndexEntityEvent::class))
            ->willReturnCallback(
                function ($name, IndexEntityEvent $event) use ($indexData) {
                    foreach ($indexData as $data) {
                        $method = count($data) === 4 ? 'addField' : 'addPlaceholderField';
                        call_user_func_array([$event, $method], $data);
                    }
                }
            );

        $this->eventDispatcher->expects($this->at(1))->method('dispatch')
            ->with(IndexEntityEvent::NAME.'.std', $this->isInstanceOf(IndexEntityEvent::class));

        $this->assertEquals(
            $expected,
            $this->indexDataProvider->getEntitiesData(\stdClass::class, [], ['CONTEXT_ID' => 9], $entityConfig)
        );
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function entitiesDataProvider()
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2015-02-03 00:00:00', new \DateTimeZone('UTC'));

        return [
            'simple field' => [
                'entityConfig' => ['fields' => [['name' => 'sku', 'type' => Query::TYPE_TEXT]]],
                'indexData' => [
                    [1, 'sku', 'SKU-01', false],
                ],
                'expected' => [1 => ['text' => ['sku' => 'SKU-01']]],
            ],
            'simple field with duplicates' => [
                'entityConfig' => ['fields' => [['name' => 'description', 'type' => Query::TYPE_TEXT]]],
                'indexData' => [
                    [1, 'description', 'Handheld Flashlight Handheld', false],
                ],
                'expected' => [1 => ['text' => ['description' => 'Handheld Flashlight Handheld']]],
            ],
            'simple field with html' => [
                'entityConfig' => ['fields' => [['name' => 'title', 'type' => Query::TYPE_TEXT]]],
                'indexData' => [
                    [1, 'title', '<p>SKU-01</p>', true],
                ],
                'expected' => [1 => ['text' => ['title' => '<p>SKU-01</p>', 'all_text' => 'SKU-01']]],
            ],
            'simple field with integer' => [
                'entityConfig' => ['fields' => [['name' => 'qty', 'type' => Query::TYPE_INTEGER]]],
                'indexData' => [[1, 'qty', 0, true]],
                'expected' => [1 => ['integer' => ['qty' => 0]]],
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
                            'title_1' => '<p>SKU-01</p>',
                            'all_text' => 'SKU-01',
                            'all_text_5' => 'SKU-01',
                        ],
                        'integer' => [
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
                            'title_1' => '<p>SKU-01</p> <p>SKU-01-gb</p>',
                            'all_text' => 'SKU-01 en_US SKU-01-gb en_GB',
                            'all_text_5' => 'SKU-01 en_US',
                            'all_text_6' => 'SKU-01-gb en_GB',
                            'descr_5' => '<p>en_US</p>',
                            'descr_6' => '<p>en_GB</p>',
                        ],
                    ],
                ],
            ],
            'all_text without text fields' => [
                'entityConfig' => [
                    'fields' => [
                        [
                            'name' => 'qty',
                            'type' => Query::TYPE_INTEGER,
                        ],
                    ],
                ],
                'indexData' => [[1, 'qty', 1, true]],
                'expected' => [
                    1 => [
                        'integer' => ['qty' => 1],
                    ],
                ],
            ],
            'empty config field' => [
                'entityConfig' => [],
                'indexData' => [[1, 'qty', 1, true]],
                'expected' => [],
            ],
            'do not drop value in all_text and all_text_localization fields, like metadata' => [
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
                    [1, 'all_text', 'for_all_text', true],
                    [1, 'all_text_LOCALIZATION_ID', 'title5 descr5 keywords5', ['LOCALIZATION_ID' => 5], true],
                    [1, 'all_text_LOCALIZATION_ID', 'title6 descr6 keywords6', ['LOCALIZATION_ID' => 6], true],
                ],
                'expected' => [
                    1 => [
                        'text' => [
                            'title_1' => '<p>SKU-01</p> <p>SKU-01-gb</p>',
                            'all_text' => 'for_all_text SKU-01 en_US title5 descr5 keywords5 SKU-01-gb en_GB ' .
                                'title6 descr6 keywords6',
                            'all_text_5' => 'SKU-01 en_US title5 descr5 keywords5 for_all_text',
                            'all_text_6' => 'SKU-01-gb en_GB title6 descr6 keywords6 for_all_text',
                            'descr_5' => '<p>en_US</p>',
                            'descr_6' => '<p>en_GB</p>',
                        ],
                    ],
                ],
            ],
            'support placeholders in non text fields' => [
                'entityConfig' => [
                    'fields' => [
                        [
                            'name' => 'integer_WEBSITE_ID',
                            'type' => Query::TYPE_INTEGER,
                        ],
                        [
                            'name' => 'datetime_LOCALIZATION_ID',
                            'type' => Query::TYPE_DATETIME,
                        ],
                        [
                            'name' => 'decimal_WEBSITE_ID',
                            'type' => Query::TYPE_DECIMAL,
                        ],
                    ],
                ],
                'indexData' => [
                    [1, 'integer_WEBSITE_ID', 1, ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 5], true],
                    [1, 'datetime_LOCALIZATION_ID', $date, ['WEBSITE_ID' => 1, 'LOCALIZATION_ID' => 6], true],
                    [1, 'decimal_WEBSITE_ID', 1.1, ['WEBSITE_ID' => 2, 'LOCALIZATION_ID' => 5], true],
                ],
                'expected' => [
                    1 => [
                        'integer' => [
                            'integer_1' => 1
                        ],
                        'datetime' => [
                            'datetime_6' => $date
                        ],
                        'decimal' => [
                            'decimal_2' => 1.1
                        ],
                    ],
                ],
            ],
            'multiple fields with duplicates in all_text' => [
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
                    [1, 'title', 'The fox', true],
                    [1, 'description', 'The quick brown fox jumps over the lazy dog', true],
                ],
                'expected' => [
                    1 => [
                        'text' => [
                            'title' => 'The fox',
                            'description' => 'The quick brown fox jumps over the lazy dog',
                            'all_text' => 'The fox quick brown jumps over the lazy dog',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Missing option "type" for "sku" field
     */
    public function testGetEntitiesDataConfigMissing()
    {
        $this->aliasResolver->expects($this->once())->method('getAlias')->with(\stdClass::class)->willReturn('std');

        $this->eventDispatcher->expects($this->atLeastOnce())->method('dispatch')
            ->willReturnCallback(
                function ($name, IndexEntityEvent $event) {
                    $event->addField(1, 'sku', 'SKU-01');
                }
            );

        $this->indexDataProvider->getEntitiesData(\stdClass::class, [], [], ['fields' => []]);
    }
}
