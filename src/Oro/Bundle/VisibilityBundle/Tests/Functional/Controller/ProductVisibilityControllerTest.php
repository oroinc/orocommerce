<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Controller;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadAnonymousCustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

class ProductVisibilityControllerTest extends WebTestCase
{
    protected function setUp(): void
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
        $scope = $this->client->getContainer()->get('oro_scope.scope_manager')->findDefaultScope();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_visibility_edit', ['id' => $product->getId()])
        );
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $form = $crawler->selectButton('Save and Close')->form();
        $redirectAction = $crawler->selectButton('Save and Close')->attr('data-action');

        /** @var ChoiceFormField $allForm */
        $allForm = $form['oro_scoped_data_type'][$scope->getId()]['all'];
        /** @var ChoiceFormField $customerForm */
        $customerForm = $form['oro_scoped_data_type'][$scope->getId()]['customer'];
        /** @var ChoiceFormField $customerGroupForm */
        $customerGroupForm = $form['oro_scoped_data_type'][$scope->getId()]['customerGroup'];

        // assert form data is set correct with loaded fixtures
        $this->assertSame('config', $allForm->getValue(), 'visibility to all');
        $this->assertSame(
            json_encode([
                $this->getReference('customer.level_1')->getId() => ['visibility' => 'visible']
            ], JSON_THROW_ON_ERROR),
            $customerForm->getValue(),
            'customer visibility'
        );
        $this->assertSame(
            json_encode([
                $this->getReference(LoadGroups::GROUP1)->getId() => ['visibility' => 'hidden']
            ], JSON_THROW_ON_ERROR),
            $customerGroupForm->getValue(),
            'customer group visibility'
        );

        // submit form with new values
        $this->client->submit(
            $form,
            [
                'oro_scoped_data_type' => [
                    $scope->getId() => [
                        'all' => 'hidden',
                        'customer' => json_encode([
                            $this->getReference('customer.level_1')->getId() => ['visibility' => 'hidden'],
                            $this->getReference('customer.level_1.2')->getId() => ['visibility' => 'visible'],
                        ], JSON_THROW_ON_ERROR),
                        'customerGroup' => json_encode([
                            $this->getReference(LoadGroups::GROUP1)->getId() =>
                                ['visibility' => CustomerGroupProductVisibility::getDefault($product)],
                            $this->getReference(LoadGroups::GROUP2)->getId() => ['visibility' => 'visible'],
                        ], JSON_THROW_ON_ERROR),
                    ],
                ],
                'input_action' => $redirectAction
            ]
        );

        $em = $this->client->getContainer()->get('doctrine')->getManager();

        // assert product visibility to all saved properly
        $pv = $em->getRepository(ProductVisibility::class)->findBy(['product' => $product]);
        $this->assertCount(1, $pv);
        $this->assertSame('hidden', reset($pv)->getVisibility());

        // assert customer group product visibility saved properly
        $agpv = $em->getRepository(CustomerGroupProductVisibility::class)
            ->findBy(['product' => $product], ['id' => 'ASC']);
        $this->assertCount(1, $agpv);
        $this->assertVisibilityEntity(
            CustomerGroupProductVisibility::class,
            'visible',
            ['customerGroup' => $this->getReference(LoadGroups::GROUP2)],
            $product
        );

        // assert customer product visibility saved properly
        $apv = $em->getRepository(CustomerProductVisibility::class)->findBy(['product' => $product], ['id' => 'ASC']);
        $this->assertCount(2, $apv);
        $this->assertVisibilityEntity(
            CustomerProductVisibility::class,
            'hidden',
            ['customer' => $this->getReference('customer.level_1')],
            $product
        );
        $this->assertVisibilityEntity(
            CustomerProductVisibility::class,
            'visible',
            ['customer' => $this->getReference('customer.level_1.2')],
            $product
        );
    }

    private function assertVisibilityEntity(string $class, string $value, array $context, Product $product): void
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $type = call_user_func([$class, 'getScopeType']);
        $scope = $this->client->getContainer()->get('oro_scope.scope_manager')->findOrCreate($type, $context);
        /** @var VisibilityInterface $visibility */
        $visibility = $em->getRepository($class)->findOneBy(['product' => $product, 'scope' => $scope]);
        $this->assertNotNull($visibility, sprintf('%s entity missing for expected value %s', $class, $value));
        $this->assertSame($value, $visibility->getVisibility());
    }

    public function testScopeWidgetAction()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $scope = $this->client->getContainer()->get('oro_scope.scope_manager')->findDefaultScope();

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
            json_encode([
                $this->getReference('customer.level_1')->getId() => ['visibility' => 'hidden']
            ], JSON_THROW_ON_ERROR),
            $crawler->filter(sprintf('[name = "oro_scoped_data_type[%s][customer]"]', $scope->getId()))
                ->attr('value'),
            'customer visibility form data'
        );
        $this->assertSame(
            json_encode([
                $this->getReference(LoadGroups::GROUP1)->getId() => ['visibility' => 'hidden'],
                $this->getAnonymousGroupId() => ['visibility' => 'visible']
            ], JSON_THROW_ON_ERROR),
            $crawler->filter(sprintf('[name = "oro_scoped_data_type[%s][customerGroup]"]', $scope->getId()))
                ->attr('value'),
            'customer group visibility form data'
        );
    }

    private function getAnonymousGroupId(): int
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(CustomerGroup::class)
            ->findOneBy(['name' => LoadAnonymousCustomerGroup::GROUP_NAME_NON_AUTHENTICATED])
            ->getId();
    }
}
