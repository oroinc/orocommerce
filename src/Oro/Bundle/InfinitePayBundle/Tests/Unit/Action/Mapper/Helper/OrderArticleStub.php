<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper\Helper;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class OrderArticleStub
{
    /** @var array */
    protected $options;

    public function __construct(array $parameters = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($parameters);
    }

    private function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'id' => uniqid('product_id_', false),
            'name' => uniqid('product_name_', false),
            'price_gross' => 1190,
            'price_net' => 1000,
            'vat_percentage' => '19.0',
            'quantity' => 1,
        ]);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return  $this->options['id'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->options['name'];
    }

    /**
     * @return int
     */
    public function getPriceGross()
    {
        return $this->options['price_gross'];
    }

    /**
     * @return int
     */
    public function getPriceNet()
    {
        return $this->options['price_net'];
    }

    /**
     * @return float
     */
    public function getVatPercentage()
    {
        return $this->options['vat_percentage'];
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->options['quantity'];
    }
}
