<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ConsentAcceptanceProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenStorage;

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
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->consentAcceptanceRepository = $this->createMock(ConsentAcceptanceRepository::class);

        $em = $this->createMock(EntityManager::class);
        $em->method('getRepository')
            ->with(ConsentAcceptance::class)
            ->willReturn($this->consentAcceptanceRepository);

        /** @var RegistryInterface|\PHPUnit\Framework\MockObject\MockObject $doctrine */
        $doctrine = $this->createMock(RegistryInterface::class);
        $doctrine->method('getEntityManagerForClass')
            ->with(ConsentAcceptance::class)
            ->willReturn($em);

        $this->consentAcceptanceProvider = new ConsentAcceptanceProvider(
            $this->tokenStorage,
            $doctrine
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->tokenStorage,
            $this->consentAcceptanceRepository,
            $this->consentAcceptanceProvider
        );
    }

    public function testGetCustomerConsentAcceptancesWithoutCustomerUser()
    {
        $this->mockToken();

        $this->consentAcceptanceRepository
            ->expects($this->never())
            ->method('getAcceptedConsentsByCustomer');

        $this->assertEquals([], $this->consentAcceptanceProvider->getCustomerConsentAcceptances());
    }

    public function testGetCustomerConsentAcceptancesForAnonymous()
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $token->expects($this->never())
            ->method('getUser');
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->consentAcceptanceRepository
            ->expects($this->never())
            ->method('getAcceptedConsentsByCustomer');

        $this->assertEquals([], $this->consentAcceptanceProvider->getCustomerConsentAcceptances());
    }

    /**
     * @dataProvider getCustomerConsentAcceptancesProvider
     *
     * @param array $consentAcceptances
     */
    public function testGetCustomerConsentAcceptances(array $consentAcceptances = [])
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 21]);

        $this->mockToken($customerUser);

        $this->consentAcceptanceRepository
            ->expects($this->once())
            ->method('getAcceptedConsentsByCustomer')
            ->with($customerUser)
            ->willReturn($consentAcceptances);

        $this->assertSame(
            $consentAcceptances,
            $this->consentAcceptanceProvider->getCustomerConsentAcceptances()
        );
    }

    public function testGetGuestCustomerConsentAcceptances()
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 21]);

        $visitor = new CustomerVisitor();
        $visitor->setCustomerUser($customerUser);
        $token = new AnonymousCustomerUserToken('', [], $visitor);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->consentAcceptanceRepository
            ->expects($this->never())
            ->method('getAcceptedConsentsByCustomer');

        $this->assertSame(
            [],
            $this->consentAcceptanceProvider->getCustomerConsentAcceptances()
        );
    }

    /**
     * @return array
     */
    public function getCustomerConsentAcceptancesProvider()
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
        $this->mockToken();

        $this->consentAcceptanceRepository
            ->expects($this->never())
            ->method('getAcceptedConsentsByCustomer');

        $this->assertNull($this->consentAcceptanceProvider->getCustomerConsentAcceptanceByConsentId(1));
    }

    /**
     * @dataProvider getGetCustomerConsentAcceptanceByConsentId
     *
     * @param array                  $consentAcceptances
     * @param int                    $consentId
     * @param ConsentAcceptance|null $expectedResult
     */
    public function testGetCustomerConsentAcceptanceByConsentId(
        array $consentAcceptances,
        int $consentId,
        ConsentAcceptance $expectedResult = null
    ) {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 21]);

        $this->mockToken($customerUser);

        $this->consentAcceptanceRepository
            ->expects($this->once())
            ->method('getAcceptedConsentsByCustomer')
            ->with($customerUser)
            ->willReturn($consentAcceptances);

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
        $this->mockToken();

        $this->consentAcceptanceRepository
            ->expects($this->never())
            ->method('getAcceptedConsentsByCustomer');

        $consents = [$this->getEntity(Consent::class, ['id' => 1])];

        $this->assertEquals([], $this->consentAcceptanceProvider->getCustomerConsentAcceptancesByConsents($consents));
    }

    /**
     * @dataProvider getGetCustomerConsentAcceptancesByConsents
     *
     * @param array $consentAcceptances
     * @param array $consents
     * @param array $expectedResult
     */
    public function testGetCustomerConsentAcceptancesByConsents(
        array $consentAcceptances,
        array $consents,
        array $expectedResult = []
    ) {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 21]);

        $this->mockToken($customerUser);

        $this->consentAcceptanceRepository
            ->expects($this->once())
            ->method('getAcceptedConsentsByCustomer')
            ->with($customerUser)
            ->willReturn($consentAcceptances);

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

    /**
     * @param CustomerUser|null $customerUser
     */
    private function mockToken(CustomerUser $customerUser = null)
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
    }
}
