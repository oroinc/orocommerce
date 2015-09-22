<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\SaleBundle\Model\QuoteToOrderConverter;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Form\Handler\QuoteToOrderHandler;

class QuoteToOrderHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QuoteToOrderConverter
     */
    protected $converter;

    /**
     * @var AccountUser
     */
    protected $accountUser;

    /**
     * @var QuoteToOrderHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = new Request();
        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->converter = $this->getMockBuilder('OroB2B\Bundle\SaleBundle\Model\QuoteToOrderConverter')
            ->disableOriginalConstructor()
            ->getMock();

        $account = new Account();
        $account->setName('account');
        $this->accountUser = new AccountUser();
        $this->accountUser->setEmail('test@test.com')
            ->setAccount($account);

        $this->handler = new QuoteToOrderHandler(
            $this->form,
            $this->request,
            $this->manager,
            $this->converter,
            $this->accountUser
        );
    }

    public function testProcessGet()
    {
        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertNull($this->handler->process(new Quote()));
    }

    public function testProcessInvalidPost()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->converter->expects($this->never())
            ->method('convert');

        $this->assertNull($this->handler->process(new Quote()));
    }

    public function testProcessValidPost()
    {
        $offerData = ['offer', 'data'];
        $quote = new Quote();
        $order = new Order();

        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn($offerData);

        $this->converter->expects($this->once())
            ->method('convert')
            ->with($quote, $offerData)
            ->willReturn($order);

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($order);
        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertEquals($order, $this->handler->process($quote));
        $this->assertEquals($this->accountUser, $order->getAccountUser());
        $this->assertEquals($this->accountUser->getAccount(), $order->getAccount());
    }
}
