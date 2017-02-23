<?php

namespace Oro\Bundle\ShippingBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

class ShippingOptionLabelExtension extends \Twig_Extension
{
    const NAME = 'oro_shipping_option_label';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return UnitLabelFormatter
     */
    protected function getLengthUnitLabelFormatter()
    {
        return $this->container->get('oro_shipping.formatter.length_unit_label');
    }

    /**
     * @return UnitLabelFormatter
     */
    protected function getWeightUnitLabelFormatter()
    {
        return $this->container->get('oro_shipping.formatter.weight_unit_label');
    }

    /**
     * @return UnitLabelFormatter
     */
    protected function getFreightClassLabelFormatter()
    {
        return $this->container->get('oro_shipping.formatter.freight_class_label');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_length_unit_format_label',
                [$this, 'formatLengthUnit'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_weight_unit_format_label',
                [$this, 'formatWeightUnit'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_freight_class_format_label',
                [$this, 'formatFreightClass'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @param string $code
     * @param bool   $isShort
     * @param bool   $isPlural
     *
     * @return string
     */
    public function formatLengthUnit($code, $isShort = false, $isPlural = false)
    {
        return $this->getLengthUnitLabelFormatter()->format($code, $isShort, $isPlural);
    }

    /**
     * @param string $code
     * @param bool   $isShort
     * @param bool   $isPlural
     *
     * @return string
     */
    public function formatWeightUnit($code, $isShort = false, $isPlural = false)
    {
        return $this->getWeightUnitLabelFormatter()->format($code, $isShort, $isPlural);
    }

    /**
     * @param string $code
     * @param bool   $isShort
     * @param bool   $isPlural
     *
     * @return string
     */
    public function formatFreightClass($code, $isShort = false, $isPlural = false)
    {
        return $this->getFreightClassLabelFormatter()->format($code, $isShort, $isPlural);
    }
}
