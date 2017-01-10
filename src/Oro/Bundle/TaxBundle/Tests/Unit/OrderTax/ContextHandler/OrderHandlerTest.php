<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\OrderTax\ContextHandler;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;
use Oro\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository;
use Oro\Bundle\TaxBundle\Event\ContextEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\OrderTax\ContextHandler\OrderHandler;
use Oro\Bundle\TaxBundle\OrderTax\ContextHandler\OrderLineItemHandler;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;

class OrderHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var OrderHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->doctrineHelper = $this
            ->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new OrderHandler($this->doctrineHelper);
    }

    public function testIncorrectOrderClass()
    {
        $object = new \stdClass();
        $contextEvent = new ContextEvent($object);
        $this->handler->onContextEvent($contextEvent);

        $this->assertSame($object, $contextEvent->getMappingObject());
        $this->assertEmpty($contextEvent->getContext());
    }

    public function testOnContextEventAccount()
    {
        $account = new Customer();
        $order = new Order();
        $order->setAccount($account);
        $event = new ContextEvent($order);
        $oldContext = clone $event->getContext();
        
        /** @var AccountTaxCodeRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->getMockBuilder(AccountTaxCodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $accountTaxCode = new AccountTaxCode();
        $accountTaxCode->setCode('ACCOUNT_TAX_CODE');
        $repository->expects($this->once())
            ->method('findOneByEntity')
            ->with(TaxCodeInterface::TYPE_ACCOUNT, $account)
            ->willReturn($accountTaxCode);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(AccountTaxCode::class)
            ->willReturn($repository);

        $this->handler->onContextEvent($event);
        $this->assertNotEquals($oldContext, $event->getContext());
        $this->assertEquals('ACCOUNT_TAX_CODE', $event->getContext()->offsetGet(Taxable::ACCOUNT_TAX_CODE));
    }
}
