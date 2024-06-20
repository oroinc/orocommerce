<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener\WebsiteSearchTerm\ContentBlock;

use Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\ContentBlock\AddContentBlockToSearchTermsDatagridListener;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddContentBlockToSearchTermsDatagridListenerTest extends TestCase
{
    private AddContentBlockToSearchTermsDatagridListener $datagridListener;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(
                static fn (string $id, array $parameters = []) => $id . '?' . json_encode($parameters)
            );

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::any())
            ->method('generate')
            ->willReturnCallback(
                static fn (string $route, array $parameters = []) => $route . '?' . http_build_query($parameters)
            );

        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $htmlTagHelper->expects(self::any())
            ->method('escape')
            ->willReturnCallback(static fn (string $string) => $string . ' (escaped)');

        $this->datagridListener = new AddContentBlockToSearchTermsDatagridListener(
            $urlGenerator,
            $translator,
            $htmlTagHelper
        );
    }

    public function testOnBuildBefore(): void
    {
        $gridConfig = DatagridConfiguration::create(
            [
                'source' => ['query' => ['from' => [['alias' => 'rootAlias']]]],
            ]
        );

        $event = new BuildBefore($this->createMock(DatagridInterface::class), $gridConfig);

        $this->datagridListener->onBuildBefore($event);

        self::assertEquals(
            [
                'source' => [
                    'query' => [
                        'from' => [['alias' => 'rootAlias']],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'rootAlias.contentBlock',
                                    'alias' => 'contentBlock',
                                ],
                                [
                                    'join' => 'contentBlock.titles',
                                    'alias' => 'contentBlockTitle',
                                ],
                            ],
                        ],
                        'select' => [
                            'contentBlock.id as contentBlockId',
                            'contentBlockTitle.string as contentBlockDefaultTitle',
                        ],
                        'where' => [
                            'and' => [
                                'contentBlockTitle.localization IS NULL',
                            ],
                        ],
                    ],
                ],
            ],
            $gridConfig->toArray()
        );
    }

    public function testOnResultAfter(): void
    {
        $resultRecord1 = new ResultRecord(['id' => 1]);
        $resultRecord2 = new ResultRecord([
            'id' => 2,
            'actionType' => 'modify',
            'redirectActionType' => 'original_results',
        ]);
        $resultRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'modify',
            'redirectActionType' => 'original_results',
            'contentBlockId' => 30,
            'contentBlockDefaultTitle' => 'Default Title',
        ]);
        $resultRecord4 = new ResultRecord([
            'id' => 4,
            'actionType' => 'modify',
            'redirectActionType' => 'product_collection',
            'actionDetails' => 'Existing action details',
            'contentBlockId' => 40,
            'contentBlockDefaultTitle' => 'Default Title',
        ]);

        $event = new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [$resultRecord1, $resultRecord2, $resultRecord3, $resultRecord4]
        );

        $this->datagridListener->onResultAfter($event);

        $expectedRecord1 = new ResultRecord(['id' => 1]);
        $expectedRecord2 = new ResultRecord([
            'id' => 2,
            'actionType' => 'modify',
            'redirectActionType' => 'original_results',
        ]);
        $expectedRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'modify',
            'redirectActionType' => 'original_results',
            'contentBlockId' => 30,
            'contentBlockDefaultTitle' => 'Default Title',
        ]);
        $expectedRecord3->setValue(
            'actionDetails',
            'oro.websitesearchterm.searchterm.grid.action_details.additional_content_block'
            . '?{"{{ main_action_details }}":null,"{{ content_block_title }}":"Default Title (escaped)",'
            . '"{{ content_block_url }}":"oro_cms_content_block_view?id=30"}'
        );
        $expectedRecord4 = new ResultRecord([
            'id' => 4,
            'actionType' => 'modify',
            'redirectActionType' => 'product_collection',
            'contentBlockId' => 40,
            'contentBlockDefaultTitle' => 'Default Title',
        ]);
        $expectedRecord4->setValue(
            'actionDetails',
            'oro.websitesearchterm.searchterm.grid.action_details.additional_content_block'
            . '?{"{{ main_action_details }}":"Existing action details","{{ content_block_title }}":'
            . '"Default Title (escaped)",'
            . '"{{ content_block_url }}":"oro_cms_content_block_view?id=40"}'
        );

        self::assertEquals(
            [
                $expectedRecord1,
                $expectedRecord2,
                $expectedRecord3,
                $expectedRecord4,
            ],
            $event->getRecords()
        );
    }
}
