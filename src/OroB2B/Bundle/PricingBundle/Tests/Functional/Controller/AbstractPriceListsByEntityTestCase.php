<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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
     * @param null|integer $id
     * @return string
     */
    abstract public function getUpdateUrl($id = null);

    /**
     * @return string
     */
    abstract public function getCreateUrl();

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
    }

    /**
     * @dataProvider priceListRelationDataProvider
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testAddOnCreate(array $submittedData, array  $expectedData)
    {
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();

        $params = $this->setSubmittedValues($submittedData, $formValues);
        $params['input_action'] = 'save_and_stay';

        $this->client->followRedirects();
        $crawler = $this->client->request(
            'POST',
            $this->getCreateUrl(),
            $params
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $this->checkExpectedData($expectedData, $form);
    }

    /**
     * @dataProvider priceListRelationDataProvider
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testAddOnUpdate(array $submittedData, array  $expectedData)
    {
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();

        $params = $this->setSubmittedValues($submittedData, $formValues);
        $params['input_action'] = 'save_and_stay';

        $crawler = $this->client->request(
            'POST',
            $this->getUpdateUrl(),
            $params
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $this->checkExpectedData($expectedData, $form);
    }

    /**
     * @return array
     */
    public function priceListRelationDataProvider()
    {
        return [
            [
                'submittedData' => [
                    1 => [
                        'fallback' => 0,
                        'priceLists' => [
                            ['priceList' => 'price_list_1', 'priority' => 3, 'mergeAllowed' => false],
                            ['priceList' => 'price_list_2', 'priority' => 23, 'mergeAllowed' => true],
                            ['priceList' => 'price_list_3', 'priority' => 22, 'mergeAllowed' => true],
                        ],
                    ],
                ],

                'expectedData' => [
                    1 => [
                        'fallback' => 0,
                        'priceLists' => [
                            ['priceList' => 'price_list_2', 'priority' => 23, 'mergeAllowed' => true],
                            ['priceList' => 'price_list_3', 'priority' => 22, 'mergeAllowed' => true],
                            ['priceList' => 'price_list_1', 'priority' => 3, 'mergeAllowed' => false],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param integer $actualIndex
     * @param array $array
     * @return integer
     */
    protected function getOrderIndex($actualIndex, array $array)
    {
        $keys = array_keys($array);

        return array_search($actualIndex, $keys);
    }

    /**
     * @param string|integer $identifier
     * @return null|Website
     */
    protected function getWebsite($identifier)
    {
        if (is_int($identifier)) {
            return $this->getContainer()
                ->get('doctrine')
                ->getRepository('OroB2BWebsiteBundle:Website')
                ->find($identifier);
        }

        return $this->getReference($identifier);
    }

    /**
     * @dataProvider priceListRelationDataProvider
     * @depends      testAddOnUpdate
     */
    public function testView()
    {
        $expectedData = func_get_args()[1];
        $crawler = $this->client->request(
            'GET',
            $this->getViewUrl()
        );
        
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        
        foreach ($expectedData as $websiteReference => $priceListsWithFallback) {
            $priceListSection = $crawler->filter('div #priceLists');
            $fallbackText = $priceListSection->filter('p strong')->text();

            $this->assertNotContains('only', $fallbackText);

            foreach ($priceListsWithFallback['priceLists'] as $priceListRelation) {
                /** @var PriceList $priceList */
                $priceList = $this->getReference($priceListRelation['priceList']);
                $row = $priceListSection->filter(sprintf('.price_list%s', $priceList->getId()));
                $mergeAllowedText = $row->filter('.price_list_merge_allowed')->text();
                if ($priceListRelation['mergeAllowed']) {
                    $this->assertContains('Yes', $mergeAllowedText);
                } else {
                    $this->assertContains('No', $mergeAllowedText);
                }
                $this->assertEquals($priceListRelation['priority'], $row->filter('.price_list_priority')->text());
                $this->assertContains($priceList->getName(), $row->filter('.price_list_link')->text());
            }
        }
    }

    /**
     * @depends testView
     */
    public function testDelete()
    {
        $priceListsRelations = $this->getPriceListsByEntity();
        $this->assertCount(3, $priceListsRelations);
        $this->assertTrue($this->checkPriceListRelationExist($priceListsRelations, 'price_list_3'));
        $form = $this->getUpdateForm();
        $this->assertTrue($form->has($this->formExtensionPath));
        //Test remove one price list
        $path = sprintf('[%s][priceListCollection][1]', $this->getDefaultWebsite()->getId());
        $form->remove($this->formExtensionPath . $path);
        $this->client->submit($form);
        $priceListsRelations = $this->getPriceListsByEntity();
        $this->assertCount(2, $priceListsRelations);
        $this->assertFalse($this->checkPriceListRelationExist($priceListsRelations, 'price_list_3'));
    }

    /**
     * @param BasePriceListRelation[] $priceListsRelations
     * @param string $priceListReference
     * @return bool
     */
    protected function checkPriceListRelationExist(array $priceListsRelations, $priceListReference)
    {
        $website = $this->getDefaultWebsite();
        
        $priceList = $this->getReference($priceListReference);
        foreach ($priceListsRelations as $priceListRelation) {
            if ($priceListRelation->getWebsite()->getId() == $website->getId()
                && $priceList->getId() == $priceListRelation->getPriceList()->getId()
            ) {
                return true;
            }
        }

        return false;
    }

    public function testValidation()
    {
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();
        /** @var Website $website */
        $website = $this->getDefaultWebsite();
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
     * @param string $message
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
     * @param null|integer $id
     * @return Form
     */
    protected function getUpdateForm($id = null)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUpdateUrl($id)
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

    /**
     * @param array $submittedData
     * @param array $formValues
     * @return array
     */
    protected function setSubmittedValues(array $submittedData, array $formValues)
    {
        foreach ($submittedData as $websiteReference => $priceLists) {
            /** @var Website $website */
            $website = $this->getWebsite($websiteReference);
            $fallbackPath = sprintf('%s[%d][fallback]', $this->formExtensionPath, $website->getId());
            $formValues[$fallbackPath] = $priceLists['fallback'];
            foreach ($priceLists['priceLists'] as $priceListKey => $priceList) {
                $collectionElementPath = sprintf(
                    '%s[%d][priceListCollection][%d]',
                    $this->formExtensionPath,
                    $website->getId(),
                    $priceListKey
                );
                /** @var PriceList $priceListEntity */
                $priceListEntity = $this->getReference($priceList['priceList']);
                $formValues[sprintf('%s[priceList]', $collectionElementPath)] = $priceListEntity->getId();
                $formValues[sprintf('%s[priority]', $collectionElementPath)] = $priceList['priority'];
                $formValues[sprintf('%s[mergeAllowed]', $collectionElementPath)] = $priceList['mergeAllowed'];
            }
        }

        return $this->explodeArrayPaths($formValues);
    }

    /**
     * @param array $expectedData
     * @param Form $form
     */
    protected function checkExpectedData(array $expectedData, Form $form)
    {
        $formValues = $this->explodeArrayPaths($form->getValues());
        $priceListsByWebsite = $formValues[$this->getMainFormName()]['priceListsByWebsites'];
        foreach ($expectedData as $websiteReference => $expectedFallbackWithPriceLists) {
            $websiteId = $this->getWebsite($websiteReference)->getId();
            $this->assertTrue(isset($priceListsByWebsite[$websiteId]));
            $this->assertEquals(
                $this->getOrderIndex($websiteReference, $expectedData),
                $this->getOrderIndex($websiteId, $priceListsByWebsite)
            );
            $actualFallbackWithPriceLists = $priceListsByWebsite[$websiteId];
            $this->assertEquals($expectedFallbackWithPriceLists['fallback'], $actualFallbackWithPriceLists['fallback']);
            if ($expectedFallbackWithPriceLists['priceLists']) {
                foreach ($expectedFallbackWithPriceLists['priceLists'] as $key => $expectedPriceListRelation) {
                    $actualPriceListRelation = $actualFallbackWithPriceLists['priceListCollection'][$key];
                    if (!$expectedPriceListRelation['mergeAllowed']) {
                        $this->assertFalse(isset($actualPriceListRelation['mergeAllowed']));
                    } else {
                        $this->assertTrue(isset($actualPriceListRelation['mergeAllowed']));
                    }
                    $this->assertEquals(
                        $expectedPriceListRelation['priority'],
                        $actualPriceListRelation['priority']
                    );
                    $priceListId = $this->getReference($expectedPriceListRelation['priceList'])->getId();
                    $this->assertEquals($actualPriceListRelation['priceList'], $priceListId);
                }
            } else {
                $this->assertCount(1, $actualFallbackWithPriceLists['priceListCollection']);
                $this->assertEmpty($actualFallbackWithPriceLists['priceListCollection'][0]['priority']);
                $this->assertEmpty($actualFallbackWithPriceLists['priceListCollection'][0]['priceList']);
            }
        }
    }

    /**
     * @param Crawler $crawler
     * @return mixed
     */
    protected function getAccountId(Crawler $crawler)
    {
        preg_match_all('#/update/(.+?)\"#is', $crawler->html(), $arr);

        return max($arr[1]);
    }

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->findOneBy(['name' => 'Default']);
    }
}
