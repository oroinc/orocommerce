<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\EventListener\WebsiteSearchTerm\Page;

use Oro\Bundle\CMSBundle\EventListener\WebsiteSearchTerm\Page\AddPageToSearchTermsDatagridListener;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddPageToSearchTermsDatagridListenerTest extends TestCase
{
    private AddPageToSearchTermsDatagridListener $datagridListener;

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

        $this->datagridListener = new AddPageToSearchTermsDatagridListener($urlGenerator, $translator, $htmlTagHelper);
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
                                    'join' => 'rootAlias.redirectCmsPage',
                                    'alias' => 'cmsPage',
                                ],
                                [
                                    'join' => 'cmsPage.titles',
                                    'alias' => 'pageTitle',
                                ],
                            ],
                        ],
                        'select' => [
                            'cmsPage.id as pageId',
                            'pageTitle.string as pageDefaultTitle',
                        ],
                        'where' => [
                            'and' => [
                                'pageTitle.localization IS NULL',
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
            'redirectActionType' => 'cms_page',
        ]);
        $resultRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'cms_page',
            'pageId' => 30,
            'pageDefaultTitle' => 'Default Title',
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
            'redirectActionType' => 'cms_page',
        ]);
        $expectedRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'cms_page',
            'pageId' => 30,
            'pageDefaultTitle' => 'Default Title',
        ]);
        $expectedRecord3->setValue(
            'actionDetails',
            'oro.websitesearchterm.searchterm.grid.action_details.redirect_cms_page'
            . '?{"{{ cms_page_url }}":"oro_cms_page_view?id=30","{{ cms_page_title }}":"Default Title (escaped)"}'
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
