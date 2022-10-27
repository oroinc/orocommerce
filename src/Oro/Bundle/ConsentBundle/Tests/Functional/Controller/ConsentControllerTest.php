<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Tests\Functional\Entity\ConsentFeatureTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogConfigChangeListener;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Symfony\Component\DomCrawler\Crawler;

class ConsentControllerTest extends WebTestCase
{
    use ConsentFeatureTrait;
    use OperationAwareTestTrait;

    private const CONSENT_TEST_NAME = 'Test Consent';
    private const CONSENT_UPDATED_TEST_NAME = 'Test Updated Consent';

    private ConfigManager $configManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadWebCatalogData::class,
            LoadContentNodesData::class,
        ]);

        $this->configManager = self::getConfigManager();
        $this->enableConsentFeature();
    }

    protected function tearDown(): void
    {
        $this->disableConsentFeature();
        $this->unsetDefaultWebCatalog();
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_consent_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('consents-grid', $crawler->html());
    }

    public function testCreate(): int
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

        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_consent']['names']['values']['default'] = self::CONSENT_TEST_NAME;
        $formValues['oro_consent']['mandatory'] = 1;
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
        $this->assertConsentSaved($crawler, self::CONSENT_TEST_NAME, 'Consent has been created');

        return $this->getConsentDataByName(self::CONSENT_TEST_NAME)->getId();
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_consent_update', ['id' => $id]));

        $html = $crawler->html();
        self::assertStringContainsString(self::CONSENT_TEST_NAME, $html);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_consent']['names']['values']['default'] = self::CONSENT_UPDATED_TEST_NAME;
        $formValues['oro_consent']['mandatory'] = 0;
        $formValues['input_action'] = '{"route":"oro_consent_update","params":{"id":"$id"}}';

        // Submit form
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $this->client->followRedirects(true);

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertConsentSaved($crawler, self::CONSENT_UPDATED_TEST_NAME, 'Consent has been saved');

        $consent = $this->getConsentDataByName(self::CONSENT_UPDATED_TEST_NAME);
        $this->assertEquals($id, $consent->getId());

        return $consent->getId();
    }

    /**
     * @depends testCreate
     */
    public function testDelete(int $id)
    {
        $entityClass = Consent::class;
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
            self::jsonToArray($this->client->getResponse()->getContent())
        );

        $this->client->request('GET', $this->getUrl('oro_consent_view', ['id' => $id]));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    private function assertConsentSaved(Crawler $crawler, string $consentName, string $assertText): void
    {
        $html = $crawler->html();
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2);
        self::assertStringContainsString($assertText, $html);
        self::assertStringContainsString($consentName, $html);
        self::assertStringContainsString($contentNode->getDefaultTitle()->getString(), $html);
        $this->assertEquals($consentName, $crawler->filter('h1.page-title__entity-title')->html());
    }

    private function getConsentDataByName(string $name): Consent
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getRepository(Consent::class);
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
}
