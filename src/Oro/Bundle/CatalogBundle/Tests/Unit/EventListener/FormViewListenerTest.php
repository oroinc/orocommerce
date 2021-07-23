<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\FormViewListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class FormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $env;

    /**
     * @var FormViewListener
     */
    protected $listener;

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

        $this->listener = new FormViewListener($this->translator, $this->doctrineHelper);
    }

    protected function tearDown(): void
    {
        unset($this->listener);
        parent::tearDown();
    }

    public function testOnProductEdit()
    {
        $formView = new FormView();

        $this->env->expects($this->once())
            ->method('render')
            ->with('OroCatalogBundle:Product:category_update.html.twig', ['form' => $formView])
            ->willReturn('');

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new Product(), $formView);
        $this->listener->onProductEdit($event);
    }

    public function testOnProductView()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|CategoryRepository $repository */
        $repository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneByProduct'])
            ->getMock();

        $product = new Product();
        $category = new Category();

        $repository
            ->expects($this->once())
            ->method('findOneByProduct')
            ->with($product)
            ->willReturn($category);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCatalogBundle:Category')
            ->willReturn($repository);

        $this->env->expects($this->once())
            ->method('render')
            ->with('OroCatalogBundle:Product:category_view.html.twig', ['entity' => $category])
            ->willReturn('');

        $scrollData = $this->getPreparedScrollData();

        $event = new BeforeListRenderEvent($this->env, $scrollData, new Product());

        $this->listener->onProductView($event);
        $this->assertScrollData($scrollData);
    }

    public function testOnProductViewWithoutCategory()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|CategoryRepository $repository */
        $repository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneByProduct'])
            ->getMock();

        $product = new Product();

        $repository
            ->expects($this->once())
            ->method('findOneByProduct')
            ->with($product)
            ->willReturn(null);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCatalogBundle:Category')
            ->willReturn($repository);

        $this->env->expects($this->never())
            ->method('render');

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new Product());

        $this->listener->onProductView($event);
    }

    public function testOnProductViewInvalidEntity()
    {
        $this->expectException(\Oro\Component\Exception\UnexpectedTypeException::class);
        $scrollData = new ScrollData();

        $event = new BeforeListRenderEvent($this->env, $scrollData, new \stdClass());

        $this->listener->onProductView($event);
    }

    /**
     * @return ScrollData
     */
    protected function getPreparedScrollData()
    {
        $data[ScrollData::DATA_BLOCKS][FormViewListener::GENERAL_BLOCK][ScrollData::SUB_BLOCKS][0][ScrollData::DATA] = [
            'productName' => [],
        ];

        return new ScrollData($data);
    }

    private function assertScrollData(ScrollData $scrollData)
    {
        $data = $scrollData->getData();
        $generalBlockData = $data[ScrollData::DATA_BLOCKS][FormViewListener::GENERAL_BLOCK][ScrollData::SUB_BLOCKS]
            [0][ScrollData::DATA];

        $this->assertArrayHasKey('productName', $generalBlockData);
        $this->assertArrayHasKey(FormViewListener::CATEGORY_FIELD, $generalBlockData);

        reset($generalBlockData);
        $this->assertEquals(FormViewListener::CATEGORY_FIELD, key($generalBlockData), 'Category not a first element');
    }
}
