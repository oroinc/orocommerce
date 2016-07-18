<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

class FormViewListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper
    ) {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $productId = (int)$request->get('id');
        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityReference('OroB2BProductBundle:Product', $productId);

        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Product:prices_view.html.twig',
            [
                'entity' => $product,
                'productUnits' => $product->getAvailableUnitCodes(),
                'productAttributes' => $this->getProductAttributes(),
                'priceAttributePrices' => $this->getPriceAttributePrices($product)
            ]
        );
        $this->addProductPricesBlock($event->getScrollData(), $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Product:prices_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addProductPricesBlock($event->getScrollData(), $template);
    }

    /**
     * @return array|PriceAttributePriceList[]
     */
    protected function getProductAttributes()
    {
        return $this->getPriceAttributePriceListRepository()->findAll();
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function getPriceAttributePrices(Product $product)
    {
        /** @var PriceAttributeProductPrice[] $priceAttributePrices */
        $priceAttributePrices = $this->getPriceAttributePriceListPricesRepository()->findBy(['product' => $product]);

        $result = [];
        foreach ($priceAttributePrices as $priceAttributePrice) {
            $priceAttributeName = $priceAttributePrice->getPriceList()->getName();
            $currency = $priceAttributePrice->getPrice()->getCurrency();
            $unit = $priceAttributePrice->getProductUnitCode();
            $amount = $priceAttributePrice->getPrice()->getValue();

            $result[$priceAttributeName][$unit][$currency] = $amount;
        }

        return $result;
    }

    /**
     * @return EntityRepository
     */
    protected function getPriceAttributePriceListRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:PriceAttributePriceList');
    }
    
    /**
     * @return EntityRepository
     */
    protected function getPriceAttributePriceListPricesRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:PriceAttributeProductPrice');
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     */
    protected function addProductPricesBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('orob2b.pricing.productprice.entity_plural_label');
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}
