<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Filter\ConsentFilterCollection;
use Oro\Bundle\ConsentBundle\Filter\RequiredConsentFilter;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentConfigProviderInterface;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Component\Testing\ReflectionUtil;

class EnabledConsentProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EnabledConsentConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $enabledConsentConfigProvider;

    /** @var EnabledConsentProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->enabledConsentConfigProvider = $this->createMock(EnabledConsentConfigProviderInterface::class);

        $this->provider = new EnabledConsentProvider(
            $this->enabledConsentConfigProvider,
            new ConsentFilterCollection([new RequiredConsentFilter()])
        );
    }

    private function getConsent(int $id, bool $mandatory): Consent
    {
        $consent = new Consent();
        ReflectionUtil::setId($consent, $id);
        $consent->setMandatory($mandatory);

        return $consent;
    }

    /**
     * @dataProvider getConsentsProvider
     */
    public function testGetConsents(
        array $consentIdToMandatoryMapping,
        array $enabledFilters,
        array $expectedConsents
    ): void {
        $this->enabledConsentConfigProvider->expects(self::any())
            ->method('getConsentConfigs')
            ->willReturnCallback(function () use ($consentIdToMandatoryMapping) {
                $consentConfigs = [];
                foreach ($consentIdToMandatoryMapping as $id => $mandatory) {
                    $consentConfigs[] = new ConsentConfig($this->getConsent($id, $mandatory));
                }

                return $consentConfigs;
            });

        self::assertEquals($expectedConsents, $this->provider->getConsents($enabledFilters));
    }

    public function getConsentsProvider(): array
    {
        return [
            'No enabled consents' => [
                'consentIdToMandatoryMapping' => [],
                'enabledFilters' => [],
                'expectedConsents' => []
            ],
            'Filter not applicable' => [
                'consentIdToMandatoryMapping' => [
                    1 => true,
                    2 => false,
                ],
                'enabledFilters' => [],
                'expectedConsents' => [
                    $this->getConsent(1, true),
                    $this->getConsent(2, false)
                ]
            ],
            'Filter applicable but not filter consent' => [
                'consentIdToMandatoryMapping' => [],
                'enabledFilters' => [RequiredConsentFilter::NAME],
                'expectedConsents' => []
            ],
            'Filter applicable' => [
                'consentIdToMandatoryMapping' => [
                    1 => true,
                    2 => false,
                ],
                'enabledFilters' => [RequiredConsentFilter::NAME],
                'expectedConsents' => [
                    $this->getConsent(1, true)
                ]
            ]
        ];
    }

    public function testGetUnacceptedRequiredConsents(): void
    {
        $consentAcceptance = new ConsentAcceptance();
        $consentAcceptance->setConsent($this->getConsent(2, true));

        $consentConfigs = [
            new ConsentConfig($this->getConsent(2, true)),
            new ConsentConfig($this->getConsent(3, true))
        ];

        $this->enabledConsentConfigProvider->expects(self::once())
            ->method('getConsentConfigs')
            ->willReturn($consentConfigs);

        self::assertEquals(
            [$consentConfigs[1]->getConsent()],
            $this->provider->getUnacceptedRequiredConsents([$consentAcceptance])
        );
    }
}
