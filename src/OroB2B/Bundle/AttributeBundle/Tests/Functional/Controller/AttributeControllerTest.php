<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\FallbackBundle\Model\FallbackType;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeOption;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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
     * @var array
     */
    protected $selectTypes = ['select', 'multiselect'];

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
        $this->loadFixtures([
            'OroB2B\Bundle\AttributeBundle\Tests\Functional\DataFixtures\LoadLocaleData',
            'OroB2B\Bundle\AttributeBundle\Tests\Functional\DataFixtures\LoadWebsiteData'
        ]);

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
     * @dataProvider attributesDataProvider
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

        if ($localized && !$this->isSelectType($type)) {
            // Check defaultValue for available locales
            $this->assertLocalize($formValues, 'defaultValue');
        } elseif (array_key_exists("$this->formUpdate[defaultValue]", $formValues)) {
            $this->assertEmpty($formValues["$this->formUpdate[defaultValue]"]);
        }

        $attributeType = $this->getAttributeTypeByName($formValues["$this->formUpdate[type]"]);
        $attributeProperties = $this->getAttributeTypePropertyFields($attributeType);
        foreach ($attributeProperties as $attributeProperty) {
            // Only this property is true by default
            if ($attributeProperty == 'onProductView') {
                $this->assertNotEmpty($formValues["$this->formUpdate[$attributeProperty][default]"]);
            } else {
                $this->assertArrayNotHasKey("$this->formUpdate[$attributeProperty][default]", $formValues);
            }

            if ($attributeProperty == 'containHtml') {
                $this->assertArrayNotHasKey($attributeProperty, $formValues);
            } else {
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

        if ($this->isSelectType($type)) {
            // Set default for options. By default exists only one option
            foreach (array_slice($data['options'], 0, 1) as $key => $option) {
                $form["$this->formUpdate[defaultOptions][$key][default]"] = $option['default'];
                $form["$this->formUpdate[defaultOptions][$key][order]"] = $option['order'];
            }
        }

        // Submit attribute create second step
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Attribute saved", $crawler->html());

        // Add second option for select and multiselect
        if ($this->isSelectType($type)) {
            $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BAttributeBundle:Attribute');

            $attribute = $this->getContainer()->get('doctrine')
                ->getRepository('OroB2BAttributeBundle:Attribute')
                ->findOneBy(['code' => 'color']);

            $masterOption = new AttributeOption();
            $masterOption->setValue('Black');
            $masterOption->setOrder(100);

            $attribute->addOption($masterOption);

            $locales = $this->getLocales();
            foreach ($locales as $locale) {
                $option = new AttributeOption();
                $option->setLocale($locale);
                $option->setFallback(FallbackType::SYSTEM);
                $option->setOrder($masterOption->getOrder());
                $attribute->addOption($option);

                $masterOption->addRelatedOption($option);
            }

            $em->flush($attribute);
        }

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
            $this->setLocalizedData($type, $data, $form);
        } else {
            $this->setNotLocalizedData($type, $data, $form);
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
            $this->assertLocalizedData($type, $data, $formValues);
        } else {
            $this->assertNotLocalizedData($type, $data, $formValues);
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
     */
    public function attributesDataProvider()
    {
        return Yaml::parse(
            file_get_contents(__DIR__ . str_replace('/', DIRECTORY_SEPARATOR, '/DataProvider/attributes.yml'))
        );
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

            if ($fallback && isset($formValues["$this->formUpdate[$scope][locales][$localeId][value]"])) {
                $this->assertEmpty($formValues["$this->formUpdate[$scope][locales][$localeId][value]"]);
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

    /**
     * @param string $type
     * @param array $data
     * @param Form $form
     */
    protected function setLocalizedData($type, array $data, Form &$form)
    {
        if ($this->isSelectType($type)) {
            $this->setSelectTypeLocalizedData($data, $form);
        } else {
            $this->setScalarTypeLocalizedData($data, $form);
        }
    }

    /**
     * @param string $type
     * @param array $data
     * @param Form $form
     */
    protected function setNotLocalizedData($type, array $data, Form &$form)
    {
        if ($this->isSelectType($type)) {
            $this->setSelectTypeNotLocalizedData($data, $form);
        } else {
            $form["$this->formUpdate[defaultValue]"] = $data['defaultValue'];
        }
    }

    /**
     * @param string $type
     * @param array $data
     * @param array $formValues
     */
    protected function assertLocalizedData($type, array $data, array $formValues)
    {
        if ($this->isSelectType($type)) {
            $this->assertSelectTypeLocalizedData($data, $formValues);
        } else {
            $this->assertScalarTypeLocalizedData($data, $formValues);
        }
    }

    /**
     * @param string $type
     * @param array $data
     * @param array $formValues
     */
    protected function assertNotLocalizedData($type, array $data, array $formValues)
    {
        if ($this->isSelectType($type)) {
            $this->assertSelectTypeNotLocalizedData($data, $formValues);
        } else {
            $this->assertEquals($data['defaultValue'], $formValues["$this->formUpdate[defaultValue]"]);
        }
    }

    /**
     * @param array $data
     * @param Form $form
     */
    protected function setSelectTypeLocalizedData(array $data, Form &$form)
    {
        foreach ($data['options'] as $key => $option) {
            $form["$this->formUpdate[defaultOptions][$key][default]"] = $option['default'];
            $form["$this->formUpdate[defaultOptions][$key][order]"] = $option['order'];
            $form["$this->formUpdate[defaultOptions][$key][is_default]"] = $option['is_default'];
            foreach ($option['locales'] as $localeName => $localeValue) {
                $locale = $this->localeRegistry[$localeName];
                $localeId = $locale->getId();
                foreach ($localeValue as $name => $value) {
                    if ($name == 'extend_value') {
                        $form["$this->formUpdate[defaultOptions][$key][locales][$localeId][$name]"] = $value;
                    } else {
                        $form["$this->formUpdate[defaultOptions][$key]"
                        . "[locales][$localeId][fallback_value][$name]"] = $value;
                    }
                }
            }
        }
    }

    /**
     * @param array $data
     * @param Form $form
     */
    protected function setScalarTypeLocalizedData(array $data, Form &$form)
    {
        $form["$this->formUpdate[defaultValue][default]"] = $data['defaultValue'];
        foreach ($data['localeDefaultValue'] as $localeName => $localeValue) {
            $locale = $this->localeRegistry[$localeName];
            $localeId = $locale->getId();
            foreach ($localeValue as $name => $value) {
                $form["$this->formUpdate[defaultValue][locales][$localeId][$name]"] = $value;
            }
        }
    }

    /**
     * @param array $data
     * @param Form $form
     */
    protected function setSelectTypeNotLocalizedData(array $data, Form &$form)
    {
        foreach ($data['options'] as $key => $option) {
            $form["$this->formUpdate[defaultOptions][$key][default]"] = $option['default'];
            $form["$this->formUpdate[defaultOptions][$key][order]"] = $option['order'];
            $form["$this->formUpdate[defaultOptions][$key][is_default]"] = $option['is_default'];
            foreach ($option['locales'] as $localeName => $localeValue) {
                $locale = $this->localeRegistry[$localeName];
                $localeId = $locale->getId();
                foreach ($localeValue as $name => $value) {
                    $form["$this->formUpdate[defaultOptions][$key][locales][$localeId][$name]"] = $value;
                }
            }
        }
    }

    /**
     * @param array $data
     * @param array $formValues
     */
    protected function assertSelectTypeNotLocalizedData(array $data, array $formValues)
    {
        foreach ($data['options'] as $key => $option) {
            $this->assertEquals(
                $option['default'],
                $formValues["$this->formUpdate[defaultOptions][$key][default]"]
            );
            $this->assertEquals(
                $option['order'],
                $formValues["$this->formUpdate[defaultOptions][$key][order]"]
            );

            $this->assertEquals(
                $option['is_default'],
                $formValues["$this->formUpdate[defaultOptions][$key][is_default]"]
            );

            foreach ($option['locales'] as $localeName => $localeValue) {
                $locale = $this->localeRegistry[$localeName];
                $localeId = $locale->getId();
                foreach ($localeValue as $name => $value) {
                    $this->assertEquals(
                        $value,
                        $formValues["$this->formUpdate[defaultOptions][$key][locales][$localeId][$name]"]
                    );
                }
            }
        }
    }

    /**
     * @param array $data
     * @param array $formValues
     */
    protected function assertSelectTypeLocalizedData(array $data, array $formValues)
    {
        foreach ($data['options'] as $key => $option) {
            $this->assertEquals(
                $option['default'],
                $formValues["$this->formUpdate[defaultOptions][$key][default]"]
            );
            $this->assertEquals(
                $option['order'],
                $formValues["$this->formUpdate[defaultOptions][$key][order]"]
            );
            $this->assertEquals(
                $option['is_default'],
                $formValues["$this->formUpdate[defaultOptions][$key][is_default]"]
            );
            foreach ($option['locales'] as $localeName => $localeValue) {
                $locale = $this->localeRegistry[$localeName];
                $localeId = $locale->getId();
                foreach ($localeValue as $name => $value) {
                    if ($name == 'extend_value') {
                        $this->assertEquals(
                            $value,
                            $formValues["$this->formUpdate[defaultOptions][$key]"
                            . "[locales][$localeId][$name]"]
                        );
                    } else {
                        $this->assertEquals(
                            $value,
                            $formValues["$this->formUpdate[defaultOptions][$key]"
                            . "[locales][$localeId][fallback_value][$name]"]
                        );
                    }
                }
            }
        }
    }

    /**
     * @param array $data
     * @param array $formValues
     */
    protected function assertScalarTypeLocalizedData(array $data, array $formValues)
    {
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
    }

    /**
     * @param string $type
     * @return bool
     */
    protected function isSelectType($type)
    {
        return in_array($type, $this->selectTypes);
    }
}
