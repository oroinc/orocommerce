<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\ApiFrontendForVisitor\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ShoppingListItemForVisitorWithoutShoppingListTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            '@OroShoppingListBundle/Tests/Functional/ApiFrontendForVisitor/DataFixtures/shopping_list_for_visitor.yml'
        ]);
    }

    public function testTryToGetList(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->cget(
            ['entity' => 'shoppinglistitems'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGet(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->get(
            ['entity' => 'shoppinglistitems', 'id' => '<toString(@line_item1->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToCreateLineItem(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->post(
            ['entity' => 'shoppinglistitems'],
            'create_line_item_visitor.yml',
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToUpdateLineItem(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $lineItemId = $this->getReference('line_item1')->getId();
        $data = [
            'data' => [
                'type'       => 'shoppinglistitems',
                'id'         => (string)$lineItemId,
                'attributes' => [
                    'quantity' => 123.4
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            $data,
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToDelete(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $lineItemId = $this->getReference('line_item1')->getId();

        $response = $this->delete(
            ['entity' => 'shoppinglistitems', 'id' => (string)$lineItemId],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testOptions(): void
    {
        $this->setVisitorCookie($this->getReference('visitor1'));

        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'shoppinglists']
        );

        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST, DELETE');
    }
}
