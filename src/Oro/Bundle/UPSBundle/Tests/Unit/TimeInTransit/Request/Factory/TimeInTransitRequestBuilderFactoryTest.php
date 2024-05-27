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
    private const UPS_API_USERNAME = 'user';
    private const UPS_API_PASSWORD = 'pass';
    private const UPS_API_KEY = 'key';
    private const UPS_OAUTH_CLIENT_ID = null;
    private const UPS_OAUTH_CLIENT_SECRET = null;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $crypter;

    /** @var UPSTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $upsTransport;

    /** @var \DateTime */
    private $pickupDate;

    /** @var AddressInterface */
    private $address;

    /** @var TimeInTransitRequestBuilderFactoryInterface */
    private $timeInTransitRequestBuilderFactory;

    protected function setUp(): void
    {
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->upsTransport = $this->createMock(UPSTransport::class);
        $this->address = new AddressStub();
        $this->pickupDate = new \DateTime();

        $this->timeInTransitRequestBuilderFactory = new TimeInTransitRequestBuilderFactory($this->crypter);
    }

    public function testCreateTimeInTransitRequestBuilder()
    {
        $this->crypter->expects(self::exactly(2))
            ->method('decryptData')
            ->withConsecutive(
                [self::UPS_API_PASSWORD],
                [null]
            )
            ->willReturnOnConsecutiveCalls(
                self::UPS_API_PASSWORD,
                null
            );

        $this->upsTransport->expects(self::once())
            ->method('getUpsApiUser')
            ->willReturn(self::UPS_API_USERNAME);
        $this->upsTransport->expects(self::once())
            ->method('getUpsApiPassword')
            ->willReturn(self::UPS_API_PASSWORD);
        $this->upsTransport->expects(self::once())
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
        $expectedBuilder->setUpsClientId(self::UPS_OAUTH_CLIENT_ID);
        $expectedBuilder->setUpsClientSecret(self::UPS_OAUTH_CLIENT_SECRET);

        $builder = $this->timeInTransitRequestBuilderFactory->createTimeInTransitRequestBuilder(
            $this->upsTransport,
            $this->address,
            $this->address,
            $this->pickupDate
        );

        self::assertEquals($expectedBuilder, $builder);
    }
}
