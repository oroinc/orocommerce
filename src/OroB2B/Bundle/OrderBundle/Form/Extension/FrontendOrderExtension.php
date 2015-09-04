<?php

namespace OroB2B\Bundle\OrderBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Model\DataStorageAwareProcessor;
use OroB2B\Bundle\ProductBundle\Model\ProductDataConverter;
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
     * @var ProductDataConverter
     */
    protected $converter;

    /**
     * @param ProductDataStorage $storage
     * @param $converter
     */
    public function __construct(ProductDataStorage $storage, ProductDataConverter $converter)
    {
        $this->storage = $storage;
        $this->converter = $converter;
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

        $productInformation = $this->converter->getProductsInfoByStoredData($data);
        foreach ($productInformation as $informationRow) {
            $product = $informationRow->getProduct();
            /** @var ProductUnit $unit */
            $unit = $product->getUnitPrecisions()->first()->getUnit();
            $lineItem = new OrderLineItem();
            $lineItem->setProduct($product)
                ->setProductSku($product->getSku())
                ->setProductUnit($unit)
                ->setProductUnitCode($unit->getCode())
                ->setQuantity($informationRow->getQuantity());
            $order->addLineItem($lineItem);
        }
    }
}
