<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutWorkflowState;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CheckoutWorkflowStateRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCheckoutWorkflowState::class]);
    }

    private function getRepository(): CheckoutWorkflowStateRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(CheckoutWorkflowState::class);
    }

    public function testGetEntityStateByToken()
    {
        $statesData = LoadCheckoutWorkflowState::getStatesData();
        $stateEntity = $this->getReference(LoadCheckoutWorkflowState::CHECKOUT_STATE_1);
        $stateData = $statesData[LoadCheckoutWorkflowState::CHECKOUT_STATE_1];

        $this->assertEquals(
            $stateEntity,
            $this->getRepository()->getEntityByToken(
                $stateData['entityId'],
                $stateData['entityClass'],
                $stateData['token']
            )
        );
    }

    public function testDeleteEntityStates()
    {
        $statesData = LoadCheckoutWorkflowState::getStatesData();
        $stateData = $statesData[LoadCheckoutWorkflowState::CHECKOUT_STATE_2];
        $this->assertEquals(2, $this->countEntities());

        $this->getRepository()->deleteEntityStates($stateData['entityId'], $stateData['entityClass']);

        $this->assertEquals(1, $this->countEntities());
    }

    private function countEntities(): int
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->select('COUNT(t)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
