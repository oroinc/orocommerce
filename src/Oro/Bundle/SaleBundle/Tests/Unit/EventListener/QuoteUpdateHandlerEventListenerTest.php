<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\MultiWebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\EventListener\QuoteUpdateHandlerEventListener;
use Oro\Bundle\SaleBundle\Model\QuoteRequestHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class QuoteUpdateHandlerEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject */
    private $websiteManager;

    /** @var QuoteRequestHandler|\PHPUnit_Framework_MockObject_MockObject */
    private $quoteRequestHandler;

    /** @var QuoteUpdateHandlerEventListener */
    private $listener;

    protected function setUp()
    {
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->quoteRequestHandler = $this->createMock(QuoteRequestHandler::class);

        $this->listener = new QuoteUpdateHandlerEventListener($this->websiteManager, $this->quoteRequestHandler);
    }

    public function testBeforeDataSetNoWebsite()
    {
        $quote = new Quote();

        $formProcessEvent = $this->createFormProcessEvent($quote);

        $website = new Website();

        $this->websiteManager->expects($this->once())->method('getDefaultWebsite')->willReturn($website);

        $this->listener->beforeDataSet($formProcessEvent);

        $this->assertSame($website, $quote->getWebsite());
    }

    public function testBeforeDataSetWebsiteAlreadySet()
    {
        $quote = new Quote();

        $formProcessEvent = $this->createFormProcessEvent($quote);

        $website = new Website();

        $quote->setWebsite($website);

        $this->websiteManager->expects($this->never())->method('getDefaultWebsite');

        $this->listener->beforeDataSet($formProcessEvent);

        $this->assertSame($website, $quote->getWebsite());
    }

    public function testBeforeSubmit()
    {
        $quote = new Quote();

        $formProcessEvent = $this->createFormProcessEvent($quote);

        $customer = new Customer;
        $customerUser = new CustomerUser;

        $this->quoteRequestHandler->expects($this->once())->method('getCustomer')->willReturn($customer);
        $this->quoteRequestHandler->expects($this->once())->method('getCustomerUser')->willReturn($customerUser);

        $this->listener->beforeSubmit($formProcessEvent);

        $this->assertSame($customer, $quote->getCustomer());
        $this->assertSame($customerUser, $quote->getCustomerUser());
    }

    /**
     * @param Quote $quote
     * @return FormProcessEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createFormProcessEvent(Quote $quote)
    {
        $formProcessEvent = $this->createMock(FormProcessEvent::class);
        $formProcessEvent->expects($this->any())->method('getData')->willReturn($quote);
        return $formProcessEvent;
    }
}
