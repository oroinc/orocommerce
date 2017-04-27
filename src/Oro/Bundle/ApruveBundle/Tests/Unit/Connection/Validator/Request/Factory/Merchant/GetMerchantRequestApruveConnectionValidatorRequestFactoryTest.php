<?php

namespace Oro\Bundle\ApruveBundle\Connection\Validator\Request\Factory\Merchant;

use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\ApruveBundle\Client\Request\Merchant\Factory\GetMerchantRequestFactoryInterface;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class GetMerchantRequestApruveConnectionValidatorRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GetMerchantRequestFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $merchantRequestFactory;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $symmetricCrypter;

    /**
     * @var GetMerchantRequestApruveConnectionValidatorRequestFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->merchantRequestFactory = $this->createMock(GetMerchantRequestFactoryInterface::class);
        $this->symmetricCrypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->factory = new GetMerchantRequestApruveConnectionValidatorRequestFactory(
            $this->merchantRequestFactory,
            $this->symmetricCrypter
        );
    }

    public function testCreateBySettings()
    {
        $settings = $this->createApruveSettingsMock();

        $encryptedMetchantId = 'encrypted_merchant_id';

        $settings->expects(static::once())
            ->method('getApruveMerchantId')
            ->willReturn($encryptedMetchantId);

        $metchantId = 'merchant_id';

        $this->symmetricCrypter->expects(static::once())
            ->method('decryptData')
            ->with($encryptedMetchantId)
            ->willReturn($metchantId);

        $request = $this->createMock(ApruveRequestInterface::class);

        $this->merchantRequestFactory->expects(static::once())
            ->method('createByMerchantId')
            ->with($metchantId)
            ->willReturn($request);

        static::assertEquals($request, $this->factory->createBySettings($settings));
    }

    /**
     * @return ApruveSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createApruveSettingsMock()
    {
        return $this->createMock(ApruveSettings::class);
    }
}
