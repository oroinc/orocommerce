<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class StartCheckoutForVisitorWithoutShoppingListTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            '@OroShoppingListBundle/Tests/Functional/ApiFrontendForVisitor/DataFixtures/shopping_list_for_visitor.yml'
        ]);

        // guard
        self::assertFalse(self::getConfigManager()->get('oro_shopping_list.availability_for_guests'));
    }

    public function testTryToStartCheckout(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->postSubresource(
            ['entity' => 'shoppinglists', 'id' => '<toString(@shopping_list1->id)>', 'association' => 'checkout'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }
}
