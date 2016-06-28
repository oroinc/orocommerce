<?php

namespace OroB2B\Bundle\RFPBundle\Form\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;

class RequestDataStorageExtension extends AbstractProductDataStorageExtension
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

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
     * {@inheritdoc}
     */
    protected function addItem(Product $product, $entity, array $itemData = [])
    {
        if (!$entity instanceof RFPRequest) {
            return;
        }
        if (!$this->isAllowedProduct($product)) {
            return;
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
            $supportedStatuses = (array)$this->configManager->get('oro_b2b_rfp.frontend_product_visibility');
            foreach ($supportedStatuses as $status) {
                $this->supportedStatuses[$status] = true;
            }
        }

        $inventoryStatus = $product->getInventoryStatus();
        return $inventoryStatus && !empty($this->supportedStatuses[$inventoryStatus->getId()]);
    }
}
