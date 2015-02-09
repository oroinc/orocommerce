<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Functional\Controller;

use OroB2B\Bundle\AttributeBundle\Model\FallbackType;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class AttributeControllerTest extends WebTestCase
{
    const TEST_CODE = 'test_code';

    /**
     * @var array|Locale[]
     */
    protected $localeRegistry = [];

    /**
     * @var array|Website[]
     */
    protected $websiteRegistry = [];

    /**
     * @var string
     */
    protected $grid = 'orob2b-attribute-grid';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\AttributeBundle\Tests\Functional\DataFixtures\LoadLocaleData']);
        $this->loadFixtures(['OroB2B\Bundle\AttributeBundle\Tests\Functional\DataFixtures\LoadWebsiteData']);

        // Fill locale registry by locales
        foreach ($this->getLocales() as $locale) {
            $this->localeRegistry[$locale->getCode()] = $locale;
        }

        // Fill website registry by websites
        foreach ($this->getWebsites() as $websites) {
            $this->websiteRegistry[$websites->getName()] = $websites;
        }
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_attribute_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($this->grid, $crawler->html());
    }

    /**
     * @dataProvider testAttributesDataProvider
     * @param string $type
     * @param string $code
     * @param string $label
     * @param string $data
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testAttributes($type, $code, $label, $data)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_attribute_create'));

        /** @var Form $form */
        $form = $crawler->selectButton('Continue')->form();
        $form['orob2b_attribute_create[code]'] = $code;
        $form['orob2b_attribute_create[type]'] = $type;

        // Submit attribute create first step
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        // Check form values
        $formValues = $form->getValues();

        $this->assertContains($code, $formValues['orob2b_attribute_update[code]']);
        $this->assertContains($type, $formValues['orob2b_attribute_update[type]']);
        $this->assertEmpty($formValues['orob2b_attribute_update[label][default]']);

        foreach ($this->localeRegistry as $locale) {
            $localeId = $locale->getId();
            $this->assertEmpty($formValues["orob2b_attribute_update[label][locales][$localeId][value]"]);
            $this->assertEquals('', $formValues["orob2b_attribute_update[label][locales][$localeId][value]"]);

            if ($locale->getParentLocale()) {
                $this->assertContains(
                    FallbackType::PARENT_LOCALE,
                    $formValues["orob2b_attribute_update[label][locales][$localeId][fallback]"]
                );
            } else {
                $this->assertContains(
                    FallbackType::SYSTEM,
                    $formValues["orob2b_attribute_update[label][locales][$localeId][fallback]"]
                );
            }
        }

        $attributeTypes = $this->getAttributeTypeByName($formValues['orob2b_attribute_update[type]']);
        $attributeProperties = $this->getAttributeTypePropertyFields($attributeTypes);
        foreach ($attributeProperties as $attributeProperty) {
            foreach ($this->websiteRegistry as $website) {
                $siteId = $website->getId();
                $this->assertContains(
                    'system',
                    $formValues["orob2b_attribute_update[$attributeProperty][websites][$siteId][fallback]"]
                );
            }
        }

        // Set default label
        $form['orob2b_attribute_update[label][default]'] = $label;

        // Submit attribute create second step
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Attribute saved", $crawler->html());

        $edit = $crawler->filter('.pull-right .edit-button')->link();
        $crawler = $this->client->click($edit);

        $this->assertEquals($code, $crawler->filter('h1.user-name')->html());

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        foreach ($data['label'] as $localeName => $localeValue) {
            $locale = $this->localeRegistry[$localeName];
            $localeId = $locale->getId();
            foreach ($localeValue as $name => $value) {
                $form["orob2b_attribute_update[label][locales][$localeId][$name]"] = $value;
            }

        }

        $form['orob2b_attribute_update[defaultValue]'] = $data['defaultValue'];

        foreach ($data['additional'] as $attributePropertyName => $attributePropertyData) {
            foreach ($attributePropertyData as $siteName => $siteValue) {
                $website = $this->websiteRegistry[$siteName];
                $siteId = $website->getId();
                foreach ($siteValue as $name => $value) {
                    $form["orob2b_attribute_update[$attributePropertyName][websites][$siteId][$name]"] = $value;
                }
            }
        }

        // Submit attribute update
        $crawler = $this->client->submit($form);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAttributesDataProvider()
    {
        return [
            'integer' => [
                'name' => 'integer',
                'code' => 'code01',
                'label' => 'Integer attribute label',
                'data' => [
                    'label' => [
                        'en_US' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ],
                        'en_CA' => [
                            'value' => 'Canada integer attribute label',
                            'fallback' => null
                        ]
                    ],
                    'defaultValue' => '100',
                    'additional' => [
                        'onProductView' => [
                            'US' => [
                                'value' => true,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ]
                    ]
                ]
            ],
            'float' => [
                'name' => 'float',
                'code' => 'code02',
                'label' => 'Float attribute label',
                'data' => [
                    'label' => [
                        'en_US' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ],
                        'en_CA' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ]
                    ],
                    'defaultValue' => '100.55',
                    'additional' => [
                        'onProductView' => [
                            'US' => [
                                'value' => true,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => true,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ]
                    ]
                ]
            ],
            'boolean' => [
                'name' => 'boolean',
                'code' => 'code03',
                'label' => 'Boolean attribute label',
                'data' => [
                    'label' => [
                        'en_US' => [
                            'value' => 'US boolean attribute label',
                            'fallback' => null
                        ],
                        'en_CA' => [
                            'value' => null,
                            'fallback' => FallbackType::PARENT_LOCALE
                        ]
                    ],
                    'defaultValue' => true,
                    'additional' => [
                        'onProductView' => [
                            'US' => [
                                'value' => false,
                                'fallback' => null
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ]
                    ]
                ]
            ],
            'string' => [
                'name' => 'string',
                'code' => 'code04',
                'label' => 'String attribute label',
                'data' => [
                    'label' => [
                        'en_US' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ],
                        'en_CA' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ]
                    ],
                    'defaultValue' => 'Some string',
                    'additional' => [
                        'onProductView' => [
                            'US' => [
                                'value' => true,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => true,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ]
                    ]
                ]
            ],
            'text' => [
                'name' => 'text',
                'code' => 'code05',
                'label' => 'Text attribute label',
                'data' => [
                    'label' => [
                        'en_US' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ],
                        'en_CA' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ]
                    ],
                    'defaultValue' => '<p>Text <b>with HTML</b>.</p>',
                    'additional' => [
                        'onProductView' => [
                            'US' => [
                                'value' => true,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => true,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ]
                    ]
                ]
            ],
            'date' => [
                'name' => 'date',
                'code' => 'code06',
                'label' => 'Date attribute label',
                'data' => [
                    'label' => [
                        'en_US' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ],
                        'en_CA' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ]
                    ],
                    'defaultValue' => '2000-01-01 00:00:00',
                    'additional' => [
                        'onProductView' => [
                            'US' => [
                                'value' => true,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => true,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ]
                    ]
                ]
            ],
            'datetime' => [
                'name' => 'datetime',
                'code' => 'code07',
                'label' => 'Datetime attribute label',
                'data' => [
                    'label' => [
                        'en_US' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ],
                        'en_CA' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ]
                    ],
                    'defaultValue' => '2000-01-01 10:11:12',
                    'additional' => [
                        'onProductView' => [
                            'US' => [
                                'value' => true,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => true,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

//    /**
//     * @depends testUpdate
//     * @param int $id
//     * @return int
//     */
//    public function testView($id)
//    {
//        $crawler = $this->client->request('GET', $this->getUrl('orob2b_attribute_view', ['id' => $id]));
//
//        $result = $this->client->getResponse();
//        $this->assertHtmlResponseStatusCodeEquals($result, 200);
//        $this->assertContains(/* some code . */' - Attributes - Product Management', $crawler->html());
//
//        return $id;
//    }

//    /**
//     * @depends testView
//     * @param int $id
//     */
//    public function testDelete($id)
//    {
//        $this->client->request('DELETE', $this->getUrl('orob2b_api_delete_attribute', ['id' => $id]));
//
//        $result = $this->client->getResponse();
//        $this->assertEmptyResponseStatusCodeEquals($result, 204);
//
//        $this->client->request('GET', $this->getUrl('orob2b_attribute_view', ['id' => $id]));
//
//        $result = $this->client->getResponse();
//        $this->assertHtmlResponseStatusCodeEquals($result, 404);
//    }

    /**
     * @return array|Website[]
     */
    protected function getWebsites()
    {
        $websites = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->findAll();

        if (!$websites) {
            throw new \LogicException('There is no available websites');
        }

        return $websites;
    }

    /**
     * @return array|Locale[]
     */
    protected function getLocales()
    {
        $locales = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BWebsiteBundle:Locale')
            ->findAll();

        if (!$locales) {
            throw new \LogicException('There is no available locales');
        }

        return $locales;
    }

    /**
     * @param $name
     * @return AttributeTypeInterface $attributeType
     */
    protected function getAttributeTypeByName($name)
    {
        $attributeType = $this->getContainer()
            ->get('orob2b_attribute.attribute_type.registry')
            ->getTypeByName($name);

        if (!$attributeType) {
            throw new \LogicException(sprintf('There is no attribute type with name "%s" .', $name));
        }

        return $attributeType;
    }

    /**
     * @param AttributeTypeInterface $attributeType
     * @return array
     */
    protected function getAttributeTypePropertyFields($attributeType)
    {
        $fields = [
            'onProductView',
            'inProductListing',
            'useInSorting',
            'onAdvancedSearch',
            'onProductComparison'
        ];

        if ($attributeType->isContainHtml()) {
            //$fields[] = 'containHtml';
        }

        if ($attributeType->isUsedForSearch()) {
            $fields[] = 'useForSearch';
        }

        if ($attributeType->isUsedInFilters()) {
            $fields[] = 'useInFilters';
        }

        return $fields;
    }
}
