<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\EventListener\FormViewListener;
use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var FrontendPriceListRequestHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendPriceListRequestHandler;

    protected function setUp()
    {
        parent::setUp();

        $this->frontendPriceListRequestHandler = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator, $this->frontendPriceListRequestHandler);
    }

    public function testOnViewNoRequest()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $listener = $this->getListener($requestStack);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMock('\Twig_Environment');
        $event = $this->createEvent($env);
        $listener->onAccountView($event);
        $listener->onAccountGroupView($event);
        $listener->onProductView($event);
    }

    /**
     * @return array
     */
    public function viewDataProvider()
    {
        return [
            'price list does not exist' => [false],
            'price list does exists' => [true],
        ];
    }

    /**
     * @param bool $isPriceListExist
     * @dataProvider viewDataProvider
     */
    public function testOnAccountView($isPriceListExist)
    {
        $accountId = 1;
        $account = new Account();
        $priceList = new PriceList();
        $templateHtml = 'template_html';

        $request = new Request(['id' => $accountId]);
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);

        $priceRepository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $priceRepository->expects($this->once())
            ->method('getPriceListByAccount')
            ->with($account)
            ->willReturn($isPriceListExist ? $priceList : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BAccountBundle:Account', $accountId)
            ->willReturn($account);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroB2BPricingBundle:PriceList')
            ->willReturn($priceRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($isPriceListExist ? $this->once() : $this->never())
            ->method('render')
            ->with('OroB2BPricingBundle:Account:price_list_view.html.twig', ['priceList' => $priceList])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $listener->onAccountView($event);
        $scrollData = $event->getScrollData()->getData();

        if ($isPriceListExist) {
            $this->assertEquals(
                [$templateHtml],
                $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
            );
        } else {
            $this->assertEmpty($scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]);
        }
    }

    /**
     * @param bool $isPriceListExist
     * @dataProvider viewDataProvider
     */
    public function testOnAccountGroupView($isPriceListExist)
    {
        $accountGroupId = 1;
        $accountGroup = new AccountGroup();
        $priceList = new PriceList();
        $templateHtml = 'template_html';

        $request = new Request(['id' => $accountGroupId]);
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);

        $priceRepository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $priceRepository->expects($this->once())
            ->method('getPriceListByAccountGroup')
            ->with($accountGroup)
            ->willReturn($isPriceListExist ? $priceList : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BAccountBundle:AccountGroup', $accountGroupId)
            ->willReturn($accountGroup);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroB2BPricingBundle:PriceList')
            ->willReturn($priceRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($isPriceListExist ? $this->once() : $this->never())
            ->method('render')
            ->with('OroB2BPricingBundle:Account:price_list_view.html.twig', ['priceList' => $priceList])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $listener->onAccountGroupView($event);
        $scrollData = $event->getScrollData()->getData();

        if ($isPriceListExist) {
            $this->assertEquals(
                [$templateHtml],
                $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
            );
        } else {
            $this->assertEmpty($scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]);
        }
    }

    public function testOnEntityEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BPricingBundle:Account:price_list_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment, $formView);
        $listener->onEntityEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnProductView()
    {
        $productId = 1;
        $product = new Product();
        $templateHtml = 'template_html';

        $request = new Request(['id' => $productId]);
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BProductBundle:Product', $productId)
            ->willReturn($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BPricingBundle:Product:prices_view.html.twig', ['entity' => $product])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $listener->onProductView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertScrollDataPriceBlock($scrollData, $templateHtml);
    }

    public function testOnProductEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BPricingBundle:Product:prices_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment, $formView);
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);
        $listener->onProductEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertScrollDataPriceBlock($scrollData, $templateHtml);
    }

    /**
     * @param array $scrollData
     * @param string $html
     */
    protected function assertScrollDataPriceBlock(array $scrollData, $html)
    {
        $this->assertEquals(
            'orob2b.pricing.productprice.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::TITLE]
        );
        $this->assertEquals(
            [$html],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
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

    /**
     * @param string $class
     * @param int $id
     * @return object
     */
    protected function getEntity($class, $id)
    {
        $entity = new $class();
        $reflection = new \ReflectionProperty(get_class($entity), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);

        return $entity;
    }

    /**
     * @param RequestStack $requestStack
     * @return FormViewListener
     */
    protected function getListener(RequestStack $requestStack)
    {
        return new FormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            $this->frontendPriceListRequestHandler
        );
    }
}
