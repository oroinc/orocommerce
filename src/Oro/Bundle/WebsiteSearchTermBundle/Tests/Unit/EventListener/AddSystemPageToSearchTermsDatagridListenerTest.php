<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\NavigationBundle\Provider\RouteTitleProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\WebsiteSearchTermBundle\EventListener\AddSystemPageToSearchTermsDatagridListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddSystemPageToSearchTermsDatagridListenerTest extends TestCase
{
    private RouteTitleProvider|MockObject $routeTitleProvider;

    private AddSystemPageToSearchTermsDatagridListener $datagridListener;

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

        $this->routeTitleProvider = $this->createMock(RouteTitleProvider::class);

        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $htmlTagHelper->expects(self::any())
            ->method('escape')
            ->willReturnCallback(static fn (string $string) => $string . ' (escaped)');

        $this->datagridListener = new AddSystemPageToSearchTermsDatagridListener(
            $urlGenerator,
            $translator,
            $this->routeTitleProvider,
            $htmlTagHelper
        );
    }

    public function testOnResultAfter(): void
    {
        $resultRecord1 = new ResultRecord(['id' => 1]);
        $resultRecord2 = new ResultRecord([
            'id' => 2,
            'actionType' => 'redirect',
            'redirectActionType' => 'system_page',
        ]);
        $systemPage = 'sample_route_name';
        $resultRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'system_page',
            'redirectSystemPage' => $systemPage,
        ]);

        $event = new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [$resultRecord1, $resultRecord2, $resultRecord3]
        );

        $systemPageTitle = 'Sample title';
        $this->routeTitleProvider
            ->expects(self::once())
            ->method('getTitle')
            ->with($systemPage, 'frontend_menu')
            ->willReturn($systemPageTitle);

        $this->datagridListener->onResultAfter($event);

        $expectedRecord1 = new ResultRecord(['id' => 1]);
        $expectedRecord2 = new ResultRecord([
            'id' => 2,
            'actionType' => 'redirect',
            'redirectActionType' => 'system_page',
        ]);
        $expectedRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'system_page',
            'redirectSystemPage' => $systemPage,
        ]);
        $expectedRecord3->setValue(
            'actionDetails',
            'oro.websitesearchterm.searchterm.grid.action_details.redirect_system_page'
            . '?{"{{ system_page_url }}":"' . $systemPage . '?","{{ system_page_title }}":'
            . '"' . $systemPageTitle . ' (escaped)"}'
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
