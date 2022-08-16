<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\EventListener\ProductFormViewListener;
use Oro\Bundle\TaxBundle\Tests\Unit\Entity\ProductStub;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

class ProductFormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $env;

    /** @var ProductFormViewListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->env = $this->createMock(Environment::class);

        $this->listener = new ProductFormViewListener(
            $this->requestStack,
            $this->doctrineHelper,
            $this->featureChecker
        );
    }

    public function testOnViewNoRequest(): void
    {
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->featureChecker->expects(self::never())
            ->method('isResourceEnabled');

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityReference');

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());
        $this->listener->onView($event);
    }

    public function testOnViewTaxCodeDisabled(): void
    {
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($this->createMock(Request::class));

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with(ProductTaxCode::class, 'entities')
            ->willReturn(false);

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityReference');

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());
        $this->listener->onView($event);
    }

    public function testOnView(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('get')
            ->with('id')
            ->willReturn(1);
        $request->expects(self::once())
            ->method('get')
            ->willReturn(1);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with(ProductTaxCode::class, 'entities')
            ->willReturn(true);

        $taxCode = $this->createMock(ProductTaxCode::class);
        $product = $this->createMock(ProductStub::class);
        $product->expects(self::once())
            ->method('getTaxCode')
            ->willReturn($taxCode);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(Product::class, 1)
            ->willReturn($product);

        $this->env->expects(self::once())
            ->method('render')
            ->with('@OroTax/Product/tax_code_view.html.twig', ['entity' => $taxCode])
            ->willReturn('rendered');

        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                'general' => [
                    ScrollData::TITLE      => 'first block',
                    ScrollData::SUB_BLOCKS => [
                        [ScrollData::DATA => ['first subblock data']],
                        [ScrollData::DATA => ['second subblock data']]
                    ]
                ]
            ]
        ]);
        $event = new BeforeListRenderEvent($this->env, $scrollData, new \stdClass());
        $this->listener->onView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'general' => [
                        ScrollData::TITLE      => 'first block',
                        ScrollData::SUB_BLOCKS => [
                            [ScrollData::DATA => ['first subblock data']],
                            [ScrollData::DATA => ['second subblock data', 'rendered']]
                        ]
                    ]
                ]
            ],
            $event->getScrollData()->getData()
        );
    }

    public function testOnEditTaxCodeDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with(ProductTaxCode::class, 'entities')
            ->willReturn(false);

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityReference');

        $event = new BeforeListRenderEvent($this->env, new ScrollData(), new \stdClass());
        $this->listener->onEdit($event);
    }

    public function testOnEdit(): void
    {
        $formView = $this->createMock(FormView::class);

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with(ProductTaxCode::class, 'entities')
            ->willReturn(true);

        $this->env->expects(self::once())
            ->method('render')
            ->with('@OroTax/Product/tax_code_update.html.twig', ['form' => $formView])
            ->willReturn('rendered');

        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                'general' => [
                    ScrollData::TITLE      => 'first block',
                    ScrollData::SUB_BLOCKS => [
                        [ScrollData::DATA => ['first subblock data']],
                        [ScrollData::DATA => ['second subblock data']]
                    ]
                ]
            ]
        ]);
        $event = new BeforeListRenderEvent($this->env, $scrollData, new \stdClass(), $formView);
        $this->listener->onEdit($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'general' => [
                        ScrollData::TITLE      => 'first block',
                        ScrollData::SUB_BLOCKS => [
                            [ScrollData::DATA => ['first subblock data']],
                            [ScrollData::DATA => ['second subblock data', 'rendered']]
                        ]
                    ]
                ]
            ],
            $event->getScrollData()->getData()
        );
    }
}
