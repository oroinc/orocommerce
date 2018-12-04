<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ConsentAcceptanceProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ConsentContextProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextProvider;

    /**
     * @var ConsentAcceptanceRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $consentAcceptanceRepository;

    /**
     * @var ConsentAcceptanceProvider
     */
    private $consentAcceptanceProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->contextProvider = $this->createMock(ConsentContextProvider::class);
        $this->consentAcceptanceRepository = $this->createMock(ConsentAcceptanceRepository::class);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(ConsentAcceptance::class)
            ->willReturn($this->consentAcceptanceRepository);

        /** @var RegistryInterface|\PHPUnit\Framework\MockObject\MockObject $doctrine */
        $doctrine = $this->createMock(RegistryInterface::class);
        $doctrine->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(ConsentAcceptance::class)
            ->willReturn($em);

        $this->consentAcceptanceProvider = new ConsentAcceptanceProvider(
            $this->contextProvider,
            $doctrine
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->contextProvider);
        unset($this->consentAcceptanceRepository);
        unset($this->consentAcceptanceProvider);
    }

    /**
     * @dataProvider getCustomerConsentAcceptancesProvider
     *
     * @param CustomerUser|null $customerUser
     * @param array             $consentAcceptances
     */
    public function testGetCustomerConsentAcceptances(CustomerUser $customerUser = null, array $consentAcceptances = [])
    {
        $this->contextProvider->expects($this->any())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        if ($customerUser !== null) {
            $this->consentAcceptanceRepository
                ->expects($this->once())
                ->method('getAcceptedConsentsByCustomer')
                ->with($customerUser)
                ->willReturn($consentAcceptances);
        } else {
            $this->consentAcceptanceRepository
                ->expects($this->never())
                ->method('getAcceptedConsentsByCustomer');
        }

        $this->assertSame(
            $consentAcceptances,
            $this->consentAcceptanceProvider->getCustomerConsentAcceptances()
        );
    }

    /**
     * @return array
     */
    public function getCustomerConsentAcceptancesProvider()
    {
        return [
            "Context doesn't contain customerUser" => [
                'customerUser' => null,
                'consentAcceptances' => []
            ],
            "There is no consentAcceptance signed by CustomerUser" => [
                'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 21]),
                'consentAcceptances' => []
            ],
            "There are several consentAcceptances signed by CustomerUser" => [
                'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 21]),
                'consentAcceptances' => [
                    $this->getEntity(ConsentAcceptance::class, ['id' => 1]),
                    $this->getEntity(ConsentAcceptance::class, ['id' => 2]),
                ]
            ],
        ];
    }

    /**
     * @dataProvider getGetCustomerConsentAcceptanceByConsentId
     *
     * @param CustomerUser|null      $customerUser
     * @param array                  $consentAcceptances
     * @param int                    $consentId
     * @param ConsentAcceptance|null $expectedResult
     */
    public function testGetCustomerConsentAcceptanceByConsentId(
        $customerUser,
        array $consentAcceptances,
        int $consentId,
        ConsentAcceptance $expectedResult = null
    ) {
        $this->contextProvider->expects($this->any())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        if ($customerUser !== null) {
            $this->consentAcceptanceRepository
                ->expects($this->once())
                ->method('getAcceptedConsentsByCustomer')
                ->with($customerUser)
                ->willReturn($consentAcceptances);
        } else {
            $this->consentAcceptanceRepository
                ->expects($this->never())
                ->method('getAcceptedConsentsByCustomer');
        }

        $this->assertSame(
            $expectedResult,
            $this->consentAcceptanceProvider->getCustomerConsentAcceptanceByConsentId($consentId)
        );
    }

    /**
     * @return array
     */
    public function getGetCustomerConsentAcceptanceByConsentId()
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
            "Context doesn't contain customerUser" => [
                'customerUser' => null,
                'consentAcceptances' => [],
                'consentId' => 1,
                'expectedResult' => null
            ],
            "There is no consentAcceptance signed by CustomerUser" => [
                'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 21]),
                'consentAcceptances' => [],
                'consentId' => 1,
                'expectedResult' => null
            ],
            "Found consentAcceptance signed by CustomerUser on given consent id" => [
                'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 21]),
                'consentAcceptances' => [
                    $consentAcceptance1,
                    $consentAcceptance2,
                ],
                'consentId' => 1,
                'expectedResult' => $consentAcceptance1
            ],
            "Not found consentAcceptance signed by CustomerUser on given consent id" => [
                'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 21]),
                'consentAcceptances' => [
                    $consentAcceptance1,
                    $consentAcceptance2,
                ],
                'consentId' => 3,
                'expectedResult' => null
            ],
        ];
    }

    /**
     * @dataProvider getGetCustomerConsentAcceptancesByConsents
     *
     * @param CustomerUser|null      $customerUser
     * @param array                  $consentAcceptances
     * @param array                  $consents
     * @param array                  $expectedResult
     */
    public function testGetCustomerConsentAcceptancesByConsents(
        $customerUser,
        array $consentAcceptances,
        array $consents,
        array $expectedResult = []
    ) {
        $this->contextProvider->expects($this->any())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        if ($customerUser !== null) {
            $this->consentAcceptanceRepository
                ->expects($this->once())
                ->method('getAcceptedConsentsByCustomer')
                ->with($customerUser)
                ->willReturn($consentAcceptances);
        } else {
            $this->consentAcceptanceRepository
                ->expects($this->never())
                ->method('getAcceptedConsentsByCustomer');
        }

        $this->assertSame(
            $expectedResult,
            $this->consentAcceptanceProvider->getCustomerConsentAcceptancesByConsents(
                $consents
            )
        );
    }

    /**
     * @return array
     */
    public function getGetCustomerConsentAcceptancesByConsents()
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
            "Context doesn't contain customerUser" => [
                'customerUser' => null,
                'consentAcceptances' => [],
                'consents' => [
                    $consent1
                ],
                'expectedResult' => []
            ],
            "There is no consentAcceptance signed by CustomerUser" => [
                'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 21]),
                'consentAcceptances' => [],
                'consents' => [
                    $consent1
                ],
                'expectedResult' => []
            ],
            "Found consentAcceptances signed by CustomerUser by given consents" => [
                'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 21]),
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
            "Not found consentAcceptance signed by CustomerUser by given consents" => [
                'customerUser' => $this->getEntity(CustomerUser::class, ['id' => 21]),
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
