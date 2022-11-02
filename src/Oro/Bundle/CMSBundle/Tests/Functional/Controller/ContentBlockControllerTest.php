<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group segfault
 */
class ContentBlockControllerTest extends WebTestCase
{
    private const CONTENT_BLOCK_ALIAS = 'content-block-alias';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    private function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_content_block_index'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('cms-content-block-grid', $crawler->html());
        static::assertStringContainsString(
            'Create Content Block',
            $crawler->filter('div.title-buttons-container')->html()
        );
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_content_block_create'));
        $form    = $crawler->selectButton('Save')->form();

        $form['oro_cms_content_block[alias]']                   = self::CONTENT_BLOCK_ALIAS;
        $form['oro_cms_content_block[titles][values][default]'] = 'Default title';
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Content block has been saved', $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testView()
    {
        /** @var ContentBlock $contentBlock */
        $contentBlock = $this->getDoctrine()->getRepository(ContentBlock::class)->findOneBy(
            ['alias' => self::CONTENT_BLOCK_ALIAS]
        );
        $crawler      = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_content_block_view', ['id' => $contentBlock->getId()])
        );
        $result       = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString($contentBlock->getAlias(), $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        /** @var ContentBlock $contentBlock */
        $contentBlock = $this->getDoctrine()->getRepository(ContentBlock::class)->findOneBy(
            ['alias' => self::CONTENT_BLOCK_ALIAS]
        );
        $crawler      = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_content_block_update', ['id' => $contentBlock->getId()])
        );

        $form = $crawler->selectButton('Save')->form();
        $this->assertEquals(self::CONTENT_BLOCK_ALIAS, $form['oro_cms_content_block[alias]']->getValue());
        $this->assertEquals('Default title', $form['oro_cms_content_block[titles][values][default]']->getValue());

        $form['oro_cms_content_block[alias]']                   = 'first-content-block-updated';
        $form['oro_cms_content_block[titles][values][default]'] = 'Default title updated';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Content block has been saved', $crawler->html());
    }
}
