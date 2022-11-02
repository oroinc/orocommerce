<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Workflow;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractRfqFrontofficeDefaultWorkflowTest extends FrontendWebTestCase
{
    /** @var Request */
    protected $request;

    /** @var WorkflowManager */
    protected $manager;

    /** @var WorkflowManager */
    protected $systemManager;

    /** @var Workflow */
    protected $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient([], $this->getBasicAuthHeader());
        $this->loadFixtures([LoadRequestData::class]);

        $this->updateCustomerUserSecurityToken($this->getCustomerUserEmail());

        $this->manager = $this->getContainer()->get('oro_workflow.manager');
        $this->systemManager = $this->getContainer()->get('oro_workflow.manager.system');

        if (!$this->manager->isActiveWorkflow($this->getWorkflowName())) {
            $this->markTestSkipped(sprintf('The Workflow "%s" is inactive', $this->getWorkflowName()));
        }

        $this->workflow = $this->manager->getWorkflow($this->getWorkflowName());
        $this->request = $this->getReference(LoadRequestData::REQUEST2);

        $this->ensureSessionIsAvailable();
    }

    /**
     * @return string
     */
    abstract protected function getCustomerUserEmail();

    /**
     * @return string
     */
    abstract protected function getWorkflowName();

    /**
     * @return array
     */
    abstract protected function getBasicAuthHeader();

    /**
     * @return array
     */
    abstract protected function getWsseAuthHeader();

    /**
     * @param object $entity
     * @param string $workflowName
     * @param string $transitionName
     * @param array $transitionData
     */
    protected function transitSystem($entity, $workflowName, $transitionName, $transitionData = [])
    {
        /* @var WorkflowItem $wi */
        $wi = $this->systemManager->getWorkflowItem($entity, $workflowName);
        $this->assertNotNull($wi);
        $wi->getData()->add($transitionData);
        $this->systemManager->transit($wi, $transitionName);
    }

    /**
     * @param Crawler $link
     * @param array $formValues
     * @param string $submitButton
     * @return string
     */
    protected function transitWeb(Crawler $link, $formValues = [], $submitButton = 'Submit')
    {
        $dialogUrl = $link->attr('data-dialog-url');
        $transitionUrl = $link->attr('data-transition-url');
        if ($dialogUrl) {
            $crawler = $this->client->request('GET', $dialogUrl, [], [], $this->getWsseAuthHeader());
            $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);
            $button = $crawler->selectButton($submitButton);
            $form = $button->form($formValues);
            $this->client->followRedirects(true);
            $this->client->submit($form);
        } else {
            $this->ajaxRequest('POST', $transitionUrl, [], [], $this->getWsseAuthHeader());
            $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        }

        return $this->client->getResponse()->getContent();
    }

    /**
     * @param Request $request
     *
     * @return Crawler
     */
    protected function openEntityViewPage(Request $request)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl($this->getEntityViewRoute(), ['id' => $request->getId()]),
            [],
            [],
            $this->getBasicAuthHeader()
        );

        $this->assertNotEmpty($crawler->html());
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        return $crawler;
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     *
     * @return string
     */
    protected function getTransitionLinkId($workflowName, $transitionName)
    {
        return sprintf('[id^="transition-%s-%s"]', $workflowName, $transitionName);
    }

    /**
     * @param Crawler $crawler
     * @param $transitionLinkId
     *
     * @return Crawler
     */
    protected function getTransitionLink(Crawler $crawler, $transitionLinkId)
    {
        return $crawler->filter(sprintf('a%s', $transitionLinkId));
    }

    /**
     * @return string
     */
    protected function getEntityViewRoute()
    {
        return 'oro_rfp_frontend_request_view';
    }

    /**
     * @param object $entity
     *
     * @return object|null
     */
    protected function refreshEntity($entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        $em = $this->getContainer()->get('doctrine')->getManagerForClass($entityClass);
        $entityId = $em->getClassMetadata($entityClass)->getIdentifierValues($entity);

        return $this->getEntity($entityClass, $entityId);
    }

    /**
     * @param string $entityClass
     * @param int|string|array $entityId
     *
     * @return null|object
     */
    protected function getEntity($entityClass, $entityId)
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($entityClass)
            ->find($entityClass, $entityId);
    }
}
