<?php

namespace OroB2B\Bundle\OrderBundle\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductRowType;
use OroB2B\Bundle\ProductBundle\Model\DataStorageAwareProcessor;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class FrontendOrderExtension extends AbstractTypeExtension
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ProductDataStorage
     */
    protected $storage;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @param ProductDataStorage $storage
     * @param ManagerRegistry $registry
     * @param string $productClass
     */
    public function __construct(ProductDataStorage $storage, ManagerRegistry $registry, $productClass)
    {
        $this->storage = $storage;
        $this->registry = $registry;
        $this->productClass = $productClass;
    }

    /**
     * @param Request $request
     * @return FrontendOrderExtension
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FrontendOrderType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->request->get(DataStorageAwareProcessor::QUICK_ADD_PARAM)) {
            $order = isset($options['data']) ? $options['data'] : null;

            if ($order instanceof Order && !$order->getId()) {
                $this->fillItems($order);
            }
        }
    }

    /**
     * @param Order $order
     */
    protected function fillItems(Order $order)
    {
        $data = $this->storage->get();
        $this->storage->remove();

        if (!$data) {
            return;
        }

        $repository = $this->getProductRepository();
        foreach ($data as $dataRow) {
            if (!array_key_exists(ProductRowType::PRODUCT_SKU_FIELD_NAME, $dataRow) ||
                !array_key_exists(ProductRowType::PRODUCT_QUANTITY_FIELD_NAME, $dataRow)
            ) {
                continue;
            }
            $product = $repository->findOneBySku($dataRow[ProductRowType::PRODUCT_SKU_FIELD_NAME]);
            if (!$product) {
                continue;
            }
            /** @var ProductUnit $unit */
            $unit = $product->getUnitPrecisions()->first()->getUnit();
            $lineItem = new OrderLineItem();
            $lineItem->setProduct($product)
                ->setProductSku($product->getSku())
                ->setProductUnit($unit)
                ->setProductUnitCode($unit->getCode())
                ->setQuantity((float)$dataRow[ProductRowType::PRODUCT_QUANTITY_FIELD_NAME]);
            $order->addLineItem($lineItem);
        }
    }

    /**
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->registry->getManagerForClass($this->productClass)
            ->getRepository($this->productClass);
    }
}
