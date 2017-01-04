<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;

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
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;

class LoadRequestWorkflowDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    const WORKFLOW_FRONTOFFICE = 'rfq_frontoffice_default';
    const WORKFLOW_BACKOFFICE = 'rfq_backoffice_default';

    /** @var  Request[] */
    protected $requests;

    /** @var  EntityManager */
    protected $em;

    /** @var WorkflowManager */
    protected $workflowManager;

    protected $transitionsWithNotes = ['provide_more_information_transition', 'request_more_information_transition'];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\RFPBundle\Migrations\Data\Demo\ORM\LoadRequestDemoData'
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    protected function initSupportingEntities(ObjectManager $manager)
    {
        $repo = $this->container->get('oro_entity.doctrine_helper')->getEntityRepositoryForClass(Request::class);
        $this->requests = $repo->findAll();

        $this->em = $manager;
        $this->workflowManager = $this->container->get('oro_workflow.manager');
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->initSupportingEntities($manager);

        $this->startWorkflows(self::WORKFLOW_BACKOFFICE, $this->requests);
        $this->startWorkflows(self::WORKFLOW_FRONTOFFICE, $this->requests);

        $user = $manager->getRepository('OroUserBundle:User')->findOneBy([]);
        $this->generateTransitionsHistory(self::WORKFLOW_FRONTOFFICE, $this->requests);
        $this->generateTransitionsHistory(self::WORKFLOW_BACKOFFICE, $this->requests, $user);
    }

    /**
     * @param string $workflowName
     * @param array $requests
     */
    protected function startWorkflows($workflowName, array $requests)
    {
        $this->workflowManager->massStartWorkflow(
            array_map(
                function ($request) use ($workflowName) {
                    return new WorkflowStartArguments($workflowName, $request);
                },
                $requests
            )
        );
    }

    /**
     * @param string $workflowName
     * @param Request[] $requests
     * @param AbstractUser $user
     */
    protected function generateTransitionsHistory($workflowName, array $requests, AbstractUser $user = null)
    {
        foreach ($requests as $request) {
            $workflowItem = $this->workflowManager->getWorkflowItem($request, $workflowName);

            if (null === $user) {
                $user = $request->getAccountUser();
            }

            $this->setUserToken($user);
            $this->randomTransitionWalk($workflowItem, rand(0, 4));
        }
    }

    /**
     * @param $workflowItem
     * @param int $count
     */
    protected function randomTransitionWalk(WorkflowItem $workflowItem, $count)
    {
        while ($count--) {
            $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem)->toArray();
            /** @var Transition $transition */
            $transition = $transitions[array_rand($transitions)];

            if (in_array($transition->getName(), $this->transitionsWithNotes)) {
                $workflowItem->getData()->set('notes', $this->getNote());
            }

            if ($this->workflowManager->isTransitionAvailable($workflowItem, $transition)) {
                $this->workflowManager->transit(
                    $workflowItem,
                    $transition
                );
            }
        }
    }

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
