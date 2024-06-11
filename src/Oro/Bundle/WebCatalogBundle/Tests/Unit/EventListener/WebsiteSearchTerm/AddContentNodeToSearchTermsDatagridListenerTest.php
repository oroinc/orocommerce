<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener\WebsiteSearchTerm;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\WebCatalogBundle\EventListener\WebsiteSearchTerm\AddContentNodeToSearchTermsDatagridListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddContentNodeToSearchTermsDatagridListenerTest extends TestCase
{
    private AddContentNodeToSearchTermsDatagridListener $datagridListener;

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

        $this->datagridListener = new AddContentNodeToSearchTermsDatagridListener(
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
                                    'join' => 'rootAlias.redirectContentNode',
                                    'alias' => 'contentNode',
                                ],
                                [
                                    'join' => 'contentNode.titles',
                                    'alias' => 'contentNodeTitle',
                                ],
                            ],
                        ],
                        'select' => [
                            'contentNode.id as contentNodeId',
                            'contentNodeTitle.string as contentNodeDefaultTitle',
                        ],
                        'where' => [
                            'and' => [
                                'contentNodeTitle.localization IS NULL',
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
            'actionType' => 'redirect',
            'redirectActionType' => 'content_node',
        ]);
        $resultRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'content_node',
            'contentNodeId' => 30,
            'contentNodeDefaultTitle' => 'Default Title',
        ]);

        $event = new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [$resultRecord1, $resultRecord2, $resultRecord3]
        );

        $this->datagridListener->onResultAfter($event);

        $expectedRecord1 = new ResultRecord(['id' => 1]);
        $expectedRecord2 = new ResultRecord([
            'id' => 2,
            'actionType' => 'redirect',
            'redirectActionType' => 'content_node',
        ]);
        $expectedRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'content_node',
            'contentNodeId' => 30,
            'contentNodeDefaultTitle' => 'Default Title',
        ]);
        $expectedRecord3->setValue(
            'actionDetails',
            'oro.websitesearchterm.searchterm.grid.action_details.redirect_content_node'
            . '?{"{{ content_node_url }}":"oro_content_node_update?id=30","{{ content_node_title }}":'
            . '"Default Title (escaped)"}'
        );

        self::assertEquals(
            [
                $expectedRecord1,
                $expectedRecord2,
                $expectedRecord3,
            ],
            $event->getRecords()
        );
    }
}
