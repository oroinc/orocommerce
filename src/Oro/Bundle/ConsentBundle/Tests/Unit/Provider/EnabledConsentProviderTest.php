<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Filter\ConsentFilterInterface;
use Oro\Bundle\ConsentBundle\Filter\RequiredConsentFilter;
use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class EnabledConsentProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ConsentConfigConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $converter;

    /**
     * @var ConsentContextProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextProvider;

    /**
     * @var EnabledConsentProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->converter = $this->createMock(ConsentConfigConverter::class);
        $this->contextProvider = $this->createMock(ConsentContextProvider::class);

        $this->provider = new EnabledConsentProvider(
            $this->configManager,
            $this->converter,
            $this->contextProvider
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->provider,
            $this->configManager,
            $this->converter
        );
    }

    /**
     * @dataProvider getConsentsProvider
     *
     * @param array $consentConfigValue
     * @param array $consentIdToMandatoryMapping
     * @param ConsentFilterInterface|null $filter
     * @param array $enabledFilters
     * @param Website|null $website
     * @param array $expectedConsents
     */
    public function testGetConsents(
        array $consentConfigValue,
        array $consentIdToMandatoryMapping,
        ConsentFilterInterface $filter = null,
        array $enabledFilters,
        Website $website = null,
        $expectedConsents
    ) {
        $this->contextProvider
            ->expects($this->once())
            ->method('getWebsite')
            ->willReturn($website);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_consent.enabled_consents', [], false, $website)
            ->willReturn($consentConfigValue);

        $this->converter->expects($this->any())
            ->method('convertFromSaved')
            ->willReturnCallback(
                function (array $consentConfigValue) use ($consentIdToMandatoryMapping) {
                    return array_map(function ($configItem) use ($consentIdToMandatoryMapping) {
                        $consentId = $configItem[ConsentConfigConverter::CONSENT_KEY];
                        $consent = $this->getEntity(Consent::class, [
                            'id' => $consentId,
                            'mandatory' => $consentIdToMandatoryMapping[$consentId]
                        ]);

                        return new ConsentConfig($consent);
                    }, $consentConfigValue);
                }
            );

        if ($filter) {
            $this->provider->addFilter($filter);
        }

        $consents = $this->provider->getConsents($enabledFilters);
        $this->assertEquals($expectedConsents, $consents);
    }

    public function testGetUnacceptedRequiredConsents()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $consentConfigValue = [[ConsentConfigConverter::CONSENT_KEY => 2], [ConsentConfigConverter::CONSENT_KEY => 3]];
        $consentIdToMandatoryMapping = [2 => true, 3 => true];
        $consentAcceptance = $this->getEntity(
            ConsentAcceptance::class,
            ['id' => 2, 'consent' => $this->getEntity(Consent::class, ['id' => 2])]
        );

        $this->contextProvider
            ->expects($this->once())
            ->method('getWebsite')
            ->willReturn($website);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_consent.enabled_consents', [], false, $website)
            ->willReturn($consentConfigValue);

        $this->converter
            ->expects($this->any())
            ->method('convertFromSaved')
            ->willReturnCallback(
                function (array $consentConfigValue) use ($consentIdToMandatoryMapping) {
                    return array_map(function ($configItem) use ($consentIdToMandatoryMapping) {
                        $consentId = $configItem[ConsentConfigConverter::CONSENT_KEY];
                        $consent = $this->getEntity(Consent::class, [
                            'id' => $consentId,
                            'mandatory' => $consentIdToMandatoryMapping[$consentId]
                        ]);

                        return new ConsentConfig($consent);
                    }, $consentConfigValue);
                }
            );

        $this->provider->addFilter(new RequiredConsentFilter());

        $consents = $this->provider->getUnacceptedRequiredConsents([$consentAcceptance]);
        $this->assertEquals([1 => $this->getEntity(Consent::class, ['id' => 3])], $consents);
    }

    /**
     * @return array
     */
    public function getConsentsProvider()
    {
        $filter = new RequiredConsentFilter();

        return [
            'No enabled consents' => [
                'consentConfigValue' => [],
                'consentIdToMandatoryMapping' => [],
                'filter' => $filter,
                'enabledFilters' => [],
                'website' => $this->getEntity(Website::class, ['id' => 1]),
                'expectedConsents' => []
            ],
            'Filter not applicable' => [
                'consentConfigValue' => [
                    [
                        ConsentConfigConverter::CONSENT_KEY => 1,
                    ],
                    [
                        ConsentConfigConverter::CONSENT_KEY => 2,
                    ]
                ],
                'consentIdToMandatoryMapping' => [
                    1 => true,
                    2 => false,
                ],
                'filter' => $filter,
                'enabledFilters' => [],
                'website' => $this->getEntity(Website::class, ['id' => 1]),
                'expectedConsents' => [
                    $this->getEntity(Consent::class, ['id' => 1, 'mandatory' => true]),
                    $this->getEntity(Consent::class, ['id' => 2, 'mandatory' => false])
                ]
            ],
            'Filter applicable but not filter consent' => [
                'consentConfigValue' => [],
                'consentIdToMandatoryMapping' => [],
                'filter' => $filter,
                'enabledFilters' => [$filter->getName()],
                'website' => $this->getEntity(Website::class, ['id' => 1]),
                'expectedConsents' => []
            ],
            'Filter applicable' => [
                'consentConfigValue' => [
                    [
                        ConsentConfigConverter::CONSENT_KEY => 1,
                    ],
                    [
                        ConsentConfigConverter::CONSENT_KEY => 2,
                    ]
                ],
                'consentIdToMandatoryMapping' => [
                    1 => true,
                    2 => false,
                ],
                'filter' => $filter,
                'enabledFilters' => [$filter->getName()],
                'website' => $this->getEntity(Website::class, ['id' => 1]),
                'expectedConsents' => [
                    $this->getEntity(Consent::class, ['id' => 1, 'mandatory' => true])
                ]
            ],
            'Website not present in the context' => [
                'consentConfigValue' => [
                    [
                        ConsentConfigConverter::CONSENT_KEY => 1,
                    ],
                    [
                        ConsentConfigConverter::CONSENT_KEY => 2,
                    ]
                ],
                'consentIdToMandatoryMapping' => [
                    1 => true,
                    2 => false,
                ],
                'filter' => $filter,
                'enabledFilters' => [$filter->getName()],
                'website' => null,
                'expectedConsents' => []
            ]
        ];
    }
}
