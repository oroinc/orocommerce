<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadWebsiteData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

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

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        self::getContainer()->get('oro_config.global')
            ->set('oro_pricing.price_strategy', MergePricesCombiningStrategy::NAME);
        $this->loadFixtures(
            [
                LoadWebsiteData::class,
                LoadCustomers::class,
                LoadGroups::class,
                LoadPriceLists::class
            ]
        );
        $this->formExtensionPath = sprintf('%s[priceListsByWebsites]', $this->getMainFormName());
    }

    private function urlToRouteJson(string $url): string
    {
        $route = $this->client->getContainer()->get('router')->match($url);
        $oroRoute = [
            'route' => $route['_route'],
            'params' => ['id' => '$id']
        ];

        return \json_encode($oroRoute);
    }

    /**
     * @dataProvider priceListRelationDataProvider
     */
    public function testAddOnCreate(array $submittedData, array  $expectedData)
    {
        $form = $this->getUpdateForm();
        $formValues = $form->getValues();

        $params = $this->setSubmittedValues($submittedData, $formValues);
        $params['input_action'] = $this->urlToRouteJson($this->getUpdateUrl());

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
                    LoadWebsiteData::DEFAULT_WEBSITE => [
                        'fallback' => PriceListCustomerFallback::ACCOUNT_GROUP,
                        'priceLists' => [
                            ['priceList' => 'price_list_1', '_position' => 3, 'mergeAllowed' => false],
                            ['priceList' => 'price_list_2', '_position' => 23, 'mergeAllowed' => true],
                            ['priceList' => 'price_list_3', '_position' => 22, 'mergeAllowed' => true]
                        ],
                    ],
                ],

                'expectedData' => [
                    LoadWebsiteData::DEFAULT_WEBSITE => [
                        'fallback' => PriceListCustomerFallback::ACCOUNT_GROUP,
                        'priceLists' => [
                            ['priceList' => 'price_list_1', '_position' => 3, 'mergeAllowed' => false],
                            ['priceList' => 'price_list_3', '_position' => 22, 'mergeAllowed' => true],
                            ['priceList' => 'price_list_2', '_position' => 23, 'mergeAllowed' => true]
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
     * @param string $identifier
     * @return null|Website
     */
    protected function getWebsite($identifier)
    {
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

        foreach ($expectedData as $priceListsWithFallback) {
            $priceListSection = $crawler->filter('div #priceLists');
            $fallbackText = $priceListSection->filter('p strong')->text();
            static::assertStringNotContainsString('only', $fallbackText);

            foreach ($priceListsWithFallback['priceLists'] as $priceListRelation) {
                /** @var PriceList $priceList */
                $priceList = $this->getReference($priceListRelation['priceList']);
                $row = $priceListSection->filter(sprintf('.price_list%s', $priceList->getId()));
                static::assertStringContainsString($priceList->getName(), $row->filter('a')->text());
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
        $path = sprintf(
            '[%s][priceListCollection][1]',
            $this->getWebsite(LoadWebsiteData::DEFAULT_WEBSITE)->getId()
        );
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
        $website = $this->getWebsite(LoadWebsiteData::DEFAULT_WEBSITE);

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
        $website = $this->getWebsite(LoadWebsiteData::DEFAULT_WEBSITE);
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
        $formValues[sprintf('%s[_position]', $collectionElementPath1)] = 1;
        $formValues[sprintf('%s[priceList]', $collectionElementPath2)] = $priceList->getId();
        $formValues[sprintf('%s[_position]', $collectionElementPath2)] = 2;

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
        static::assertStringContainsString($message, $crawler->html());
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
            $pos = strpos($key, '[');
            if (!$pos) {
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
    protected function getCreateForm()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getCreateUrl()
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $crawler->selectButton('Save and Close')->form();
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
                $formValues[sprintf('%s[_position]', $collectionElementPath)] = $priceList['_position'];
            }
        }

        return $this->explodeArrayPaths($formValues);
    }

    protected function checkExpectedData(array $expectedData, Form $form)
    {
        $formValues = $this->explodeArrayPaths($form->getValues());
        $priceListsByWebsite = $formValues[$this->getMainFormName()]['priceListsByWebsites'];
        foreach ($expectedData as $websiteReference => $expectedFallbackWithPriceLists) {
            $websiteId = $this->getWebsite($websiteReference)->getId();
            $this->assertTrue(isset($priceListsByWebsite[$websiteId]));
            $actualFallbackWithPriceLists = $priceListsByWebsite[$websiteId];
            $this->assertEquals($expectedFallbackWithPriceLists['fallback'], $actualFallbackWithPriceLists['fallback']);
            if ($expectedFallbackWithPriceLists['priceLists']) {
                foreach ($expectedFallbackWithPriceLists['priceLists'] as $key => $expectedPriceListRelation) {
                    $actualPriceListRelation = $actualFallbackWithPriceLists['priceListCollection'][$key];
                    $this->assertEquals(
                        $expectedPriceListRelation['_position'],
                        $actualPriceListRelation['_position']
                    );
                    $priceListId = $this->getReference($expectedPriceListRelation['priceList'])->getId();
                    $this->assertEquals($actualPriceListRelation['priceList'], $priceListId);
                }
            } else {
                $this->assertCount(1, $actualFallbackWithPriceLists['priceListCollection']);
                $this->assertEmpty($actualFallbackWithPriceLists['priceListCollection'][0]['_position']);
                $this->assertEmpty($actualFallbackWithPriceLists['priceListCollection'][0]['priceList']);
            }
        }
    }

    /**
     * @param Crawler $crawler
     * @return mixed
     */
    protected function getCustomerId(Crawler $crawler)
    {
        preg_match_all('#/update/(.+?)\"#is', $crawler->html(), $arr);

        return max($arr[1]);
    }
}
