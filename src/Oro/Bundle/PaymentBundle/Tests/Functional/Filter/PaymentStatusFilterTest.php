<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Filter;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Filter\PaymentStatusFilter;
use Oro\Bundle\PaymentBundle\Form\Type\Filter\PaymentStatusFilterType;
use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PaymentBundle\Tests\Functional\Filter\DataFixtures\LoadPaymentStatusFilterTestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PaymentStatusFilterTest extends WebTestCase
{
    private PaymentStatusFilter $filter;
    private Manager $datagridManager;
    private TranslatorInterface $translator;
    private PaymentStatusLabelFormatter $paymentStatusLabelFormatter;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPaymentStatusFilterTestData::class]);

        $formFactory = self::getContainer()->get('form.factory');
        $filterUtility = self::getContainer()->get('oro_filter.filter_utility');
        $this->datagridManager = self::getContainer()->get('oro_datagrid.datagrid.manager');
        $this->translator = self::getContainer()->get('translator');
        $this->paymentStatusLabelFormatter = self::getContainer()->get('oro_payment.formatter.payment_status_label');

        $this->filter = new PaymentStatusFilter($formFactory, $filterUtility);
    }

    public function testGetFormType(): void
    {
        $form = $this->filter->getForm();

        self::assertInstanceOf(PaymentStatusFilterType::class, $form->getConfig()->getType()->getInnerType());
    }

    public function testInitSetsDefaultFrontendType(): void
    {
        $this->filter->init('payment_status_filter', [
            FilterUtility::DATA_NAME_KEY => 'paymentStatus',
        ]);

        $metadata = $this->filter->getMetadata();

        self::assertEquals('select', $metadata['type']);
    }

    public function testFilterMetadataOnOrdersGrid(): void
    {
        $datagrid = $this->datagridManager->getDatagrid('orders-grid');
        $resolvedMetadata = $datagrid->getResolvedMetadata();

        self::assertTrue($resolvedMetadata->offsetExists('filters'));

        $filtersMetadata = $resolvedMetadata->offsetGet('filters');
        $paymentStatusFilterMetadata = null;
        foreach ($filtersMetadata as $filterMetadatum) {
            if ($filterMetadatum['name'] === 'paymentStatus') {
                $paymentStatusFilterMetadata = $filterMetadatum;
                break;
            }
        }

        self::assertNotNull($paymentStatusFilterMetadata, 'Filter metadata for paymentStatus not found');
        self::assertEquals('select', $paymentStatusFilterMetadata['type']);
        self::assertEquals(
            $this->translator->trans('oro.order.payment_status.label'),
            $paymentStatusFilterMetadata['label']
        );

        $availableChoices = $this->paymentStatusLabelFormatter->getAvailableStatuses(Order::class);
        $expectedChoices = [];
        foreach ($availableChoices as $label => $value) {
            $expectedChoices[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        self::assertEquals($expectedChoices, $paymentStatusFilterMetadata['choices']);
    }

    public function testFilterByPaidInFullOnOrdersGrid(): void
    {
        $datagrid = $this->datagridManager->getDatagrid(
            'orders-grid',
            [
                AbstractFilterExtension::FILTER_ROOT_PARAM => [
                    'paymentStatus' => ['value' => PaymentStatuses::PAID_IN_FULL],
                ],
            ]
        );

        $expectedOrders = [
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_PAID_IN_FULL),
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_FORCED_STATUS),
        ];

        $orderIdentifiers = array_column($datagrid->getData()->getData(), 'identifier');

        foreach ($expectedOrders as $expectedOrder) {
            self::assertContains($expectedOrder->getIdentifier(), $orderIdentifiers);
        }
    }

    public function testFilterByMultipleStatusesOnOrdersGrid(): void
    {
        $datagrid = $this->datagridManager->getDatagrid(
            'orders-grid',
            [
                AbstractFilterExtension::FILTER_ROOT_PARAM => [
                    'paymentStatus' => [
                        'value' => [
                            PaymentStatuses::PAID_IN_FULL,
                            PaymentStatuses::PAID_PARTIALLY,
                            PaymentStatuses::PENDING,
                        ],
                    ],
                ],
            ]
        );

        // Enable multiple selection for the filter.
        $datagrid->getConfig()->offsetSetByPath(
            'filters.columns.paymentStatus.options.field_options.multiple',
            true
        );

        $expectedOrders = [
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_PAID_IN_FULL),
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_PAID_PARTIALLY),
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_PENDING),
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_FORCED_STATUS),
        ];

        $orderIdentifiers = array_column($datagrid->getData()->getData(), 'identifier');

        foreach ($expectedOrders as $expectedOrder) {
            self::assertContains($expectedOrder->getIdentifier(), $orderIdentifiers);
        }

        // Verify that orders with other statuses are not included
        $excludedOrders = [
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_CANCELED),
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_REFUNDED),
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_AUTHORIZED),
        ];

        foreach ($excludedOrders as $excludedOrder) {
            self::assertNotContains($excludedOrder->getIdentifier(), $orderIdentifiers);
        }
    }

    public function testFilterByNotContainsOnOrdersGrid(): void
    {
        $datagrid = $this->datagridManager->getDatagrid(
            'orders-grid',
            [
                AbstractFilterExtension::FILTER_ROOT_PARAM => [
                    'paymentStatus' => [
                        'type' => ChoiceFilterType::TYPE_NOT_CONTAINS,
                        'value' => [PaymentStatuses::CANCELED, PaymentStatuses::DECLINED],
                    ],
                ],
            ]
        );

        // Enable multiple selection for the filter.
        $datagrid->getConfig()->offsetSetByPath(
            'filters.columns.paymentStatus.options.field_options.multiple',
            true
        );

        $orderIdentifiers = array_column($datagrid->getData()->getData(), 'identifier');

        // Verify that canceled and declined orders are excluded
        $excludedOrders = [
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_CANCELED),
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_PAYMENT_FAILED), // DECLINED status
        ];

        foreach ($excludedOrders as $excludedOrder) {
            self::assertNotContains($excludedOrder->getIdentifier(), $orderIdentifiers);
        }

        // Verify that other orders are included
        $includedOrders = [
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_PAID_IN_FULL),
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_PAID_PARTIALLY),
            $this->getReference(LoadPaymentStatusFilterTestData::ORDER_PENDING),
        ];

        foreach ($includedOrders as $includedOrder) {
            self::assertContains($includedOrder->getIdentifier(), $orderIdentifiers);
        }
    }

    public function testFilterCombinedWithOtherFiltersOnOrdersGrid(): void
    {
        $datagrid = $this->datagridManager->getDatagrid(
            'orders-grid',
            [
                AbstractFilterExtension::FILTER_ROOT_PARAM => [
                    'paymentStatus' => ['value' => PaymentStatuses::PAID_IN_FULL],
                    'currency' => ['value' => 'USD'],
                ],
            ]
        );

        /** @var Order $expectedOrder */
        $expectedOrder = $this->getReference(LoadPaymentStatusFilterTestData::ORDER_PAID_IN_FULL);

        $orderIdentifiers = array_column($datagrid->getData()->getData(), 'identifier');
        self::assertContains($expectedOrder->getIdentifier(), $orderIdentifiers);

        // Verify EUR order with PAID_IN_FULL status is excluded due to currency filter
        /** @var Order $excludedOrder */
        $excludedOrder = $this->getReference(LoadPaymentStatusFilterTestData::ORDER_FORCED_STATUS);
        self::assertNotContains($excludedOrder->getIdentifier(), $orderIdentifiers);
    }
}
