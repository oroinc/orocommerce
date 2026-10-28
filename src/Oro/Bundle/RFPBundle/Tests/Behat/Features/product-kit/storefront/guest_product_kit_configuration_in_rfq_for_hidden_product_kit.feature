@regression
@ticket-BB-27684
@fixture-OroRFPBundle:product-kit/storefront/rfq_with_product_kits__product.yml

Feature: Guest product kit configuration in RFQ for hidden product kit

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |
    And I enable configuration options:
      | oro_rfp.guest_rfp                         |
      | oro_shopping_list.availability_for_guests |

  Scenario: Add product kit to the RFQ as a guest
    Given I proceed as the Buyer
    And I am on the homepage
    When I type "product-kit-01" in "search"
    And I click "Search Button"
    And I click "View Details" for "Product Kit 01" product
    And I click "Configure and Add to Shopping List"
    Then I should see "Product Kit Dialog" with elements:
      | Title | Product Kit 01 |
      | Price | Total: $124.69 |

    When I click "Add to Shopping List" in "Shopping List Button Group in Dialog" element
    Then I should see 'Product kit has been added to \"Shopping List\"' flash message

    When I follow "Shopping List" link within flash message "Product kit has been added to \"Shopping list\""
    And click "Request Quote"
    Then I should see "product-kit-01 - Product Kit 01 Mandatory Item 1 piece simple-product-01 - Simple Product 01" in the "RFQ Products List Line Item 1" element

  Scenario: Hide the Product Kit
    Given I proceed as the Admin
    When I login as administrator
    And go to Products / Products
    And click "View" on row "product-kit-01" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Hidden" from "Visibility to All"
    And I save and close form
    Then I should see "Product visibility has been saved" flash message

  Scenario: Configure the hidden Product Kit as a guest
    Given  I proceed as the Buyer
    When I click "Edit Request Product Line Item"
    And I click "RFQ Kit Item Line Item 1 Configure Button"
    Then I should see "Product Kit Dialog" with elements:
      | Title | N/A        |
      | Price | Total: N/A |
    And I should not see "Product Kit 01" in the "Product Kit Dialog" element
    And I should not see "Optional Item" in the "Product Kit Dialog" element
    And I should not see "Mandatory Item" in the "Product Kit Dialog" element
    And I should not see "simple-product-01" in the "Product Kit Dialog" element
    And I should not see "$124.6867" in the "Product Kit Dialog" element
    And I should see "Product cannot be empty" in the "Product Kit Dialog" element
    And I should see "Original selection no longer available" in the "Product Kit Dialog" element
