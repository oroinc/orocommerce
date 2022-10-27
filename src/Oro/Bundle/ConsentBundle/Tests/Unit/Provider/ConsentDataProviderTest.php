<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConsentBundle\Builder\ConsentDataBuilder;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Filter\FrontendConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Filter\RequiredConsentFilter;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsentDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private EnabledConsentProvider|\PHPUnit\Framework\MockObject\MockObject $enabledConsentProvider;

    private ConsentDataBuilder|\PHPUnit\Framework\MockObject\MockObject $consentDataBuilder;

    private ConsentDataProvider $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->enabledConsentProvider = $this->createMock(EnabledConsentProvider::class);
        $this->consentDataBuilder = $this->createMock(ConsentDataBuilder::class);

        $this->provider = new ConsentDataProvider(
            $this->enabledConsentProvider,
            $this->consentDataBuilder
        );
    }

    public function testGetAllConsentData(): void
    {
        $consent = new Consent();
        $consentData = new ConsentData($consent);

        $this->enabledConsentProvider->expects(self::once())
            ->method('getConsents')
            ->with([FrontendConsentContentNodeValidFilter::NAME])
            ->willReturn([$consent]);

        $this->consentDataBuilder->expects(self::once())
            ->method('build')
            ->with($consent)
            ->willReturn($consentData);

        self::assertEquals([$consentData], $this->provider->getAllConsentData());
    }

    public function testGetNotAcceptedRequiredConsentData(): void
    {
        $consentAccepted = new Consent();
        $consentNotAccepted = new Consent();
        $consentDataAccepted = (new ConsentData($consentAccepted))->setAccepted(true);
        $consentDataNotAccepted = (new ConsentData($consentNotAccepted))->setAccepted(false);

        $this->mockFilteredConsents(
            $consentAccepted,
            $consentNotAccepted,
            $consentDataAccepted,
            $consentDataNotAccepted
        );

        self::assertEquals(
            [$consentDataNotAccepted],
            $this->provider->getNotAcceptedRequiredConsentData()
        );
    }

    public function testGetRequiredConsentData(): void
    {
        $consentAccepted = new Consent();
        $consentNotAccepted = new Consent();
        $consentDataAccepted = (new ConsentData($consentAccepted))->setAccepted(true);
        $consentDataNotAccepted = (new ConsentData($consentNotAccepted))->setAccepted(false);

        $this->mockFilteredConsents(
            $consentAccepted,
            $consentNotAccepted,
            $consentDataAccepted,
            $consentDataNotAccepted
        );

        self::assertEquals(
            [$consentDataAccepted, $consentDataNotAccepted],
            $this->provider->getRequiredConsentData()
        );
    }

    private function mockFilteredConsents(
        Consent $consentAccepted,
        Consent $consentNotAccepted,
        ConsentData $consentDataAccepted,
        ConsentData $consentDataNotAccepted
    ): void {
        $this->enabledConsentProvider->expects(self::once())
            ->method('getConsents')
            ->with([
                FrontendConsentContentNodeValidFilter::NAME,
                RequiredConsentFilter::NAME
            ])
            ->willReturn([$consentAccepted, $consentNotAccepted]);

        $this->consentDataBuilder->expects(self::exactly(2))
            ->method('build')
            ->withConsecutive([$consentAccepted], [$consentNotAccepted])
            ->willReturnOnConsecutiveCalls($consentDataAccepted, $consentDataNotAccepted);
    }
}
