<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;

/**
 * @dbIsolation
 */
class CategoryControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            ]
        );
    }

    public function testEdit()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        /** @var Account $account */
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);
        /** @var Group $group */
        $group = $this->getReference(LoadGroups::GROUP1);

        $this->client->followRedirects();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_catalog_category_update', ['id' => $category->getId()])
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $form = $crawler->selectButton('Save and Close')->form();
        $parameters = $this->explodeArrayPaths($form->getValues());
        $token = $crawler->filterXPath('//input[@name="orob2b_catalog_category[_token]"]/@value')->text();

        $catalogVisibility = CategoryVisibility::HIDDEN;
        $visibilityForAccount = AccountCategoryVisibility::VISIBLE;
        $visibilityForAccountGroup = AccountGroupCategoryVisibility::VISIBLE;

        $parameters['orob2b_catalog_category'] = array_merge(
            [
                '_token' => $token,
                'categoryVisibility' => $catalogVisibility,
                'visibilityForAccount' => json_encode([$account->getId() => ['visibility' => $visibilityForAccount]]),
                'visibilityForAccountGroup' => json_encode(
                    [$group->getId() => ['visibility' => $visibilityForAccountGroup]]
                ),
            ],
            $parameters['orob2b_catalog_category']
        );

        $crawler = $this->client->request(
            'POST',
            $this->getUrl('orob2b_catalog_category_update', ['id' => $category->getId()]),
            $parameters
        );

        $this->assertNotContains($crawler->html(), 'grid-account-category-visibility-grid');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_catalog_category_update', ['id' => $category->getId()])
        );

        $selectedCatalogVisibility = $crawler->filterXPath(
            '//select[@name="orob2b_catalog_category[categoryVisibility]"]/option[@selected]/@value'
        )->text();

        $this->assertEquals($catalogVisibility, $selectedCatalogVisibility);

        $accountGroupCategoryVisibilityData = $this->getChangeSetData(
            $crawler,
            'account-category-visibility-changeset'
        );
        $this->checkVisibilityValue($accountGroupCategoryVisibilityData, $group->getId(), $visibilityForAccountGroup);

        $accountCategoryVisibilityData = $this->getChangeSetData(
            $crawler,
            'accountgroup-category-visibility-changeset'
        );
        $this->checkVisibilityValue($accountCategoryVisibilityData, $account->getId(), $visibilityForAccount);
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
