<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutPromotionalDiscountsData;

class CheckoutLineItemPromotionDiscountsTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadCheckoutPromotionalDiscountsData::class
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'checkoutlineitems'],
            [
                'filter' => [
                    'checkout' => [
                        '<toString(@checkout.completed->id)>',
                        '<toString(@checkout.ready_for_completion->id)>'
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.ready_for_completion.line_item.1->id)>',
                        'attributes' => [
                            'quantity' => 1,
                            'currency' => 'USD',
                            'price' => '100.5000',
                            'subTotal' => '100.5000',
                            'totalValue' => '99.5000',
                            'discount' => '-1.0000'
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.ready_for_completion.line_item.2->id)>',
                        'attributes' => [
                            'quantity' => 1,
                            'currency' => null,
                            'price' => null,
                            'subTotal' => null,
                            'totalValue' => null,
                            'discount' => '0.0000'
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.completed.line_item.1->id)>',
                        'attributes' => [
                            'quantity' => 1,
                            'currency' => 'USD',
                            'price' => '100.5000',
                            'subTotal' => '100.5000',
                            'totalValue' => '99.5000',
                            'discount' => '-1.0000'
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.completed.line_item.2->id)>',
                        'attributes' => [
                            'quantity' => 1,
                            'currency' => 'USD',
                            'price' => '115.9000',
                            'subTotal' => '115.9000',
                            'totalValue' => '115.9000',
                            'discount' => '0.0000'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithTotalValueOnly(): void
    {
        $response = $this->cget(
            ['entity' => 'checkoutlineitems'],
            [
                'filter' => [
                    'checkout' => [
                        '<toString(@checkout.completed->id)>',
                        '<toString(@checkout.ready_for_completion->id)>'
                    ]
                ],
                'fields[checkoutlineitems]' => 'totalValue'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.ready_for_completion.line_item.1->id)>',
                        'attributes' => [
                            'totalValue' => '99.5000'
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.ready_for_completion.line_item.2->id)>',
                        'attributes' => [
                            'totalValue' => null
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.completed.line_item.1->id)>',
                        'attributes' => [
                            'totalValue' => '99.5000'
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.completed.line_item.2->id)>',
                        'attributes' => [
                            'totalValue' => '115.9000'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithDiscountOnly(): void
    {
        $response = $this->cget(
            ['entity' => 'checkoutlineitems'],
            [
                'filter' => [
                    'checkout' => [
                        '<toString(@checkout.completed->id)>',
                        '<toString(@checkout.ready_for_completion->id)>'
                    ]
                ],
                'fields[checkoutlineitems]' => 'discount'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.ready_for_completion.line_item.1->id)>',
                        'attributes' => [
                            'discount' => '-1.0000'
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.ready_for_completion.line_item.2->id)>',
                        'attributes' => [
                            'discount' => '0.0000'
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.completed.line_item.1->id)>',
                        'attributes' => [
                            'discount' => '-1.0000'
                        ]
                    ],
                    [
                        'type' => 'checkoutlineitems',
                        'id' => '<toString(@checkout.completed.line_item.2->id)>',
                        'attributes' => [
                            'discount' => '0.0000'
                        ]
                    ]
                ]
            ],
            $response
        );
    }
}
