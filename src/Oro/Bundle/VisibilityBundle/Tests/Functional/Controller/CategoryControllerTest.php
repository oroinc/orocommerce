<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;

/**
 * @dbIsolation
 */
class CategoryControllerTest extends WebTestCase
{
    /** @var Category */
    protected $category;

    /** @var  Account */
    protected $account;

    /** @var AccountGroup */
    protected $group;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            ]
        );

        $this->category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $this->account = $this->getReference('account.level_1');
        $this->group = $this->getReference(LoadGroups::GROUP1);
    }

    public function testEdit()
    {
        $categoryVisibility = CategoryVisibility::HIDDEN;
        $visibilityForAccount = AccountCategoryVisibility::VISIBLE;
        $visibilityForAccountGroup = AccountGroupCategoryVisibility::VISIBLE;

        $crawler = $this->submitForm(
            $categoryVisibility,
            json_encode([$this->account->getId() => ['visibility' => $visibilityForAccount]]),
            json_encode([$this->group->getId() => ['visibility' => $visibilityForAccountGroup]])
        );

        $this->assertNotContains('grid-account-category-visibility-grid', $crawler->html());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $this->category->getId()])
        );

        $selectedCatalogVisibility = $crawler
            ->filterXPath('//select[@name="oro_catalog_category[visibility][all]"]/option[@selected]/@value')
            ->text();

        $this->assertEquals($categoryVisibility, $selectedCatalogVisibility);

        $accountGroupCategoryVisibilityData = $this->getChangeSetData(
            $crawler,
            'accountgroup-category-visibility-changeset'
        );

        $this->checkVisibilityValue(
            $accountGroupCategoryVisibilityData,
            $this->group->getId(),
            $visibilityForAccountGroup
        );

        $accountCategoryVisibilityData = $this->getChangeSetData(
            $crawler,
            'account-category-visibility-changeset'
        );

        $this->checkVisibilityValue($accountCategoryVisibilityData, $this->account->getId(), $visibilityForAccount);
    }

    public function testSubmitInvalidData()
    {
        $crawler = $this->submitForm(
            'wrong Visibility',
            '{"wrong_id":{"visibility":"hidden"}}',
            '{"wrong_id":{"visibility":"hidden"}}'
        );

        $this->assertContains('This value is not valid', $crawler->html());
        $this->assertContains('invalidDataMessage', $crawler->html());
    }

    /**
     * @depends testEdit
     */
    public function testDeleteVisibilityOnSetDefault()
    {
        /** @var EntityManager $em */
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $categoryVisibilityRepo = $em->getRepository(
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility'
        );
        $accountCategoryVisibilityRepo = $em->getRepository(
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility'
        );
        $accountGroupCategoryVisibilityRepo = $em->getRepository(
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility'
        );

        $this->assertNotEquals(0, $this->getEntitiesCount($categoryVisibilityRepo));
        $this->assertNotEquals(0, $this->getEntitiesCount($accountCategoryVisibilityRepo));
        $this->assertNotEquals(0, $this->getEntitiesCount($accountGroupCategoryVisibilityRepo));

        $this->submitForm(
            CategoryVisibility::getDefault($this->category),
            json_encode(
                [$this->account->getId() => ['visibility' => AccountCategoryVisibility::getDefault($this->category)]]
            ),
            json_encode(
                [$this->group->getId() => ['visibility' => AccountGroupCategoryVisibility::getDefault($this->category)]]
            )
        );

        $this->assertEquals(0, $this->getEntitiesCount($categoryVisibilityRepo));
        $this->assertEquals(0, $this->getEntitiesCount($accountCategoryVisibilityRepo));
        $this->assertEquals(0, $this->getEntitiesCount($accountGroupCategoryVisibilityRepo));
    }

    /**
     * @param EntityRepository $repository
     * @return int
     */
    protected function getEntitiesCount(EntityRepository $repository)
    {
        return (int)$repository->createQueryBuilder('entity')
            ->select('COUNT(entity.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param string $categoryVisibility
     * @param string $visibilityForAccount
     * @param string $visibilityForAccountGroup
     * @return Crawler
     */
    protected function submitForm($categoryVisibility, $visibilityForAccount, $visibilityForAccountGroup)
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

        $parameters['oro_catalog_category'] = array_merge(
            $parameters['oro_catalog_category'],
            [
                '_token' => $token,
                'visibility' => [
                    'all' => $categoryVisibility,
                    'account' => $visibilityForAccount,
                    'accountGroup' => $visibilityForAccountGroup,
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
