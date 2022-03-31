<?php

namespace Oro\Bundle\FixedProductShippingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FixedProductShippingBundle\Migrations\Data\ORM\LoadPriceAttributePriceListData;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ShippingBundle\EventListener\FormViewListener as ShippingFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds shipping_cost field to the product view and edit pages.
 */
class FormViewListener
{
    private TranslatorInterface $translator;
    private ManagerRegistry $registry;
    private PriceAttributePricesProvider $priceAttributePricesProvider;

    public function __construct(
        TranslatorInterface $translator,
        ManagerRegistry $registry,
        PriceAttributePricesProvider $priceAttributePricesProvider
    ) {
        $this->translator = $translator;
        $this->registry = $registry;
        $this->priceAttributePricesProvider = $priceAttributePricesProvider;
    }

    /**
     * @param BeforeListRenderEvent $event
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function onProductView(BeforeListRenderEvent $event): void
    {
        $product = $event->getEntity();
        $priceList = $this->getPriceListShippingCostAttribute();
        if (!$priceList) {
            return;
        }

        $priceAttributePrices = $this->priceAttributePricesProvider
            ->getPricesWithUnitAndCurrencies($priceList, $product);

        $template = $event->getEnvironment()->render(
            '@OroFixedProductShipping/Product/shipping_cost_view.html.twig',
            [
                'product' => $product,
                'priceList' => $priceList,
                'priceAttributePrices' => $priceAttributePrices,
            ]
        );
        $this->updateOrAddBlock($event->getScrollData(), $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function onProductEdit(BeforeListRenderEvent $event): void
    {
        $template = $event->getEnvironment()->render(
            '@OroFixedProductShipping/Product/shipping_cost_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->updateOrAddBlock($event->getScrollData(), $template);
    }

    private function updateOrAddBlock(ScrollData $scrollData, string $html): void
    {
        $ids = $scrollData->getSubblockIds(ShippingFormViewListener::SHIPPING_BLOCK_NAME);
        if (!$ids) {
            $blockLabel = $this->translator->trans(ShippingFormViewListener::SHIPPING_BLOCK_LABEL);
            $scrollData->addNamedBlock(
                ShippingFormViewListener::SHIPPING_BLOCK_NAME,
                $blockLabel,
                ShippingFormViewListener::SHIPPING_BLOCK_PRIORITY
            );
            $subBlockId = $scrollData->addSubBlock(ShippingFormViewListener::SHIPPING_BLOCK_NAME);
        } else {
            $subBlockId = end($ids);
        }

        $scrollData->addSubBlockData(ShippingFormViewListener::SHIPPING_BLOCK_NAME, $subBlockId, $html);
    }

    private function getPriceListShippingCostAttribute(): ?PriceAttributePriceList
    {
        return $this->registry
            ->getRepository(PriceAttributePriceList::class)
            ->findOneBy(['name' => LoadPriceAttributePriceListData::SHIPPING_COST_NAME]);
    }
}
