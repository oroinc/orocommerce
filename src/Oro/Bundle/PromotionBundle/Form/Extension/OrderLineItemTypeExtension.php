<?php

namespace Oro\Bundle\PromotionBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\PromotionBundle\Provider\DiscountsProvider;
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
     * @var DiscountsProvider
     */
    protected $discountsProvider;

    /**
     * @var SectionProvider
     */
    protected $sectionProvider;

    /**
     * @var LineItemSubtotalProvider
     */
    protected $lineItemSubtotalProvider;

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param TaxManager $taxManager
     * @param DiscountsProvider $discountsProvider
     * @param SectionProvider $sectionProvider
     * @param LineItemSubtotalProvider $lineItemSubtotalProvider
     */
    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxManager $taxManager,
        DiscountsProvider $discountsProvider,
        SectionProvider $sectionProvider,
        LineItemSubtotalProvider $lineItemSubtotalProvider
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxManager = $taxManager;
        $this->discountsProvider = $discountsProvider;
        $this->sectionProvider = $sectionProvider;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
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

        $this->sectionProvider->addSections(OrderLineItemType::NAME, $sections);
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
        $discountAmount = $this->discountsProvider->getDiscountsAmountByLineItem($orderLineItem);

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

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderLineItemType::class;
    }
}
