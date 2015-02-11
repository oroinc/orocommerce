<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Functional\Controller;

use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Integer as IntegerConstraint;
use OroB2B\Bundle\AttributeBundle\Model\SharingType;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\GreaterThanZero;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AttributeBundle\Model\FallbackType;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class AttributeControllerTest extends WebTestCase
{
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

    /**
     * @var string
     */
    protected $formCreate = 'orob2b_attribute_create';
    
    /**
     * @var string
     */
    protected $formUpdate = 'orob2b_attribute_update';

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

    /**
     * @dataProvider testAttributesDataProvider
     * @param string $type
     * @param string $code
     * @param boolean $localized
     * @param string $sharingGroup
     * @param array $validation
     * @param string $label
     * @param string $data
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function testAttributes($type, $code, $localized, $sharingGroup, $validation, $label, $data)
    {
        $crawler = $this->client->request('GET', $this->getUrl("$this->formCreate"));

        /** @var Form $form */
        $form = $crawler->selectButton('Continue')->form();
        $form["$this->formCreate[code]"] = $code;
        $form["$this->formCreate[type]"] = $type;
        $form["$this->formCreate[localized]"] = $localized;

        // Submit attribute create first step
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        // Check form values
        $formValues = $form->getValues();

        $this->assertContains($code, $formValues["$this->formUpdate[code]"]);
        $this->assertContains($type, $formValues["$this->formUpdate[type]"]);
        $this->assertEmpty($formValues["$this->formUpdate[label][default]"]);

        // Check labels
        $this->assertLocalize($formValues, 'label');

        if ($localized) {
            // Check defaultValue for available locales
            $this->assertLocalize($formValues, 'defaultValue');
        }

        $attributeTypes = $this->getAttributeTypeByName($formValues["$this->formUpdate[type]"]);
        $attributeProperties = $this->getAttributeTypePropertyFields($attributeTypes);
        foreach ($attributeProperties as $attributeProperty) {
            if (in_array($attributeProperty, array('onProductView'))) {
                $this->assertEquals(true, $formValues["$this->formUpdate[$attributeProperty][default]"]);
            }

            if ($attributeProperty !== 'containHtml') {
                foreach ($this->websiteRegistry as $website) {
                    $siteId = $website->getId();
                    $this->assertContains(
                        'system',
                        $formValues["$this->formUpdate[$attributeProperty][websites][$siteId][fallback]"]
                    );
                }
            }
        }

        // Set default label. This field is required.
        $form["$this->formUpdate[label][default]"] = $label;

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

        // Set sharing type
        $form["$this->formUpdate[sharingType]"] = $sharingGroup;

        // Set validation
        foreach ($validation as $key => $value) {
            $form["$this->formUpdate[$key]"] = $value;
        }

        foreach ($data['label'] as $localeName => $localeValue) {
            $locale = $this->localeRegistry[$localeName];
            $localeId = $locale->getId();
            foreach ($localeValue as $name => $value) {
                $form["$this->formUpdate[label][locales][$localeId][$name]"] = $value;
            }

        }

        if ($localized) {
            $form["$this->formUpdate[defaultValue][default]"] = $data['defaultValue'];
            foreach ($data['localeDefaultValue'] as $localeName => $localeValue) {
                $locale = $this->localeRegistry[$localeName];
                $localeId = $locale->getId();
                foreach ($localeValue as $name => $value) {
                    $form["$this->formUpdate[defaultValue][locales][$localeId][$name]"] = $value;
                }
            }
        } else {
            $form["$this->formUpdate[defaultValue]"] = $data['defaultValue'];
        }

        foreach ($data['additional'] as $attributePropertyName => $attributePropertyData) {
            foreach ($attributePropertyData as $siteName => $siteValue) {
                $website = $this->websiteRegistry[$siteName];
                $siteId = $website->getId();
                foreach ($siteValue as $name => $value) {
                    $form["$this->formUpdate[$attributePropertyName][websites][$siteId][$name]"] = $value;
                }
            }
        }

        // Submit attribute update and stay on page
        $form->setValues(['input_action' => 'save_and_stay']);
        $crawler = $this->client->submit($form);
        $this->assertContains("$code - Edit - Attributes - Product management", $crawler->html());
        $this->assertContains("Attribute saved", $crawler->html());

        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getValues();

        // Check sharing type
        $this->assertEquals($sharingGroup, $formValues["$this->formUpdate[sharingType]"]);

        // Check validation
        foreach ($validation as $key => $value) {
            if ($value) {
                $this->assertEquals($value, $formValues["$this->formUpdate[$key]"]);
            }
        }

        foreach ($data['label'] as $localeName => $localeValue) {
            $locale = $this->localeRegistry[$localeName];
            $localeId = $locale->getId();
            foreach ($localeValue as $name => $value) {
                $this->assertEquals($value, $formValues["$this->formUpdate[label][locales][$localeId][$name]"]);
            }
        }

        if ($localized) {
            $this->assertEquals($data['defaultValue'], $formValues["$this->formUpdate[defaultValue][default]"]);
            foreach ($data['localeDefaultValue'] as $localeName => $localeValue) {
                $locale = $this->localeRegistry[$localeName];
                $localeId = $locale->getId();
                foreach ($localeValue as $name => $value) {
                    $this->assertEquals(
                        $value,
                        $formValues["$this->formUpdate[defaultValue][locales][$localeId][$name]"]
                    );
                }
            }
        } else {
            $this->assertEquals($data['defaultValue'], $formValues["$this->formUpdate[defaultValue]"]);
        }

        foreach ($data['additional'] as $attributePropertyName => $attributePropertyData) {
            foreach ($attributePropertyData as $siteName => $siteValue) {
                $website = $this->websiteRegistry[$siteName];
                $siteId = $website->getId();
                foreach ($siteValue as $name => $value) {
                    if ($value) {
                        $this->assertEquals(
                            $value,
                            $formValues["$this->formUpdate[$attributePropertyName][websites][$siteId][$name]"]
                        );
                    }
                }
            }
        }

        // Go to attribute grid
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_attribute_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains($this->grid, $crawler->html());

        // Remove current attribute
        $response = $this->client->requestGrid(
            $this->grid,
            [$this->grid . '[_filter][code][value]' => $code]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $this->assertEquals($code, $result['code']);

        $attributeId = (int)$result['id'];

        $this->client->request('DELETE', $this->getUrl('orob2b_api_delete_attribute', ['id' => $attributeId]));

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('orob2b_attribute_view', ['id' => $attributeId]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAttributesDataProvider()
    {
        return [
            'integer localized' => [
                'name' => 'integer',
                'code' => 'code01',
                'localized' => true,
                'sharingGroup' => SharingType::GENERAL,
                'validation' => [
                    'required' => true,
                    'unique' => false,
                    'validation' => GreaterThanZero::ALIAS,
                ],
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
                    'localeDefaultValue' => [
                        'en_US' => [
                            'value' => null,
                            'fallback' => FallbackType::SYSTEM
                        ],
                        'en_CA' => [
                            'value' => '200',
                            'fallback' => null
                        ]
                    ],
                    'additional' => [
                        'onProductView' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'inProductListing' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInSorting' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onAdvancedSearch' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onProductComparison' => [
                            'US' => [
                                'value' => false,
                                'fallback' => null
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInFilters' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ]
                    ]
                ]
            ],
            'integer not localized' => [
                'name' => 'integer',
                'code' => 'code01',
                'localized' => false,
                'sharingGroup' => SharingType::GENERAL,
                'validation' => [
                    'required' => true,
                    'unique' => true,
                    'validation' => GreaterThanZero::ALIAS,
                ],
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
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'inProductListing' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInSorting' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onAdvancedSearch' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onProductComparison' => [
                            'US' => [
                                'value' => false,
                                'fallback' => null
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInFilters' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ]
                    ]
                ]
            ],
            'float localized' => [
                'name' => 'float',
                'code' => 'code02',
                'localized' => true,
                'sharingGroup' => SharingType::GROUP,
                'validation' => [
                    'required' => false,
                    'unique' => false,
                    'validation' => null,
                ],
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
                    'defaultValue' => '3.10',
                    'localeDefaultValue' => [
                        'en_US' => [
                            'value' => '3.14',
                            'fallback' => null
                        ],
                        'en_CA' => [
                            'value' => null,
                            'fallback' => FallbackType::PARENT_LOCALE
                        ]
                    ],
                    'additional' => [
                        'onProductView' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ],
                        'inProductListing' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInSorting' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onAdvancedSearch' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onProductComparison' => [
                            'US' => [
                                'value' => false,
                                'fallback' => null
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInFilters' => [
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
            'float not localized' => [
                'name' => 'float',
                'code' => 'code02',
                'localized' => false,
                'sharingGroup' => SharingType::WEBSITE,
                'validation' => [
                    'required' => false,
                    'unique' => false,
                    'validation' => IntegerConstraint::ALIAS,
                ],
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
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ],
                        'inProductListing' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInSorting' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onAdvancedSearch' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onProductComparison' => [
                            'US' => [
                                'value' => false,
                                'fallback' => null
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInFilters' => [
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
            'boolean localized' => [
                'name' => 'boolean',
                'code' => 'code03',
                'localized' => true,
                'sharingGroup' => SharingType::GENERAL,
                'validation' => [],
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
                    'localeDefaultValue' => [
                        'en_US' => [
                            'value' => true,
                            'fallback' => null
                        ],
                        'en_CA' => [
                            'value' => true,
                            'fallback' => null
                        ]
                    ],
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
                        ],
                        'inProductListing' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInSorting' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onAdvancedSearch' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onProductComparison' => [
                            'US' => [
                                'value' => false,
                                'fallback' => null
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInFilters' => [
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
            'boolean not localized' => [
                'name' => 'boolean',
                'code' => 'code03',
                'localized' => false,
                'sharingGroup' => SharingType::GENERAL,
                'validation' => [],
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
                        ],
                        'inProductListing' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInSorting' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onAdvancedSearch' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'onProductComparison' => [
                            'US' => [
                                'value' => false,
                                'fallback' => null
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => null
                            ]
                        ],
                        'useInFilters' => [
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
            'string not localized' => [
                'name' => 'string',
                'code' => 'code04',
                'localized' => false,
                'sharingGroup' => SharingType::GENERAL,
                'validation' => [
                    'required' => true,
                    'unique' => true,
                    'validation' => null,
                ],
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
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ]
                    ]
                ]
            ],
            'text not localized' => [
                'name' => 'text',
                'code' => 'code05',
                'localized' => false,
                'sharingGroup' => SharingType::GENERAL,
                'validation' => [
                    'required' => true,
                    'unique' => true,
                    'validation' => null,
                ],
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
                                'value' => false,
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
            'date not localized' => [
                'name' => 'date',
                'code' => 'code06',
                'localized' => false,
                'sharingGroup' => SharingType::GENERAL,
                'validation' => [
                    'required' => true,
                    'unique' => true
                ],
                'label' => 'Date attribute label',
                'data' => [
                    'label' => [
                        'en_US' => [
                            'value' => false,
                            'fallback' => FallbackType::SYSTEM
                        ],
                        'en_CA' => [
                            'value' => false,
                            'fallback' => FallbackType::SYSTEM
                        ]
                    ],
                    'defaultValue' => '2000-01-01',
                    'additional' => [
                        'onProductView' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ]
                    ]
                ]
            ],
            'datetime not localized' => [
                'name' => 'datetime',
                'code' => 'code07',
                'localized' => false,
                'sharingGroup' => SharingType::GENERAL,
                'validation' => [
                    'required' => true,
                    'unique' => false
                ],
                'label' => 'Datetime attribute label',
                'data' => [
                    'label' => [
                        'en_US' => [
                            'value' => false,
                            'fallback' => FallbackType::SYSTEM
                        ],
                        'en_CA' => [
                            'value' => false,
                            'fallback' => FallbackType::SYSTEM
                        ]
                    ],
                    'defaultValue' => '2000-01-01T08:11:12Z',
                    'additional' => [
                        'onProductView' => [
                            'US' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ],
                            'Canada' => [
                                'value' => false,
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $formValues
     * @param string $scope
     */
    protected function assertLocalize(array $formValues, $scope)
    {
        foreach ($this->localeRegistry as $locale) {
            $localeId = $locale->getId();
            $fallback = $formValues["$this->formUpdate[$scope][locales][$localeId][fallback]"];
            $value = isset($formValues["$this->formUpdate[$scope][locales][$localeId][value]"])
                ? $formValues["$this->formUpdate[$scope][locales][$localeId][value]"]
                : '';

            if ($value) {
                $this->assertEmpty($value);
            }

            if ($locale->getParentLocale()) {
                $this->assertContains(FallbackType::PARENT_LOCALE, $fallback);
            } else {
                $this->assertContains(FallbackType::SYSTEM, $fallback);
            }
        }
    }

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
            $fields[] = 'containHtml';
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
