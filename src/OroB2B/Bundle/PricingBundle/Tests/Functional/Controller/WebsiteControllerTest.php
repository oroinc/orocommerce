<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Extension\WebsiteFormExtension;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteType;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class WebsiteControllerTest extends WebTestCase
{
    /** @var  Website */
    protected $website;

    /** @var string */
    protected $formExtensionPath;

    /** @var PriceList[] $priceLists */
    protected $priceLists;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations']);
        $this->website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $this->priceLists = [
            $this->getReference('price_list_1'),
            $this->getReference('price_list_2'),
            $this->getReference('price_list_3'),
        ];
        $this->formExtensionPath = sprintf(
            '%s[%s]',
            WebsiteType::NAME,
            WebsiteFormExtension::PRICE_LISTS_TO_WEBSITE_FIELD
        );
    }

    public function testDelete()
    {
        $this->assertCount(3, $this->getPriceListsByWebsite());
        $form = $this->getUpdateForm();
        $this->assertTrue(isset($form[$this->formExtensionPath]));
        $form->remove($this->formExtensionPath);
        $this->client->submit($form);
        $this->assertCount(0, $this->getPriceListsByWebsite());
    }

    /**
     * @depends testDelete
     */
    public function testAdd()
    {
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();
        $i = 0;
        foreach ($this->priceLists as $priceList) {
            $collectionElementPath = sprintf('%s[%d]', $this->formExtensionPath, $i);
            $formValues[sprintf('%s[priceList]', $collectionElementPath)] = $priceList->getId();
            $formValues[sprintf('%s[priority]', $collectionElementPath)] = ++$i;
        }
        $params = $this->explodeArrayPaths($formValues);
        $this->client->request(
            'POST',
            $this->getUrl('orob2b_website_update', ['id' => $this->website->getId()]),
            $params
        );
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();
        $elementsCount = count($this->priceLists);
        $i = $elementsCount - 1;
        foreach ($this->priceLists as $priceList) {
            $collectionElementPath = sprintf('%s[%d]', $this->formExtensionPath, $i);
            $this->assertTrue(isset($formValues[sprintf('%s[priceList]', $collectionElementPath)]));
            $this->assertTrue(isset($formValues[sprintf('%s[priority]', $collectionElementPath)]));
            $this->assertEquals($formValues[sprintf('%s[priceList]', $collectionElementPath)], $priceList->getId());
            $this->assertEquals($formValues[sprintf('%s[priority]', $collectionElementPath)], $elementsCount - $i);
            $i--;
        }
    }

    /**
     * @depends testAdd
     */
    public function testView()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_website_view', ['id' => $this->website->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        $i = 0;
        foreach ($this->priceLists as $priceList) {
            $this->assertContains($priceList->getName(), $html);
            $this->assertContains((string)++$i, $html);
        }
    }

    public function testValidation()
    {
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $collectionElementPath1 = sprintf('%s[%d]', $this->formExtensionPath, 0);
        $collectionElementPath2 = sprintf('%s[%d]', $this->formExtensionPath, 1);
        $formValues[sprintf('%s[priceList]', $collectionElementPath1)] = $priceList->getId();
        $formValues[sprintf('%s[priority]', $collectionElementPath1)] = '';
        $this->checkValidationMessage($formValues, 'This value should not be blank');
        $formValues[sprintf('%s[priority]', $collectionElementPath1)] = 'not_integer';
        $this->checkValidationMessage($formValues, 'This value should be integer number');
        $formValues[sprintf('%s[priority]', $collectionElementPath1)] = 1;
        $formValues[sprintf('%s[priceList]', $collectionElementPath2)] = $priceList->getId();
        $formValues[sprintf('%s[priority]', $collectionElementPath2)] = 2;

        $this->checkValidationMessage($formValues, 'Price list is duplicated.');
    }

    /**
     * @param array $formValues
     * @param string $message
     */
    protected function checkValidationMessage(array $formValues, $message)
    {
        $params = $this->explodeArrayPaths($formValues);
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('orob2b_website_update', ['id' => $this->website->getId()]),
            $params
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($message, $crawler->html());
    }

    /**
     * @return Form
     */
    protected function getUpdateForm()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_website_update', ['id' => $this->website->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $crawler->selectButton('Save and Close')->form();
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceListsByWebsite()
    {
        return $this->client
            ->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroB2BPricingBundle:PriceListToWebsite')
            ->findBy(['website' => $this->website]);
    }

    /**
     * @param array $values
     * @return array
     */
    protected function explodeArrayPaths($values)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $parameters = [];
        foreach ($values as $key => $val) {
            if (!$pos = strpos($key, '[')) {
                continue;
            }
            $key = '[' . substr($key, 0, $pos) . ']' . substr($key, $pos);
            $accessor->setValue($parameters, $key, $val);
        }

        return $parameters;
    }
}
