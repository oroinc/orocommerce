<?php

namespace Oro\Bundle\PromotionBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\PromotionBundle\Provider\OrdersAppliedDiscountsProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Aimed to add new column `Applied Discounts` to Order Line Items list in Order form on Order edit page
 */
class OrderLineItemTypeExtension extends AbstractTypeExtension
{
    const BASE_ORDER = 60;

    /**
     * @var TaxationSettingsProvider
     */
    protected $taxationSettingsProvider;

    /**
     * @var TaxManager
     */
    protected $taxManager;

    /**
     * @var OrdersAppliedDiscountsProvider
     */
    protected $ordersAppliedDiscountProvider;

    /**
     * @var SectionProvider
     */
    protected $sectionProvider;
    /**
     * @var LineItemSubtotalProvider
     */
    private $lineItemSubtotalProvider;

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param TaxManager $taxManager
     * @param OrdersAppliedDiscountsProvider $ordersAppliedDiscountProvider
     * @param SectionProvider $sectionProvider
     * @param LineItemSubtotalProvider $lineItemSubtotalProvider
     */
    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxManager $taxManager,
        OrdersAppliedDiscountsProvider $ordersAppliedDiscountProvider,
        SectionProvider $sectionProvider,
        LineItemSubtotalProvider $lineItemSubtotalProvider
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxManager = $taxManager;
        $this->ordersAppliedDiscountProvider = $ordersAppliedDiscountProvider;
        $this->sectionProvider = $sectionProvider;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderLineItemType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $sections = [];
        $sectionNames = [
            'applied_discounts' => 'oro.order.edit.order_line_item.applied_discounts.label',
        ];
        $order = self::BASE_ORDER;

        foreach ($sectionNames as $sectionName => $label) {
            $sections[$sectionName] = [
                'order' => $order++,
                'label' => $label,
            ];
        }

        $this->sectionProvider->addSections($this->getExtendedType(), $sections);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $form->getData();

        if (!$orderLineItem) {
            return;
        }

        if (!$orderLineItem->getId()) {
            return;
        }

        $currency = $orderLineItem->getCurrency();

        $rowTotalWithoutDiscount = $this->lineItemSubtotalProvider->getRowTotal($orderLineItem, $currency);
        $discountAmount = $this->ordersAppliedDiscountProvider->getDiscountsAmountByLineItem($orderLineItem);

        $view->vars['applied_discounts'] = [
            'discountAmount' => $discountAmount,
            'currency' => $currency,
            'rowTotalWithoutDiscount' => $rowTotalWithoutDiscount,
        ];

        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $view->vars['applied_discounts']['taxes'] = $this->taxManager->getTax($orderLineItem);
    }
}
