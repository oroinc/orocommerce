<?php

namespace OroB2B\Bundle\SaleBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UIBundle\Twig;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;

class QuoteExtension extends \Twig_Extension
{
    const NAME = 'orob2b_sale_quote';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'orob2b_format_sale_quote_product_item',
                [$this, 'formatProductItem'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param QuoteProductItem $item
     * @return string
     */
    public function formatProductItem(QuoteProductItem $item)
    {
        /* @var $translator Translator */
        $translator = $this->container->get('translator');

        /* @var $twig Twig\Environment */
        $twig = $this->container->get('twig');

        $unitFormatter  = $twig->getFilter('orob2b_format_product_unit_value')->getCallable();
        $priceFormatter = $twig->getFilter('oro_format_price')->getCallable();

        $units  = $item->getProductUnit()
            ? $unitFormatter($item->getQuantity(), $item->getProductUnit())
            : sprintf('%s %s', $item->getQuantity(), $item->getProductUnitCode())
        ;
        $price  = $priceFormatter($item->getPrice());
        $unit   = $translator->trans(sprintf('orob2b.product_unit.%s.label.full', $item->getProductUnitCode()));

        $str = $translator->trans(
            'orob2b.sale.quote.quoteproduct.quoteproductitem.item',
            [
                '{units}'   => $units,
                '{price}'   => $price,
                '{unit}'    => $unit,
            ]
        );

        return $str;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
