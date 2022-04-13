@fixture-OroFixedProductShippingBundle:FixedProductIntegration.yml

Feature: Check validation for Shipping Rule - Fixed Product
    In order to validate fixed product shipping rule options
    As Administrator
    I need to be able to add/change fixed product shipping rule

    Scenario: Get validation error on saving shipping method
        Given I login as administrator
        And go to System/ Shipping Rules
        And I click "Create Shipping Rule"
        And fill "Shipping Rule" with:
            | Name       | Fixed Product |
            | Sort Order | 1             |
            | Currency   | USD           |
            | Method     | Fixed Product |
        And fill "Fast Shipping Rule Form" with:
            | Surcharge Type   | Percent       |
            | Surcharge On     | Product Price |
        And I save and close form
        Then I should see "Shipping Rule Flat Rate" validation errors:
            | Surcharge Amount | This value should not be blank. |

    Scenario: Change Surcharge Type
        And fill "Fast Shipping Rule Form" with:
            | Surcharge Type   | Fixed Amount |
        And I should not see "Surcharge On"
        And fill "Fast Shipping Rule Form" with:
            | Surcharge Type   | Percent |
        And I should see "Surcharge On"

    Scenario: Save shipping method successfully
        And I fill "Shipping Rule Fixed Product" with:
            | Surcharge Amount | 15 |
        And I save and close form
        Then I should see "Shipping rule has been saved" flash message
