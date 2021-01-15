<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\InventoryBundle\EventListener\ProductBackOrderFormViewListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ProductInventoryBackorderFormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $em;

    /**
     * @var Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $env;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var Request|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $request;

    /**
     * @var ProductBackOrderFormViewListener
     */
    protected $productBackOrderFormViewListener;

    /**
     * @var BeforeListRenderEvent
     */
    protected $event;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrine;

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
        $this->em = $this->createMock(EntityManager::class);

        $this->request = $this->createMock(Request::class);

        $this->requestStack = new RequestStack();
        $this->requestStack->push($this->request);

        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->productBackOrderFormViewListener = new ProductBackOrderFormViewListener(
            $this->requestStack,
            $this->doctrine,
            $this->translator
        );

        $this->event = new BeforeListRenderEvent(
            $this->env,
            new ScrollData(),
            new \stdClass()
        );
    }

    public function testOnProductViewIgnoredIfNoProductId()
    {
        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');
        $this->productBackOrderFormViewListener->onProductView($this->event);
    }

    public function testOnProductViewIgnoredIfNoProductFound()
    {
        $this->em->expects($this->once())
            ->method('getReference')
            ->willReturn(null);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn('1');
        $this->productBackOrderFormViewListener->onProductView($this->event);
    }

    public function testOnProductViewRendersAndAddsSubBlock()
    {
        $this->request->expects($this->once())
            ->method('get')
            ->willReturn('1');
        $product = new Product();
        $this->em->expects($this->once())
            ->method('getReference')
            ->willReturn($product);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);

        $this->event->getScrollData()->setData([
            ScrollData::DATA_BLOCKS => [
                1 => [
                    ScrollData::TITLE => 'oro.product.sections.inventory.trans',
                    ScrollData::SUB_BLOCKS => [[]]
                ]
            ],
        ]);

        $this->productBackOrderFormViewListener->onProductView($this->event);
    }
}
