<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\CheckoutBundle\Datagrid\CheckoutGridListener;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Model\CompletedCheckoutData;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutGridListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const ENTITY_1 = 'Entity1';
    private const ENTITY_2 = 'Entity2';

    private const SUBTOTAL = 20.0;
    private const SHIPPING_COST = 10;
    private const TOTAL = 30.0;

    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalProcessor;

    /** @var CheckoutRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutRepository;

    /** @var CheckoutGridListener */
    private $listener;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->checkoutRepository = $this->createMock(CheckoutRepository::class);
        $this->totalProcessor = $this->createMock(TotalProcessorProvider::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap([
                ['currency', true],
                ['subtotal', true],
                ['total', true],
            ]);
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturnMap([
                ['totals', true]
            ]);

        $currencyManager = $this->createMock(UserCurrencyManager::class);
        $currencyManager->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->listener = new CheckoutGridListener(
            $currencyManager,
            $this->checkoutRepository,
            $this->totalProcessor,
            $this->entityNameResolver,
            $this->doctrineHelper,
            (new InflectorFactory())->build()
        );
    }

    public function testOnBuildBefore()
    {
        $configuration = $this->getGridConfiguration();
        $datagrid = $this->createMock(DatagridInterface::class);

        $parameters = $this->createMock(ParameterBag::class);
        $parameters->expects($this->once())
            ->method('set')
            ->with(CheckoutGridListener::USER_CURRENCY_PARAMETER, 'USD');
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $event = new BuildBefore($datagrid, $configuration);
        $this->listener->onBuildBefore($event);
    }

    private function getGridConfiguration(): DatagridConfiguration
    {
        $configuration = DatagridConfiguration::createNamed('test', []);
        $configuration->offsetAddToArrayByPath('[source][query][from]', [['alias' => 'rootAlias']]);
        $configuration->offsetSetByPath('[source][query][select]', ['rootAlias.id as id']);
        $configuration->offsetSetByPath('[columns]', ['id' => ['label' => 'id']]);
        $configuration->offsetSetByPath('[filters][columns]', ['id' => ['data_name' => 'id']]);
        $configuration->offsetSetByPath('[sorters][columns]', ['id' => ['data_name' => 'id']]);

        return $configuration;
    }

    public function testOnResultAfter()
    {
        $event = $this->createMock(OrmResultAfter::class);
        $event->expects($this->once())
            ->method('getRecords')
            ->willReturn([]);
        $this->listener->onResultAfter($event);
    }

    public function testBuildItemsCountColumn()
    {
        $data = array_combine(range(2, 10, 2), range(2, 10, 2));

        $this->checkoutRepository->expects($this->atLeastOnce())
            ->method('countItemsPerCheckout')
            ->willReturn($data);

        $records = [];
        $checkouts = [];

        for ($i = 1; $i <= 10; $i++) {
            $completed = (bool) ($i % 2);

            $records[$i] = new ResultRecord(['id' => $i, 'completed' => $completed]);

            $checkout = new Checkout();
            $checkout->getCompletedData()->offsetSet(CompletedCheckoutData::ITEMS_COUNT, $completed ? 42 + $i : null);

            $checkouts[$i] = $checkout;
        }

        $this->checkoutRepository->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) use ($checkouts) {
                return $checkouts[$id];
            });

        $event = $this->createMock(OrmResultAfter::class);
        $event->expects($this->atLeastOnce())
            ->method('getRecords')
            ->willReturn($records);

        $this->listener->onResultAfter($event);

        foreach ($records as $key => $record) {
            $completed = (bool) ($key % 2);

            $this->assertEquals($completed ? 42 + $key : $key, $record->getValue('itemsCount'));
        }
    }

    public function testBuildStartedFromColumn()
    {
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 42, 'label' => 'test']);

        $checkout1 = new Checkout();
        $source1 = (new CheckoutSourceStub())->setShoppingList($shoppingList);
        $checkout1->setSource($source1);

        $this->checkoutRepository->expects($this->atLeastOnce())
            ->method('getCheckoutsByIds')
            ->willReturn([
                3 => $checkout1,
                5 => (new Checkout())->setSource(new CheckoutSourceStub())
            ]);

        $foundSources = [];
        $records = [
            new ResultRecord(['id' => 3, 'completed' => false]),
            new ResultRecord(['id' => 2, 'completed' => false]),
            new ResultRecord(['id' => 4, 'completed' => true]),
            new ResultRecord(['id' => 5, 'completed' => false])
        ];

        $event = $this->createMock(OrmResultAfter::class);
        $event->expects($this->atLeastOnce())
            ->method('getRecords')
            ->willReturn($records);

        $this->entityNameResolver->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturnCallback(function ($entity) use ($shoppingList) {
                if ($entity === $shoppingList) {
                    return $shoppingList->getLabel();
                }

                return null;
            });

        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(function ($entity) use ($shoppingList) {
                if ($entity === $shoppingList) {
                    return $shoppingList->getId();
                }

                return null;
            });

        $checkout = new Checkout();
        $checkout->getCompletedData()->offsetSet(CompletedCheckoutData::STARTED_FROM, 'started test');

        $this->checkoutRepository->expects($this->any())
            ->method('find')
            ->willReturn($checkout);
        $this->totalProcessor->expects($this->any())
            ->method('getTotal')
            ->willReturn(new Subtotal());

        $this->listener->onResultAfter($event);

        foreach ($records as $record) {
            $startedFrom = $record->getValue('startedFrom');

            $foundSources[] = $startedFrom;

            $this->assertEquals($startedFrom['label'] ?? $startedFrom, $record->getValue('startedFromLabel'));
        }
        $this->assertCheckoutSource(
            $foundSources,
            $shoppingList->getLabel(),
            'shopping_list',
            42,
            'Did not found any ShoppingList entity'
        );
        $this->assertCheckoutSource(
            $foundSources,
            'started test',
            null,
            null,
            'Did not found any data from completed checkout'
        );
    }

    private function assertCheckoutSource(
        array $sources,
        string $expectedLabel,
        ?string $expectedType,
        ?int $expectedId,
        string $message
    ): void {
        $found = false;

        foreach ($sources as $source) {
            $typeFound = isset($source['type']) && ($expectedType === $source['type']);
            $labelFound = isset($source['label']) && ($expectedLabel === $source['label']);
            $idFound = isset($source['id']) && ($expectedId === $source['id']);
            if (($source === $expectedLabel) || ($typeFound && $labelFound && $idFound)) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, $message);
    }

    /**
     * @dataProvider updateTotalsDataProvider
     */
    public function testUpdateTotals(array $recordData, bool $withSource, array $expectedData)
    {
        $this->totalProcessor->expects($this->atMost(1))
            ->method('getTotal')
            ->willReturn((new Subtotal())->setAmount(self::SUBTOTAL)->setCurrency('EUR'));

        $shoppingList = new ShoppingList();
        $checkout = new Checkout();
        $source = (new CheckoutSourceStub())->setShoppingList($shoppingList);
        $checkout->setSource($source);
        $checkout->getCompletedData()->offsetSet(CompletedCheckoutData::CURRENCY, 'USD');

        $this->checkoutRepository->expects($this->any())
            ->method('find')
            ->willReturn($checkout);
        $this->checkoutRepository->expects($this->once())
            ->method('getCheckoutsByIds')
            ->willReturn($withSource ? [1 => $checkout] : []);

        $record = new ResultRecord($recordData);
        $records = [$record];

        $event = $this->createMock(OrmResultAfter::class);
        $event->expects($this->atLeastOnce())
            ->method('getRecords')
            ->willReturn($records);

        $this->listener->onResultAfter($event);

        foreach ($expectedData as $key => $value) {
            $this->assertSame($value, $record->getValue($key));
        }
    }

    public function updateTotalsDataProvider(): array
    {
        return [
            'with source and not valid totals' => [
                'recordData' => [
                    'id' => 1,
                    'subtotal' => 5,
                    'isSubtotalValid' => false,
                    'shippingEstimateAmount' => self::SHIPPING_COST,
                    'total' => 100,
                    'currency' => 'EUR',
                ],
                true,
                'expectedData' => [
                    'id' => 1,
                    'subtotal' => self::SUBTOTAL,
                    'shippingEstimateAmount' => self::SHIPPING_COST,
                    'total' => self::TOTAL,
                    'currency' => 'EUR',
                ],
            ],
            'without source and not valid totals' => [
                'recordData' => [
                    'id' => 1,
                    'subtotal' => 5,
                    'isSubtotalValid' => false,
                    'shippingEstimateAmount' => self::SHIPPING_COST,
                    'total' => 100,
                    'currency' => 'EUR',
                ],
                false,
                'expectedData' => [
                    'id' => 1,
                    'subtotal' => 5,
                    'shippingEstimateAmount' => 10,
                    'total' => 100,
                    'currency' => 'EUR',
                ],
            ],
            'with valid totals' => [
                'recordData' => [
                    'id' => 1,
                    'subtotal' => 5,
                    'isSubtotalValid' => true,
                    'shippingEstimateAmount' => self::SHIPPING_COST,
                    'total' => 100,
                    'currency' => 'EUR',
                ],
                true,
                'expectedData' => [
                    'id' => 1,
                    'subtotal' => 5,
                    'shippingEstimateAmount' => 10,
                    'total' => 100,
                    'currency' => 'EUR',
                ],
            ],
            'completed' => [
                'recordData' => [
                    'id' => 1,
                    'subtotal' => 5,
                    'isSubtotalValid' => true,
                    'shippingEstimateAmount' => self::SHIPPING_COST,
                    'total' => 100,
                    'currency' => 'EUR',
                    'completed' => true,
                ],
                true,
                'expectedData' => [
                    'id' => 1,
                    'subtotal' => 5,
                    'shippingEstimateAmount' => 10,
                    'total' => 100,
                    'currency' => 'USD',
                ],
            ],

        ];
    }
}
