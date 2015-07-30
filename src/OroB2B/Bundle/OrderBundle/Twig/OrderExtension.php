<?php

namespace OroB2B\Bundle\OrderBundle\Twig;

use OroB2B\Bundle\OrderBundle\Formatter\OrderProductFormatter;

use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;

class OrderExtension extends \Twig_Extension
{
    const NAME = 'orob2b_order_order';

    /**
     * @var OrderProductFormatter
     */
    protected $orderProductFormatter;

    /**
     * @param OrderProductFormatter $orderProductFormatter
     */
    public function __construct(OrderProductFormatter $orderProductFormatter)
    {
        $this->orderProductFormatter = $orderProductFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'orob2b_format_order_product_item',
                [$this, 'formatProductItem'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param OrderProductItem $item
     * @return string
     */
    public function formatProductItem(OrderProductItem $item)
    {
        return $this->orderProductFormatter->formatItem($item);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
