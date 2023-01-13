@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroProductBundle:products_hide_variations.yml
@fixture-OroProductBundle:products_hide_variations_prices.yml
@fixture-OroProductBundle:products_hide_variations_checkout_related.yml
@ticket-BB-15564
@regression

Feature: Create RFQ with product variant
  In order to create RFQ
  As a Buyer
  I want to be able to choose product variants from autocomplete

  Scenario: Create sessions
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |

  Scenario: Prepare product attributes
    Given I proceed as the Admin
    And I login as administrator

    # Create attribute 1
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Attribute_1 |
      | Type       | Select      |
    And I click "Continue"
    And I fill form with:
      | Label | Attribute_1 |
    And set Options with:
      | Label    |
      | Value 11 |
      | Value 12 |
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Update schema
    And I go to Products / Product Attributes
    And I confirm schema update

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes                                                                                                                                                                       |
      | Attribute group | true    | [Attribute_1] |
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare configurable products

    # Variants for CNF_A
    Given I go to Products / Products
    And filter SKU as is equal to "PROD_A_1"
    And I click Edit PROD_A_1 in grid
    And I fill in product attribute "Attribute_1" with "Value 11"
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Save configurable products with simple products selected
    And I go to Products / Products
    And filter SKU as is equal to "CNF_A"
    And I click Edit CNF_A in grid
    And I should see "There are no product variants"
    And I fill "ProductForm" with:
      | Configurable Attributes | [Attribute_1] |
    And I check records in grid:
      | PROD_A_1 |
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Check quick order form does not contain variants when hidden
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend

    When I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | PROD_A_1 |
    And I wait for products to load
    Then I should see text matching "Item number cannot be found"

  Scenario: Simple product variations are hidden by default from autocomplete search result
    # Check Search
    When I type "PROD_A_1" in "search"
    And I click "Search Button"
    Then I should not see "PROD_A_1" product

    And I follow "Account"
    And I click "Requests For Quote"

    When I click "New Quote"
    Then should not see the following options for "SKU" select in form "Frontstore RFQ Line Item Form1" pre-filled with "PROD":
      | PROD_A_1 Product A 1 |

    When I open select entity popup for field "SKU" in form "Frontstore RFQ Line Item Form1"
    Then I should not see "PROD_A_1"

  Scenario: Change configuration to display simple variations for RFQ
    Given I proceed as the Admin
    When go to System/ Configuration
    And I follow "Commerce/Product/Configurable Products" on configuration sidebar
    And I fill "Display Simple Variations Form" with:
      | Display Simple Variations Default | false        |
      | Display Simple Variations         | hide_catalog |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Simple product variations are avaialble from autocomplete search result when enabled
    Given I proceed as the User

    When I reload the page
    And I fill form with:
      | PO Number | RFQ001 |
    Then should see the following options for "SKU" select in form "Frontstore RFQ Line Item Form1" pre-filled with "PROD":
      | PROD_A_1 Product A 1 |

    When I open select entity popup for field "SKU" in form "Frontstore RFQ Line Item Form1"
    Then I should see "PROD_A_1"

    When click on PROD_A_1 in grid
    And fill "Frontstore RFQ Line Item Form1" with:
      | Target Price | 1 |
    And click "Update Line Item"
    Then I should see "Product A 1"
    And I should see "QTY: 1 item"
    And I should see "Target Price $1.00"
    And I should see "Listed Price: $1.00"

    When click "Submit Request"
    Then should see "Request has been saved" flash message
    And I should see "Item #: PROD_A_1"

    When I type "PROD_A_1" in "search"
    And I click "Search Button"
    Then I should not see "PROD_A_1" product

  Scenario: Check created RFQ for product variant
    When I proceed as the Admin
    And go to Sales/ Requests For Quote
    And click view "RFQ001" in grid
    Then I should see "PROD_A_1"

  Scenario: Create RFQ from quick order form with enabled variants
    Given I proceed as the User
    And I am on the homepage
    When I click "Quick Order Form"
    And I fill "Quick Order Form" with:
      | SKU1 | PROD_A_1 |
    And I wait for products to load
    And I click "Get Quote"
    Then I should see "REQUEST A QUOTE"
    When I fill form with:
      | PO Number | RFQ002 |
    When click "Submit Request"
    Then should see "Request has been saved" flash message
    And I should see "Item #: PROD_A_1"
