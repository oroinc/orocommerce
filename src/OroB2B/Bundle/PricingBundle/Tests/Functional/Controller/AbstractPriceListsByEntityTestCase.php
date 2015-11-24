<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractPriceListsByEntityTestCase extends WebTestCase
{
    /** @var string */
    protected $formExtensionPath;

    /** @var Website[] $websites */
    protected $websites;

    /** @var PriceList[] $priceLists */
    protected $priceLists;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
        $this->formExtensionPath = sprintf('%s[priceListsByWebsites]', $this->getMainFormName());
        $this->websites = [
            $this->getReference('Canada'),
            $this->getReference('US'),
        ];

        $this->priceLists = [
            $this->getReference('price_list_1'),
            $this->getReference('price_list_2'),
            $this->getReference('price_list_3'),
        ];
    }

    public function testDelete()
    {
        $this->assertCount(1, $this->getPriceListsByEntity());
        $form = $this->getUpdateForm();
        $this->assertTrue($form->has($this->formExtensionPath));
        $form->remove($this->formExtensionPath);
        $this->client->submit($form);
        $this->assertCount(0, $this->getPriceListsByEntity());
        $form = $this->getUpdateForm();
        $this->assertFalse($form->has($this->formExtensionPath));
    }

    /**
     * @depends testDelete
     */
    public function testAdd()
    {
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();
        foreach ($this->websites as $website) {
            $i = 0;
            foreach ($this->priceLists as $priceList) {
                $collectionElementPath = sprintf('%s[%d][%d]', $this->formExtensionPath, $website->getId(), $i);
                $formValues[sprintf('%s[priceList]', $collectionElementPath)] = $priceList->getId();
                $formValues[sprintf('%s[priority]', $collectionElementPath)] = ++$i;
            }
        }
        $params = $this->explodeArrayPaths($formValues);
        $this->client->request(
            'POST',
            $this->getUpdateUrl(),
            $params
        );
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();
        foreach ($this->websites as $website) {
            $i = 0;
            foreach ($this->priceLists as $priceList) {
                $this->assertTrue($this->checkPriceListExistInDatabase($priceList));
                $collectionElementPath = sprintf('%s[%d][%d]', $this->formExtensionPath, $website->getId(), $i);
                $this->assertTrue(isset($formValues[sprintf('%s[priceList]', $collectionElementPath)]));
                $this->assertTrue(isset($formValues[sprintf('%s[priority]', $collectionElementPath)]));
                $this->assertEquals($formValues[sprintf('%s[priceList]', $collectionElementPath)], $priceList->getId());
                $this->assertEquals($formValues[sprintf('%s[priority]', $collectionElementPath)], ++$i);
            }
        }
    }

    /**
     * @depends testAdd
     */
    public function testView()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getViewUrl()
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        foreach ($this->websites as $website) {
            $i = 0;
            $this->assertContains($website->getName(), $html);
            foreach ($this->priceLists as $priceList) {
                $this->assertContains($priceList->getName(), $html);
                $this->assertContains((string)++$i, $html);
            }
        }
    }

    public function testValidation()
    {
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();
        /** @var Website $website */
        $website = $this->getReference('Canada');
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $collectionElementPath1 = sprintf('%s[%d][%d]', $this->formExtensionPath, $website->getId(), 0);
        $collectionElementPath2 = sprintf('%s[%d][%d]', $this->formExtensionPath, $website->getId(), 1);
        $formValues[sprintf('%s[priceList]', $collectionElementPath1)] = $priceList->getId();
        $formValues[sprintf('%s[priority]', $collectionElementPath1)] = '';
        $this->checkValidationMessage($formValues, 'This value should not be blank');
        $formValues[sprintf('%s[priority]', $collectionElementPath1)] = 'not_integer';
        $this->checkValidationMessage($formValues, 'This value should be integer number');
        $formValues[sprintf('%s[priority]', $collectionElementPath1)] = 1;
        $formValues[sprintf('%s[priceList]', $collectionElementPath2)] = $priceList->getId();
        $formValues[sprintf('%s[priority]', $collectionElementPath2)] = 2;
        $this->checkValidationMessage($formValues, 'Duplicate price list');
    }

    /**
     * @param array $formValues
     * @param $message
     */
    protected function checkValidationMessage(array $formValues, $message)
    {
        $params = $this->explodeArrayPaths($formValues);
        $crawler = $this->client->request(
            'POST',
            $this->getUpdateUrl(),
            $params
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($message, $crawler->html());
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

    /**
     * @return BasePriceListRelation[]
     */
    abstract public function getPriceListsByEntity();

    /**
     * @return string
     */
    abstract public function getUpdateUrl();

    /**
     * @return string
     */
    abstract public function getViewUrl();

    /**
     * @return string
     */
    abstract public function getMainFormName();

    /**
     * @return Form
     */
    protected function getUpdateForm()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUpdateUrl()
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $crawler->selectButton('Save and Close')->form();
    }

    /**
     * @param PriceList $priceList
     * @return bool
     */
    protected function checkPriceListExistInDatabase(PriceList $priceList)
    {
        $priceListsFromDatabase = $this->getPriceListsByEntity();
        foreach ($priceListsFromDatabase as $priceListToEntity) {
            if ($priceListToEntity->getPriceList()->getId() == $priceList->getId()) {
                return true;
            }
        }

        return false;
    }
}
