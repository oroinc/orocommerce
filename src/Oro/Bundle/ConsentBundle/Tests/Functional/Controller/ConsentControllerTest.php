<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Symfony\Component\DomCrawler\Crawler;

class ConsentControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;
    use OperationAwareTestTrait;

    private const CONSENT_TEST_NAME = 'Test Consent';
    private const CONSENT_UPDATED_TEST_NAME = 'Test Updated Consent';

    private ?int $initialWebCatalogId;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadWebCatalogData::class,
            LoadContentNodesData::class,
        ]);

        $configManager = self::getConfigManager();
        $this->initialWebCatalogId = $configManager->get('oro_web_catalog.web_catalog');
        $configManager->set('oro_consent.consent_feature_enabled', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_web_catalog.web_catalog', $this->initialWebCatalogId);
        $configManager->set('oro_consent.consent_feature_enabled', false);
        $configManager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_consent_index'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('consents-grid', $crawler->html());
    }

    public function testCreate(): int
    {
        $webCatalogId = $this->getReference(LoadWebCatalogData::CATALOG_1)->getId();

        $configManager = self::getConfigManager();
        $configManager->set('oro_web_catalog.web_catalog', $webCatalogId);
        $configManager->flush();

        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2);
        $crawler = $this->client->request('GET', $this->getUrl('oro_consent_create'));
        $result  = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_consent']['names']['values']['default'] = self::CONSENT_TEST_NAME;
        $formValues['oro_consent']['mandatory'] = 1;
        $formValues['oro_consent']['content_node'] = $contentNode->getId();
        unset($formValues['oro_consent']['declinedNotification']);
        $formValues['input_action'] = '{"route":"oro_consent_update","params":{"id":"$id"}}';

        self::assertEquals($webCatalogId, $form['oro_consent[webcatalog]']->getValue());
        $this->client->followRedirects();

        // Submit form
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $result = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($result, 200);
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
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_consent']['names']['values']['default'] = self::CONSENT_UPDATED_TEST_NAME;
        $formValues['oro_consent']['mandatory'] = 0;
        $formValues['input_action'] = '{"route":"oro_consent_update","params":{"id":"$id"}}';

        // Submit form
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $this->client->followRedirects();

        self::assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertConsentSaved($crawler, self::CONSENT_UPDATED_TEST_NAME, 'Consent has been saved');

        $consent = $this->getConsentDataByName(self::CONSENT_UPDATED_TEST_NAME);
        self::assertEquals($id, $consent->getId());

        return $consent->getId();
    }

    /**
     * @depends testCreate
     */
    public function testDelete(int $id): void
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
        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertEquals(
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

        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    private function assertConsentSaved(Crawler $crawler, string $consentName, string $assertText): void
    {
        $html = $crawler->html();
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2);
        self::assertStringContainsString($assertText, $html);
        self::assertStringContainsString($consentName, $html);
        self::assertStringContainsString($contentNode->getDefaultTitle()->getString(), $html);
        self::assertEquals($consentName, $crawler->filter('h1.page-title__entity-title')->html());
    }

    private function getConsentDataByName(string $name): Consent
    {
        /** @var EntityRepository $repository */
        $repository = self::getContainer()->get('doctrine')
            ->getRepository(Consent::class);
        $qb = $repository->createQueryBuilder('consent');
        $joinExpr = $qb->expr()->isNull('name.localization');
        /** @var Consent $consent */
        $consent = $qb
            ->select('partial consent.{id}')
            ->innerJoin('consent.names', 'name', Join::WITH, $joinExpr)
            ->andWhere('name.string = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        self::assertNotEmpty($consent);
        self::assertFalse($consent->getDeclinedNotification());

        return $consent;
    }
}
