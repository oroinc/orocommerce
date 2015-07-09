<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Entity;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTermManager;

class PaymentTermManagerTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_TERM_CLASS = 'OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm';
    const DEFAULT_DELETE_MESSAGE_TEXT = 'Delete message text';
    const ADDITIONAL_TEXT_WITH_TWO_LINKS = ' with two links';
    const ADDITIONAL_TEXT_FOR_CUSTOMER_ONLY = ' with customer link';
    const ADDITIONAL_TEXT_FOR_CUSTOMER_GROUP_ONLY = ' with customer link';


    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $om;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    protected $paymentTermManager;

    protected $customerRepository;
    protected $customerGroupRepository;


    protected function setUp()
    {
        $this->om = $this->getMock('\Doctrine\Common\Persistence\ObjectManager');
        $this->translator = $this
            ->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->router = $this
            ->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTermManager = new PaymentTermManager($this->om, $this->translator, $this->router);

        $this->customerRepository = $this
            ->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerGroupRepository = $this
            ->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param $customer
     * @param $customerGroup
     * @param $expectedData
     * @dataProvider deleteTextDataProvider
     */
    public function testGetDeleteMessageText($customer, $customerGroup, $expectedData)
    {
        $paymentTerm = new PaymentTerm();

        $this->customerRepository->expects($this->any())
            ->method('findBy')
            ->with(['paymentTerm' => $paymentTerm])
            ->will($this->returnValue($customer));


        $this->customerGroupRepository->expects($this->any())
            ->method('findBy')
            ->with(['paymentTerm' => $paymentTerm])
            ->will($this->returnValue($customerGroup));

        $this->om->expects($this->at(0))
            ->method('getRepository')
            ->with($this->equalTo('OroB2BCustomerBundle:CustomerGroup'))
            ->will($this->returnValue($this->customerGroupRepository));

        $this->om->expects($this->at(1))
            ->method('getRepository')
            ->with($this->equalTo('OroB2BCustomerBundle:Customer'))
            ->will($this->returnValue($this->customerRepository));

        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($name, $additional) {
                switch ($name) {
                    case 'oro.ui.delete_confirm':
                        return static::DEFAULT_DELETE_MESSAGE_TEXT;
                    case 'orob2b.payment.controller.paymentterm.delete.with_two_url.message':
                        return static::ADDITIONAL_TEXT_WITH_TWO_LINKS;
                    case 'orob2b.payment.controller.paymentterm.delete.with_url.message':
                        if (strstr($additional['%url%'], 'CUSTOMER_ONLY') !== false) {
                            return static::ADDITIONAL_TEXT_FOR_CUSTOMER_ONLY;
                        }
                        return static::ADDITIONAL_TEXT_FOR_CUSTOMER_GROUP_ONLY;
                    case 'orob2b.customer.customergroup.entity_plural_label':
                        return 'CUSTOMER_GROUP';
                    case 'orob2b.customer.entity_plural_label':
                        return 'CUSTOMER_ONLY';
                    default:
                        return '';
                }
            }));

        $this->assertEquals($expectedData, $this->paymentTermManager->getDeleteMessageText($paymentTerm));
    }

    public function deleteTextDataProvider()
    {
        return [
            'two links in text' => [
                'customer' => new Customer(),
                'customerGroup' => new CustomerGroup(),
                'expectedData' => static::DEFAULT_DELETE_MESSAGE_TEXT . static::ADDITIONAL_TEXT_WITH_TWO_LINKS
            ],
            'customer links only' => [
                'customer' => new Customer(),
                'customerGroup' => null,
                'expectedData' => static::DEFAULT_DELETE_MESSAGE_TEXT . static::ADDITIONAL_TEXT_FOR_CUSTOMER_ONLY
            ],
            'customer group links only' => [
                'customer' => null,
                'customerGroup' => new CustomerGroup(),
                'expectedData' => static::DEFAULT_DELETE_MESSAGE_TEXT . static::ADDITIONAL_TEXT_FOR_CUSTOMER_GROUP_ONLY
            ],
            'default text only' => [
                'customer' => null,
                'customerGroup' => null,
                'expectedData' => static::DEFAULT_DELETE_MESSAGE_TEXT
            ],
        ];
    }
}
