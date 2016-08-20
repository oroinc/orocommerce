<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;

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

    const PRODUCT_VISIBILITY_CLASS = 'OroAccountBundle:Visibility\ProductVisibility';
    const ACCOUNT_PRODUCT_VISIBILITY_CLASS = 'OroAccountBundle:Visibility\AccountProductVisibility';
    const ACCOUNT_GROUP_PRODUCT_VISIBILITY_CLASS = 'OroAccountBundle:Visibility\AccountGroupProductVisibility';

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

    /** @var int */
    protected $defaultWebsiteId;

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
                'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
                'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
                'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            ]
        );

        $this->defaultWebsiteId = $this->getDefaultWebsite()->getId();

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
        $defaultWebsiteParams = $parameters[WebsiteScopedDataType::NAME][$this->defaultWebsiteId];
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
                    'entityClass' => 'Oro\Bundle\ProductBundle\Entity\Product'
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
        $duplicatedProduct = $em->getRepository('OroProductBundle:Product')
            ->findOneBySku(sprintf('%s-1', $this->product->getSku()));
        foreach ($this->visibilityClassNames as $className) {
            /** @var VisibilityInterface[]|WebsiteAwareInterface[] $visibilities */
            $visibilities = $em->getRepository($className)->findBy(['product' => $duplicatedProduct]);
            $this->assertCount(1, $visibilities);

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
        $this->assertCountVisibilities(1, $this->product);

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
        $this->assertCountVisibilities(0, $this->product);
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

        $parameters[WebsiteScopedDataType::NAME] = [
            $this->defaultWebsiteId => [
                self::ALL_KEY => $this->visibilityToAllDefaultWebsite,
                self::ACCOUNT_KEY => $this->visibilityToAccountDefaultWebsite,
                self::ACCOUNT_GROUP_KEY => $this->visibilityToAccountGroupDefaultWebsite,
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

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroWebsiteBundle:Website')
            ->getRepository('OroWebsiteBundle:Website')
            ->getDefaultWebsite();
    }
}
