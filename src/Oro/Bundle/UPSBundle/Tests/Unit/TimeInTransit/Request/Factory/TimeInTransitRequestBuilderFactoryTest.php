<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Request\Factory;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder\TimeInTransitRequestBuilder;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory\TimeInTransitRequestBuilderFactory;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory\TimeInTransitRequestBuilderFactoryInterface;

class TimeInTransitRequestBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @internal
     */
    const UPS_API_USERNAME = 'user';

    /**
     * @internal
     */
    const UPS_API_PASSWORD = 'pass';

    /**
     * @internal
     */
    const UPS_API_KEY = 'key';

    /**
     * @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $crypter;

    /**
     * @var UPSTransport|\PHPUnit\Framework\MockObject\MockObject
     */
    private $upsTransport;

    /**
     * @var TimeInTransitRequestBuilderFactoryInterface
     */
    private $timeInTransitRequestBuilderFactory;

    /**
     * @var \DateTime
     */
    private $pickupDate;

    /**
     * @var AddressInterface
     */
    private $address;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->upsTransport = $this->createMock(UPSTransport::class);
        $this->address = new AddressStub();
        $this->pickupDate = new \DateTime();

        $this->timeInTransitRequestBuilderFactory
            = new TimeInTransitRequestBuilderFactory($this->crypter);
    }

    public function testCreateTimeInTransitRequestBuilder()
    {
        $this->crypter
            ->expects(static::once())
            ->method('decryptData')
            ->willReturn(self::UPS_API_PASSWORD);

        $this->upsTransport
            ->expects(static::once())
            ->method('getUpsApiUser')
            ->willReturn(self::UPS_API_USERNAME);

        $this->upsTransport
            ->expects(static::once())
            ->method('getUpsApiPassword')
            ->willReturn(self::UPS_API_PASSWORD);

        $this->upsTransport
            ->expects(static::once())
            ->method('getUpsApiKey')
            ->willReturn(self::UPS_API_KEY);

        $expectedBuilder = new TimeInTransitRequestBuilder(
            self::UPS_API_USERNAME,
            self::UPS_API_PASSWORD,
            self::UPS_API_KEY,
            $this->address,
            $this->address,
            $this->pickupDate
        );

        $builder = $this
            ->timeInTransitRequestBuilderFactory
            ->createTimeInTransitRequestBuilder($this->upsTransport, $this->address, $this->address, $this->pickupDate);

        static::assertEquals($expectedBuilder, $builder);
    }
}
