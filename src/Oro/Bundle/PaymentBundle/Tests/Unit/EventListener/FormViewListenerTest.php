<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Datagrid;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\EventListener\FormViewListener;

class FormViewListenerTest extends FormViewListenerTestCase
{
    const PAYMENT_TERM_CLASS = 'Oro\Bundle\PaymentBundle\Entity\PaymentTerm';

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator, $this->listener);
    }

    public function testOnViewNoRequest()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $listener = new FormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            static::PAYMENT_TERM_CLASS
        );
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMock('\Twig_Environment');
        $event = $this->createEvent($env);

        $listener->onAccountView($event);
        $listener->onAccountGroupView($event);
    }

    /**
     * @return array
     */
    public function viewAccountDataProvider()
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
    public function viewAccountGroupDataProvider()
    {
        return [
            'payment term does not exists' => [false],
            'payment term does exists' => [true],
        ];
    }

    /**
     * @param bool $isPaymentTermExist
     * @param bool $isPaymentTermInGroupExist
     * @dataProvider viewAccountDataProvider
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testOnAccountView($isPaymentTermExist, $isPaymentTermInGroupExist)
    {
        $accountId = 1;
        $account = new Account();
        $accountGroup = new AccountGroup();
        $paymentTerm = new PaymentTerm();
        $templateAccountPaymentTermHtml = 'template_html_with_account_payment_term';
        $templateAccountGroupPaymentTermHtml = 'template_html_with_account_group_payment_term';
        $templateAccountGroupWithoutPaymentTermHtml = 'template_html_without_account_group_payment_term';

        if ($isPaymentTermInGroupExist) {
            $account->setGroup($accountGroup);
        }

        $paymentTermRepository = $this->getMockBuilder(
            'Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTermRepository->expects($this->once())
            ->method('getOnePaymentTermByAccount')
            ->with($account)
            ->willReturn($isPaymentTermExist ? $paymentTerm : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:Account', $accountId)
            ->willReturn($account);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(static::PAYMENT_TERM_CLASS)
            ->willReturn($paymentTermRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');

        if ($isPaymentTermExist) {
            $environment->expects($isPaymentTermExist ? $this->once() : $this->never())
                ->method('render')
                ->with('OroPaymentBundle:Account:payment_term_view.html.twig')
                ->willReturn($templateAccountPaymentTermHtml);
        } else {
            $this->translator->expects($this->at(0))
                ->method('trans')
                ->with('oro.payment.account.payment_term_non_defined_in_group');

            $paymentTermRepository->expects($this->any())
                ->method('getOnePaymentTermByAccountGroup')
                ->with($accountGroup)
                ->willReturn($isPaymentTermInGroupExist ? $paymentTerm : null);

            if ($isPaymentTermInGroupExist) {
                $this->translator->expects($this->at(1))
                    ->method('trans')
                    ->with('oro.payment.account.payment_term_defined_in_group');
            }

            $environment->expects($this->once())
                ->method('render')
                ->with(
                    'OroPaymentBundle:Account:payment_term_view.html.twig'
                )
                ->willReturn(
                    $isPaymentTermInGroupExist ?
                    $templateAccountGroupPaymentTermHtml :
                    $templateAccountGroupWithoutPaymentTermHtml
                );
        }

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(
            new Request(['id' => $accountId])
        );

        $listener = new FormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            static::PAYMENT_TERM_CLASS
        );

        $event = $this->createEvent($environment);
        $listener->onAccountView($event);
        $scrollData = $event->getScrollData()->getData();

        if ($isPaymentTermExist) {
            $this->assertEqualsForScrollData($templateAccountPaymentTermHtml, $scrollData);
        } elseif ($isPaymentTermInGroupExist) {
            $this->assertEqualsForScrollData($templateAccountGroupPaymentTermHtml, $scrollData);
        } else {
            $this->assertEqualsForScrollData($templateAccountGroupWithoutPaymentTermHtml, $scrollData);
        }
    }

    /**
     * @param bool $isPaymentTermExist
     * @dataProvider viewAccountGroupDataProvider
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function testOnAccountGroupView($isPaymentTermExist)
    {
        $accountGroupId = 1;
        $accountGroup = new AccountGroup();
        $paymentTerm = new PaymentTerm();
        $templateHtml = 'template_html';
        $emptyTemplate = 'template_html_empty';

        $priceRepository = $this->getMockBuilder('Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $priceRepository->expects($this->once())
            ->method('getOnePaymentTermByAccountGroup')
            ->with($accountGroup)
            ->willReturn($isPaymentTermExist ? $paymentTerm : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCustomerBundle:AccountGroup', $accountGroupId)
            ->willReturn($accountGroup);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(static::PAYMENT_TERM_CLASS)
            ->willReturn($priceRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with(
                'OroPaymentBundle:Account:payment_term_view.html.twig'
            )
            ->willReturn($isPaymentTermExist ? $templateHtml : $emptyTemplate);

        $event = $this->createEvent($environment);
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(
            new Request(['id' => $accountGroupId])
        );

        $listener = new FormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            static::PAYMENT_TERM_CLASS
        );

        $listener->onAccountGroupView($event);
        $scrollData = $event->getScrollData()->getData();

        if ($isPaymentTermExist) {
            $this->assertEqualsForScrollData($templateHtml, $scrollData);
        } else {
            $this->assertEqualsForScrollData($emptyTemplate, $scrollData);
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
            ->with('OroPaymentBundle:Account/Form:payment_term_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment, $formView);

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $listener = new FormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            static::PAYMENT_TERM_CLASS
        );

        $listener->onEntityEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEqualsForScrollData($templateHtml, $scrollData);
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

    /**
     * @param string $template
     * @param array  $scrollData
     */
    protected function assertEqualsForScrollData($template, $scrollData)
    {
        $this->assertEquals(
            [$template],
            $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }
}
