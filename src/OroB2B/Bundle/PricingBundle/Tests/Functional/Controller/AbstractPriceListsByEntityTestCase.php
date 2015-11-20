<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractPriceListsByEntityTestCase extends WebTestCase
{
    /** @var string */
    protected $formExtensionPath;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
        $this->formExtensionPath = sprintf('%s[priceListsByWebsites]', $this->getMainFormName());
    }

    public function testDelete()
    {
        $this->assertCount(1, $this->getPriceListsByEntity());
        $form = $this->getUpdateForm();
        $this->assertTrue($form->has($this->formExtensionPath));
        $form->remove($this->formExtensionPath);
        $this->client->submit($form);
        $this->assertCount(0, $this->getPriceListsByEntity());
        $form = $this->getUpdateForm();
        $this->assertFalse($form->has($this->formExtensionPath));
    }

    public function testAdd()
    {
        /** @var Website[] $websites */
        $websites = [
            $this->getReference('Canada'),
            $this->getReference('US'),
        ];

        /** @var PriceList[] $priceLists */
        $priceLists = [
            $this->getReference('price_list_1'),
            $this->getReference('price_list_2'),
            $this->getReference('price_list_3'),
        ];

        $form = $this->getUpdateForm();
        $formValues = $form->getValues();
        foreach ($websites as $website) {
            $i = 0;
            foreach ($priceLists as $priceList) {
                $collectionElementPath = sprintf('%s[%d][%d]', $this->formExtensionPath, $website->getId(), $i);
                $formValues[sprintf('%s[priceList]', $collectionElementPath)] = $priceList->getId();
                $formValues[sprintf('%s[priority]', $collectionElementPath)] = $priceList->getId();
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
    abstract public function getMainFormName();

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
}
