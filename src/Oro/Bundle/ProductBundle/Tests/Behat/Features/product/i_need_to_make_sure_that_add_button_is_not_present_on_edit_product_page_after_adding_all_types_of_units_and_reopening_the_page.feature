@regression
@fixture-OroProductBundle:quick_order_product.yml
Feature: I need to make sure that "+Add' button" is not present on edit product page after adding all types of units and reopening the page
  In order to prevent to the "+Add' button from appearing for Additional Units if all type of units are added
  As administrator
  I need to make sure that "+Add' button" is not present on edit product page after I add all types of units and reopen the page

    #   Scenario: Preconditions
    #   I should have:
    #   Product:
    #   SKU     Name         Unit of Quantity   Additional Units
    #   003     Product_01   each               item

  Scenario: If add all "Additional Units" for product and reopen the page there should be no "+Add" button
    Given I login as administrator
    And I go to Products/ Products
    And click edit "SKU003" in grid
    Then I should see "Add More Rows" element inside "Additional Units Form Section" element
    When set Additional Unit with:
      | Unit     | Precision | Rate |
      | Hour     | 1         | 10   |
      | Kilogram | 1         | 10   |
      | Piece    | 1         | 10   |
      | Set      | 1         | 10   |
# TODO: After BB-13717 is fixed, return precision 0 here
#      | Hour     | 0         | 10   |
#      | Kilogram | 0         | 10   |
#      | Piece    | 0         | 10   |
#      | Set      | 0         | 10   |
    Then I should not see "Add More Rows" element inside "Additional Units Form Section" element
    And I save and close form
    And I should see "Product has been saved" flash message
    When I click "Edit"
    Then I should not see "Add More Rows" element inside "Additional Units Form Section" element
