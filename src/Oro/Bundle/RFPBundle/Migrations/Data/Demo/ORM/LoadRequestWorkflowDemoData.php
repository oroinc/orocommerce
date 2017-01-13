<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class LoadRequestWorkflowDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    const WORKFLOW_FRONTOFFICE = 'rfq_frontoffice_default';
    const WORKFLOW_BACKOFFICE = 'rfq_backoffice_default';

    /** @var array */
    protected $transitionsWithNotes = ['provide_more_information_transition', 'request_more_information_transition'];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadRequestDemoData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        //Backup Original Token
        $originalToken = $this->container->get('security.token_storage')->getToken();

        $requests = $manager->getRepository(Request::class)->findAll();
        $user = $manager->getRepository(User::class)->findOneBy([]);

        $this->generateTransitionsHistory(self::WORKFLOW_BACKOFFICE, $requests, $user);
        $this->generateTransitionsHistory(self::WORKFLOW_FRONTOFFICE, $requests);

        //Restore Original Token
        $this->container->get('security.token_storage')->setToken($originalToken);
    }

    /**
     * @return object|WorkflowManager
     */
    private function getWorkflowManager()
    {
        static $workflowManager;
        if (!$workflowManager) {
            $workflowManager = $this->container->get('oro_workflow.manager');
        }

        return $workflowManager;
    }

    /**
     * @param string $workflowName
     * @param array|Request[] $requests
     * @param AbstractUser $user
     */
    private function generateTransitionsHistory($workflowName, array $requests, AbstractUser $user = null)
    {
        foreach ($requests as $request) {
            $workflowItem = $this->getWorkflowManager()->getWorkflowItem($request, $workflowName);

            $user = $user ?: $request->getCustomerUser();
            if (null === $user) {
                continue;
            }
            $this->setUserToken($user);
            $this->randomTransitionWalk($workflowItem, rand(0, 4));
        }
    }

    /**
     * @param $workflowItem
     * @param int $count
     */
    private function randomTransitionWalk(WorkflowItem $workflowItem, $count)
    {
        while ($count--) {
            $transitions = $this->getWorkflowManager()->getTransitionsByWorkflowItem($workflowItem)->toArray();
            /** @var Transition $transition */
            $transition = $transitions[array_rand($transitions)];

            if (in_array($transition->getName(), $this->transitionsWithNotes, true)) {
                $workflowItem->getData()->set('notes', $this->getNote());
            }

            if ($this->getWorkflowManager()->isTransitionAvailable($workflowItem, $transition)) {
                $this->getWorkflowManager()->transit(
                    $workflowItem,
                    $transition
                );
            }
        }
    }

    /**
     * @param AbstractUser $user
     */
    private function setUserToken(AbstractUser $user)
    {
        /** @var Organization $organization */
        $organization = $user->getOrganization();

        $token = new UsernamePasswordOrganizationToken($user, false, 'main', $organization, $user->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
    }

    /**
     * @return string
     */
    private function getNote()
    {
        return 'Aliquam quis turpis eget elit sodales scelerisque.' .
        'Mauris sit amet eros. Suspendisse accumsan tortor quis turpis.' .
        'Sed ante. Vivamus tortor. Duis mattis egestas metus.';
    }
}
