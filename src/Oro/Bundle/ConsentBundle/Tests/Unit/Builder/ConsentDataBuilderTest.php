<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Builder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ConsentBundle\Builder\CmsPageDataBuilder;
use Oro\Bundle\ConsentBundle\Builder\ConsentDataBuilder;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Model\CmsPageData;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsentDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ConsentAcceptanceProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $consentAcceptanceProvider;

    /**
     * @var CmsPageDataBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cmsPageDataBuilder;

    /**
     * @var ConsentDataBuilder
     */
    private $consentDataBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->consentAcceptanceProvider = $this->createMock(ConsentAcceptanceProvider::class);

        /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper */
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->will(
                $this->returnCallback(
                    function (Collection $collection) {
                        return $collection->first()->getString();
                    }
                )
            );

        $this->cmsPageDataBuilder = $this->createMock(CmsPageDataBuilder::class);
        $this->consentDataBuilder = new ConsentDataBuilder(
            $this->consentAcceptanceProvider,
            $localizationHelper,
            $this->cmsPageDataBuilder
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->consentAcceptanceProvider,
            $this->cmsPageDataBuilder,
            $this->consentDataBuilder
        );
    }

    /**
     * @dataProvider buildProvider
     *
     * @param Consent                $consent
     * @param ConsentAcceptance|null $consentAcceptance
     * @param CmsPageData|null       $cmsPageData
     * @param array                  $expectedConsentJsonData
     */
    public function testBuild(
        Consent $consent,
        ConsentAcceptance $consentAcceptance = null,
        CmsPageData $cmsPageData = null,
        array $expectedConsentJsonData
    ) {
        $this->consentAcceptanceProvider
            ->expects($this->once())
            ->method('getCustomerConsentAcceptanceByConsentId')
            ->with($consent->getId())
            ->willReturn($consentAcceptance);

        $this->cmsPageDataBuilder
            ->expects($this->once())
            ->method('build')
            ->with($consent, $consentAcceptance)
            ->willReturn($cmsPageData);

        $consentData = $this->consentDataBuilder->build($consent);
        $this->assertEquals($expectedConsentJsonData, $consentData->jsonSerialize());
    }

    /**
     * @return array
     */
    public function buildProvider()
    {
        $fallbackValue = new LocalizedFallbackValue();
        $fallbackValue->setString('consent_1');

        $cmsPageDataObj = new CmsPageData();
        $cmsPageDataObj->setId(12);
        $cmsPageDataObj->setUrl('/cms-page-url');

        return [
            "ConsentAcceptance found" => [
                'consent' => $this->getEntity(
                    Consent::class,
                    [
                        'id' => 1,
                        'names' => new ArrayCollection([$fallbackValue]),
                        'mandatory' => true
                    ]
                ),
                'consentAcceptance' => $this->getEntity(ConsentAcceptance::class, ['id' => 1]),
                'cmsPageData' => null,
                'expectedConsentJsonData' => [
                    'consentId' => 1,
                    'required' => true,
                    'consentTitle' => 'consent_1',
                    'accepted' => true,
                ]
            ],
            "ConsentAcceptance not found" => [
                'consent' => $this->getEntity(
                    Consent::class,
                    [
                        'id' => 1,
                        'names' => new ArrayCollection([$fallbackValue]),
                        'mandatory' => false
                    ]
                ),
                'consentAcceptance' => null,
                'cmsPageData' => null,
                'expectedConsentJsonData' => [
                    'consentId' => 1,
                    'required' => false,
                    'consentTitle' => 'consent_1',
                    'accepted' => false
                ]
            ],
            "CmsPageData successfully built" => [
                'consent' => $this->getEntity(
                    Consent::class,
                    [
                        'id' => 1,
                        'names' => new ArrayCollection([$fallbackValue]),
                        'mandatory' => false
                    ]
                ),
                'consentAcceptance' => null,
                'cmsPageData' => $cmsPageDataObj,
                'expectedConsentJsonData' => [
                    'consentId' => 1,
                    'required' => false,
                    'consentTitle' => 'consent_1',
                    'accepted' => false,
                    'cmsPageData' => [
                        'id'  => 12,
                        'url' => '/cms-page-url'
                    ]
                ]
            ]
        ];
    }
}
