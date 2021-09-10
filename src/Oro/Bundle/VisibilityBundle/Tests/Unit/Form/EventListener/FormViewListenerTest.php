<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\VisibilityBundle\Form\EventListener\FormViewListener;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class FormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Environment
     */
    protected $env;

    /**
     * @var FormViewListener
     */
    protected $listener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RequestStack
     */
    protected $requestStack;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->env = $this->createMock(Environment::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->listener = new FormViewListener($this->translator, $this->doctrineHelper, $this->requestStack);
    }

    public function testOnCategoryEditNoRequest()
    {
        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);
        $this->listener->onCategoryEdit($event);
    }

    public function testOnCategoryEdit()
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('get')->with('id')->willReturn(1);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $scrollData = new ScrollData();

        $formView = new FormView();
        $event = new BeforeListRenderEvent($this->env, $scrollData, new \stdClass(), $formView);

        $category = new Category();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroCatalogBundle:Category', 1)
            ->willReturn($category);

        $this->env->expects($this->once())
            ->method('render')
            ->with(
                '@OroVisibility/Category/customer_category_visibility_edit.html.twig',
                ['entity' => $category, 'form' => $formView]
            )
            ->willReturn('rendered');

        $this->listener->onCategoryEdit($event);

        $expectedData = [
            ScrollData::DATA_BLOCKS => [
                0 => [
                    ScrollData::SUB_BLOCKS => [
                        0 => [
                            ScrollData::DATA => [
                                0 => 'rendered',
                            ],
                        ],
                    ],
                    ScrollData::TITLE => 'oro.visibility.categoryvisibility.visibility.label.trans',
                    ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                ],
            ],
        ];

        $this->assertEquals($expectedData, $scrollData->getData());
    }
}
