<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactory;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Storage\DataStorageInterface;
use Oro\Bundle\RFPBundle\Form\Extension\OrderDataStorageExtension;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderDataStorageExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceProvider;

    /** @var ProductPriceScopeCriteriaFactory */
    private $priceScopeCriteriaFactory;

    /** @var ProductPriceCriteriaFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceCriteriaFactory;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var OrderDataStorageExtension */
    private $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = new ProductPriceScopeCriteriaFactory();
        $this->productPriceCriteriaFactory = $this->createMock(ProductPriceCriteriaFactoryInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->extension = new OrderDataStorageExtension(
            $this->requestStack,
            $this->productPriceProvider,
            $this->priceScopeCriteriaFactory,
            $this->productPriceCriteriaFactory
        );
        $this->extension->setFeatureChecker($this->featureChecker);
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([OrderType::class], OrderDataStorageExtension::getExtendedTypes());
    }

    /**
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(
        array $lineItemsInfo,
        array $lineItemToMatchedPrices,
        array $matchedPrices
    ) {
        $order = $this->getOrder($lineItemsInfo);
        $matchedPrices = $this->getMatchedPrices($matchedPrices);
        $priceScopeCriteria = $this->getPriceScopeCriteria($lineItemsInfo, $order);
        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);

        $this->extension->addFeature('test');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test')
            ->willReturn(true);

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with(DataStorageInterface::STORAGE_KEY)
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $orderLineItemsKeys = array_column($lineItemsInfo['lineItems'], 'identity');

        $productPriceCriteria->expects($this->any())
            ->method('getIdentifier')
            ->willReturnOnConsecutiveCalls(...$orderLineItemsKeys);

        $this->productPriceCriteriaFactory->expects($this->once())
            ->method('createListFromProductLineItems')
            ->with($order->getLineItems(), $order->getCurrency())
            ->willReturn(array_fill(0, count($orderLineItemsKeys), $productPriceCriteria));

        $this->productPriceProvider->expects($this->once())
            ->method('getMatchedPrices')
            ->with($this->isType('array'), $priceScopeCriteria)
            ->willReturn($matchedPrices);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->any())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SET_DATA,
                $this->logicalAnd(
                    $this->isInstanceOf(\Closure::class),
                    $this->callback(function (\Closure $closure) use ($order) {
                        $event = $this->createMock(FormEvent::class);
                        $event->expects($this->once())
                            ->method('getData')
                            ->willReturn($order);
                        $this->assertNull($closure($event));

                        return true;
                    })
                )
            );

        $this->extension->buildForm($builder, []);

        foreach ($order->getLineItems() as $lineItem) {
            if (array_key_exists($lineItem->getId(), $lineItemToMatchedPrices)) {
                $identifier = $lineItemToMatchedPrices[$lineItem->getId()];
                $this->assertEquals($matchedPrices[$identifier], $lineItem->getPrice());
            } else {
                $this->assertNull($lineItem->getPrice());
            }
        }
    }

    public function buildFormDataProvider(): array
    {
        return [
            [
                'data' => [
                    'customer' => ['id' => 1],
                    'website' => ['id' => 1],
                    'currency' => 'USD',
                    'lineItems' => [
                        [
                            'id' => 1,
                            'product' => ['id' => 1],
                            'productUnit' => ['code' => 'piece'],
                            'quantity' => 2,
                            'identity' => '1-piece-2-USD'
                        ],
                        [
                            'id' => 2,
                            'product' => ['id' => 3],
                            'productUnit' => ['code' => 'kg'],
                            'quantity' => 20,
                            'identity' => '3-kg-20-USD'
                        ],
                        [
                            'id' => 3,
                            'product' => ['id' => 5],
                            'productUnit' => ['code' => 'box'],
                            'quantity' => 200,
                            'identity' => '5-box-200-USD'
                        ],
                    ],
                ],
                'lineItemToMatchedPrices' => [
                    1 => '1-piece-2-USD',
                    2 => '3-kg-20-USD',
                ],
                'matchedPrices' => [
                    '1-piece-2-USD' => [
                        'value' => 10,
                        'currency' => 'USD',
                    ],
                    '3-kg-20-USD' => [
                        'value' => 100,
                        'currency' => 'USD',
                    ],
                ],
            ]
        ];
    }

    private function getOrder(array $data): Order
    {
        $lineItems = new ArrayCollection();
        foreach ($data['lineItems'] as $lineItem) {
            $lineItem['product'] = $this
                ->getEntity(Product::class, $lineItem['product']);
            $lineItem['productUnit'] = $this
                ->getEntity(ProductUnit::class, $lineItem['productUnit']);
            unset($lineItem['identity']);
            $lineItems->add($this->getEntity(OrderLineItem::class, $lineItem));
        }
        $data['customer'] = $this->getEntity(Customer::class, $data['customer']);
        $data['website'] = $this->getEntity(Website::class, $data['website']);
        $data['lineItems'] = $lineItems;
        return $this->getEntity(Order::class, $data);
    }

    private function getPriceScopeCriteria(array $data, Order $order): ProductPriceScopeCriteriaInterface
    {
        $customer = $this->getEntity(Customer::class, $data['customer']);
        $website = $this->getEntity(Website::class, $data['website']);

        return $this->priceScopeCriteriaFactory->create(
            $website,
            $customer,
            $order
        );
    }

    private function getMatchedPrices(array $matchedPrices): array
    {
        foreach ($matchedPrices as &$matchedPrice) {
            $matchedPrice = Price::create($matchedPrice['value'], $matchedPrice['currency']);
        }
        return $matchedPrices;
    }

    public function testBuildFormNotApplicableEmptyGetParameter()
    {
        $this->extension->addFeature('test');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test')
            ->willReturn(true);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())
            ->method('addEventListener');

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with(DataStorageInterface::STORAGE_KEY)
            ->willReturn(null);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormNotApplicableEmptyRequest()
    {
        $this->extension->addFeature('test');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test')
            ->willReturn(true);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())
            ->method('addEventListener');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormDisabledFeature()
    {
        $this->extension->addFeature('test');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test')
            ->willReturn(false);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())
            ->method('addEventListener');

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');

        $this->extension->buildForm($builder, []);
    }

    /**
     * @dataProvider fillDataDataProvider
     */
    public function testFillDataEmptyCriteria(array $data)
    {
        $this->extension->addFeature('test');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('test')
            ->willReturn(true);

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('get')
            ->with(DataStorageInterface::STORAGE_KEY)
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->productPriceCriteriaFactory->expects($this->any())
            ->method('createListFromProductLineItems')
            ->willReturn([]);

        $this->productPriceProvider->expects($this->never())
            ->method('getMatchedPrices');

        $order = $this->getOrder($data);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->any())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SET_DATA,
                $this->callback(function (\Closure $closure) use ($order) {
                    $event = $this->createMock(FormEvent::class);
                    $event->expects($this->once())
                        ->method('getData')
                        ->willReturn($order);
                    $closure($event);

                    return true;
                })
            );

        $this->extension->buildForm($builder, []);
    }

    public function fillDataDataProvider(): array
    {
        return [
            'no line items' => [
                [
                    'customer' => ['id' => 1],
                    'website' => ['id' => 1],
                    'currency' => 'USD',
                    'lineItems' => [],
                ]
            ],
            'invalid arguments' => [
                [
                    'customer' => ['id' => 1],
                    'website' => ['id' => 1],
                    'currency' => 'USD',
                    'lineItems' => [
                        [
                            'id' => 1,
                            'product' => ['id' => null],
                            'productUnit' => ['code' => 'piece'],
                            'quantity' => 2,
                        ]
                    ],
                ]
            ],
            'invalid currency' => [
                [
                    'customer' => ['id' => 1],
                    'website' => ['id' => 1],
                    'currency' => '',
                    'lineItems' => [
                        [
                            'id' => 1,
                            'product' => ['id' => 1],
                            'productUnit' => ['code' => 'piece'],
                            'quantity' => 2,
                        ],
                    ],
                ]
            ],
        ];
    }
}
