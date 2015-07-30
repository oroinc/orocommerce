<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Datagrid;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\EventListener\FormViewListener;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;

class FormViewListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var FormViewListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new FormViewListener($this->translator, $this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->listener);
    }

    public function testOnViewNoRequest()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMock('\Twig_Environment');
        $event = $this->createEvent($env);
        $this->listener->onCustomerView($event);
        $this->listener->onCustomerGroupView($event);
    }

    /**
     * @return array
     */
    public function viewCustomerDataProvider()
    {
        return [
            'payment term does not exists, payment term in group does exist' => [false, true],
            'payment term does not exists, payment term in group does not exist' => [false, false],
            'payment term does exists' => [true, false],
        ];
    }

    /**
     * @return array
     */
    public function viewCustomerGroupDataProvider()
    {
        return [
            'payment term does not exists' => [false],
            'payment term does exists' => [true],
        ];
    }

    /**
     * @param bool $isPaymentTermExist
     * @param bool $isPaymentTermInGroupExist
     * @dataProvider viewCustomerDataProvider
     */
    public function testOnCustomerView($isPaymentTermExist, $isPaymentTermInGroupExist)
    {
        $customerId = 1;
        $customer = new Customer();
        $customerGroup = new CustomerGroup();
        $paymentTerm = new PaymentTerm();
        $templateCustomerPaymentTermHtml = 'template_html_with_customer_payment_term';
        $templateCustomerGroupPaymentTermHtml = 'template_html_with_customer_group_payment_term';
        $templateCustomerGroupWithoutPaymentTermHtml = 'template_html_without_customer_group_payment_term';

        if ($isPaymentTermInGroupExist) {
            $customer->setGroup($customerGroup);
        }

        $this->listener->setRequest(new Request(['id' => $customerId]));

        $paymentTermRepository = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTermRepository->expects($this->once())
            ->method('getOnePaymentTermByCustomer')
            ->with($customer)
            ->willReturn($isPaymentTermExist ? $paymentTerm : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BCustomerBundle:Customer', $customerId)
            ->willReturn($customer);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with('OroB2BPaymentBundle:PaymentTerm')
            ->willReturn($paymentTermRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');

        if ($isPaymentTermExist) {
            $environment->expects($isPaymentTermExist ? $this->once() : $this->never())
                ->method('render')
                ->with('OroB2BPaymentBundle:Customer:payment_term_view.html.twig', ['paymentTerm' => $paymentTerm])
                ->willReturn($templateCustomerPaymentTermHtml);
        } else {
            $paymentTermRepository->expects($this->any())
                ->method('getOnePaymentTermByCustomerGroup')
                ->with($customerGroup)
                ->willReturn($isPaymentTermInGroupExist ? $paymentTerm : null);

            $environment->expects($this->once())
                ->method('render')
                ->with(
                    'OroB2BPaymentBundle:Customer:payment_term_for_customer_view.html.twig',
                    [
                        'customerGroupPaymentTerm' => $isPaymentTermInGroupExist ? $paymentTerm : null
                    ]
                )
                ->willReturn(
                    $isPaymentTermInGroupExist ?
                        $templateCustomerGroupPaymentTermHtml :
                        $templateCustomerGroupWithoutPaymentTermHtml
                );
        }

        $event = $this->createEvent($environment);
        $this->listener->onCustomerView($event);
        $scrollData = $event->getScrollData()->getData();

        if ($isPaymentTermExist) {
            $this->assertEquals(
                [$templateCustomerPaymentTermHtml],
                $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
            );
        } elseif ($isPaymentTermInGroupExist) {
            $this->assertEquals(
                [$templateCustomerGroupPaymentTermHtml],
                $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
            );
        } else {
            $this->assertEquals(
                [$templateCustomerGroupWithoutPaymentTermHtml],
                $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
            );
        }
    }

    /**
     * @param bool $isPaymentTermExist
     * @dataProvider viewCustomerGroupDataProvider
     */
    public function testOnCustomerGroupView($isPaymentTermExist)
    {
        $customerGroupId = 1;
        $customerGroup = new CustomerGroup();
        $paymentTerm = new PaymentTerm();
        $templateHtml = 'template_html';
        $emptyTemplate = 'template_html_empty';

        $this->listener->setRequest(new Request(['id' => $customerGroupId]));

        $priceRepository = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $priceRepository->expects($this->once())
            ->method('getOnePaymentTermByCustomerGroup')
            ->with($customerGroup)
            ->willReturn($isPaymentTermExist ? $paymentTerm : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BCustomerBundle:CustomerGroup', $customerGroupId)
            ->willReturn($customerGroup);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroB2BPaymentBundle:PaymentTerm')
            ->willReturn($priceRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with(
                'OroB2BPaymentBundle:Customer:payment_term_view.html.twig',
                [
                    'paymentTerm' => $isPaymentTermExist ? $paymentTerm : null
                ]
            )
            ->willReturn($isPaymentTermExist ? $templateHtml : $emptyTemplate);

        $event = $this->createEvent($environment);
        $this->listener->onCustomerGroupView($event);
        $scrollData = $event->getScrollData()->getData();

        if ($isPaymentTermExist) {
            $this->assertEquals(
                [$templateHtml],
                $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
            );
        } else {
            $this->assertEquals(
                [$emptyTemplate],
                $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
            );
        }
    }

    public function testOnEntityEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BPaymentBundle:Customer:payment_term_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment, $formView);
        $this->listener->onEntityEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    /**
     * @param \Twig_Environment $environment
     * @param FormView $formView
     * @return BeforeListRenderEvent
     */
    protected function createEvent(\Twig_Environment $environment, FormView $formView = null)
    {
        $defaultData = [
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => []
                        ]
                    ]
                ]
            ]
        ];

        return new BeforeListRenderEvent($environment, new ScrollData($defaultData), $formView);
    }
}
