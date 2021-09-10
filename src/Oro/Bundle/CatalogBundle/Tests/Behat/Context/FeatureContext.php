<?php

namespace Oro\Bundle\CatalogBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Checks, that category elements displayed on the page in the same order
     * Example: I should see following categories in same order:
     *            | NewCategory  |
     *            | NewCategory2 |
     *            | NewCategory3 |
     *
     * @Then /^(?:|I )should see following categories in same order:$/
     */
    public function iShouldSeeCategoriesInSameOrder(TableNode $table)
    {
        $categoryElements = $this
            ->getPage()
            ->findAll(
                'css',
                'h1.category-title--divide-content'
            );
        $expectedCategories = $table->getColumn(0);
        self::assertNotEmpty(
            $categoryElements,
            sprintf(
                'Expected that next categories "%s" will be on page, but none categories found !',
                implode('","', $expectedCategories)
            )
        );
        $categories = array_map(function (NodeElement $categoryElement) {
            return $categoryElement->getText();
        }, $categoryElements);
        self::assertEquals(
            $expectedCategories,
            $categories,
            sprintf(
                'Expected that next categories "%s" will be on page in same order, but found "%s" !',
                implode('","', $expectedCategories),
                implode('","', $categories)
            )
        );
    }

    /**
     * @Then /^(?:|I )should see "(?P<text>[^"]*)" for "(?P<Name>[^"]*)" category$/
     */
    public function shouldSeeForCategory($text, $Name)
    {
        $categoryItem = $this->findElementContains('CategoryItem', $Name);
        self::assertNotNull($categoryItem, sprintf('product with SKU "%s" not found', $Name));

        if ($this->isElementVisible($text, $categoryItem)) {
            return;
        }

        self::assertNotFalse(
            stripos($categoryItem->getText(), $text),
            sprintf('text or element "%s" for product with SKU "%s" is not present or not visible', $text, $Name)
        );
    }

    /**
     * @Then /^(?:|I )should not see "(?P<text>[^"]*)" for "(?P<Name>[^"]*)" category$/
     */
    public function shouldNotSeeForCategory($text, $Name)
    {
        $categoryItem = $this->findElementContains('CategoryItem', $Name);
        self::assertNotNull($categoryItem, sprintf('product with SKU "%s" not found', $Name));

        $textAndElementPresentedOnPage = $this->isElementVisible($text, $categoryItem)
            || stripos($categoryItem->getText(), $text);

        self::assertFalse(
            $textAndElementPresentedOnPage,
            sprintf('text or element "%s" for product with SKU "%s" is present or visible', $text, $Name)
        );
    }

    /**
     * @Then /^tree level of category "(?P<title>[^"]*)" is (?P<level>[0-9]+)$/
     */
    public function treeLevelOfCategoryIs(string $title, int $level): void
    {
        $doctrine = $this->getAppContainer()->get('doctrine');
        $manager = $doctrine->getManagerForClass(Category::class);

        /** @var Organization $organization */
        $organization = $doctrine
            ->getManagerForClass(Organization::class)
            ->getRepository(Organization::class)
            ->getFirst();

        $category = $this->getCategory($manager, $title, $organization);
        $this->assertEquals($level, $category->getLevel());
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getCategory(ObjectManager $manager, string $title, OrganizationInterface $organization): Category
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $manager->getRepository(Category::class)->findOneByDefaultTitleQueryBuilder($title);
        $queryBuilder
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $organization);

        $category = $queryBuilder->getQuery()->getOneOrNullResult();
        $this->assertNotNull($category, sprintf('Category %s not found', $category));
        $manager->refresh($category);

        return $category;
    }

    /**
     * @Then /^I assert canonical URL for "(?P<categoryName>.+)" category is a System URL including subcategories$/
     *
     * @param string $categoryName
     */
    public function assertCanonicalUrlForCategoryIncludingSubcategories($categoryName)
    {
        $this->assertCanonicalUrlForCategory($categoryName, 1);
    }

    /**
     * @Then /^I assert canonical URL for "(?P<categoryName>.+)" category is a System URL not including subcategories$/
     *
     * @param string $categoryName
     */
    public function assertCanonicalUrlForCategoryNotIncludingSubcategories($categoryName)
    {
        $this->assertCanonicalUrlForCategory($categoryName, 0);
    }

    private function assertCanonicalUrlForCategory(string $categoryName, int $includingSubcategories)
    {
        /** @var ObjectManager $manager */
        $manager = $this->getAppContainer()->get('doctrine')->getManagerForClass(Category::class);
        /** @var Category $category */
        $category = $manager->getRepository(Category::class)->findOneBy(['denormalizedDefaultTitle' => $categoryName]);

        $canonicalElement = $this->createElement('Canonical URL');

        static::assertEquals(
            sprintf(
                '%s/product/?categoryId=%s&includeSubcategories=%d',
                $this->getCurrentApplicationUrl(),
                $category->getId(),
                $includingSubcategories
            ),
            $canonicalElement->getAttribute('href')
        );
    }

    /**
     * @return string
     */
    private function getCurrentApplicationUrl()
    {
        $currentUrl = $this->getSession()->getCurrentUrl();
        $scheme = parse_url($currentUrl, PHP_URL_SCHEME);
        $host = parse_url($currentUrl, PHP_URL_HOST);
        $port = parse_url($currentUrl, PHP_URL_PORT);
        if ($port) {
            $port = ':' . $port;
        }
        return sprintf('%s://%s%s', $scheme, $host, $port);
    }
}
