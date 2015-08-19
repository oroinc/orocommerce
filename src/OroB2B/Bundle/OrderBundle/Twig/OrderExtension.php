<?php

namespace OroB2B\Bundle\OrderBundle\Twig;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Formatter\OrderLineItemFormatter;

class OrderExtension extends \Twig_Extension
{
    const NAME = 'orob2b_order_order';

    /**
     * @var OrderLineItemFormatter
     */
    protected $formatter;

    /**
     * @param OrderLineItemFormatter $formatter
     */
    public function __construct(OrderLineItemFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'orob2b_format_order_line_item',
                [$this, 'formatLineItem'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param OrderLineItem $item
     * @return string
     */
    public function formatLineItem(OrderLineItem $item)
    {
        return $this->formatter->formatItem($item);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
