<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener\WebsiteSearchTerm;

use Oro\Bundle\CatalogBundle\EventListener\WebsiteSearchTerm\AddCategoryToSearchTermsDatagridListener;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddCategoryToSearchTermsDatagridListenerTest extends TestCase
{
    private AddCategoryToSearchTermsDatagridListener $datagridListener;

    #[\Override]
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

        $this->datagridListener = new AddCategoryToSearchTermsDatagridListener(
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
                                    'join' => 'rootAlias.redirectCategory',
                                    'alias' => 'category',
                                ],
                                [
                                    'join' => 'category.titles',
                                    'alias' => 'categoryTitle',
                                    'conditionType' => 'WITH',
                                    'condition' => 'categoryTitle.localization IS NULL',
                                ],
                            ],
                        ],
                        'select' => [
                            'category.id as categoryId',
                            'categoryTitle.string as categoryDefaultTitle',
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
            'redirectActionType' => 'category',
        ]);
        $resultRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'category',
            'categoryId' => 30,
            'categoryDefaultTitle' => 'Default Title',
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
            'redirectActionType' => 'category',
        ]);
        $expectedRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'category',
            'categoryId' => 30,
            'categoryDefaultTitle' => 'Default Title',
        ]);
        $expectedRecord3->setValue(
            'actionDetails',
            'oro.websitesearchterm.searchterm.grid.action_details.redirect_category'
            . '?{"{{ category_url }}":"oro_catalog_category_update?id=30","{{ category_title }}":'
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
