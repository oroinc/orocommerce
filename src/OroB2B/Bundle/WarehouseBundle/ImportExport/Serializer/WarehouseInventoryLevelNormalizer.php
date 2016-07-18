<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\Serializer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class WarehouseInventoryLevelNormalizer implements DenormalizerInterface, NormalizerInterface
{
    /**
     * @var ProductUnitLabelFormatter
     */
    private $formatter;

    /**
     * WarehouseInventoryLevelNormalizer constructor.
     *
     * @param ProductUnitLabelFormatter $formatter
     */
    public function __construct(ProductUnitLabelFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return $data instanceof WarehouseInventoryLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        // Set quantity to null if not exporting real object
        if (!$object->getId() && 0 == $object->getQuantity()) {
            $object->setQuantity(null);
        }

        $product = $object->getProduct();
        $result = [
            'quantity' => $object->getQuantity()
        ];

        if ($product) {
            $result['product'] = [
                'sku' => $product->getSku(),
                'defaultName' => $product->getDefaultName() ? $product->getDefaultName()->getString() : null,
                'inventoryStatus' => ($product->getInventoryStatus()) ? $product->getInventoryStatus()->getName() : null
            ];
        }

        if ($object->getWarehouse()) {
            $result['warehouse'] = [
                'name' => $object->getWarehouse()->getName()
            ];
        }

        $result = array_merge($result, $this->getUnitPrecision($object));

        return $result;
    }

    /**
     * @param WarehouseInventoryLevel $object
     * @return array
     */
    protected function getUnitPrecision(WarehouseInventoryLevel $object)
    {
        $unitPrecision = $object->getProductUnitPrecision();
        if (!$unitPrecision) {
            return [];
        }
        $code = $unitPrecision->getUnit() ? $unitPrecision->getUnit()->getCode(): null;
        $code = $this->formatter->format($code, true, $object->getQuantity() > 1);

        return ['productUnitPrecision' => [
            'unit' => [
                'code' => $code
            ]
        ]];
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (!is_array($data) || !isset($data['product'])) {
            return null;
        }

        $productData = $data['product'];

        $productEntity = new Product();
        $productEntity->setSku($productData['sku']);

        $inventoryLevel = new WarehouseInventoryLevel();
        $productUnitPrecision = new ProductUnitPrecision();

        $productUnitPrecision->setProduct($productEntity);

        if (array_key_exists('inventoryStatus', $productData)) {
            $productEntity->setInventoryStatus($productData['inventoryStatus']);
        }

        $this->determineQuantity($inventoryLevel, $data);

        if (isset($data['warehouse'])) {
            $warehouse = new Warehouse();
            $warehouse->setName($data['warehouse']['name']);
            $inventoryLevel->setWarehouse($warehouse);
        }

        if (array_key_exists('productUnitPrecision', $data)) {
            $productUnitPrecisionData = $data['productUnitPrecision'];

            $productUnit = new ProductUnit();
            $productUnit->setCode(
                isset($productUnitPrecisionData['unit']) ? $productUnitPrecisionData['unit']['code'] : ''
            );
            $productUnitPrecision->setUnit($productUnit);
        }

        $inventoryLevel->setProductUnitPrecision($productUnitPrecision);

        return $inventoryLevel;
    }

    /**
     * @param WarehouseInventoryLevel $inventoryLevel
     * @param array $data
     */
    protected function determineQuantity(WarehouseInventoryLevel $inventoryLevel, array $data)
    {
        if (array_key_exists('quantity', $data)) {
            $inventoryLevel->setQuantity($data['quantity'] ?: 0);
        } else {
            $inventoryLevel->setQuantity(null);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        return !empty($data) && isset($data['product']) && $type === WarehouseInventoryLevel::class;
    }
}
