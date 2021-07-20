<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Controller;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\VisibilityTrait;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

class CategoryControllerTest extends WebTestCase
{
    use VisibilityTrait;

    /** @var Category */
    protected $category;

    /** @var  Customer */
    protected $customer;

    /** @var CustomerGroup */
    protected $group;

    /** @var ScopeManager */
    protected $scopeManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadCategoryVisibilityData::class,
                LoadCustomers::class,
            ]
        );
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');

        $this->category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $this->customer = $this->getReference('customer.level_1');
        $this->group = $this->getReference(LoadGroups::GROUP1);
    }

    public function testEdit()
    {
        $categoryVisibility = CategoryVisibility::HIDDEN;
        $visibilityForCustomer = CustomerCategoryVisibility::VISIBLE;
        $visibilityForCustomerGroup = CustomerGroupCategoryVisibility::VISIBLE;

        $crawler = $this->submitForm(
            $categoryVisibility,
            json_encode([$this->customer->getId() => ['visibility' => $visibilityForCustomer]]),
            json_encode([$this->group->getId() => ['visibility' => $visibilityForCustomerGroup]])
        );

        static::assertStringNotContainsString('grid-customer-category-visibility-grid', $crawler->html());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $this->category->getId()])
        );

        $selectedCatalogVisibility = $crawler
            ->filterXPath('//select[@name="oro_catalog_category[visibility][all]"]/option[@selected]/@value')
            ->text();

        $this->assertEquals($categoryVisibility, $selectedCatalogVisibility);

        $customerGroupCategoryVisibilityData = $this->getChangeSetData(
            $crawler,
            'customergroup-category-visibility-changeset'
        );

        $this->checkVisibilityValue(
            $customerGroupCategoryVisibilityData,
            $this->group->getId(),
            $visibilityForCustomerGroup
        );

        $customerCategoryVisibilityData = $this->getChangeSetData(
            $crawler,
            'customer-category-visibility-changeset'
        );

        $this->checkVisibilityValue($customerCategoryVisibilityData, $this->customer->getId(), $visibilityForCustomer);
    }

    public function testSubmitInvalidData()
    {
        $crawler = $this->submitForm(
            'wrong Visibility',
            '{"wrong_id":{"visibility":"hidden"}}',
            '{"wrong_id":{"visibility":"hidden"}}'
        );

        static::assertStringContainsString('This value is not valid', $crawler->html());
        static::assertStringContainsString('VisibilityChangeSet', $crawler->html());
    }

    /**
     * @depends testEdit
     */
    public function testDeleteVisibilityOnSetDefault()
    {
        $manager = $this->client->getContainer()->get('doctrine');

        $this->assertNotNull(
            $this->getCategoryVisibility($manager, $this->category)->getId()
        );

        $this->assertNotNull(
            $this->getCategoryVisibilityForCustomer(
                $manager,
                $this->category,
                $this->customer
            )->getId()
        );

        $this->assertNotNull(
            $this->getCategoryVisibilityForCustomerGroup(
                $manager,
                $this->category,
                $this->group
            )->getId()
        );

        $this->submitForm(
            CategoryVisibility::getDefault($this->category),
            json_encode(
                [$this->customer->getId() => ['visibility' => CustomerCategoryVisibility::getDefault($this->category)]]
            ),
            json_encode(
                [
                    $this->group->getId() => [
                        'visibility' => CustomerGroupCategoryVisibility::getDefault($this->category),
                    ],
                ]
            )
        );

        $this->assertNull(
            $this->getCategoryVisibility($manager, $this->category)->getId()
        );

        $this->assertNull(
            $this->getCategoryVisibilityForCustomer(
                $manager,
                $this->category,
                $this->customer
            )->getId()
        );

        $this->assertNull(
            $this->getCategoryVisibilityForCustomerGroup(
                $manager,
                $this->category,
                $this->group
            )->getId()
        );
    }

    /**
     * @dataProvider dataProviderForNotExistingCategories
     * @param int|string $categoryId
     */
    public function testControllerActionWithNotExistingCategoryId($categoryId)
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_product_frontend_product_index',
                [
                    RequestProductHandler::CATEGORY_ID_KEY => $categoryId,
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * @return array
     */
    public function dataProviderForNotExistingCategories()
    {
        return [
            [99999],
            ['99999'],
            ['dummy-string'],
            [''],
        ];
    }

    public function testControllerActionWithExistingButInvisibleCategory()
    {
        $this->category = $this->getReference(LoadCategoryData::THIRD_LEVEL2);

        $categoryId = $this->category->getId();

        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_product_frontend_product_index',
                [
                    RequestProductHandler::CATEGORY_ID_KEY => $categoryId,
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * @param string $categoryVisibility
     * @param string $visibilityForCustomer
     * @param string $visibilityForCustomerGroup
     * @return Crawler
     */
    protected function submitForm($categoryVisibility, $visibilityForCustomer, $visibilityForCustomerGroup)
    {
        $this->client->followRedirects();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $this->category->getId()])
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $form = $crawler->selectButton('Save')->form();
        $parameters = $this->explodeArrayPaths($form->getValues());
        $token = $crawler->filterXPath('//input[@name="oro_catalog_category[_token]"]/@value')->text();

        $parameters['oro_catalog_category']['inventoryThreshold']['scalarValue'] = 0;
        $parameters['oro_catalog_category'][LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION]['scalarValue'] = 0;
        $parameters['oro_catalog_category'] = array_merge(
            $parameters['oro_catalog_category'],
            [
                '_token' => $token,
                'visibility' => [
                    'all' => $categoryVisibility,
                    'customer' => $visibilityForCustomer,
                    'customerGroup' => $visibilityForCustomerGroup,
                ],
            ]
        );

        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_catalog_category_update', ['id' => $this->category->getId()]),
            $parameters
        );

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        return $crawler;
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
            $key = '['.substr($key, 0, $pos).']'.substr($key, $pos);
            $accessor->setValue($parameters, $key, $val);
        }

        return $parameters;
    }

    /**
     * @param Crawler $crawler
     * @param string $changeSetId
     * @return array
     */
    protected function getChangeSetData(Crawler $crawler, $changeSetId)
    {
        $data = $crawler->filterXPath(
            sprintf('//input[@id="%s"]/@value', $changeSetId)
        )->text();

        return json_decode($data, true);
    }

    /**
     * @param array $data
     * @param string $id
     * @param string $visibility
     */
    protected function checkVisibilityValue($data, $id, $visibility)
    {
        foreach ($data as $key => $item) {
            if ($key == $id) {
                $this->assertEquals($visibility, $item['visibility']);

                return;
            }
        }
    }
}
