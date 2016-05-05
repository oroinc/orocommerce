<?php

namespace OroB2B\Bundle\ProductBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\ProductLineItem;
use OroB2B\Bundle\ProductBundle\Form\Type\FrontendLineItemType;

class ProductLineItemFormProvider extends AbstractServerRenderDataProvider
{
    const NULL_PRODUCT_KEY = 'no-product';

    /**
     * @var FormAccessor[]
     */
    protected $data = [];

    /**
     * @var FormInterface[]
     */
    protected $form = [];

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $key = static::NULL_PRODUCT_KEY;
        $product = null;
        $data = $context->data();
        if ($data->has('product')) {
            $product = $data->get('product');
            $key = $product->getId();
        }
        if (!isset($this->data[$key])) {
            $this->data[$key] = new FormAccessor($this->getForm($product));
        }
        return $this->data[$key];
    }

    /**
     * @param Product $product
     * @return FormInterface
     */
    public function getForm(Product $product = null)
    {
        $key = static::NULL_PRODUCT_KEY;
        if ($product !== null) {
            $key = $product->getId();
        }
        if (!isset($this->form[$key])) {
            $this->form[$key] = $this->formFactory->create(FrontendLineItemType::NAME, $this->getLineItem($product));
        }
        return $this->form[$key];
    }

    /**
     * @param Product $product
     * @return ProductLineItem|null
     */
    public function getLineItem(Product $product = null)
    {
        $lineItem = new ProductLineItem(null);
        if ($product) {
            $lineItem->setProduct($product);
        }
        return $lineItem;
    }
}
