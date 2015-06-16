<?php

namespace OroB2B\Bundle\SaleBundle\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;

class QuoteExtension extends \Twig_Extension
{
    const NAME = 'orob2b_sale_quote';

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var Twig\Environment
     */
    protected $twigEnvironment;

    /**
     * @param TranslatorInterface $translator
     * @param \Twig_Environment $twigEnvironment
     */
    public function __construct(TranslatorInterface $translator, \Twig_Environment $twigEnvironment)
    {
        $this->translator       = $translator;
        $this->twigEnvironment  = $twigEnvironment;
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
        $unitFormatter  = $this->twigEnvironment->getFilter('orob2b_format_product_unit_value')->getCallable();
        $priceFormatter = $this->twigEnvironment->getFilter('oro_format_price')->getCallable();

        $units  = $item->getProductUnit()
            ? $unitFormatter($item->getQuantity(), $item->getProductUnit())
            : sprintf('%s %s', $item->getQuantity(), $item->getProductUnitCode())
        ;
        $price  = $priceFormatter($item->getPrice());
        $unit   = $this->translator->trans(sprintf('orob2b.product_unit.%s.label.full', $item->getProductUnitCode()));

        $str = $this->translator->trans(
            'orob2b.sale.quoteproductitem.item',
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
