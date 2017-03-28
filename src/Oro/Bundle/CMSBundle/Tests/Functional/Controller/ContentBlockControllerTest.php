<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ContentBlockControllerTest extends WebTestCase
{
    const CONTENT_BLOCK_ALIAS = 'content-block-alias';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->registry = $this->getContainer()->get('doctrine');
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_content_block_index'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('cms-content-block-grid', $crawler->html());
        $this->assertContains('Create Content Block', $crawler->filter('div.title-buttons-container')->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_content_block_create'));
        $form    = $crawler->selectButton('Save and Close')->form();

        $form['content_block[alias]']                   = self::CONTENT_BLOCK_ALIAS;
        $form['content_block[titles][values][default]'] = 'Default title';
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Content block has been saved', $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testView()
    {
        /** @var ContentBlock $contentBlock */
        $contentBlock = $this->registry->getRepository(ContentBlock::class)->findOneBy(
            ['alias' => self::CONTENT_BLOCK_ALIAS]
        );
        $crawler      = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_content_block_view', ['id' => $contentBlock->getId()])
        );
        $result       = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($contentBlock->getAlias(), $crawler->html());
        $this->assertContains($contentBlock->getDefaultTitle()->getString(), $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        /** @var ContentBlock $contentBlock */
        $contentBlock = $this->registry->getRepository(ContentBlock::class)->findOneBy(
            ['alias' => self::CONTENT_BLOCK_ALIAS]
        );
        $crawler      = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_content_block_update', ['id' => $contentBlock->getId()])
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $this->assertEquals(self::CONTENT_BLOCK_ALIAS, $form['content_block[alias]']->getValue());
        $this->assertEquals('Default title', $form['content_block[titles][values][default]']->getValue());

        $form['content_block[alias]']                   = 'first-content-block-updated';
        $form['content_block[titles][values][default]'] = 'Default title updated';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Content block has been saved', $crawler->html());
    }
}
