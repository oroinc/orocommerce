<?php

namespace Oro\Bundle\ApruveBundle\Connection\Validator\Request\Factory\Merchant;

use Oro\Bundle\ApruveBundle\Client\Request\Merchant\Factory\GetMerchantRequestFactoryInterface;
use Oro\Bundle\ApruveBundle\Connection\Validator\Request\Factory\ApruveConnectionValidatorRequestFactoryInterface;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class GetMerchantRequestApruveConnectionValidatorRequestFactory implements
    ApruveConnectionValidatorRequestFactoryInterface
{
    /**
     * @internal
     */
    const REQUEST_URL = 'Rate';

    /**
     * @var GetMerchantRequestFactoryInterface
     */
    private $merchantRequestFactory;

    /**
     * @var SymmetricCrypterInterface
     */
    private $symmetricCrypter;

    /**
     * @param GetMerchantRequestFactoryInterface $merchantRequestFactory
     * @param SymmetricCrypterInterface          $symmetricCrypter
     */
    public function __construct(
        GetMerchantRequestFactoryInterface $merchantRequestFactory,
        SymmetricCrypterInterface $symmetricCrypter
    ) {
        $this->symmetricCrypter = $symmetricCrypter;
        $this->merchantRequestFactory = $merchantRequestFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createBySettings(ApruveSettings $settings)
    {
        $merchantId = $this->symmetricCrypter->decryptData($settings->getApruveMerchantId());

        return $this->merchantRequestFactory->createByMerchantId($merchantId);
    }
}
