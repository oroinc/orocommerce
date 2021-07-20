<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 */
class CustomerGroupChangesListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadPriceListRelations::class]);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(CustomerGroup::class);
    }

    /**
     * @param $operationName
     * @param $entityId
     * @param $entityClass
     *
     * @return array
     */
    private function getOperationExecuteParams($operationName, $entityId, $entityClass)
    {
        $actionContext = [
            'entityId'    => $entityId,
            'entityClass' => $entityClass
        ];
        $container = self::getContainer();
        $operation = $container->get('oro_action.operation_registry')->findByName($operationName);
        $actionData = $container->get('oro_action.helper.context')->getActionData($actionContext);

        $tokenData = $container
            ->get('oro_action.operation.execution.form_provider')
            ->createTokenData($operation, $actionData);
        $container->get('session')->save();

        return $tokenData;
    }

    private function sendDeleteCustomerGroupRequest(CustomerGroup $group)
    {
        $groupId = $group->getId();

        $operationName = 'oro_customer_groups_delete';
        $entityClass = CustomerGroup::class;
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'entityId[id]'  => $groupId,
                    'entityClass'   => $entityClass,
                ]
            ),
            $this->getOperationExecuteParams($operationName, ['id' => $groupId], $entityClass),
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $em = $this->getEntityManager();
        $em->clear();

        $removedGroup = $em->getRepository(CustomerGroup::class)
            ->find($groupId);

        static::assertNull($removedGroup);
    }

    public function testDeleteCustomerGroupWithAssignedCustomer()
    {
        $this->sendDeleteCustomerGroupRequest($this->getReference(LoadGroups::GROUP1));

        self::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                'website'       => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                'customerGroup' => $this->getReference('customer_group.group1')->getId(),
                'customer'      => $this->getReference('customer.level_1.3')->getId()
            ]
        );
    }

    public function testDeleteCustomerGroupWithoutCustomer()
    {
        $this->sendDeleteCustomerGroupRequest($this->getReference(LoadGroups::GROUP3));

        self::assertEmptyMessages(Topics::REBUILD_COMBINED_PRICE_LISTS);
    }
}
