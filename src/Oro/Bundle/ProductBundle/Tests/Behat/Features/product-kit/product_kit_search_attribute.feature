@elasticsearch
@feature-BB-21129
@ticket-BB-23173
@ticket-BB-23174
@fixture-OroCustomerBundle:CustomerUserFixture.yml
@fixture-OroProductBundle:ConfigurableAttributeFamily.yml
@fixture-OroProductBundle:ProductKitsExportFixture.yml

Feature: Product Kit search attribute
  In order to be able to search for product kits by searchable attributes in the back-office or storefront
  As a user of the back-office or a buyer
  I search for products through the main product search functionality

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add new searchable attribute to simple product related to product kit
    Given I proceed as the Admin
    When I login as administrator
    And I go to Products/ Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | KitAttribute |
      | Type       | String       |
    And I click "Continue"
    And I fill "Product Attribute Form" with:
      | Frontend Searchable | Yes |
      | Backend Searchable  | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I go to Products/ Product Families
    And I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [KitAttribute] |
    And I save and close form
    Then I should see "Successfully updated" flash message

    When I go to Products/Products
    And click edit "PSKU1" in grid
    And I fill "ProductForm" with:
      | KitAttribute | KitAttributeValue |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check the search of product kits after adding a new searchable attribute to simple product related to product kit in the back-office
    And I click "Search"
    When I type "KitAttributeValue" in "search"
    Then I should see 2 search suggestion
    When I click "Search Submit"
    Then I should be on Search Result page
    And number of records should be 2
    And I should see following search results:
      | Title                       | Type    |
      | PSKU1 - Product 1           | Product |
      | PSKU_KIT1 - Product Kit 1   | Product |

  Scenario: Check the search of product kits after adding a new searchable attribute to simple product related to product kit on the storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I go to the homepage
    When I type "KitAttributeValue" in "search"
    Then I should see an "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 2
    And PSKU_KIT1 must be first record in "Product Frontend Grid"
    And PSKU1 must be second record in "Product Frontend Grid"

  Scenario: Disable searchable option for product attribute of simple product related to product kit in the back-office
    Given I proceed as the Admin
    When I go to Products/ Product Attributes
    And click edit "KitAttribute" in grid
    And I fill "Product Attribute Form" with:
      | Frontend Searchable | No |
      | Backend Searchable  | No |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Check the search of product kits after disabling searchable option for simple product related to product kit in the back-office
    When I click "Search"
    When I type "KitAttributeValue" in "search"
    Then I should see "No results found"

  Scenario: Check the search of product kits after disabling searchable option for simple product related to product kit on the storefront
    Given I proceed as the Buyer
    When I type "KitAttributeValue" in "search"
    Then I should not see an "Search Autocomplete Item" element
    And I should not see an "Search Autocomplete Submit" element
    And I should see "No products were found to match your search" in the "Search Autocomplete No Found" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 0
    And I should see "There are no products"

  Scenario: Enable searchable attribute for simple product related to product kit in the back-office
    Given I proceed as the Admin
    When I go to Products/ Product Attributes
    And click edit "KitAttribute" in grid
    And I fill "Product Attribute Form" with:
      | Frontend Searchable | Yes |
      | Backend Searchable  | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Check the search of product kits after enabling searchable attribute for simple product related to product kit in the back-office
    When I click "Search"
    And I type "KitAttributeValue" in "search"
    Then I should see 2 search suggestion
    When I click "Search Submit"
    Then I should be on Search Result page
    And number of records should be 2
    And I should see following search results:
      | Title                     | Type    |
      | PSKU1 - Product 1         | Product |
      | PSKU_KIT1 - Product Kit 1 | Product |

  Scenario: Check the search of product kits after enabling searchable attribute for simple product related to product kit on the storefront
    Given I proceed as the Buyer
    When I type "KitAttributeValue" in "search"
    Then I should see an "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 1" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product 1" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 2
    And PSKU_KIT1 must be first record in "Product Frontend Grid"
    And PSKU1 must be second record in "Product Frontend Grid"

# TODO: Uncomment and apply after BB-23247
#  Scenario: Check the search of product kits after removing searchable attribute for simple product related to product kit in the back-office
#    Given I proceed as the Admin
#    And I go to Products/ Product Attributes
#    When I click Remove "KitAttribute" in grid
#    Then I should see "Are you sure you want to delete this attribute?"
#    When I click "Yes"
#    Then I should see "Attribute successfully deleted" flash message
#    And I click "Search"
#    When I type "KitAttributeValue" in "search"
#    Then I should see "No results found"
#
#  Scenario: Check the search of product kits after removing searchable attribute for simple product related to product kit on the storefront
#    Given I proceed as the Buyer
#    When I type "KitAttributeValue" in "search"
#    Then I should not see an "Search Autocomplete Item" element
#    And I should not see an "Search Autocomplete Submit" element
#    And I should see "No products were found to match your search" in the "Search Autocomplete No Found" element

  Scenario: Check the search of product kits by product attribute to another family group in the back-office
    Given I proceed as the Admin
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | AnotherKitFamilyAttribute |
      | Type       | String                    |
    And I click "Continue"
    And I fill "Product Attribute Form" with:
      | Frontend Searchable | Yes |
      | Backend Searchable  | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message
    When I go to Products/ Product Families
    And I click "Create Product Family"
    And fill "Product Family Form" with:
      | Code       | KitFamily                   |
      | Label      | Kit Family                  |
      | Enabled    | True                        |
      | Attributes | [AnotherKitFamilyAttribute] |
    And I save and close form
    Then I should see "Product Family was successfully saved" flash message
    When I go to Products/Products
    And click "Create Product"
    And fill "ProductForm Step One" with:
      | Product Family | Kit Family |
    And I click "Continue"
    And fill "Create Product Form" with:
      | SKU                       | ANOTHER_FAMILY_SKU             |
      | Name                      | Another Family Product Name    |
      | Status                    | Enabled                        |
      | Unit Of Quantity          | item                           |
      | AnotherKitFamilyAttribute | AnotherKitFamilyAttributeValue |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I go to Products/Products
    And click edit "PSKU_KIT2" in grid
    And I click "Add Kit Item"
    And I fill "ProductKitForm" with:
      | Kit Item 2 Label        | Another Kit Label |
      | Kit Item 2 Product Unit | item              |
    And I click "Add Product" in "Product Kit Item 2" element
    And I click on ANOTHER_FAMILY_SKU in grid "KitItemProductsAddGrid"
    And I click "Kit Item 2 Toggler"
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I click "Search"
    And I type "AnotherKitFamilyAttributeValue" in "search"
    Then I should see 2 search suggestion
    When I click "Search Submit"
    Then I should be on Search Result page
    And number of records should be 2
    And I should see following search results:
      | Title                                            | Type    |
      | ANOTHER_FAMILY_SKU - Another Family Product Name | Product |
      | PSKU_KIT2 - Product Kit 2                        | Product |

  Scenario: Check the search of product kits by product attribute to another family group on the storefront
    Given I proceed as the Buyer
    When I type "AnotherKitFamilyAttributeValue" in "search"
    Then I should see an "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Another Family Product Name" inside "Search Autocomplete" element
    When I press "ArrowDown" key on "Search Form Field" element
    Then I should see "Search Form Field" element focused
    And I should see "Search Autocomplete Item Selected" element with text "Product Kit 2" inside "Search Autocomplete" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 2
    And ANOTHER_FAMILY_SKU must be first record in "Product Frontend Grid"
    And PSKU_KIT2 must be second record in "Product Frontend Grid"
