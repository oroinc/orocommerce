<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\ConsentBundle\Helper\CmsPageHelper;
use Oro\Bundle\ConsentBundle\Helper\ConsentContextInitializeHelperInterface;
use Oro\Bundle\ConsentBundle\Provider\CustomerUserConsentProvider;
use Oro\Bundle\ConsentBundle\Provider\EnabledConsentProvider;
use Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub\Consent;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CustomerUserConsentProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CustomerUserConsentProvider */
    private $provider;

    /** @var ConsentContextInitializeHelperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $consentContextInitializeHelper;

    /** @var CmsPageHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $cmsPageHelper;

    /** @var EnabledConsentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enabledConsentProvider;

    /** @var RegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->consentContextInitializeHelper = $this->createMock(ConsentContextInitializeHelperInterface::class);
        $this->cmsPageHelper = $this->createMock(CmsPageHelper::class);
        $this->enabledConsentProvider = $this->createMock(EnabledConsentProvider::class);
        $this->doctrine = $this->createMock(RegistryInterface::class);

        $this->provider = new CustomerUserConsentProvider(
            $this->consentContextInitializeHelper,
            $this->cmsPageHelper,
            $this->enabledConsentProvider,
            $this->doctrine
        );
    }

    /**
     * @param CustomerUser $customerUser
     * @param Consent[] $consents
     * @param ConsentAcceptance[] $acceptedConsents
     * @param array $expected
     * @dataProvider getCustomerUserConsentsWithAcceptancesProvider
     */
    public function testGetCustomerUserConsentsWithAcceptances(
        CustomerUser $customerUser,
        array $consents,
        array $acceptedConsents,
        array $expected
    ) {
        $this->consentContextInitializeHelper->expects($this->once())
            ->method('initialize')
            ->with($customerUser);

        $entityManager = $this->createMock(EntityManager::class);
        $this->doctrine->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(ConsentAcceptance::class)
            ->willReturn($entityManager);
        $repository = $this->createMock(ConsentAcceptanceRepository::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(ConsentAcceptance::class)
            ->willReturn($repository);

        $this->enabledConsentProvider->expects($this->once())
            ->method('getConsents')
            ->with([], [])
            ->willReturn($consents);

        $repository->expects($this->once())
            ->method('getAcceptedConsentsByCustomer')
            ->with($customerUser)
            ->willReturn($acceptedConsents);

        $this->cmsPageHelper->expects($this->any())
            ->method('getCmsPage')
            ->willReturnCallback(function (Consent $consent, ConsentAcceptance $consentAcceptance) {
                return $consentAcceptance->getLandingPage();
            });

        $this->assertEquals(
            $expected,
            $this->provider->getCustomerUserConsentsWithAcceptances($customerUser)
        );
    }

    /**
     * @return array
     */
    public function getCustomerUserConsentsWithAcceptancesProvider()
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 42]);

        $consent1 = $this->getEntity(Consent::class, ['id' => 11]);
        $consent2 = $this->getEntity(Consent::class, ['id' => 12]);
        $consent3 = $this->getEntity(Consent::class, ['id' => 13]);
        $consent4 = $this->getEntity(Consent::class, ['id' => 14]);
        $consent5 = $this->getEntity(Consent::class, ['id' => 15]);

        $landingPage2 = $this->getEntity(Page::class, ['id' => 32]);
        $landingPage5 = $this->getEntity(Page::class, ['id' => 35]);

        $consentAcceptance2 = $this->getEntity(ConsentAcceptance::class, [
            'id' => 22,
            'consent' => $consent2,
            'customerUser' => $customerUser,
            'landingPage' => $landingPage2,
        ]);
        $consentAcceptance3 = $this->getEntity(ConsentAcceptance::class, [
            'id' => 23,
            'consent' => $consent3,
            'customerUser' => $customerUser,
        ]);
        $consentAcceptance5 = $this->getEntity(ConsentAcceptance::class, [
            'id' => 25,
            'consent' => $consent5,
            'customerUser' => $customerUser,
            'landingPage' => $landingPage5,
        ]);

        return [
            'several enabled consents, some of them accepted, with and without landing pages' => [
                'customerUser' => $customerUser,
                'consents' => [
                    $consent1,
                    $consent2,
                    $consent3,
                    $consent4,
                    $consent5,
                ],
                'acceptedConsents' => [
                    $consentAcceptance2,
                    $consentAcceptance3,
                    $consentAcceptance5,
                ],
                'expected' => [
                    [
                        'consent' => $consent1,
                        'accepted' => false,
                        'landingPage' => null,
                    ],
                    [
                        'consent' => $consent2,
                        'accepted' => true,
                        'landingPage' => $landingPage2,
                    ],
                    [
                        'consent' => $consent3,
                        'accepted' => true,
                        'landingPage' => null,
                    ],
                    [
                        'consent' => $consent4,
                        'accepted' => false,
                        'landingPage' => null,
                    ],
                    [
                        'consent' => $consent5,
                        'accepted' => true,
                        'landingPage' => $landingPage5,
                    ],
                ],
            ],
        ];
    }

    public function testHasEnabledConsentsByCustomerUser()
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 42]);

        $this->consentContextInitializeHelper->expects($this->once())
            ->method('initialize')
            ->with($customerUser);

        $this->enabledConsentProvider->expects($this->once())
            ->method('getConsents')
            ->with([], [])
            ->willReturn(
                [$this->getEntity(Consent::class, ['id' => 11])]
            );

        $this->assertTrue($this->provider->hasEnabledConsentsByCustomerUser($customerUser));
    }

    public function testHasEnabledConsentsByCustomerUserNoConsents()
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 42]);

        $this->consentContextInitializeHelper->expects($this->once())
            ->method('initialize')
            ->with($customerUser);

        $this->enabledConsentProvider->expects($this->once())
            ->method('getConsents')
            ->with([], [])
            ->willReturn(
                []
            );

        $this->assertFalse($this->provider->hasEnabledConsentsByCustomerUser($customerUser));
    }
}
