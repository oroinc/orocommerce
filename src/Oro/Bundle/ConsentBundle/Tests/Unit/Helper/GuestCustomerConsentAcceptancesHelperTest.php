<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Helper;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\ConsentBundle\Helper\GuestCustomerConsentAcceptancesHelper;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class GuestCustomerConsentAcceptancesHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var  DoctrineHelper |\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var  GuestCustomerConsentAcceptancesHelper */
    private $helper;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->helper = new GuestCustomerConsentAcceptancesHelper($this->doctrineHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        unset(
            $this->doctrineHelper,
            $this->helper
        );
    }

    /**
     * @dataProvider consentAcceptanceProvider
     * @param $actualAcceptances
     * @param $customerUser
     * @param $existingAcceptances
     * @param $expectedAcceptances
     */
    public function testFilterGuestCustomerAcceptances(
        $actualAcceptances,
        $customerUser,
        $existingAcceptances,
        $expectedAcceptances
    ) {
        if ($customerUser->getId() && !empty($actualAcceptances)) {
            $repository = $this->createMock(ConsentAcceptanceRepository::class);
            $this->doctrineHelper->expects($this->once())
                ->method('getEntityRepository')
                ->with(ConsentAcceptance::class)
                ->willReturn($repository);
            $repository->expects($this->once())
                ->method('findBy')
                ->willReturn($existingAcceptances);
        }
        $result = $this->helper->filterGuestCustomerAcceptances($customerUser, $actualAcceptances);

        $this->assertEquals($expectedAcceptances, $result);
    }

    /**
     * @return array
     */
    public function consentAcceptanceProvider()
    {
        $consent1 = $this->getEntity(Consent::class, ['id' => 1]);
        $consent2 = $this->getEntity(Consent::class, ['id' => 2]);

        return [
            "Customer user doesn't exist" => [
                "consentAcceptances" => [
                    $this->getEntity(ConsentAcceptance::class, ['consent' => $consent1])
                ],
                "customerUser" => $this->getEntity(CustomerUser::class),
                "customerAcceptances" => [],
                "expectedAcceptances" => [
                    $this->getEntity(ConsentAcceptance::class, ['consent' => $consent1])
                ]
            ],
            "Customer has id but no acceptances" => [
                "consentAcceptances" => [],
                "customerUser" => $this->getEntity(CustomerUser::class, ['id' => 35]),
                "customerAcceptances" => [
                    $this->getEntity(ConsentAcceptance::class, ['consent' => $consent1])
                ],
                "expectedAcceptances" => []
            ],
            "Acceptances should be filtered" => [
                "consentAcceptances" => [
                    $this->getEntity(ConsentAcceptance::class, ['consent' => $consent1]),
                    $this->getEntity(ConsentAcceptance::class, ['consent' => $consent2])
                ],
                "customerUser" => $this->getEntity(CustomerUser::class, ['id' => 35]),
                "customerAcceptances" => [
                    $this->getEntity(ConsentAcceptance::class, ['consent' => $consent2])
                ],
                "expectedAcceptances" => [
                    $this->getEntity(ConsentAcceptance::class, ['consent' => $consent1])
                ]
            ]
        ];
    }
}
