<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class TaxJurisdictionControllerTest extends WebTestCase
{
    const CODE = 'code';
    const DESCRIPTION = 'description';
    const COUNTRY = 'ZW';
    const COUNTRY_FULL = 'Zimbabwe';
    const STATE = 'ZW-MA';
    const STATE_FULL = 'Manicaland';
    const ZIP_CODES = '11111, 00001-000003';

    const CODE_UPDATED = 'codeUpdated';
    const DESCRIPTION_UPDATED = 'description updated';
    const COUNTRY_UPDATED = 'HN';
    const COUNTRY_FULL_UPDATED = 'Honduras';
    const STATE_UPDATED = 'HN-CH';
    const STATE_FULL_UPDATED = 'Choluteca';
    const ZIP_CODES_UPDATED = '11111, 00001-000005';

    const SAVE_MESSAGE = 'Tax Jurisdiction has been saved';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_tax_jurisdiction_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_tax_jurisdiction_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertTaxJurisdictionSave(
            $crawler,
            self::CODE,
            self::DESCRIPTION,
            self::COUNTRY,
            self::COUNTRY_FULL,
            self::STATE,
            self::STATE_FULL,
            self::ZIP_CODES
        );
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'tax-jurisdiction-grid',
            ['tax-jurisdiction-grid[_filter][code][value]' => self::CODE]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_tax_jurisdiction_update', ['id' => $result['id']])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertTaxJurisdictionSave(
            $crawler,
            self::CODE_UPDATED,
            self::DESCRIPTION_UPDATED,
            self::COUNTRY_UPDATED,
            self::COUNTRY_FULL_UPDATED,
            self::STATE_UPDATED,
            self::STATE_FULL_UPDATED,
            self::ZIP_CODES_UPDATED
        );

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_tax_jurisdiction_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains(self::CODE_UPDATED . ' - View - Tax Jurisdictions - Taxes', $html);

        $this->assertViewPage(
            $html,
            self::CODE_UPDATED,
            self::DESCRIPTION_UPDATED,
            self::COUNTRY_FULL_UPDATED,
            self::STATE_FULL_UPDATED
        );
    }

    /**
     * @param Crawler $crawler
     * @param string  $code
     * @param string  $description
     * @param string  $country
     * @param string  $countryFull
     * @param string  $state
     * @param string  $stateFull
     * @param string  $zipCodes
     */
    protected function assertTaxJurisdictionSave(
        Crawler $crawler,
        $code,
        $description,
        $country,
        $countryFull,
        $state,
        $stateFull,
        $zipCodes
    ) {
        $formData = [
            'orob2b_tax_jurisdiction_type[code]' => $code,
            'orob2b_tax_jurisdiction_type[description]' => $description,
            'orob2b_tax_jurisdiction_type[zipCodes]' => $zipCodes
        ];

        $form = $crawler->selectButton('Save and Close')->form();

        $formData = $this->setCountryAndState($form, $formData, $country, $countryFull, $state, $stateFull);

        $form->setValues($formData);
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains(self::SAVE_MESSAGE, $html);
        $this->assertViewPage($html, $code, $description, $countryFull, $stateFull);
    }

    /**
     * @param Form $form
     * @param array $formData
     * @param string $country
     * @param string $countryFull
     * @param string $state
     * @param string $stateFull
     * @return array
     */
    protected function setCountryAndState(Form $form, array $formData, $country, $countryFull, $state, $stateFull)
    {
        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select name="orob2b_tax_jurisdiction_type[country]" ' .
            'id="orob2b_tax_jurisdiction_type_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="' . $country . '">' . $countryFull . '</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $formData['orob2b_tax_jurisdiction_type[country]'] = $country;

        $doc->loadHTML(
            '<select name="orob2b_tax_jurisdiction_type[region]" ' .
            'id="orob2b_tax_jurisdiction_type_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="' . $state . '">' . $stateFull . '</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $formData['orob2b_tax_jurisdiction_type[region]'] = $state;

        return $formData;
    }

    /**
     * @param string $html
     * @param string $code
     * @param string $description
     * @param string $country
     * @param string $state
     */
    protected function assertViewPage($html, $code, $description, $country, $state)
    {
        $this->assertContains($code, $html);
        $this->assertContains($description, $html);
        $this->assertContains($country, $html);
        $this->assertContains($state, $html);
    }
}
