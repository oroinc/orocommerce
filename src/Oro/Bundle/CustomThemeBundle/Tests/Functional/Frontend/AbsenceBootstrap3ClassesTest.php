<?php

namespace Oro\Bundle\CustomThemeBundle\Tests\Functional\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AbsenceBootstrap3ClassesTest extends WebTestCase
{
    use AbsenceBootstrap3ClassesTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $authHeader = $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW);
        $this->initClient([], $authHeader);
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadFrontendProductData::class,
            LoadRequestData::class,
        ]);
    }

    /**
     * @dataProvider themeProvider
     *
     * @param string $theme
     */
    public function testAbsenceOfClassHomePage($theme)
    {
        $this->setTheme($theme);

        $crawler = $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $this->assertBootstrapClassesNotExist($crawler);
    }

    /**
     * @dataProvider themeProvider
     *
     * @param string $theme
     */
    public function testAbsenceOfClassProductListPage($theme)
    {
        $this->setTheme($theme);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_product_index'));
        $this->assertBootstrapClassesNotExist($crawler);
    }

    /**
     * @dataProvider themeProvider
     *
     * @param string $theme
     */
    public function testAbsenceOfClassProductViewPage($theme)
    {
        $this->setTheme($theme);

        $id = $this->getReference(LoadProductData::PRODUCT_1)->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_product_view', ['id' => $id]));
        $this->assertBootstrapClassesNotExist($crawler);
    }

    /**
     * @dataProvider themeProvider
     *
     * @param string $theme
     */
    public function testAbsenceOfClassQuoteViewPage($theme)
    {
        $this->setTheme($theme);

        $id = $this->getReference(LoadRequestData::REQUEST1)->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_view', ['id' => $id]));
        $this->assertBootstrapClassesNotExist($crawler);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $config = self::getConfigManager('global');
        $config->reset('oro_frontend.frontend_theme');
    }
}
