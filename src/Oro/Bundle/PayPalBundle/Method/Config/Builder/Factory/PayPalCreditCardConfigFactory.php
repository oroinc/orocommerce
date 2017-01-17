<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Builder\Factory;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Method\Config\Builder\PayPalCreditCardConfigBuilder;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class PayPalCreditCardConfigFactory implements PayPalConfigFactoryInterface
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
     * @return PayPalCreditCardConfigBuilder
     */
    public function createPayPalConfigBuilder()
    {
        return new PayPalCreditCardConfigBuilder($this->encoder, $this->localizationHelper);
    }
}
