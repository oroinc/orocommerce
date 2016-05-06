<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData as TestFixturesLoadWebsiteData;

/**
 * @dbIsolation
 */
class ProductVisibilityControllerTest extends WebTestCase
{
    const ALL_KEY = 'all';
    const ACCOUNT_KEY = 'account';
    const ACCOUNT_GROUP_KEY = 'accountGroup';

    const VISIBILITY_KEY = 'visibility';
    const VISIBLE = 'visible';
    const HIDDEN = 'hidden';

    const PRODUCT_VISIBILITY_CLASS = 'OroB2BAccountBundle:Visibility\ProductVisibility';
    const ACCOUNT_PRODUCT_VISIBILITY_CLASS = 'OroB2BAccountBundle:Visibility\AccountProductVisibility';
    const ACCOUNT_GROUP_PRODUCT_VISIBILITY_CLASS = 'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility';

    /** @var  Product */
    protected $product;

    /** @var  Account */
    protected $account;

    /** @var  AccountGroup */
    protected $group;

    /** @var  integer[] */
    protected $websiteIds = [];

    /** @var  string */
    protected $visibilityToAllDefaultWebsite;

    /** @var  string */
    protected $visibilityToAccountDefaultWebsite;

    /** @var  string */
    protected $visibilityToAccountGroupDefaultWebsite;

    /** @var  string */
    protected $visibilityToAllNotDefaultWebsite;

    /** @var  string */
    protected $visibilityToAccountNotDefaultWebsite;

    /** @var  string */
    protected $visibilityToAccountGroupNotDefaultWebsite;

    /** @var  array */
    protected $visibilityClassNames = [
        self::PRODUCT_VISIBILITY_CLASS,
        self::ACCOUNT_PRODUCT_VISIBILITY_CLASS,
        self::ACCOUNT_GROUP_PRODUCT_VISIBILITY_CLASS,
    ];

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            ]
        );

        $this->product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);
        $this->group = $this->getReference(LoadGroups::GROUP1);
        $this->visibilityToAllDefaultWebsite = self::VISIBLE;
        $this->visibilityToAccountDefaultWebsite = json_encode(
            [$this->account->getId() => [self::VISIBILITY_KEY => self::VISIBLE]]
        );
        $this->visibilityToAccountGroupDefaultWebsite = json_encode(
            [$this->group->getId() => [self::VISIBILITY_KEY => self::VISIBLE]]
        );

        $this->visibilityToAllNotDefaultWebsite = self::HIDDEN;
        $this->visibilityToAccountNotDefaultWebsite = json_encode(
            [$this->account->getId() => [self::VISIBILITY_KEY => self::HIDDEN]]
        );
        $this->visibilityToAccountGroupNotDefaultWebsite = json_encode(
            [$this->group->getId() => [self::VISIBILITY_KEY => self::HIDDEN]]
        );
    }

    public function tearDown()
    {
        $this->client->getContainer()->get('doctrine')->getManager()->clear();
        parent::tearDown();
    }

    public function testUpdate()
    {
        $this->submitForm();
        $crawler = $this->getVisibilityPage();
        $form = $crawler->selectButton('Save and Close')->form();
        $parameters = $this->explodeArrayPaths($form->getValues());

        //first tab checks
        $defaultWebsiteParams = $parameters[WebsiteScopedDataType::NAME][$this->websiteIds[0]];
        $this->assertEquals(
            $defaultWebsiteParams[self::ALL_KEY],
            $this->visibilityToAllDefaultWebsite
        );
        $this->assertEquals(
            $defaultWebsiteParams[self::ACCOUNT_KEY],
            $this->visibilityToAccountDefaultWebsite
        );
        $this->assertEquals(
            $defaultWebsiteParams[self::ACCOUNT_GROUP_KEY],
            $this->visibilityToAccountGroupDefaultWebsite
        );

        //second tab checks
        $notDefaultWebsiteVisibilities = $this->getSelectedVisibilitiesThroughAjax(
            $this->product->getId(),
            $this->websiteIds[1]
        );
        $this->assertEquals($notDefaultWebsiteVisibilities[self::ALL_KEY], $this->visibilityToAllNotDefaultWebsite);
        $this->assertEquals(
            $notDefaultWebsiteVisibilities[self::ACCOUNT_KEY],
            $this->visibilityToAccountNotDefaultWebsite
        );
        $this->assertEquals(
            $notDefaultWebsiteVisibilities[self::ACCOUNT_GROUP_KEY],
            $this->visibilityToAccountGroupNotDefaultWebsite
        );
    }

    /**
     * @depends testUpdate
     */
    public function testDuplicateProduct()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->client->followRedirects(true);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => 'orob2b_product_duplicate',
                    'route' => 'orob2b_product_view',
                    'entityId' => $this->product->getId(),
                    'entityClass' => 'OroB2B\Bundle\ProductBundle\Entity\Product'
                ]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        /** @var EntityManager $em */
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $duplicatedProduct = $em->getRepository('OroB2BProductBundle:Product')
            ->findOneBySku(sprintf('%s-1', $this->product->getSku()));
        foreach ($this->visibilityClassNames as $className) {
            /** @var VisibilityInterface[]|WebsiteAwareInterface[] $visibilities */
            $visibilities = $em->getRepository($className)->findBy(['product' => $duplicatedProduct]);
            $this->assertCount(2, $visibilities);

            foreach ($visibilities as $duplicatedVisibility) {
                /** @var VisibilityInterface $visibilitySourceProduct */
                $visibilitySourceProduct = $em->getRepository($className)
                    ->findOneBy(['product' => $this->product, 'website' => $duplicatedVisibility->getWebsite()]);
                $this->assertEquals($visibilitySourceProduct->getVisibility(), $duplicatedVisibility->getVisibility());
            }
        }
    }

    /**
     * @depends testDuplicateProduct
     */
    public function testDeleteVisibilityOnSetDefault()
    {
        $this->assertCountVisibilities(2, $this->product);

        $this->visibilityToAllDefaultWebsite = ProductVisibility::getDefault($this->product);
        $this->visibilityToAccountDefaultWebsite = json_encode(
            [$this->account->getId() => [self::VISIBILITY_KEY => AccountProductVisibility::getDefault($this->product)]]
        );
        $this->visibilityToAccountGroupDefaultWebsite = json_encode(
            [
                $this->group->getId() => [
                    self::VISIBILITY_KEY => AccountGroupProductVisibility::getDefault($this->product),
                ],
            ]
        );
        $this->submitForm();
        $this->assertCountVisibilities(1, $this->product);
    }

    /**
     * @param integer $count
     * @param Product $product
     */
    protected function assertCountVisibilities($count, Product $product)
    {
        /** @var EntityManager $em */
        $em = $this->client->getContainer()->get('doctrine')->getManager();

        foreach ($this->visibilityClassNames as $className) {
            $actualCount = (int)$em->getRepository($className)
                ->createQueryBuilder('entity')
                ->select('COUNT(entity.id)')
                ->where('entity.product = :product')
                ->setParameter('product', $product)
                ->getQuery()
                ->getSingleScalarResult();
            $this->assertEquals($count, $actualCount);
        }
    }

    /**
     * @return Crawler
     */
    protected function submitForm()
    {
        $crawler = $this->getVisibilityPage();

        $websiteTitles = [
            LoadWebsiteData::DEFAULT_WEBSITE_NAME,
            TestFixturesLoadWebsiteData::WEBSITE1,
            TestFixturesLoadWebsiteData::WEBSITE2,
            TestFixturesLoadWebsiteData::WEBSITE3
        ];
        $counter = 0;

        // Test exits tabs
        $crawler->filter('.oro-tabs ul li.tab')->each(
            function (Crawler $node) use (&$counter, $websiteTitles) {
                $this->assertContains($websiteTitles[$counter], $node->text());
                $options = json_decode($node->filterXPath('//a/@data-page-component-options')->text(), true);
                $this->websiteIds[] = $options['options']['alias'];
                $counter++;
            }
        );
        $parameters[WebsiteScopedDataType::NAME] = [
            $this->websiteIds[0] => [
                self::ALL_KEY => $this->visibilityToAllDefaultWebsite,
                self::ACCOUNT_KEY => $this->visibilityToAccountDefaultWebsite,
                self::ACCOUNT_GROUP_KEY => $this->visibilityToAccountGroupDefaultWebsite,
            ],
            $this->websiteIds[1] => [
                self::ALL_KEY => $this->visibilityToAllNotDefaultWebsite,
                self::ACCOUNT_KEY => $this->visibilityToAccountNotDefaultWebsite,
                self::ACCOUNT_GROUP_KEY => $this->visibilityToAccountGroupNotDefaultWebsite,
            ],
            '_token' => $crawler->filterXPath(
                sprintf('//input[@name="%s[_token]"]/@value', WebsiteScopedDataType::NAME)
            )
                ->text(),
        ];
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('orob2b_product_visibility_edit', ['id' => $this->product->getId()]),
            $parameters
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        return $crawler;
    }

    /**
     * @param $productId
     * @param $websiteId
     * @return array
     */
    protected function getSelectedVisibilitiesThroughAjax($productId, $websiteId)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_product_visibility_website',
                ['productId' => $productId, 'id' => $websiteId]
            ),
            ['_widgetContainer' => 'widget']
        );

        return [
            self::ALL_KEY => $crawler
                ->filterXPath(
                    sprintf(
                        '//select[@name="%s[%d][all]"]/option[@selected]/@value',
                        WebsiteScopedDataType::NAME,
                        $websiteId
                    )
                )
                ->text(),
            self::ACCOUNT_KEY => $this->getChangeSetRawData(
                $crawler,
                sprintf('account-product-visibility-changeset-%d', $websiteId)
            ),
            self::ACCOUNT_GROUP_KEY => $this->getChangeSetRawData(
                $crawler,
                sprintf('accountgroup-product-visibility-changeset-%d', $websiteId)
            ),
        ];
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
     * @return string
     */
    protected function getChangeSetRawData(Crawler $crawler, $changeSetId)
    {
        return $crawler->filterXPath(
            sprintf('//input[@id="%s"]/@value', $changeSetId)
        )->text();
    }

    /**
     * @return Crawler
     */
    protected function getVisibilityPage()
    {
        $this->client->followRedirects();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_product_visibility_edit', ['id' => $this->product->getId()])
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        return $crawler;
    }
}
