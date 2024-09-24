<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\WebsiteSearchTermBundle\EventListener\AddUriToSearchTermsDatagridListener;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddUriToSearchTermsDatagridListenerTest extends TestCase
{
    private AddUriToSearchTermsDatagridListener $datagridListener;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(
                static fn (string $id, array $parameters = []) => $id . '?' . json_encode($parameters)
            );

        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $htmlTagHelper->expects(self::any())
            ->method('sanitize')
            ->willReturnCallback(static fn (string $string) => $string . ' (sanitized)');

        $this->datagridListener = new AddUriToSearchTermsDatagridListener($translator, $htmlTagHelper);
    }

    public function testOnResultAfter(): void
    {
        $resultRecord1 = new ResultRecord(['id' => 1]);
        $resultRecord2 = new ResultRecord([
            'id' => 2,
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
        ]);
        $resultRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
            'redirectUri' => 'https://example.com',
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
            'redirectActionType' => 'uri',
        ]);
        $expectedRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
            'redirectUri' => 'https://example.com',
        ]);
        $expectedRecord3->setValue(
            'actionDetails',
            'oro.websitesearchterm.searchterm.grid.action_details.redirect_uri?{'
            . '"{{ url }}":"https:\/\/example.com",'
            . '"{{ title }}":"https:\/\/example.com",'
            . '"{{ link }}":"<a href=\"https:\/\/example.com\" target=\"_blank\">'
            . 'https:\/\/example.com'
            . '<\/a> (sanitized)"}'
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

    public function testOnResultAfterWhen51Chars(): void
    {
        $resultRecord1 = new ResultRecord(['id' => 1]);
        $resultRecord2 = new ResultRecord([
            'id' => 2,
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
        ]);
        $resultRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
            'redirectUri' => 'https://exaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaample.com',
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
            'redirectActionType' => 'uri',
        ]);
        $expectedRecord3 = new ResultRecord([
            'id' => 3,
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
            'redirectUri' => 'https://exaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaample.com',
        ]);
        $expectedRecord3->setValue(
            'actionDetails',
            'oro.websitesearchterm.searchterm.grid.action_details.redirect_uri?{'
            . '"{{ url }}":"https:\/\/exaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaample.com",'
            . '"{{ title }}":"https:\/\/exaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaample...",'
            . '"{{ link }}":"<a href=\"https:\/\/exaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaample.com\" target=\"_blank\">'
            . 'https:\/\/exaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaample...'
            . '<\/a> (sanitized)"}'
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
