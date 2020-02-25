@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroAddressBundle:CountryNameTranslation.yml
@fixture-OroCustomerBundle:LoadCustomerCustomerUserEntitiesFixture.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:CountryAndRegionTranslationsCheckoutFixture.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml
Feature: Country and region translations front-store
  In order to manager Address Book on front store
  As a Buyer
  I want to see translated country and region names in customer user`s address book

  Scenario: Feature Background
    Given I enable the existing localizations
    And I login as administrator
    When I go to System / Localization / Translations
    And I click "Update Cache"
    Then I should see "Translation Cache has been updated" flash message

  Scenario: frontend-customer-customer-address-grid
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Localization Switcher"
    And I select "Zulu" localization
    And follow "Account"
    When click "Address Book"
    Then should see following "Customers Address Book Grid" grid:
      | Customer Address | City          | State       | Zip/Postal Code | Country           |
      | CustomerStreet1  | CustomerCity1 | BerlinZulu  | 10011           | GermanyZulu       |
      | CustomerStreet2  | CustomerCity2 | FloridaZulu | 10012           | United StatesZulu |
    When I filter Country as contains "GermanyZulu" in "Customers Address Book Grid"
    Then should see following "Customers Address Book Grid" grid:
      | Customer Address | City          | State      | Zip/Postal Code | Country     |
      | CustomerStreet1  | CustomerCity1 | BerlinZulu | 10011           | GermanyZulu |
    And number of records in "Customers Address Book Grid" grid should be 1
    And I reset "Country" filter on grid "Customers Address Book Grid"
    When filter State as contains "FloridaZulu" in "Customers Address Book Grid"
    Then should see following "Customers Address Book Grid" grid:
      | Customer Address | City          | State       | Zip/Postal Code | Country           |
      | CustomerStreet2  | CustomerCity2 | FloridaZulu | 10012           | United StatesZulu |
    When click edit "10012" in grid
    And fill form with:
      | Country | GermanyZulu |
      | State   | BerlinZulu  |
    And click "Save"
    Then should see following "Customers Address Book Grid" grid:
      | Customer Address | City          | State      | Zip/Postal Code | Country     |
      | CustomerStreet1  | CustomerCity1 | BerlinZulu | 10011           | GermanyZulu |
      | CustomerStreet2  | CustomerCity2 | BerlinZulu | 10012           | GermanyZulu |

  Scenario: frontend-customer-customer-user-address-grid
    Given should see following "Customer Users Address Book Grid" grid:
      | Customer Address | City           | State       | Zip/Postal Code | Country           |
      | CustomerUStreet1 | CustomerUCity1 | BerlinZulu  | 10013           | GermanyZulu       |
      | CustomerUStreet2 | CustomerUCity2 | FloridaZulu | 10014           | United StatesZulu |
    When I filter Country as contains "GermanyZulu" in "Customer Users Address Book Grid"
    Then should see following "Customer Users Address Book Grid" grid:
      | Customer Address | City           | State      | Zip/Postal Code | Country     |
      | CustomerUStreet1 | CustomerUCity1 | BerlinZulu | 10013           | GermanyZulu |
    And number of records in "Customer Users Address Book Grid" grid should be 1
    And I reset "Country" filter on grid "Customer Users Address Book Grid"
    When filter State as contains "FloridaZulu" in "Customer Users Address Book Grid"
    Then should see following "Customer Users Address Book Grid" grid:
      | Customer Address | City           | State       | Zip/Postal Code | Country           |
      | CustomerUStreet2 | CustomerUCity2 | FloridaZulu | 10014           | United StatesZulu |
    When click edit "10014" in grid
    And fill form with:
      | Country | GermanyZulu |
      | State   | BerlinZulu  |
    And click "Save"
    And click "Address Book"
    Then should see following "Customer Users Address Book Grid" grid:
      | Customer Address | City           | State      | Zip/Postal Code | Country     |
      | CustomerUStreet1 | CustomerUCity1 | BerlinZulu | 10013           | GermanyZulu |
      | CustomerUStreet2 | CustomerUCity2 | BerlinZulu | 10014           | GermanyZulu |
