<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\EventListener;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
use OroB2B\Bundle\PricingBundle\Form\Extension\WebsiteFormExtension;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class WebsiteListenerTest extends WebTestCase
{
    const WEBSITE_NAME = 'USA';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->doctrineHelper = $this->client->getContainer()->get('oro_entity.doctrine_helper');
        $this->clearTriggers();
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_website_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_website_type' => [
                '_token' => $form['orob2b_website_type[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'name' => self::WEBSITE_NAME,
                'fallback' => true,
                WebsiteFormExtension::PRICE_LISTS_TO_WEBSITE_FIELD => [
                    1 => [
                         PriceListSelectWithPriorityType::PRIORITY_FIELD => '100',
                         PriceListSelectWithPriorityType::PRICE_LIST_FIELD => '1',
                         PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD => '1'
                    ]
                ]
            ],
        ];

        $this->client->followRedirects(true);

        // Submit form
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $this->doctrineHelper = $this->client->getContainer()->get('oro_entity.doctrine_helper');
        /** @var Website $website */
        $website = $this->doctrineHelper
            ->getEntityRepository('OroB2B\Bundle\WebsiteBundle\Entity\Website')
            ->findOneBy(['name' => self::WEBSITE_NAME]);

        $priceListRelations = $this->getPriceListRelations($website);
        $triggers = $this->getTriggers();

        $this->assertCount(1, $priceListRelations);
        $this->assertCount(1, $triggers);
        $this->assertSame($website, $triggers[0]->getWebsite());

        return $website->getId();
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testUpdateRelations($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_website_update', ['id' => $id]));
        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();

        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_website_type' => [
                '_token' => $form['orob2b_website_type[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'name' => self::WEBSITE_NAME,
                'fallback' => true,
                WebsiteFormExtension::PRICE_LISTS_TO_WEBSITE_FIELD => [
                    1 => [
                        PriceListSelectWithPriorityType::PRIORITY_FIELD => '100',
                        PriceListSelectWithPriorityType::PRICE_LIST_FIELD => '1',
                        PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD => '1'
                    ]
                ]
            ],
        ];

        $this->client->followRedirects(true);

        // Submit form
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);

        /** @var Website $website */
        $website = $this->doctrineHelper
            ->getEntityRepository('OroB2B\Bundle\WebsiteBundle\Entity\Website')
            ->find($id);

        $priceListRelations = $this->getPriceListRelations($website);
        $triggers = $this->getTriggers();

        $this->assertCount(1, $priceListRelations);
        $this->assertCount(1, $triggers);
        $this->assertSame($website, $triggers[0]->getWebsite());
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser();
    }

    protected function clearTriggers()
    {
        $this->doctrineHelper
            ->getEntityRepository('OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger')
            ->createQueryBuilder('ct')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @return array|PriceListChangeTrigger[]
     */
    protected function getTriggers()
    {
        return $this->doctrineHelper
            ->getEntityRepository('OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger')
            ->findAll();
    }

    /**
     * @param $website
     * @return array
     */
    protected function getPriceListRelations($website)
    {
        return $this->doctrineHelper
            ->getEntityRepository('OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite')
            ->findBy(['website' => $website]);
    }
}
