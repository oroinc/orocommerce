<?php

namespace Oro\Bundle\RFPBundle\Form\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * The form type extension that pre-fill a RFQ with requested products taken from the product data storage.
 */
class RequestDataStorageExtension extends AbstractProductDataStorageExtension
{
    private ProductRFPAvailabilityProvider $productAvailabilityProvider;
    private TranslatorInterface $translator;
    private Environment $twig;

    public function __construct(
        RequestStack $requestStack,
        ProductDataStorage $storage,
        PropertyAccessorInterface $propertyAccessor,
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        ProductRFPAvailabilityProvider $productAvailabilityProvider,
        TranslatorInterface $translator,
        Environment $twig
    ) {
        parent::__construct($requestStack, $storage, $propertyAccessor, $doctrine, $logger);
        $this->productAvailabilityProvider = $productAvailabilityProvider;
        $this->translator = $translator;
        $this->twig = $twig;
    }

    #[\Override]
    protected function fillItemsData(object $entity, array $itemsData): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($this->getEntityClass());
        $canNotBeAddedToRFQ = [];
        foreach ($itemsData as $dataRow) {
            $productId = $dataRow[ProductDataStorage::PRODUCT_ID_KEY] ?? null;
            if (null === $productId) {
                continue;
            }
            $product = $em->find(Product::class, $productId);
            if (null === $product) {
                continue;
            }

            if (!$this->productAvailabilityProvider->isProductAllowedForRFP($product)) {
                $canNotBeAddedToRFQ[] = $product;
            } else {
                $this->addItem($product, $entity, $dataRow);
            }
        }

        if (!empty($canNotBeAddedToRFQ)) {
            $message = $this->twig->render(
                '@OroRFP/Form/FlashBag/warning.html.twig',
                [
                    'message' => $this->translator->trans('oro.frontend.rfp.data_storage.cannot_be_added_to_rfq'),
                    'products' => $canNotBeAddedToRFQ
                ]
            );
            $this->requestStack->getSession()->getFlashBag()->add('warning', $message);
        }
    }

    #[\Override]
    protected function addItem(Product $product, object $entity, array $itemData): void
    {
        /** @var RFPRequest $entity */

        $requestProduct = new RequestProduct();

        $this->fillEntityData($requestProduct, $itemData);

        $requestProduct->setProduct($product);

        $requestProductItem = new RequestProductItem();
        if (\array_key_exists(ProductDataStorage::PRODUCT_QUANTITY_KEY, $itemData)) {
            $requestProductItem->setQuantity($itemData[ProductDataStorage::PRODUCT_QUANTITY_KEY]);
        }
        $requestProduct->addRequestProductItem($requestProductItem);

        $this->fillEntityData($requestProductItem, $itemData);
        $this->setDefaultProductUnit($requestProductItem, $product);
        $this->addKitItemLineItems($requestProduct, $itemData);

        if ($requestProductItem->getProductUnit()) {
            $entity->addRequestProduct($requestProduct);
        }
    }

    private function addKitItemLineItems(RequestProduct $requestProduct, array $itemData): void
    {
        $kitItemLineItemsData = $itemData[ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEMS_DATA_KEY] ?? [];
        foreach ($kitItemLineItemsData as $kitItemLineItemData) {
            if (
                !isset($kitItemLineItemData[ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_KIT_ITEM_KEY])
                || !isset($kitItemLineItemData[ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_KEY])
            ) {
                continue;
            }

            $kitItemLineItem = new RequestProductKitItemLineItem();
            $this->fillEntityData($kitItemLineItem, $kitItemLineItemData);
            $this->setDefaultProductUnit($kitItemLineItem, $kitItemLineItem->getProduct());
            if (!$kitItemLineItem->getProductUnit()) {
                continue;
            }

            $requestProduct->addKitItemLineItem($kitItemLineItem);
        }
    }

    private function setDefaultProductUnit(
        RequestProductItem|RequestProductKitItemLineItem $lineItem,
        Product $product
    ): void {
        if (!$lineItem->getProductUnit()) {
            $unit = $this->getDefaultProductUnit($product);
            if (null === $unit) {
                return;
            }

            $lineItem->setProductUnit($unit);
            $lineItem->setProductUnitCode($unit->getCode());
        }
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return RFPRequest::class;
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [RequestType::class];
    }
}
