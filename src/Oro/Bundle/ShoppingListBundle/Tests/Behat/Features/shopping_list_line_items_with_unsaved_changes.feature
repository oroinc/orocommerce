@ticket-BB-20192
@fixture-OroLocaleBundle:ZuluLocalization.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml

Feature: Shopping List Line Items With Unsaved Changes
  As a Buyer
  I need to be protected from accidental unsaved data loss

  Scenario: Create different window session
    Given sessions active:
      | Admin     | first_session  |
      | Buyer     | second_session |

  Scenario: Enable required currencies and localization
    Given I proceed as the Admin
    And I login as administrator
    Then I set configuration property "oro_shopping_list.shopping_lists_page_enabled" to "1"
    And I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    Then I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States) , Zulu_Loc] |
      | Default Localization  | English (United States)              |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Open Shopping List edit page
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    And I click Edit "List 2" in grid
    Then I should see following grid:
      | SKU    | Qty Update All |
      | SKU123 | 10 item        |

  Scenario: Discard order transaction with unsaved changed
    When I click on "Shopping List Line Item 1 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 3    |
    And I click "Create Order"
    Then should see "You have unsaved changes, are you sure you want to leave this page?" in confirmation dialogue
    And I click "Cancel" in confirmation dialogue
    And I click on "Shopping List Line Item 1 Cancel Button"
    Then Page title equals to "List 2 - Shopping Lists - My Account"

  Scenario: Discard currency change with unsaved changed
    When I click on "Shopping List Line Item 1 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 3    |

    And I select "€" currency
    Then should see "You have unsaved changes, are you sure you want to leave this page?" in confirmation dialogue
    And I click "Cancel" in confirmation dialogue
    And I click on "Shopping List Line Item 1 Cancel Button"
    And I should see that "$" currency is active
    And Page title equals to "List 2 - Shopping Lists - My Account"

  Scenario: Discard localization change with unsaved changed
    When I click on "Shopping List Line Item 1 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 3    |
    And I select "Zulu" localization
    Then should see "You have unsaved changes, are you sure you want to leave this page?" in confirmation dialogue
    And I click "Cancel" in confirmation dialogue
    And I click on "Shopping List Line Item 1 Cancel Button"
    Then I should see that "English (United States)" localization is active
    And Page title equals to "List 2 - Shopping Lists - My Account"
