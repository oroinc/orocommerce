<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Tests\Functional\ContentWidget\Stub\StubContentWidgetType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class ContentWidgetControllerTest extends WebTestCase
{
    private const WIDGET_NAME = 'test-widget';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadOrganization::class]);
    }

    public function testCreate(): ContentWidget
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_content_widget_create'));

        $form = $crawler->selectButton('Save')->form();
        $form['oro_cms_content_widget[name]'] = self::WIDGET_NAME;
        $form['oro_cms_content_widget[widgetType]'] = StubContentWidgetType::getName();

        $this->client->followRedirects();

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Content widget has been saved', $crawler->html());

        $contentWidget = $this->getContainer()
            ->get('doctrine')
            ->getRepository(ContentWidget::class)
            ->findOneBy(['name' => self::WIDGET_NAME]);

        $this->assertInstanceOf(ContentWidget::class, $contentWidget);
        $this->assertEquals(StubContentWidgetType::getName(), $contentWidget->getWidgetType());

        return $contentWidget;
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(ContentWidget $contentWidget): ContentWidget
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_content_widget_update', ['id' => $contentWidget->getId()])
        );

        $form = $crawler->selectButton('Save')->form();
        $form['oro_cms_content_widget[description]'] = self::WIDGET_NAME . '-updated';

        $this->client->followRedirects();

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Content widget has been saved', $crawler->html());

        $contentWidget = $this->getContainer()
            ->get('doctrine')
            ->getRepository(ContentWidget::class)
            ->findOneBy(['description' => self::WIDGET_NAME . '-updated']);

        $this->assertInstanceOf(ContentWidget::class, $contentWidget);
        $this->assertEquals(StubContentWidgetType::getName(), $contentWidget->getWidgetType());

        return $contentWidget;
    }

    /**
     * @depends testUpdate
     */
    public function testView(ContentWidget $contentWidget): ContentWidget
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_content_widget_view', ['id' => $contentWidget->getId()])
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString($contentWidget->getName(), $crawler->html());
        static::assertStringContainsString($contentWidget->getWidgetType(), $crawler->html());

        return $contentWidget;
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_content_widget_index'));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('cms-content-widget-grid', $crawler->html());
        static::assertStringContainsString(
            'Create Content Widget',
            $crawler->filter('div.title-buttons-container')->html()
        );
    }

    /**
     * @depends testView
     */
    public function testGrid(ContentWidget $contentWidget): void
    {
        $response = $this->client->requestGrid('cms-content-widget-grid');

        $result = $this->getJsonResponseContent($response, 200);

        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);

        $this->assertCount($this->getContentWidgetCount(), $result['data']);

        $data = reset($result['data']);

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($contentWidget->getId(), $data['id']);
        $this->assertArrayHasKey('name', $data);
        $this->assertEquals($contentWidget->getName(), $data['name']);
        $this->assertArrayHasKey('widgetType', $data);
        $this->assertEquals($contentWidget->getWidgetType(), $data['widgetType']);
    }

    private function getContentWidgetCount(): int
    {
        $qb = $this->getContainer()->get('doctrine')
            ->getRepository(ContentWidget::class)
            ->createQueryBuilder('cw');

        $qb
            ->select($qb->expr()->count('cw'))
            ->where($qb->expr()->eq('cw.organization', ':organization'))
            ->setParameter('organization', $this->getReference(LoadOrganization::ORGANIZATION));

        return $qb->getQuery()->getSingleScalarResult();
    }
}
