<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Tests\DataFixtures\LoadScopeData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

/**
 * @dbIsolation
 */
class ProductVisibilityControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadProductVisibilityData::class,
                LoadScopeData::class
            ]
        );
    }

    public function testUpdate()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        //load product visibility page
        $scope = $this->getClient()->getContainer()->get('oro_scope.scope_manager')->findDefaultScope();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_visibility_edit', ['id' => $product->getId()])
        );
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $form = $crawler->selectButton('Save and Close')->form();

        /** @var ChoiceFormField $allForm */
        $allForm = $form['oro_scoped_data_type'][$scope->getId()]['all'];
        /** @var ChoiceFormField $accountForm */
        $accountForm = $form['oro_scoped_data_type'][$scope->getId()]['account'];
        /** @var ChoiceFormField $accountGroupForm */
        $accountGroupForm = $form['oro_scoped_data_type'][$scope->getId()]['accountGroup'];

        // assert form data is set correct with loaded fixtures
        $this->assertSame('config', $allForm->getValue(), 'visibility to all');
        $this->assertSame(
            json_encode([$this->getReference('account.level_1')->getId() => ['visibility' => 'visible']]),
            $accountForm->getValue(),
            'account visibility'
        );
        $this->assertSame(
            json_encode([$this->getReference(LoadGroups::GROUP1)->getId() => ['visibility' => 'hidden']]),
            $accountGroupForm->getValue(),
            'account group visibility'
        );

        // submit form with new values
        $this->client->submit(
            $form,
            [
                'oro_scoped_data_type' => [
                    $scope->getId() => [
                        'all' => 'hidden',
                        'account' => json_encode(
                            [
                                $this->getReference('account.level_1')->getId() => ['visibility' => 'hidden'],
                                $this->getReference('account.level_1.2')->getId() => ['visibility' => 'visible'],
                            ]
                        ),
                        'accountGroup' => json_encode(
                            [
                                $this->getReference(LoadGroups::GROUP1)->getId() =>
                                    ['visibility' => AccountGroupProductVisibility::getDefault($product)],
                                $this->getReference(LoadGroups::GROUP2)->getId() => ['visibility' => 'visible'],
                            ]
                        ),
                    ],
                ],
            ]
        );

        $em = $this->getClient()->getContainer()->get('doctrine')->getManager();

        // assert product visibility to all saved properly
        $pv = $em->getRepository(ProductVisibility::class)->findBy(['product' => $product]);
        $this->assertCount(1, $pv);
        $this->assertSame('hidden', reset($pv)->getVisibility());

        // assert account group product visibility saved properly
        $agpv = $em->getRepository(AccountGroupProductVisibility::class)
            ->findBy(['product' => $product], ['id' => 'ASC']);
        $this->assertCount(1, $agpv);
        $this->assertVisibilityEntity(
            AccountGroupProductVisibility::class,
            'visible',
            ['accountGroup' => $this->getReference(LoadGroups::GROUP2)],
            $product
        );

        // assert account product visibility saved properly
        $apv = $em->getRepository(AccountProductVisibility::class)->findBy(['product' => $product], ['id' => 'ASC']);
        $this->assertCount(2, $apv);
        $this->assertVisibilityEntity(
            AccountProductVisibility::class,
            'hidden',
            ['account' => $this->getReference('account.level_1')],
            $product
        );
        $this->assertVisibilityEntity(
            AccountProductVisibility::class,
            'visible',
            ['account' => $this->getReference('account.level_1.2')],
            $product
        );
    }

    /**
     * @param string $class
     * @param string $value
     * @param array $context
     * @param Product $product
     */
    protected function assertVisibilityEntity($class, $value, array $context, Product $product)
    {
        $em = $this->getClient()->getContainer()->get('doctrine')->getManager();
        $type = call_user_func([$class, 'getScopeType']);
        $scope = $this->getClient()->getContainer()->get('oro_scope.scope_manager')->findOrCreate($type, $context);
        /** @var VisibilityInterface $visibility */
        $visibility = $em->getRepository($class)->findOneBy(['product' => $product, 'scope' => $scope]);
        $this->assertNotNull($visibility, sprintf("%s entity missing for expected value %s", $class, $value));
        $this->assertSame($value, $visibility->getVisibility());
    }

    public function testScopeWidgetAction()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $scope = $this->getClient()->getContainer()->get('oro_scope.scope_manager')->findDefaultScope();

        //load widget
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_visibility_scoped', ['productId' => $product->getId(), 'id' => $scope->getId()])
        );
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        // assert form data is set correct with loaded fixtures
        $this->assertSame(
            'visible',
            $crawler->filter(sprintf('[name = "oro_scoped_data_type[%s][all]"] option[selected]', $scope->getId()))
                ->attr('value'),
            'visibility to all'
        );
        $this->assertSame(
            null,
            $crawler->filter(sprintf('[name = "oro_scoped_data_type[%s][account]"]', $scope->getId()))
                ->attr('value'),
            'account visibility form data'
        );
        $this->assertSame(
            json_encode([$this->getReference(LoadGroups::GROUP1)->getId() => ['visibility' => 'hidden']]),
            $crawler->filter(sprintf('[name = "oro_scoped_data_type[%s][accountGroup]"]', $scope->getId()))
                ->attr('value'),
            'account group visibility form data'
        );
    }
}
