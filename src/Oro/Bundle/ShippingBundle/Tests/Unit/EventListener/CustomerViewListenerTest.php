<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\EventListener\FormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CustomerViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    protected $env;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var FormViewListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->env = $this->createMock(Environment::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->request = $this->createMock(Request::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->listener = new FormViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->requestStack
        );
    }

    public function testOnProductViewWithoutRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->request->expects($this->never())
            ->method('get');

        $event = new BeforeListRenderEvent(
            $this->env,
            new ScrollData(),
            new \stdClass()
        );

        $this->listener->onProductView($event);
    }

    public function testOnProductViewWithEmptyRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        $event = new BeforeListRenderEvent(
            $this->env,
            new ScrollData(),
            new \stdClass()
        );

        $this->listener->onProductView($event);
    }

    public function testOnProductViewWithoutProduct()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);
        $this->request->expects($this->once())->method('get')->with('id')->willReturn(42);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroProductBundle:Product', 42)
            ->willReturn(null);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepositoryForClass');

        $event = new BeforeListRenderEvent(
            $this->env,
            new ScrollData(),
            new \stdClass()
        );

        $this->listener->onProductView($event);
    }

    public function testOnProductViewWithEmptyShippingOptions()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(47);

        $product = new Product();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroProductBundle:Product', 47)
            ->willReturn($product);

        $productShippingOptionsRepository = $this->createMock(EntityRepository::class);
        $productShippingOptionsRepository->expects($this->once())
            ->method('findBy')
            ->with(['product' => 47])
            ->willReturn([]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with('OroShippingBundle:ProductShippingOptions')
            ->willReturn($productShippingOptionsRepository);

        $this->env->expects($this->never())
            ->method('render');

        $event = new BeforeListRenderEvent(
            $this->env,
            new ScrollData(),
            $product
        );

        $this->listener->onProductView($event);
    }

    public function testOnProductView()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(47);

        $product = new Product();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroProductBundle:Product', 47)
            ->willReturn($product);

        $productShippingOptionsRepository = $this->createMock(EntityRepository::class);

        $productShippingOptionsRepository->expects($this->once())
            ->method('findBy')
            ->with(['product' => 47])
            ->willReturn(
                [
                    new ProductShippingOptions(),
                    new ProductShippingOptions(),
                ]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with('OroShippingBundle:ProductShippingOptions')
            ->willReturn($productShippingOptionsRepository);

        $renderedHtml = 'rendered_html';

        /** @var Environment|\PHPUnit\Framework\MockObject\MockObject $twig */
        $this->env->expects($this->once())
            ->method('render')
            ->with(
                '@OroShipping/Product/shipping_options_view.html.twig',
                [
                    'entity' => $product,
                    'shippingOptions' => [new ProductShippingOptions(), new ProductShippingOptions()]
                ]
            )
            ->willReturn($renderedHtml);

        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent($this->env, $scrollData, new \stdClass());

        $this->listener->onProductView($event);

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                'shipping' => [
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                0 => $renderedHtml,
                            ],
                        ],
                    ],
                    ScrollData::TITLE => 'oro.shipping.product.section.shipping_options.trans',
                    ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                    ScrollData::PRIORITY => 1800
                ],
            ],
        ];

        $this->assertEquals($expectedData, $scrollData->getData());
    }

    public function testOnProductEdit()
    {
        $formView = $this->createMock(FormView::class);
        $renderedHtml = 'rendered_html';

        $this->env->expects($this->once())
            ->method('render')
            ->with('@OroShipping/Product/shipping_options_update.html.twig', ['form' => $formView])
            ->willReturn($renderedHtml);

        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent($this->env, $scrollData, new \stdClass(), $formView);

        $this->listener->onProductEdit($event);

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                'shipping' => [
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                0 => $renderedHtml,
                            ],
                        ],
                    ],
                    ScrollData::TITLE => 'oro.shipping.product.section.shipping_options.trans',
                    ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                    ScrollData::PRIORITY => 1800
                ],
            ],
        ];

        $this->assertEquals($expectedData, $scrollData->getData());
    }
}
