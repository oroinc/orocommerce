<?php

namespace OroB2B\Bundle\ProductBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ProductBundle\Exception\ComponentProcessorNotFoundException;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorInterface;
use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorRegistry;
use OroB2B\Bundle\ProductBundle\Model\ProductDataConverter;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddHandler
{
    const PRODUCT_SKU_KEY = 'productSku';
    const PRODUCT_QUANTITY_KEY = 'productQuantity';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ComponentProcessorRegistry
     */
    protected $processorsRegistry;

    /**
     * @var ProductDataConverter
     */
    protected $converter;

    /**
     * @param Request $request
     * @param ComponentProcessorRegistry $processorsRegistry
     * @param ProductDataConverter $converter
     */
    public function __construct(
        Request $request,
        ComponentProcessorRegistry $processorsRegistry,
        ProductDataConverter $converter
    ) {
        $this->request = $request;
        $this->processorsRegistry = $processorsRegistry;
        $this->converter = $converter;
    }

    /**
     * @param FormInterface $form
     * @return null|Response
     */
    public function handleRequest(FormInterface $form)
    {
        $processor = $this->getProcessor($form->get('component')->getData());

        $products = $form->get('products')->getData();
        $products = is_array($products) ? $this->processData($products) : [];

        return $processor->process($products, $this->request);
    }

    /**
     * @param array $products
     * @return array
     */
    protected function processData(array $products)
    {
        $data = [];

        foreach ($products as $product) {
            $entity = $this->converter->convertSkuToProduct($product[self::PRODUCT_SKU_KEY]);

            if ($product) {
                $data[] = [
                    ProductDataStorage::PRODUCT_KEY => $entity->getId(),
                    ProductDataStorage::QUANTITY_KEY => $product[self::PRODUCT_QUANTITY_KEY]
                ];
            }
        }

        return $data;
    }

    /**
     * @param $name
     * @return null|ComponentProcessorInterface
     * @throws ComponentProcessorNotFoundException
     */
    protected function getProcessor($name)
    {
        if (!$this->processorsRegistry->hasProcessor($name)) {
            throw new ComponentProcessorNotFoundException();
        }

        return $this->processorsRegistry->getProcessorByName($name);
    }
}
