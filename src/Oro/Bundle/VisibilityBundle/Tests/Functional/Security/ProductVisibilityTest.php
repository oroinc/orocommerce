<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Security;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;

/**
 * @group CommunityEdition
 * @dbIsolation
 */
class ProductVisibilityTest extends WebTestCase
{
    const VISIBILITY_SYSTEM_CONFIGURATION_PATH = 'oro_visibility.product_visibility';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadProductVisibilityData::class,
        ]);
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
    }

    /**
     * @dataProvider visibilityDataProvider
     *
     * @param string $configValue
     * @param array $expectedData
     */
    public function testVisibility($configValue, $expectedData)
    {
        $this->initUser();
        $configManager = $this->getClientInstance()->getContainer()->get('oro_config.global');
        $configManager->set(self::VISIBILITY_SYSTEM_CONFIGURATION_PATH, $configValue);
        $configManager->flush();
        foreach ($expectedData as $productSKU => $resultCode) {
            $product = $this->getReference($productSKU);
            $this->assertInstanceOf(Product::class, $product);
            /** @var EntityManager $em */
            $em = $this->getContainer()->get('doctrine')->getManager();
            $apv = $em->createQueryBuilder()->select('v, s')->from(AccountProductVisibility::class, 'v')->join(
                'v.scope',
                's'
            )->where('v.product = :p')->setParameter('p', $product)->getQuery()->getResult();
            $agpv = $em->createQueryBuilder()->select('v, s')->from(AccountGroupProductVisibility::class, 'v')->join(
                'v.scope',
                's'
            )->where('v.product = :p')->setParameter('p', $product)->getQuery()->getResult();
            $pv = $em->createQueryBuilder()->select('v, s')->from(ProductVisibility::class, 'v')->join(
                'v.scope',
                's'
            )->where('v.product = :p')->setParameter('p', $product)->getQuery()->getResult();
            $this->client->request(
                'GET',
                $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()])
            );
            $response = $this->client->getResponse();
            $this->assertSame($response->getStatusCode(), $resultCode, $productSKU);
        }
    }

    /**
     * @return array
     */
    public function visibilityDataProvider()
    {
        return [
            'config visible' => [
                'configValue' => ProductVisibility::VISIBLE,
                'expectedData' => [
                    LoadProductData::PRODUCT_1 => 200,
                    LoadProductData::PRODUCT_2 => 403,
                    LoadProductData::PRODUCT_3 => 403,
                    LoadProductData::PRODUCT_4 => 403,
                    LoadProductData::PRODUCT_5 => 403,
                    LoadProductData::PRODUCT_6 => 200,
                ],
            ],
//            'config hidden' => [
//                'configValue' => ProductVisibility::HIDDEN,
//                'expectedData' => [
//                    LoadProductData::PRODUCT_1 => 403,
//                    LoadProductData::PRODUCT_2 => 200,
//                    LoadProductData::PRODUCT_3 => 200,
//                    LoadProductData::PRODUCT_4 => 403,
//                    LoadProductData::PRODUCT_5 => 403,
//                ]
//            ],
        ];
    }

    protected function tearDown()
    {
        $configManager = $this->getClientInstance()->getContainer()->get('oro_config.global');
        $configManager->set(self::VISIBILITY_SYSTEM_CONFIGURATION_PATH, ProductVisibility::VISIBLE);
        $configManager->flush();
    }

    protected function initUser()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        /** @var AccountUser $user */
        $user = $em->getRepository(AccountUser::class)
            ->findOneBy(['email' => LoadAccountUserData::AUTH_USER]);
        $user->setAccount($this->getReference('account.level_1'));
        $em->persist($user);
        $em->flush();
    }
}
