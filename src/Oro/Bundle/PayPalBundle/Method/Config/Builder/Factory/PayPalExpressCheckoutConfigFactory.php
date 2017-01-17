<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Builder\Factory;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Method\Config\Builder\PayPalExpressCheckoutConfigBuilder;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class PayPalExpressCheckoutConfigFactory implements PayPalConfigFactoryInterface
{

    /**
     * @var SymmetricCrypterInterface
     */
    protected $encoder;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @param SymmetricCrypterInterface $encoder
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        SymmetricCrypterInterface $encoder,
        LocalizationHelper $localizationHelper
    ) {
        $this->encoder = $encoder;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @return PayPalExpressCheckoutConfigBuilder
     */
    public function createPayPalConfigBuilder()
    {
        return new PayPalExpressCheckoutConfigBuilder($this->encoder, $this->localizationHelper);
    }
}
