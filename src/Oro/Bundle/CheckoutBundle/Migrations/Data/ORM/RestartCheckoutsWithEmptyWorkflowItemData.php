<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Migration for restart broken checkout with WorkflowItems that not contains valid json data,
 * needs to fix error in checkout workflows after platform update with broken WorkflowItems
 */
class RestartCheckoutsWithEmptyWorkflowItemData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const int BUFFER_SIZE = 100;

    public function load(ObjectManager $manager)
    {
        $workflowManager = $this->container->get('oro_workflow.manager');

        $workflowNames = $this->getCheckoutWorkflowNames($manager);

        $qb = $manager->getRepository(WorkflowItem::class)->createQueryBuilder('wi');
        // Query for select WorkflowItem on which depends checkout workflows,
        // that not contains valid json string(empty string, NULL, []),
        // here use {% pattern in reason of in the version of postgresql less than 16 not exist IS JSON type check
        // and customer must have possibility to upgrade from latest to newest version of oro
        $query = $qb->select('wi')
            ->where($qb->expr()->in('wi.workflowName', ':workflowNames'))
            ->andWhere($qb->expr()->notLike('wi.serializedData', ':jsonPattern'))
            ->setParameter('workflowNames', $workflowNames)
            ->setParameter('jsonPattern', '{%', Types::STRING);

        $resultIterator = new BufferedIdentityQueryResultIterator($query);
        $resultIterator->setBufferSize(self::BUFFER_SIZE);

        /** @var WorkflowItem $workflowItem */
        foreach ($resultIterator as $workflowItem) {
            // Creating a new WorkflowData needs for avoid transit to enter_credentials_step
            // in reason of step condition check WorkflowItem.data.checkout.customerUser
            // and WorkflowItem.data.checkout.customerUser.isGuest
            $workflowData = new WorkflowData();
            $workflowData->set(
                $workflowItem->getDefinition()->getEntityAttributeName(),
                $workflowItem->getEntity(),
                false
            );
            $workflowItem->setData($workflowData);
            // Restart checkout with broken WorkflowItem to avoid multiple errors in layout expressions after upgrade
            $workflowManager->transitWithoutChecks($workflowItem, TransitionManager::DEFAULT_START_TRANSITION_NAME);
        }
    }

    /**
     * Return workflow names for workflows which operates with checkout
     * @param ObjectManager $manager
     * @return array<string>
     */
    private function getCheckoutWorkflowNames(ObjectManager $manager): array
    {
        $definitions = $manager->getRepository(WorkflowDefinition::class)->findAll();
        $checkoutWorkflowNames = [];

        foreach ($definitions as $definition) {
            if (CheckoutWorkflowHelper::isCheckoutWorkflowDefinition($definition)) {
                $checkoutWorkflowNames[] = $definition->getName();
            }
        }

        return $checkoutWorkflowNames;
    }
}
