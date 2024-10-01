<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\ProductBrandViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Twig\Markup;

class ProductBrandViewListenerTest extends TestCase
{
    private ProductBrandViewListener $brandViewListener;
    private AuthorizationCheckerInterface|MockObject $authorizationChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->brandViewListener = new ProductBrandViewListener(
            $this->authorizationChecker
        );
    }

    /** @dataProvider onViewListDataProvider */
    public function testOnViewList(bool $isGranted, array $scrollDataArray, array $expectedResult): void
    {
        $this
            ->authorizationChecker
            ->expects(self::atLeastOnce())
            ->method('isGranted')
            ->willReturn($isGranted);

        $environment = $this->createMock(Environment::class);
        $product = new Product();
        $product->setBrand(new Brand());
        $scrollData = new ScrollData($scrollDataArray);
        $event = new BeforeListRenderEvent(
            $environment,
            $scrollData,
            $product
        );

        $this->brandViewListener->onViewList($event);

        self::assertEquals($event->getScrollData()->getData(), $expectedResult);
    }

    public function testOnViewListWithNotProductEntity(): void
    {
        $this
            ->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $environment = $this->createMock(Environment::class);
        $entity = new \stdClass();
        $scrollData = new ScrollData([]);
        $event = new BeforeListRenderEvent(
            $environment,
            $scrollData,
            $entity
        );

        $this->brandViewListener->onViewList($event);
    }

    public function onViewListDataProvider(): array
    {
        $markup = new Markup(
            'TestContent',
            'UTf-8'
        );
        $scrollDataArray = [
            ScrollData::DATA_BLOCKS => [
                'general' => [
                    ScrollData::TITLE => 'TestTitle',
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => [
                                'sku' => $markup,
                                'names' => $markup,
                                'info' => $markup,
                            ]
                        ],
                        [
                            ScrollData::DATA => [
                                'unitOfQuantity' => $markup,
                                'brand' => 'TestBrand',
                            ]
                        ],
                    ]
                ]
            ]
        ];

        return [
            'user granted to view brands' => [
                'isGranted' => true,
                'scrollDataArray' => $scrollDataArray,
                'expectedResult' => $scrollDataArray
            ],
            'user not granted to view brands' => [
                'isGranted' => false,
                'scrollDataArray' => $scrollDataArray,
                'expectedResult' => [
                    ScrollData::DATA_BLOCKS => [
                        'general' => [
                            ScrollData::TITLE => 'TestTitle',
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [
                                        'sku' => $markup,
                                        'names' => $markup,
                                        'info' => $markup,
                                    ]
                                ],
                                [
                                    ScrollData::DATA => [
                                        'unitOfQuantity' => $markup,
                                    ]
                                ],
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }
}
