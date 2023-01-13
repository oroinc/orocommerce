<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;

class TaxJurisdictionControllerTest extends WebTestCase
{
    private const CODE = 'code';
    private const DESCRIPTION = 'description';
    private const COUNTRY = 'ZW';
    private const COUNTRY_FULL = 'Zimbabwe';
    private const STATE = 'ZW-MA';
    private const STATE_FULL = 'Manicaland';
    private const ZIP_CODES = [
        ['zipRangeStart' => '11111', 'zipRangeEnd' => null],
        ['zipRangeStart' => '00001', 'zipRangeEnd' => '000003'],
        ['zipRangeStart' => null, 'zipRangeEnd' => '22222'],
    ];
    private const CODE_UPDATED = 'codeUpdated';
    private const DESCRIPTION_UPDATED = 'description updated';
    private const COUNTRY_UPDATED = 'HN';
    private const COUNTRY_FULL_UPDATED = 'Honduras';
    private const STATE_UPDATED = 'HN-CH';
    private const STATE_FULL_UPDATED = 'Choluteca';
    private const ZIP_CODES_UPDATED = [
        ['zipRangeStart' => '11111', 'zipRangeEnd' => null],
        ['zipRangeStart' => '00001', 'zipRangeEnd' => '000005'],
        ['zipRangeStart' => null, 'zipRangeEnd' => '22222'],
    ];
    private const SAVE_MESSAGE = 'Tax Jurisdiction has been saved';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_tax_jurisdiction_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('tax-jurisdiction-grid', $crawler->html());
    }

    public function testCreate(): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_tax_jurisdiction_create'));
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

        /** @var TaxJurisdiction $taxJurisdiction */
        $taxJurisdiction = $this->getContainer()->get('doctrine')
            ->getRepository(TaxJurisdiction::class)
            ->findOneBy(['code' => self::CODE]);
        $this->assertNotEmpty($taxJurisdiction);

        return $taxJurisdiction->getId();
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id): int
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tax_jurisdiction_update', ['id' => $id])
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
     */
    public function testView(int $id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tax_jurisdiction_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString(self::CODE_UPDATED . ' - View - Tax Jurisdictions - Taxes', $html);

        $this->assertViewPage(
            $html,
            self::CODE_UPDATED,
            self::DESCRIPTION_UPDATED,
            self::COUNTRY_FULL_UPDATED,
            self::STATE_FULL_UPDATED
        );
    }

    private function assertTaxJurisdictionSave(
        Crawler $crawler,
        string $code,
        string $description,
        string $country,
        string $countryFull,
        string $state,
        string $stateFull,
        array $zipCodes
    ): void {
        $token = $this->getCsrfToken('oro_tax_jurisdiction_type')->getValue();

        $formData = [
            'oro_tax_jurisdiction_type' => [
                'code' => $code,
                'description' => $description,
                'zipCodes' => $zipCodes,
                '_token' => $token,
            ],
        ];

        $form = $crawler->selectButton('Save and Close')->form();
        $redirectAction = $crawler->selectButton('Save and Close')->attr('data-action');

        $formData = $this->setCountryAndState($form, $formData, $country, $countryFull, $state, $stateFull);
        $formData['input_action'] = $redirectAction;

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString(self::SAVE_MESSAGE, $html);
        $this->assertViewPage($html, $code, $description, $countryFull, $stateFull);
    }

    private function setCountryAndState(
        Form $form,
        array $formData,
        string $country,
        string $countryFull,
        string $state,
        string $stateFull
    ): array {
        $doc = new \DOMDocument('1.0');
        $doc->loadHTML(
            '<select name="oro_tax_jurisdiction_type[country]" ' .
            'id="oro_tax_jurisdiction_type_country" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="' . $country . '">' . $countryFull . '</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $formData['oro_tax_jurisdiction_type']['country'] = $country;

        $doc->loadHTML(
            '<select name="oro_tax_jurisdiction_type[region]" ' .
            'id="oro_tax_jurisdiction_type_region" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="' . $state . '">' . $stateFull . '</option> </select>'
        );
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $formData['oro_tax_jurisdiction_type']['region'] = $state;

        return $formData;
    }

    private function assertViewPage(
        string $html,
        string $code,
        string $description,
        string $country,
        string $state
    ): void {
        self::assertStringContainsString($code, $html);
        self::assertStringContainsString($description, $html);
        self::assertStringContainsString($country, $html);
        self::assertStringContainsString($state, $html);
    }
}
