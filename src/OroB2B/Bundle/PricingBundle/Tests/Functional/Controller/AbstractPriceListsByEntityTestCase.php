<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

use Symfony\Component\DomCrawler\Field\InputFormField;
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

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists',
            ]
        );
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

    public function testAdd()
    {
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();
        foreach ($this->websites as $website) {
            $i = 0;
            $fallbackPath = sprintf('%s[%d][fallback]', $this->formExtensionPath, $website->getId());
            $formValues[$fallbackPath] = (int)($website->getId() % 2 == 0);
            foreach ($this->priceLists as $priceList) {
                $collectionElementPath = sprintf(
                    '%s[%d][priceListCollection][%d]',
                    $this->formExtensionPath,
                    $website->getId(),
                    $i
                );
                $formValues[sprintf('%s[priceList]', $collectionElementPath)] = $priceList->getId();
                $formValues[sprintf('%s[priority]', $collectionElementPath)] = $i + 1;
                $formValues[sprintf('%s[mergeAllowed]', $collectionElementPath)] = $i % 2 == 0;
                $i++;
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
        $priceListsIds = array_map(
            function (PriceList $priceList) {
                return $priceList->getId();
            },
            $this->priceLists
        );
        foreach ($this->websites as $website) {
            $i = 0;
            $collectionPath = sprintf(
                '%s[%d][priceListCollection]',
                $this->formExtensionPath,
                $website->getId()
            );
            /** @var array $priceListCollection */
            $priceListCollection = $form->get($collectionPath);
            $this->assertTrue($this->checkSortingByPriority($priceListCollection));
            $fallbackPath = sprintf('%s[%d][fallback]', $this->formExtensionPath, $website->getId());
            $this->assertEquals($form->get($fallbackPath)->getValue(), (int)($website->getId() % 2 == 0));
            foreach ($this->priceLists as $priceList) {
                $collectionElementPath = sprintf(
                    '%s[%d][priceListCollection][%d]',
                    $this->formExtensionPath,
                    $website->getId(),
                    $i
                );
                $this->assertTrue($this->checkPriceListExistInDatabase($priceList));

                $this->assertTrue(isset($formValues[sprintf('%s[priceList]', $collectionElementPath)]));
                $this->assertTrue(isset($formValues[sprintf('%s[priority]', $collectionElementPath)]));
                if ($i % 2 == 0) {
                    $this->assertTrue(isset($formValues[sprintf('%s[mergeAllowed]', $collectionElementPath)]));
                } else {
                    $this->assertFalse(isset($formValues[sprintf('%s[mergeAllowed]', $collectionElementPath)]));
                }
                $this->assertContains(
                    (int)$formValues[sprintf('%s[priceList]', $collectionElementPath)],
                    $priceListsIds
                );
                $this->assertContains($formValues[sprintf('%s[priority]', $collectionElementPath)], [1, 2, 3]);
                $i++;
            }
        }
    }

    /**
     * @param array $priceListCollection
     *
     * @return bool
     */
    protected function checkSortingByPriority(array $priceListCollection)
    {
        foreach ($priceListCollection as $priceList) {
            /** @var InputFormField $priorityField */
            $priorityField = $priceList['priority'];
            $currentValue = $priorityField->getValue();
            if (!isset($lastValue)) {
                $lastValue = $currentValue;
                continue;
            }
            if ($currentValue > $lastValue) {
                return false;
            }
            $lastValue = $currentValue;
        }

        return true;
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
        foreach ($this->websites as $website) {
            $insideTab = $crawler->filter('div #website' . $website->getId());
            $this->assertContains($website->getName(), $crawler->html());
            $fallbackText = $insideTab->filter('p strong')->text();
            if ((int)($website->getId() % 2 == 0)) {
                $this->assertNotContains('only', $fallbackText);
            } else {
                $this->assertContains('only', $fallbackText);
            };
            $i = 0;
            foreach ($this->priceLists as $priceList) {
                $row = $insideTab->filter(sprintf('.price_list%s', $priceList->getId()));
                $mergeAllowedText = $row->filter('.price_list_merge_allowed')->text();
                if ($i % 2 == 0) {
                    $this->assertEquals('Yes', $mergeAllowedText);
                } else {
                    $this->assertEquals('No', $mergeAllowedText);
                }
                $this->assertEquals($i + 1, $row->filter('.price_list_priority')->text());
                $this->assertContains($priceList->getName(), $row->filter('.price_list_link')->text());
                $i++;
            }
        }
    }

    /**
     * @depends testView
     */
    public function testDelete()
    {
        $this->assertCount(6, $this->getPriceListsByEntity());
        $form = $this->getUpdateForm();
        $this->assertTrue($form->has($this->formExtensionPath));
        //Test remove all price lists by one website
        $form->remove(
            $this->formExtensionPath . sprintf('[%s][priceListCollection]', $this->getReference('Canada')->getId())
        );
        $this->client->submit($form);
        $this->assertCount(3, $this->getPriceListsByEntity());
        $form = $this->getUpdateForm();
        //Test remove one price list
        $form->remove(
            $this->formExtensionPath . sprintf('[%s][priceListCollection][1]', $this->getReference('US')->getId())
        );
        $this->client->submit($form);
        $this->assertCount(2, $this->getPriceListsByEntity());
    }

    public function testValidation()
    {
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();
        /** @var Website $website */
        $website = $this->getReference('Canada');
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $collectionElementPath1 = sprintf(
            '%s[%d][priceListCollection][%d]',
            $this->formExtensionPath,
            $website->getId(),
            0
        );
        $collectionElementPath2 = sprintf(
            '%s[%d][priceListCollection][%d]',
            $this->formExtensionPath,
            $website->getId(),
            1
        );
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
     * @param $message
     */
    protected function checkValidationMessage(
        array $formValues,
        $message
    ) {
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
