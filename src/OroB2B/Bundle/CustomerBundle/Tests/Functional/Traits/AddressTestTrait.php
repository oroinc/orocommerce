<?php
namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\Traits;

use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\AddressBundle\Entity\AddressType;

trait AddressTestTrait
{
    /**
     * Fill form for address tests (create test)
     *
     * @param Form $form
     * @return \Symfony\Component\DomCrawler\Form
     */
    protected function fillFormForCreateTest(Form $form)
    {
        $formNode = $form->getNode();
        $formNode->setAttribute('action', $formNode->getAttribute('action') . '?_widgetContainer=dialog');

        $form['orob2b_customer_typed_address[street]']            = 'Street';
        $form['orob2b_customer_typed_address[city]']              = 'City';
        $form['orob2b_customer_typed_address[postalCode]']        = 'Zip code';
        $form['orob2b_customer_typed_address[types]']             = [AddressType::TYPE_BILLING];
        $form['orob2b_customer_typed_address[defaults][default]'] = [AddressType::TYPE_BILLING];

        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="orob2b_customer_typed_address[country]" id="orob2b_customer_typed_address_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="AF">Afghanistan</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_customer_typed_address[country]'] = 'AF';

        $doc->loadHTML(
            '<select name="orob2b_customer_typed_address[region]" id="orob2b_customer_typed_address_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="AF-BDS">Badakhshān</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_customer_typed_address[region]'] = 'AF-BDS';

        return $form;
    }

    /**
     * Fill form for address tests (update test)
     *
     * @param Form $form
     * @return Form
     */
    protected function fillFormForUpdateTest(Form $form)
    {
        $formNode = $form->getNode();
        $formNode->setAttribute('action', $formNode->getAttribute('action') . '?_widgetContainer=dialog');

        $form['orob2b_customer_typed_address[types]'] = [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING];
        $form['orob2b_customer_typed_address[defaults][default]'] = [false, AddressType::TYPE_SHIPPING];


        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="orob2b_customer_typed_address[country]" id="orob2b_customer_typed_address_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW">Zimbabwe</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_customer_typed_address[country]'] = 'ZW';

        $doc->loadHTML(
            '<select name="orob2b_customer_typed_address[region]" id="orob2b_customer_typed_address_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="ZW-MA">Manicaland</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['orob2b_customer_typed_address[region]'] = 'ZW-MA';

        return $form;
    }
}
