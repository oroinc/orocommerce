<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

class LoadAdditionalCompletionCheckoutData extends AbstractLoadCheckoutData
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadCheckouts($manager, $this->getData());
    }

    private function getData(): array
    {
        return [
            'checkout.ready_for_completion.ship_to_billing_address' => [
                'checkout' => ['currency' => 'USD'],
                'customerUser' => 'customer_user',
                'shoppingListLineItems' => [
                    ['product' => LoadProductData::PRODUCT_1],
                    ['product' => LoadProductData::PRODUCT_2]
                ],
                'shipToBillingAddress' => true,
                'billingAddress' => $this->createCheckoutAddress([
                    'type' => 'billing',
                    'country' => 'country_usa',
                    'city' => 'Rochester',
                    'region' => 'region_usa_california',
                    'street' => '1215 Caldwell Road',
                    'postalCode' => '14608',
                    'firstName' => 'John',
                    'lastName' => 'Doe'
                ])
            ],
            'checkout.ready_for_completion.no_ship_to_billing_address' => [
                'checkout' => ['currency' => 'USD'],
                'customerUser' => 'customer_user',
                'shoppingListLineItems' => [
                    ['product' => LoadProductData::PRODUCT_1],
                    ['product' => LoadProductData::PRODUCT_6]
                ],
                'billingAddress' => $this->createCheckoutAddress([
                    'type' => 'billing',
                    'country' => 'country_usa',
                    'city' => 'Rochester',
                    'region' => 'region_usa_california',
                    'street' => '1215 Caldwell Road',
                    'postalCode' => '14608',
                    'firstName' => 'John',
                    'lastName' => 'Doe'
                ])
            ]
        ];
    }
}
