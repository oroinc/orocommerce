<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\WebsiteSearchTerm\Product;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\Product\AddProductToSearchTermViewPageListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class AddProductToSearchTermViewPageListenerTest extends TestCase
{
    private Environment|MockObject $environment;

    private AddProductToSearchTermViewPageListener $listener;

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);

        $this->listener = new AddProductToSearchTermViewPageListener();
    }

    public function testOnEntityViewWhenBlankSearchTerm(): void
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new SearchTerm());

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenNotRedirectProduct(): void
    {
        $scrollData = new ScrollData();
        $searchTerm = (new SearchTerm())->setActionType('redirect');
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenNotRedirect(): void
    {
        $scrollData = new ScrollData();
        $searchTerm = (new SearchTerm())->setActionType('modify')->setRedirectActionType('product');
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $this->listener->onEntityView($event);

        self::assertEquals([], $scrollData->getData());
    }

    public function testOnEntityViewWhenEmptyScrollData(): void
    {
        $scrollData = new ScrollData();
        $product = new Product();
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('product')
            ->setRedirectProduct($product);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $productData = 'product data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroProduct/SearchTerm/redirect_product_field.html.twig',
                ['entity' => $searchTerm->getRedirectProduct()]
            )
            ->willReturn($productData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'redirectProduct' => $productData,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $scrollData->getData()
        );
    }

    public function testOnEntityViewWhenNotEmptyScrollData(): void
    {
        $scrollData = new ScrollData([
            ScrollData::DATA_BLOCKS => [
                'action' => [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => ['sampleField' => 'sample data'],
                        ],
                    ],
                ],
            ],
        ]);
        $product = new Product();
        $searchTerm = (new SearchTermStub())
            ->setActionType('redirect')
            ->setRedirectActionType('product')
            ->setRedirectProduct($product);
        $event = new BeforeListRenderEvent($this->environment, $scrollData, $searchTerm);

        $productData = 'product data';
        $this->environment
            ->expects(self::once())
            ->method('render')
            ->with(
                '@OroProduct/SearchTerm/redirect_product_field.html.twig',
                ['entity' => $searchTerm->getRedirectProduct()]
            )
            ->willReturn($productData);

        $this->listener->onEntityView($event);

        self::assertEquals(
            [
                ScrollData::DATA_BLOCKS => [
                    'action' => [
                        ScrollData::SUB_BLOCKS => [
                            [
                                ScrollData::DATA => [
                                    'sampleField' => 'sample data',
                                    'redirectProduct' => $productData,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $scrollData->getData()
        );
    }
}
