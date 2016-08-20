<?php

namespace Oro\Bundle\RFPBundle\Form\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

class RequestDataStorageExtension extends AbstractProductDataStorageExtension
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $supportedStatuses = [];

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param Session $session
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function fillItemsData($entity, array $itemsData = [])
    {
        $repository = $this->getProductRepository();
        $canNotBeAddedToRFQ = [];
        foreach ($itemsData as $dataRow) {
            if (!array_key_exists(ProductDataStorage::PRODUCT_SKU_KEY, $dataRow)) {
                continue;
            }

            $product = $repository->findOneBySku($dataRow[ProductDataStorage::PRODUCT_SKU_KEY]);
            if (!$product) {
                continue;
            }

            $result = $this->addItem($product, $entity, $dataRow);
            if ($result === false) {
                $canNotBeAddedToRFQ[] = ['sku' => $product->getSku(), 'name' => $product->getDefaultName()];
            }
        }

        $message = $this->container->get('templating')->render(
            'OroRFPBundle:Form/FlashBag:warning.html.twig',
            [
                'message' => $this->translator->trans('oro.frontend.rfp.data_storage.cannot_be_added_to_rfq'),
                'products' => $canNotBeAddedToRFQ
            ]
        );

        if (!empty($canNotBeAddedToRFQ)) {
            $this->session->getFlashBag()->add('warning', $message);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addItem(Product $product, $entity, array $itemData = [])
    {
        if (!$entity instanceof RFPRequest) {
            return;
        }
        if (!$this->isAllowedProduct($product)) {
            return false;
        }

        $requestProductItem = new RequestProductItem();
        $requestProduct = new RequestProduct();

        $this->fillEntityData($requestProduct, $itemData);

        $requestProduct
            ->setProduct($product)
            ->addRequestProductItem($requestProductItem);

        if (array_key_exists(ProductDataStorage::PRODUCT_QUANTITY_KEY, $itemData)) {
            $requestProductItem->setQuantity($itemData[ProductDataStorage::PRODUCT_QUANTITY_KEY]);
        }

        $this->fillEntityData($requestProductItem, $itemData);

        if (!$requestProductItem->getProductUnit()) {
            /** @var ProductUnitPrecision $unitPrecision */
            $unitPrecision = $product->getUnitPrecisions()->first();
            if (!$unitPrecision) {
                return;
            }

            /** @var ProductUnit $unit */
            $unit = $unitPrecision->getUnit();
            if (!$unit) {
                return;
            }

            $requestProductItem->setProductUnit($unit);
        }

        if ($requestProductItem->getProductUnit()) {
            $entity->addRequestProduct($requestProduct);
        }
    }

    /**
     * @param Product $product
     *
     * @return bool
     */
    protected function isAllowedProduct(Product $product)
    {
        if (!$this->supportedStatuses) {
            $this->supportedStatuses = (array)$this->configManager->get('oro_b2b_rfp.frontend_product_visibility');
        }
        $inventoryStatus = $product->getInventoryStatus();
        if (is_object($inventoryStatus)) {
            $inventoryStatus = $inventoryStatus->getId();
        }

        return $inventoryStatus && in_array($inventoryStatus, $this->supportedStatuses);
    }

    /**
     * @param array $products
     *
     * @return bool
     */
    public function isAllowedRFP($products)
    {
        $repository = $this->getProductRepository();
        foreach ($products as $product) {
            if (!empty($product['productSku'])) {
                $product = $repository->findOneBySku($product['productSku']);
                if (!empty($product) && ($this->isAllowedProduct($product) === true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
