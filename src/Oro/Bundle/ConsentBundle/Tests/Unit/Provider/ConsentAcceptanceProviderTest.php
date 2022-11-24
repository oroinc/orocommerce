<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ConsentAcceptanceProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ConsentAcceptanceRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $consentAcceptanceRepository;

    /** @var ConsentAcceptanceProvider */
    private $consentAcceptanceProvider;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->consentAcceptanceRepository = $this->createMock(ConsentAcceptanceRepository::class);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(ConsentAcceptance::class)
            ->willReturn($this->consentAcceptanceRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(ConsentAcceptance::class)
            ->willReturn($em);

        $this->consentAcceptanceProvider = new ConsentAcceptanceProvider(
            $this->tokenAccessor,
            $doctrine
        );
    }

    public function testGetCustomerConsentAcceptancesWithoutCustomerUser()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->consentAcceptanceRepository->expects($this->never())
            ->method('getAcceptedConsentsByCustomer');

        $this->assertSame(
            [],
            $this->consentAcceptanceProvider->getCustomerConsentAcceptances()
        );
    }

    /**
     * @dataProvider getCustomerConsentAcceptancesProvider
     */
    public function testGetCustomerConsentAcceptances(array $consentAcceptances = [])
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 21]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->consentAcceptanceRepository->expects($this->once())
            ->method('getAcceptedConsentsByCustomer')
            ->with($customerUser)
            ->willReturn($consentAcceptances);

        $this->assertSame(
            $consentAcceptances,
            $this->consentAcceptanceProvider->getCustomerConsentAcceptances()
        );
    }

    public function getCustomerConsentAcceptancesProvider(): array
    {
        return [
            'There is no consentAcceptance signed by CustomerUser' => [
                'consentAcceptances' => []
            ],
            'There are several consentAcceptances signed by CustomerUser' => [
                'consentAcceptances' => [
                    $this->getEntity(ConsentAcceptance::class, ['id' => 1]),
                    $this->getEntity(ConsentAcceptance::class, ['id' => 2]),
                ]
            ],
        ];
    }

    public function testGetCustomerConsentAcceptanceByConsentIdWithoutCustomerUser()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->consentAcceptanceRepository->expects($this->never())
            ->method('getAcceptedConsentsByCustomer');

        $this->assertNull($this->consentAcceptanceProvider->getCustomerConsentAcceptanceByConsentId(1));
    }

    /**
     * @dataProvider getGetCustomerConsentAcceptanceByConsentId
     */
    public function testGetCustomerConsentAcceptanceByConsentId(
        array $consentAcceptances,
        int $consentId,
        ConsentAcceptance $expectedResult = null
    ) {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 21]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->consentAcceptanceRepository->expects($this->once())
            ->method('getAcceptedConsentsByCustomer')
            ->with($customerUser)
            ->willReturn($consentAcceptances);

        $this->assertSame(
            $expectedResult,
            $this->consentAcceptanceProvider->getCustomerConsentAcceptanceByConsentId($consentId)
        );
    }

    public function getGetCustomerConsentAcceptanceByConsentId(): array
    {
        $consentAcceptance1 = $this->getEntity(
            ConsentAcceptance::class,
            [
                'id' => 1,
                'consent' => $this->getEntity(Consent::class, ['id' => 1])
            ]
        );
        $consentAcceptance2 = $this->getEntity(
            ConsentAcceptance::class,
            [
                'id' => 2,
                'consent' => $this->getEntity(Consent::class, ['id' => 2])
            ]
        );

        return [
            'There is no consentAcceptance signed by CustomerUser' => [
                'consentAcceptances' => [],
                'consentId' => 1,
                'expectedResult' => null
            ],
            'Found consentAcceptance signed by CustomerUser on given consent id' => [
                'consentAcceptances' => [
                    $consentAcceptance1,
                    $consentAcceptance2,
                ],
                'consentId' => 1,
                'expectedResult' => $consentAcceptance1
            ],
            'Not found consentAcceptance signed by CustomerUser on given consent id' => [
                'consentAcceptances' => [
                    $consentAcceptance1,
                    $consentAcceptance2,
                ],
                'consentId' => 3,
                'expectedResult' => null
            ],
        ];
    }

    public function testGetCustomerConsentAcceptancesByConsentsWithoutCustomerUser()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->consentAcceptanceRepository->expects($this->never())
            ->method('getAcceptedConsentsByCustomer');

        $consents = [$this->getEntity(Consent::class, ['id' => 1])];

        $this->assertSame(
            [],
            $this->consentAcceptanceProvider->getCustomerConsentAcceptancesByConsents($consents)
        );
    }

    /**
     * @dataProvider getGetCustomerConsentAcceptancesByConsents
     */
    public function testGetCustomerConsentAcceptancesByConsents(
        array $consentAcceptances,
        array $consents,
        array $expectedResult = []
    ) {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 21]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->consentAcceptanceRepository->expects($this->once())
            ->method('getAcceptedConsentsByCustomer')
            ->with($customerUser)
            ->willReturn($consentAcceptances);

        $this->assertSame(
            $expectedResult,
            $this->consentAcceptanceProvider->getCustomerConsentAcceptancesByConsents($consents)
        );
    }

    public function getGetCustomerConsentAcceptancesByConsents(): array
    {
        $consent1 = $this->getEntity(Consent::class, ['id' => 1]);
        $consent2 = $this->getEntity(Consent::class, ['id' => 2]);
        $consent3 = $this->getEntity(Consent::class, ['id' => 3]);

        $consentAcceptance1 = $this->getEntity(
            ConsentAcceptance::class,
            [
                'id' => 1,
                'consent' => $consent1
            ]
        );
        $consentAcceptance2 = $this->getEntity(
            ConsentAcceptance::class,
            [
                'id' => 2,
                'consent' => $consent2
            ]
        );

        return [
            'There is no consentAcceptance signed by CustomerUser' => [
                'consentAcceptances' => [],
                'consents' => [
                    $consent1
                ],
                'expectedResult' => []
            ],
            'Found consentAcceptances signed by CustomerUser by given consents' => [
                'consentAcceptances' => [
                    $consentAcceptance1,
                    $consentAcceptance2,
                ],
                'consents' => [
                    $consent1,
                    $consent2,
                    $consent3
                ],
                'expectedResult' => [
                    $consentAcceptance1,
                    $consentAcceptance2,
                ]
            ],
            'Not found consentAcceptance signed by CustomerUser by given consents' => [
                'consentAcceptances' => [
                    $consentAcceptance1,
                    $consentAcceptance2,
                ],
                'consents' => [$consent3],
                'expectedResult' => []
            ],
        ];
    }
}
