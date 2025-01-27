<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\EventListener\QuoteUpdateHandlerEventListener;
use Oro\Bundle\SaleBundle\Form\Handler\QuoteCustomerDataRequestHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class QuoteUpdateHandlerEventListenerTest extends TestCase
{
    private WebsiteManager&MockObject $websiteManager;

    private QuoteCustomerDataRequestHandler&MockObject $quoteCustomerDataRequestHandler;

    private QuoteUpdateHandlerEventListener $listener;

    private FormProcessEvent $event;

    private Quote $quote;

    #[\Override]
    protected function setUp(): void
    {
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->quoteCustomerDataRequestHandler = $this->createMock(QuoteCustomerDataRequestHandler::class);

        $this->listener = new QuoteUpdateHandlerEventListener(
            $this->websiteManager,
            $this->quoteCustomerDataRequestHandler
        );

        $form = $this->createMock(FormInterface::class);

        $this->quote = new Quote();
        $this->event = new FormProcessEvent($form, $this->quote);
    }

    public function testEnsureWebsite()
    {
        $website = new Website();

        $this->websiteManager->expects(self::once())->method('getDefaultWebsite')->willReturn($website);

        $this->listener->ensureWebsite($this->event);

        $this->assertSame($website, $this->quote->getWebsite());
    }

    public function testEnsureWebsiteAlreadySet()
    {
        $website = new Website();

        $this->quote->setWebsite($website);

        $this->websiteManager->expects(self::never())->method('getDefaultWebsite');

        $this->listener->ensureWebsite($this->event);

        $this->assertSame($website, $this->quote->getWebsite());
    }

    public function testEnsureCustomer()
    {
        $this->quoteCustomerDataRequestHandler
            ->expects(self::once())
            ->method('handle')
            ->with($this->quote);

        $this->listener->ensureCustomer($this->event);
    }
}
