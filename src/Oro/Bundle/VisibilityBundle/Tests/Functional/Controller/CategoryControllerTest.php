<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Controller;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\VisibilityAwareTestTrait;
use Symfony\Component\DomCrawler\Crawler;

class CategoryControllerTest extends WebTestCase
{
    use VisibilityAwareTestTrait;

    private Category $category;

    private Customer $customer;

    private CustomerGroup $group;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadCategoryVisibilityData::class,
            LoadCustomers::class,
        ]);
        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();

        $this->category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $this->customer = $this->getReference('customer.level_1');
        $this->group = $this->getReference(LoadGroups::GROUP1);
    }

    public function testEdit(): void
    {
        $categoryVisibility = CategoryVisibility::HIDDEN;
        $visibilityForCustomer = CustomerCategoryVisibility::VISIBLE;
        $visibilityForCustomerGroup = CustomerGroupCategoryVisibility::VISIBLE;

        $crawler = $this->submitForm(
            $categoryVisibility,
            json_encode(
                [$this->customer->getId() => ['visibility' => $visibilityForCustomer]],
                JSON_THROW_ON_ERROR
            ),
            json_encode(
                [$this->group->getId() => ['visibility' => $visibilityForCustomerGroup]],
                JSON_THROW_ON_ERROR
            )
        );

        static::assertStringNotContainsString('grid-customer-category-visibility-grid', $crawler->html());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $this->category->getId()])
        );

        $selectedCatalogVisibility = $crawler
            ->filterXPath('//select[@name="oro_catalog_category[visibility][all]"]/option[@selected]/@value')
            ->text();

        self::assertEquals($categoryVisibility, $selectedCatalogVisibility);

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

    public function testSubmitInvalidData(): void
    {
        $crawler = $this->submitForm(
            'wrong Visibility',
            '{"wrong_id":{"visibility":"hidden"}}',
            '{"wrong_id":{"visibility":"hidden"}}'
        );

        static::assertStringContainsString('The selected choice is invalid.', $crawler->html());
        static::assertStringContainsString('VisibilityChangeSet', $crawler->html());
    }

    /**
     * @depends testEdit
     */
    public function testDeleteVisibilityOnSetDefault(): void
    {
        $manager = $this->client->getContainer()->get('doctrine');

        self::assertNotNull(
            $this->getCategoryVisibility($manager, $this->category)->getId()
        );

        self::assertNotNull(
            $this->getCategoryVisibilityForCustomer($manager, $this->category, $this->customer)->getId()
        );

        self::assertNotNull(
            $this->getCategoryVisibilityForCustomerGroup($manager, $this->category, $this->group)->getId()
        );

        $this->submitForm(
            CategoryVisibility::getDefault($this->category),
            json_encode(
                [
                    $this->customer->getId() => [
                        'visibility' => CustomerCategoryVisibility::getDefault($this->category)
                    ]
                ],
                JSON_THROW_ON_ERROR
            ),
            json_encode(
                [
                    $this->group->getId() => [
                        'visibility' => CustomerGroupCategoryVisibility::getDefault($this->category),
                    ]
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertNull(
            $this->getCategoryVisibility($manager, $this->category)->getId()
        );

        self::assertNull(
            $this->getCategoryVisibilityForCustomer($manager, $this->category, $this->customer)->getId()
        );

        self::assertNull(
            $this->getCategoryVisibilityForCustomerGroup($manager, $this->category, $this->group)->getId()
        );
    }

    /**
     * @dataProvider dataProviderForNotExistingCategories
     */
    public function testControllerActionWithNotExistingCategoryId(int|string $categoryId): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
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
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function dataProviderForNotExistingCategories(): array
    {
        return [
            [99999],
            ['99999'],
            ['dummy-string'],
            [''],
        ];
    }

    public function testControllerActionWithExistingButInvisibleCategory(): void
    {
        $this->category = $this->getReference(LoadCategoryData::THIRD_LEVEL2);

        $categoryId = $this->category->getId();

        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
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
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    private function submitForm(
        string $categoryVisibility,
        string $visibilityForCustomer,
        string $visibilityForCustomerGroup
    ): Crawler {
        $this->client->followRedirects();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $this->category->getId()])
        );
        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, 200);
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
        $parameters['input_action'] = '{"route": "oro_catalog_category_index"}';

        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_catalog_category_update', ['id' => $this->category->getId()]),
            $parameters
        );

        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, 200);

        return $crawler;
    }

    private function explodeArrayPaths(array $values): array
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $parameters = [];
        foreach ($values as $key => $val) {
            $pos = strpos($key, '[');
            if (!$pos) {
                continue;
            }
            $key = '['.substr($key, 0, $pos).']'.substr($key, $pos);
            $accessor->setValue($parameters, $key, $val);
        }

        return $parameters;
    }

    private function getChangeSetData(Crawler $crawler, string $changeSetId): array
    {
        $data = $crawler->filterXPath(sprintf('//input[@id="%s"]/@value', $changeSetId))->text();

        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }

    private function checkVisibilityValue(array $data, string $id, string $visibility): void
    {
        foreach ($data as $key => $item) {
            if ($key === $id) {
                self::assertEquals($visibility, $item['visibility']);

                return;
            }
        }
    }
}
