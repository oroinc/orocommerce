@regression
@ticket-BB-20155
@fixture-OroProductBundle:ProductAttributesFixture.yml

Feature: Gallery for Product image attributes

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product attributes
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | Drawing |
      | Type       | Image   |
    And I click "Continue"
    And I fill form with:
      | File Size (MB)    | 10                  |
      | Thumbnail Width   | 100                 |
      | Thumbnail Height  | 100                 |
      | File Applications | [default, commerce] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click "Create Attribute"
    And I fill form with:
      | Field Name | MultipleImages  |
      | Type       | Multiple Images |
    And I click "Continue"
    And I fill form with:
      | File Size (MB)    | 10                  |
      | Thumbnail Width   | 64                  |
      | Thumbnail Height  | 64                  |
      | File Applications | [default, commerce] |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message

    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Update product family with new attributes
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [Drawing, MultipleImages] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Edit product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    When I fill "Product Form" with:
      | Drawing          | cat1.jpg |
      | MultipleImages 1 | cat2.jpg |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check gallery for image attributes
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I type "SKU123" in "search"
    And I click "Search Button"
    And I click "View Details" for "SKU123" product
    When I click "Drawing Gallery Trigger"
    Then I should see images in "Popup Gallery Widget" element
    And I click "Popup Gallery Widget Close"
    When I click "Multi Images Gallery Trigger"
    Then I should see images in "Popup Gallery Widget" element
    And I click "Popup Gallery Widget Close"
