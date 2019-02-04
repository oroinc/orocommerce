<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Tests\Functional\Entity\ConsentFeatureTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogConfigChangeListener;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class ConsentControllerTest extends WebTestCase
{
    use ConsentFeatureTrait;

    const CONSENT_TEST_NAME = "Test Consent";
    const CONSENT_UPDATED_TEST_NAME = "Test Updated Consent";

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadWebCatalogData::class,
            LoadContentNodesData::class,
        ]);

        $this->configManager = $this->getContainer()->get('oro_config.global');
        $this->enableConsentFeature();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->disableConsentFeature();
        $this->unsetDefaultWebCatalog();
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_consent_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('consents-grid', $crawler->html());
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        $this->configManager->set(
            WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME,
            $webCatalog
        );
        $this->configManager->flush();
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2);
        $crawler = $this->client->request('GET', $this->getUrl('oro_consent_create'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_consent']['names']['values']['default'] = self::CONSENT_TEST_NAME;
        $formValues['oro_consent']['mandatory'] = (int) true;
        $formValues['oro_consent']['content_node'] = $contentNode->getId();
        unset($formValues['oro_consent']['declinedNotification']);
        $formValues['input_action'] = '{"route":"oro_consent_update","params":{"id":"$id"}}';

        $this->assertEquals(
            $webCatalog->getId(),
            $form['oro_consent[webcatalog]']->getValue()
        );
        $this->client->followRedirects(true);

        // Submit form
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertConsentSaved(
            $crawler,
            self::CONSENT_TEST_NAME,
            'Consent has been created'
        );

        $consent = $this->getConsentDataByName(self::CONSENT_TEST_NAME);

        return $consent->getId();
    }

    /**
     * @depends testCreate
     * @param int $id
     *
     * @return int
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_consent_update', ['id' => $id]));

        $html = $crawler->html();
        $this->assertContains(self::CONSENT_TEST_NAME, $html);

        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_consent']['names']['values']['default'] = self::CONSENT_UPDATED_TEST_NAME;
        $formValues['oro_consent']['mandatory'] = (int) false;
        $formValues['input_action'] = '{"route":"oro_consent_update","params":{"id":"$id"}}';

        // Submit form
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $this->client->followRedirects(true);

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertConsentSaved(
            $crawler,
            self::CONSENT_UPDATED_TEST_NAME,
            'Consent has been saved'
        );

        $consent = $this->getConsentDataByName(self::CONSENT_UPDATED_TEST_NAME);
        $this->assertEquals($id, $consent->getId());

        return $consent->getId();
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testDelete($id)
    {
        $entityClass = self::getContainer()->getParameter('oro_consent.entity.consent.class');
        $operationName = 'DELETE';
        $params = $this->getOperationExecuteParams($operationName, $id, $entityClass);
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'entityId' => $id,
                    'entityClass' => $entityClass,
                ]
            ),
            $params,
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('oro_consent_index'),
                'pageReload' => true
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

        $this->client->request('GET', $this->getUrl('oro_consent_view', ['id' => $id]));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * @param Crawler $crawler
     * @param string $consentName
     * @param string $assertText
     */
    protected function assertConsentSaved(Crawler $crawler, $consentName, $assertText = 'Consent has been saved')
    {
        $html = $crawler->html();
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2);
        $this->assertContains($assertText, $html);
        $this->assertContains($consentName, $html);
        $this->assertContains($contentNode->getDefaultTitle()->getString(), $html);
        $this->assertEquals($consentName, $crawler->filter('h1.page-title__entity-title')->html());
    }

    /**
     * @param string $name
     *
     * @return Consent
     */
    protected function getConsentDataByName($name)
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroConsentBundle:Consent')
            ->getRepository('OroConsentBundle:Consent');
        $qb = $repository->createQueryBuilder('consent');
        $joinExpr = $qb->expr()->isNull('name.localization');
        $consent = $qb
            ->select('partial consent.{id}')
            ->innerJoin('consent.names', 'name', Join::WITH, $joinExpr)
            ->andWhere('name.string = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNotEmpty($consent);
        $this->assertEquals(false, $consent->getDeclinedNotification());

        return $consent;
    }

    /**
     * @param $operationName
     * @param $entityId
     * @param $entityClass
     *
     * @return array
     */
    protected function getOperationExecuteParams($operationName, $entityId, $entityClass)
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
}
