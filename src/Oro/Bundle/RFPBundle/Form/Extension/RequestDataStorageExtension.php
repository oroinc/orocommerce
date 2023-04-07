<?php

namespace Oro\Bundle\RFPBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
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
    private ProductAvailabilityProvider $productAvailabilityProvider;
    private TranslatorInterface $translator;
    private Environment $twig;

    public function __construct(
        RequestStack $requestStack,
        ProductDataStorage $storage,
        PropertyAccessorInterface $propertyAccessor,
        DoctrineHelper $doctrineHelper,
        AclHelper $aclHelper,
        LoggerInterface $logger,
        ProductAvailabilityProvider $productAvailabilityProvider,
        TranslatorInterface $translator,
        Environment $twig
    ) {
        parent::__construct($requestStack, $storage, $propertyAccessor, $doctrineHelper, $aclHelper, $logger);
        $this->productAvailabilityProvider = $productAvailabilityProvider;
        $this->translator = $translator;
        $this->twig = $twig;
    }

    /**
     * {@inheritDoc}
     */
    protected function fillItemsData(object $entity, array $itemsData): void
    {
        $repository = $this->getProductRepository();
        $canNotBeAddedToRFQ = [];
        foreach ($itemsData as $dataRow) {
            if (!\array_key_exists(ProductDataStorage::PRODUCT_SKU_KEY, $dataRow)) {
                continue;
            }

            $qb = $repository->getBySkuQueryBuilder($dataRow[ProductDataStorage::PRODUCT_SKU_KEY]);
            $product = $this->aclHelper->apply($qb)->getOneOrNullResult();
            if (!$product) {
                continue;
            }

            if (!$this->productAvailabilityProvider->isProductApplicableForRFP($product)) {
                $this->requestStack->getSession()->getFlashBag()->add(
                    'warning',
                    'oro.frontend.rfp.data_storage.no_qty_products_cant_be_added_to_rfq'
                );
                continue;
            }

            $this->addItem($product, $entity, $dataRow);
            if (!$this->productAvailabilityProvider->isProductAllowedForRFP($product)) {
                $canNotBeAddedToRFQ[] = $product;
            }
        }

        $message = $this->twig->render(
            '@OroRFP/Form/FlashBag/warning.html.twig',
            [
                'message' => $this->translator->trans('oro.frontend.rfp.data_storage.cannot_be_added_to_rfq'),
                'products' => $canNotBeAddedToRFQ
            ]
        );

        if (!empty($canNotBeAddedToRFQ)) {
            $this->requestStack->getSession()->getFlashBag()->add('warning', $message);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function addItem(Product $product, object $entity, array $itemData): void
    {
        /** @var RFPRequest $entity */

        if (!$this->productAvailabilityProvider->isProductAllowedForRFP($product)) {
            return;
        }

        $requestProduct = new RequestProduct();

        $this->fillEntityData($requestProduct, $itemData);

        $requestProduct->setProduct($product);

        $requestProductItem = new RequestProductItem();
        if (\array_key_exists(ProductDataStorage::PRODUCT_QUANTITY_KEY, $itemData)) {
            $requestProductItem->setQuantity($itemData[ProductDataStorage::PRODUCT_QUANTITY_KEY]);
        }
        $requestProduct->addRequestProductItem($requestProductItem);

        $this->fillEntityData($requestProductItem, $itemData);

        if (!$requestProductItem->getProductUnit()) {
            $unit = $this->getDefaultProductUnit($product);
            if (null === $unit) {
                return;
            }
            $requestProductItem->setProductUnit($unit);
        }

        if ($requestProductItem->getProductUnit()) {
            $entity->addRequestProduct($requestProduct);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityClass(): string
    {
        return RFPRequest::class;
    }

    /**
     * {@inheritDoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [RequestType::class];
    }
}
