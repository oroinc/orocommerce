<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\Tests\Functional\EventListener\Order;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderTotalEventListener;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\TaxBundle\EventListener\Order\OrderTaxesListener;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxSubtotalProvider;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadOrderLineItemDraftDataWithDeletedItemAndTaxes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @dbIsolationPerTest
 */
final class OrderTaxesListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private DraftSessionOrmFilterManager $draftSessionOrmFilterManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadOrderLineItemDraftDataWithDeletedItemAndTaxes::class,
        ]);

        self::getConfigManager()->set('oro_tax.tax_enable', true);
        self::getConfigManager()->set('oro_tax.use_as_base_by_default', 'destination');
        self::getConfigManager()->set('oro_tax.destination', 'shipping_address');
        self::getConfigManager()->set('oro_tax.start_calculation_on', 'item');
        self::getConfigManager()->set('oro_tax.start_calculation_with', 'row_total');
        self::getConfigManager()->set('oro_tax.product_prices_include_tax', false);
        self::getConfigManager()->flush();

        $this->clearTaxCache();

        $this->draftSessionOrmFilterManager = self::getContainer()
            ->get('oro_order.draft_session.manager.draft_session_orm_filter_manager');
        $this->draftSessionOrmFilterManager->disable();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->draftSessionOrmFilterManager->enable();
        $this->clearTaxCache();

        parent::tearDown();
    }

    private function clearTaxCache(): void
    {
        self::getContainer()->get('oro_tax.taxation_provider.cache')->clear();
        $matchers = self::getContainer()->get('oro_tax.address_matcher_registry')->getMatchers();
        foreach ($matchers as $matcher) {
            if ($matcher instanceof ResetInterface) {
                $matcher->reset();
            }
        }
    }

    private function getTaxProvider(): TaxProviderInterface
    {
        return self::getContainer()->get('oro_tax.provider.tax_provider_registry')->getEnabledProvider();
    }

    private function resultItemsToTaxItems(array $items): array
    {
        return array_map(
            static fn (Result $lineItem) => [
                'unit' => $lineItem->getUnit()->getArrayCopy(),
                'row' => $lineItem->getRow()->getArrayCopy(),
                'taxes' => array_map(
                    static fn (AbstractResultElement $item) => $item->getArrayCopy(),
                    $lineItem->getTaxes()
                ),
            ],
            $items
        );
    }

    private function dispatchOrderEvent(Order $order): OrderEvent
    {
        $form = self::getContainer()->get('form.factory')->create(
            OrderType::class,
            $order,
            ['validation_groups' => ['order_entry_point']]
        );
        $form->submit([], false);

        $event = new OrderEvent($form, $form->getData(), []);
        self::getContainer()->get('oro_tax.event_listener.order.taxes')->onOrderEvent($event);
        self::getContainer()->get('oro_order.event_listener.order.total')->onOrderEvent($event);

        return $event;
    }

    private function getTaxSubtotalAmount(OrderEvent $event): float
    {
        $totals = $event->getData()[OrderTotalEventListener::TOTALS_KEY];
        $subtotals = $totals[TotalProcessorProvider::SUBTOTALS];

        foreach ($subtotals as $subtotal) {
            if ($subtotal['type'] === TaxSubtotalProvider::TYPE) {
                return (float)$subtotal['amount'];
            }
        }

        return 0.0;
    }

    public function testTaxAmountsDifferAfterDraftSyncRemovesLineItem(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Order $orderDraft */
        $orderDraft = $this->getReference(LoadOrderLineItemDraftDataWithDeletedItemAndTaxes::ORDER_DRAFT);

        self::assertCount(2, $order->getLineItems());

        // Save taxes for the order so TaxValue records exist in the database.
        $this->getTaxProvider()->saveTax($order);

        // Get original taxes directly from the tax provider.
        $originalResult = $this->getTaxProvider()->getTax($order);
        $originalTaxItems = $this->resultItemsToTaxItems($originalResult->getItems());
        self::assertCount(2, $originalTaxItems);

        $originalRowTaxAmounts = array_map(
            static fn (array $item) => $item['row']['taxAmount'],
            $originalTaxItems
        );

        // Synchronize the draft that marks one line item as deleted.
        /** @var EntityDraftSynchronizerInterface $synchronizer */
        $synchronizer = self::getContainer()->get('oro_order.draft_session.synchronizer');
        $synchronizer->synchronizeFromDraft($orderDraft, $order);

        self::assertCount(1, $order->getLineItems());

        // Get taxes after draft synchronization via OrderEvent — exactly as AjaxOrderController does.
        $event = $this->dispatchOrderEvent($order);

        $syncedTaxItems = $event->getData()[OrderTaxesListener::TAX_ITEMS];
        self::assertCount(1, $syncedTaxItems);

        $syncedRowTaxAmounts = array_map(
            static fn (array $item) => $item['row']['taxAmount'],
            $syncedTaxItems
        );

        self::assertNotEquals(
            $originalRowTaxAmounts,
            $syncedRowTaxAmounts,
            'Row tax amounts must differ after draft synchronization removes a line item'
        );

        // Verify the tax subtotal in totals reflects the removal.
        $syncedTaxSubtotalAmount = $this->getTaxSubtotalAmount($event);
        $originalTaxSubtotalAmount = (float)$originalResult->getTotal()->getArrayCopy()['taxAmount'];
        self::assertNotEquals(
            $originalTaxSubtotalAmount,
            $syncedTaxSubtotalAmount,
            'Tax subtotal in totals must differ after draft synchronization removes a line item'
        );
    }

    public function testTaxAmountsBecomeZeroWhenAllLineItemsDeletedViaDraft(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Order $orderDraft */
        $orderDraft = $this->getReference(LoadOrderLineItemDraftDataWithDeletedItemAndTaxes::ORDER_DRAFT_ALL_DELETED);

        self::assertCount(2, $order->getLineItems());

        // Save taxes for the order so TaxValue records exist in the database.
        $this->getTaxProvider()->saveTax($order);

        // Get original taxes directly from the tax provider.
        $originalResult = $this->getTaxProvider()->getTax($order);
        $originalTaxItems = $this->resultItemsToTaxItems($originalResult->getItems());
        self::assertCount(2, $originalTaxItems);

        // Synchronize the draft that marks all line items as deleted.
        /** @var EntityDraftSynchronizerInterface $synchronizer */
        $synchronizer = self::getContainer()->get('oro_order.draft_session.synchronizer');
        $synchronizer->synchronizeFromDraft($orderDraft, $order);

        self::assertCount(0, $order->getLineItems());

        // Get taxes after draft synchronization via OrderEvent.
        $event = $this->dispatchOrderEvent($order);

        $syncedTaxItems = $event->getData()[OrderTaxesListener::TAX_ITEMS];
        self::assertCount(0, $syncedTaxItems);

        // Verify the tax subtotal in totals becomes zero.
        $syncedTaxSubtotalAmount = $this->getTaxSubtotalAmount($event);
        self::assertEquals(0.0, $syncedTaxSubtotalAmount);
    }
}
