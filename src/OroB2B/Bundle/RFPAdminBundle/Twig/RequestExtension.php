<?php

namespace OroB2B\Bundle\RFPAdminBundle\Twig;

use Oro\Bundle\UIBundle\Twig;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

class RequestExtension extends \Twig_Extension
{
    const NAME = 'orob2b_rfpadmin_request';

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var Twig\Environment
     */
    protected $twigEnvironment;

    /**
     * @param Translator $translator
     * @param Twig\Environment $twigEnvironment
     */
    public function __construct(Translator $translator, Twig\Environment $twigEnvironment)
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
                'orob2b_format_rfpadmin_request_product_item',
                [$this, 'formatProductItem'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param RequestProductItem $item
     * @return string
     */
    public function formatProductItem(RequestProductItem $item)
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
            'orob2b.rfpadmin.request.requestproduct.requestproductitem.item',
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
