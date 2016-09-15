<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\WarehouseBundle\EventListener\CategoryWarehouseFormViewListener;

class CategoryWarehouseFormViewListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var CategoryWarehouseFormViewListener
     */
    protected $categoryWarehouseFormViewListener;

    /** @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject * */
    protected $event;

    protected function setUp()
    {
        $this->requestStack = $this->getMock(RequestStack::class);
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->categoryWarehouseFormViewListener = new CategoryWarehouseFormViewListener(
            $this->requestStack,
            $this->doctrineHelper,
            $this->translator
        );
        $this->event = $this->getMockBuilder(BeforeListRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testOnCategoryEditIgnoredIfNoCategoryId()
    {
        $this->doctrineHelper->expects($this->never())->method('getEntityReference');
        $this->categoryWarehouseFormViewListener->onCategoryEdit($this->event);
    }

    public function testOnCategoryEditIgnoredIfNoCategoryFound()
    {
        $this->doctrineHelper->expects($this->once())->method('getEntityReference');
        $this->request->expects($this->once())->method('get')->willReturn('1');
        $this->categoryWarehouseFormViewListener->onCategoryEdit($this->event);
    }

    public function testCategoryEditRendersAndAddsSubBlock()
    {
        $this->request->expects($this->once())->method('get')->willReturn('1');
        $category = new Category();
        $this->doctrineHelper->expects($this->once())->method('getEntityReference')->willReturn($category);
        $env = $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock();
        $this->event->expects($this->once())->method('getEnvironment')->willReturn($env);
        $scrollData = $this->getMock(ScrollData::class);
        $this->event->expects($this->once())->method('getScrollData')->willReturn($scrollData);
        $env->expects($this->once())->method('render');
        $scrollData->expects($this->once())->method('addSubBlockData');
        $scrollData->expects($this->once())->method('getData')->willReturn(['dataBlocks' => [1 => ['title' => 1]]]);
        $this->categoryWarehouseFormViewListener->onCategoryEdit($this->event);
    }
}
