<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Controller;

use Oro\Bundle\EntityExtendBundle\Tests\Functional\AbstractConfigControllerTest;
use Oro\Bundle\UIBundle\Route\Router;

class ProductAttributeControllerTest extends AbstractConfigControllerTest
{
    /**
     * @param string $fieldType
     * @param string $name
     * @return \Symfony\Component\DomCrawler\Form
     */
    private function processFirstStep($fieldType, $name, $alias = null)
    {
        $a = $alias?:$this->getTestEntityAlias();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_create', ['alias' => $a])
        );

        $continueButton = $crawler->selectButton('Continue');

        $form = $continueButton->form();
        $form['oro_entity_extend_field_type[fieldName]'] = $name;
        $form['oro_entity_extend_field_type[type]'] = $fieldType;
        $this->client->followRedirects(true);

        $crawler = $this->client->submit(
            $form,
            [Router::ACTION_PARAMETER => $continueButton->attr('data-action')]
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $crawler->selectButton('Save and Close')->form();
    }

    public function testBeforeEntityAttributeSaveEventForProductAlias()
    {
        $form = $this->processFirstStep('enum', 'enum', 'product');
        $formValues = $form->getPhpValues();
        $formValues['oro_entity_config_type']['enum']['enum_options'] = [
            [
                'label' => 'First',
                'priority' => 1
            ]
        ];
        $this->arrayHasKey('is_visible', $formValues['oro_entity_config_type']['datagrid']);
        $this->assertEquals(3, $formValues['oro_entity_config_type']['datagrid']['is_visible']);
        $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $this->assertResponse();
    }

    private function assertResponse()
    {
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Attribute was successfully saved', $result->getContent());
    }
}
