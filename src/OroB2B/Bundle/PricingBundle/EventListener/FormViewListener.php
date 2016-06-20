<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
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
    public function onAccountView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        /** @var Account $account */
        $account = $this->doctrineHelper->getEntityReference('OroB2BAccountBundle:Account', (int)$request->get('id'));
        /** @var PriceListToAccount[] $priceLists */
        $priceLists = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListToAccount')
            ->findBy(['account' => $account], ['website' => 'ASC']);
        /** @var  PriceListAccountFallback[] $fallbackEntities */
        $fallbackEntities = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListAccountFallback')
            ->findBy(['account' => $account]);
        $choices = [
            PriceListAccountFallback::CURRENT_ACCOUNT_ONLY =>
                'orob2b.pricing.fallback.current_account_only.label',
            PriceListAccountFallback::ACCOUNT_GROUP =>
                'orob2b.pricing.fallback.account_group.label',
        ];
        $this->addPriceListInfo($event, $priceLists, $fallbackEntities, $this->getWebsites(), $choices);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountGroupView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->doctrineHelper->getEntityReference(
            'OroB2BAccountBundle:AccountGroup',
            (int)$request->get('id')
        );
        /** @var PriceListToAccountGroup[] $priceLists */
        $priceLists = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListToAccountGroup')
            ->findBy(['accountGroup' => $accountGroup], ['website' => 'ASC']);
        /** @var  PriceListAccountGroupFallback[] $fallbackEntities */
        $fallbackEntities = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->findBy(['accountGroup' => $accountGroup]);
        $choices = [
            PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY =>
                'orob2b.pricing.fallback.current_account_group_only.label',
            PriceListAccountGroupFallback::WEBSITE =>
                'orob2b.pricing.fallback.website.label',
        ];
        $this->addPriceListInfo($event, $priceLists, $fallbackEntities, $this->getWebsites(), $choices);
    }

    /**
     * @return Website[]
     */
    protected function getWebsites()
    {
        return $this->doctrineHelper
            ->getEntityRepository('OroB2BWebsiteBundle:Website')
            ->findBy([], ['id' => 'ASC']);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onEntityEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Account:price_list_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $blockLabel = $this->translator->trans('orob2b.pricing.pricelist.entity_plural_label');
        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($blockLabel, 0);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
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

    /**
     * @param BeforeListRenderEvent $event
     * @param BasePriceListRelation[] $priceLists
     * @param array $fallbackEntities
     * @param Website[] $websites
     * @param array $choices
     */
    protected function addPriceListInfo(
        BeforeListRenderEvent $event,
        $priceLists,
        $fallbackEntities,
        $websites,
        $choices
    ) {
        $template = $event->getEnvironment()->render(
            'OroB2BPricingBundle:Account:price_list_view.html.twig',
            [
                'priceListsByWebsites' => $this->groupPriceListsByWebsite($priceLists),
                'fallbackByWebsites' => $this->groupFallbackByWebsites($fallbackEntities),
                'websites' => $websites,
                'choices' => $choices,
            ]
        );
        $blockLabel = $this->translator->trans('orob2b.pricing.pricelist.entity_plural_label');
        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($blockLabel, 0);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }

    /**
     * @param BasePriceListRelation[] $priceLists
     * @return array
     */
    protected function groupPriceListsByWebsite(array $priceLists)
    {
        $result = [];
        foreach ($priceLists as $priceList) {
            $result[$priceList->getWebsite()->getId()][] = $priceList;
        }

        foreach ($result as &$websitePriceLists) {
            usort(
                $websitePriceLists,
                function (BasePriceListRelation $priceList1, BasePriceListRelation $priceList2) {
                    $priority1 = $priceList1->getPriority();
                    $priority2 = $priceList2->getPriority();
                    if ($priority1 == $priority2) {
                        return 0;
                    }

                    return ($priority1 < $priority2) ? -1 : 1;
                }
            );
        }

        return $result;
    }

    /**
     * @param PriceListFallback[] $entities
     * @return array
     */
    protected function groupFallbackByWebsites(array $entities)
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[$entity->getWebsite()->getId()] = $entity->getFallback();
        }

        return $result;
    }
}
