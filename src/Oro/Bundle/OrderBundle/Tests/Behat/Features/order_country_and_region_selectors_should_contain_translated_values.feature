@ticket-BAP-17369
@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroAddressBundle:CountryNameTranslation.yml
@fixture-OroOrderBundle:order.yml

Feature: Order Country and region selectors should contain translated values
  In order to manage orders
  As an Administrator
  I want to to see correctly translated names of country and region during creation and editing of order

  Scenario: Feature Background
    Given I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English, Zulu_Loc] |
      | Default Localization  | Zulu_Loc            |
    And I submit form
    When I go to System / Localization / Translations
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: Create Order - Country/region selector should contain translated values
    Given I go to Sales / Orders
    When I click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer                      | first customer      |
      | Customer User                 | Amanda Cole         |
      | Billing Address               | Enter other address |
      | Billing Address Country       | GermanyZulu         |
      | Billing Address State         | BerlinZulu          |
      | Billing Address Street        | someStreet          |
      | Billing Address City          | someCity            |
      | Billing Address Postal Code   | somePostalCode      |
      | Billing Address First name    | someFirstName       |
      | Billing Address Last name     | someLastName        |
      | Billing Address Organization  | someOrganization    |
    And fill "Order Form" with:
      | Shipping Address              | Enter other address |
      | Shipping Address Country      | GermanyZulu         |
      | Shipping Address State        | BerlinZulu          |
      | Shipping Address Street       | someStreet          |
      | Shipping Address City         | someCity            |
      | Shipping Address Postal Code  | somePostalCode      |
      | Shipping Address First name   | someFirstName       |
      | Shipping Address Last name    | someLastName        |
      | Shipping Address Organization | someOrganization    |
      | Product                       | AA1                 |
      | Quantity                      | 1                   |
      | Price                         | 10                  |
    And I click "Save and Close"
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see Order with:
      | Billing Address  | GermanyZulu |
      | Shipping Address | GermanyZulu |

  Scenario: Edit Order - Country/region selector should contain translated values
    Given I go to Sales / Orders
    When I click Edit SimpleOrder in grid
    And fill "Order Form" with:
      | Billing Address               | Enter other address |
      | Billing Address Country       | GermanyZulu         |
      | Billing Address State         | BerlinZulu          |
      | Billing Address Street        | someStreet          |
      | Billing Address City          | someCity            |
      | Billing Address Postal Code   | somePostalCode      |
      | Billing Address First name    | someFirstName       |
      | Billing Address Last name     | someLastName        |
      | Billing Address Organization  | someOrganization    |
    And fill "Order Form" with:
      | Shipping Address              | Enter other address |
      | Shipping Address Country      | GermanyZulu         |
      | Shipping Address State        | BerlinZulu          |
      | Shipping Address Street       | someStreet          |
      | Shipping Address City         | someCity            |
      | Shipping Address Postal Code  | somePostalCode      |
      | Shipping Address First name   | someFirstName       |
      | Shipping Address Last name    | someLastName        |
      | Shipping Address Organization | someOrganization    |
    And I click "Save and Close"
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see Order with:
      | Billing Address  | GermanyZulu |
      | Shipping Address | GermanyZulu |
