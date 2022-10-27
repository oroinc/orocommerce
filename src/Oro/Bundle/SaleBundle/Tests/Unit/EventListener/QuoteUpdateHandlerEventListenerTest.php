<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\EventListener\QuoteUpdateHandlerEventListener;
use Oro\Bundle\SaleBundle\Model\QuoteRequestHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class QuoteUpdateHandlerEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var QuoteRequestHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteRequestHandler;

    /** @var QuoteUpdateHandlerEventListener */
    private $listener;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var FormProcessEvent */
    private $event;

    /** @var Quote */
    private $quote;

    protected function setUp(): void
    {
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->quoteRequestHandler = $this->createMock(QuoteRequestHandler::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->listener = new QuoteUpdateHandlerEventListener(
            $this->websiteManager,
            $this->quoteRequestHandler,
            $this->requestStack
        );

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $this->quote = new Quote();
        $this->event = new FormProcessEvent($form, $this->quote);
    }

    public function testEnsureWebsite()
    {
        $website = new Website();

        $this->websiteManager->expects($this->once())->method('getDefaultWebsite')->willReturn($website);

        $this->listener->ensureWebsite($this->event);

        $this->assertSame($website, $this->quote->getWebsite());
    }

    public function testEnsureWebsiteAlreadySet()
    {
        $website = new Website();

        $this->quote->setWebsite($website);

        $this->websiteManager->expects($this->never())->method('getDefaultWebsite');

        $this->listener->ensureWebsite($this->event);

        $this->assertSame($website, $this->quote->getWebsite());
    }

    public function testEnsureCustomer()
    {
        $customer = new Customer;
        $customerUser = new CustomerUser;

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getMethod')->willReturn('POST');

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->quoteRequestHandler->expects($this->once())->method('getCustomer')->willReturn($customer);
        $this->quoteRequestHandler->expects($this->once())->method('getCustomerUser')->willReturn($customerUser);

        $this->listener->ensureCustomer($this->event);

        $this->assertSame($customer, $this->quote->getCustomer());
        $this->assertSame($customerUser, $this->quote->getCustomerUser());
    }

    public function testEnsureCustomerOtherRequests()
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getMethod')->willReturn('GET'); //not our case

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->quoteRequestHandler->expects($this->never())->method('getCustomer');
        $this->quoteRequestHandler->expects($this->never())->method('getCustomerUser');

        $this->listener->ensureCustomer($this->event);
    }
}
