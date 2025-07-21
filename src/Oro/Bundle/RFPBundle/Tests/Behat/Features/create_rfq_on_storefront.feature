@random-failed
@fixture-OroRFPBundle:RFQ_with_removed_unit.yml
@ticket-BB-16463
@ticket-BB-21064
@ticket-BB-21454
@ticket-BB-23035
@ticket-BB-16819
Feature: Create RFQ on storefront
  In order to control RFQ content
  As an Administrator
  I need to be able to see correct RFQ content on view page
  As an Buyer
  I shouldn't be able to create RFQ with hidden products

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Save "Requests For Quote" storefront menu item
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Storefront Menus
    And I click view "oro_customer_menu" in grid
    And I click Orders in menu tree
    And I click Requests For Quote in menu tree
    And I save form
    Then I should see "Menu item saved successfully." flash message

  Scenario: Create RFQ that contains notes
    Given I continue as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Requests For Quote"
    And I click "New Quote"
    And fill form with:
      | First Name | First Name    |
      | Last Name  | Last Name     |
      | Company    | Company New   |
      | Notes      | <h1>note</h1> |
      | PO Number  | 007           |
    And I click "Submit Request"
    Then I should see "Request has been saved" flash message

    When I continue as the Admin
    And I go to Sales/Requests For Quote
    And I click view 007 in grid
    Then I should see RFQ with:
      | First Name | First Name    |
      | Last Name  | Last Name     |
      | Company    | Company New   |
      | Notes      | <h1>note</h1> |
      | PO Number  | 007           |

  Scenario: Products management in RFQ on storefront
    Given I continue as the Buyer
    And I click "Account Dropdown"
    And I click "Requests For Quote"
    And I click "New Quote"
    When I open select entity popup for field "Line Item Product" in form "Frontend Request Form"
    Then I should see following grid:
      | SKU    | Name     | Inventory Status |
      | SKU123 | product1 | In Stock         |
    And click on SKU123 in grid
    Then I should see "SKU123 - product1" in the "RFQ Products List" element
    When I fill "Frontend Request Form" with:
      | Line Item First Unit | set |
    And I click "Add Another Line"
    Then "Frontend Request Form" must contains values:
      | Line Item First Unit | set |
    When I click "Remove Request Product Edit Line Item"
    When I click "Remove Request Product Edit Line Item"
    Then I should not see "SKU123 - product1" in the "RFQ Products List" element

  Scenario: Create RFQ with hidden product on storefront
    Given I click "Account Dropdown"
    And I click "Requests For Quote"
    And I click "New Quote"
    When I open select entity popup for field "Line Item Product" in form "Frontend Request Form"
    Then I should see following grid:
      | SKU    | Name     | Inventory Status |
      | SKU123 | product1 | In Stock         |
    And click on SKU123 in grid
    Then I should see "SKU123 - product1" in the "RFQ Products List" element
    When I fill "Frontend Request Form" with:
      | Line Item First Unit | set |
    Then "Frontend Request Form" must contains values:
      | Line Item First Unit | set |

    When I continue as the Admin
    And I go to Product/Products
    And I click view SKU123 in grid
    And click "More actions"
    Then click "Manage Visibility"
    And I select "Hidden" from "Visibility to All"
    And I save and close form

    When I continue as the Buyer
    And I click "Submit Request"
    Then I should see "Product cannot be empty."

    When I continue as the Admin
    And I go to Product/Products
    And I click view SKU123 in grid
    And click "More actions"
    Then click "Manage Visibility"
    And I select "Visible" from "Visibility to All"
    And I save and close form
    And I should see "Product visibility has been saved" flash message

    When I continue as the Buyer
    And I wait 3 seconds
    And I open select entity popup for field "Line Item Product" in form "Frontend Request Form"
    Then I should see following grid:
      | SKU    | Name     | Inventory Status |
      | SKU123 | product1 | In Stock         |
    And click on SKU123 in grid
    Then I should see "SKU123 - product1" in the "RFQ Products List" element
    And I fill "Frontend Request Form" with:
      | Line Item First Unit | set |
      | First Name | First Name    |
      | Last Name  | Last Name     |
      | Company    | Company New   |
      | PO Number  | 008           |
    And I click "Submit Request"
    Then I should see "Request has been saved" flash message
    And should see "REQUEST FOR QUOTE #3"
    And should see "Product1"
