@regression
@ticket-BB-22744
@fixture-OroVisibilityBundle:category_tree_with_product_visibility.yml

#  IMPORTANT: the one of possible reasons for failure of this feature may be in count of digital assets from main data
#  at the moment the count is 56, please make sure that they are still same

Feature: Product Visibility Attachment Audit Log Appending
  In order to see product visibility and attachment changes audit log
  As an administrator
  I should able to see audit log when I change visibilities and upload/delete attachment once I enabled options

  Scenario: Login backoffice as administrator
    Given I login as administrator

  Scenario Outline: Enable append audit log option for attachment/visibility product field
    When I go to System/Entities/Entity Management
    And filter Name as is equal to "<Name>"
    And I click "Edit"
    And I press "Other"
    And fill form with:
      | Auditable | Yes |
    And I save and close form
    Then I should see "Entity saved" flash message
    When I click Edit <AuditField> in grid
    And fill form with:
      | Auditable | Yes |
    And I save and close form
    Then I should see "Field saved" flash message
    When I click Edit <AuditRelatedField> in grid
    And I fill form with:
      | Auditable | Yes |
    And I save form
    And I fill form with:
      | Append Audit Log To The Related Entity | Yes |
    And I save and close form
    Then I should see "Field saved" flash message
    Examples:
      | Name                           | AuditField | AuditRelatedField |
      | Attachment                     | file       | Product           |
      | ProductVisibility              | visibility | product           |
      | CustomerProductVisibility      | visibility | product           |
      | CustomerGroupProductVisibility | visibility | product           |

  Scenario: Should see audit log once add attachment for current product
    When I go to Products/ Products
    And click view "PSKU1" in grid
    And follow "More actions"
    And click "Add attachment"
    When I fill "Attachment Form" with:
      | File    | cat1.jpg    |
      | Comment | Sweet kitty |
    And click "Save"
    Then I should see "Attachment created successfully" flash message
    And I should see Sweet kitty in grid with following data:
      | File name | cat1.jpg |
      | File size | 76.77 KB |
    When I click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values  | New values                                                                     |
      | Attachment: | Attachment:  Attachment "Item #1" added: File: File "7" Product: Product "1" |
    And I close ui dialog

  Scenario: Delete attachment
    Given I click Delete cat1.jpg in grid
    When I click "Yes, Delete"
    Then I should see "Item deleted" flash message
    When I click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values                                                                      | New values  |
      | Attachment: Attachment "Item #1" removed: File: File "7" Product: Product "1" | Attachment: |
    And I close ui dialog

  Scenario: Should see audit log once update visibility to all for current product
    When I go to Products/ Products
    And click view "PSKU1" in grid
    And click "More actions"
    And click "Manage Visibility"
    And I select "Hidden" from "Visibility to All"
    And I save and close form
    Then I should see "Product visibility has been saved" flash message
    When I click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values                 | New values                                                                                                    |
      | Product Visibility to All: | Product Visibility to All:  Product Visibility to All "hidden" added: Product: Product "1" Visibility: hidden |
    And I close ui dialog

  Scenario: Should see audit log once update visibility to customer for current product
    When I click "More actions"
    And click "Manage Visibility"
    And I fill "Visibility Product Form" with:
      | Visibility To Customers First | Current Product |
    And I save and close form
    Then I should see "Product visibility has been saved" flash message
    When I click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values                                                                                                       | New values                                                                                                               |
      | Visibility to Customer Products: Visibility to Customer Products "current_product" changed: Visibility: category | Visibility to Customer Products:  Visibility to Customer Products "current_product" changed: Visibility: current_product |
    And I close ui dialog

  Scenario: Should see audit log once update visibility to customer group for current product
    When I click "More actions"
    And click "Manage Visibility"
    And I fill "Visibility Product Form" with:
      | Visibility To Customer First Group | Hidden |
    And I save and close form
    Then I should see "Product visibility has been saved" flash message
    When I click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values                                                                                                          | New values                                                                                                         |
      | Visibility to Customer Group Products: Visibility to Customer Group Products "hidden" changed: Visibility: category | Visibility to Customer Group Products:  Visibility to Customer Group Products "hidden" changed: Visibility: hidden |
    And I close ui dialog
