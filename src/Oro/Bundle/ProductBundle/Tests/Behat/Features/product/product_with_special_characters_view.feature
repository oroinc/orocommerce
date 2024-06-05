@ticket-BB-16275
@fixture-OroProductBundle:product_with_special_characters_view.yml

Feature: Product with special characters view
  In order to see have proper product view page
  As an Buyer
    I want to be able to see product name properly displayed on product view page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to Products / Products
    And I click Edit PSKU1 in grid
    And I set Images with:
      | Main  | Listing | Additional |
      | 1     | 1       | 1          |
    And I click on "Digital Asset Choose"
    And I fill "Digital Asset Dialog Form" with:
      | File  | cat1.jpg |
      | Title | cat1.jpg |
    And I click "Upload"
    And click on cat1.jpg in grid
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check that product name is displayed properly
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When type "PSKU1" in "search"
    And I click "Search Button"
    And I click "View Details" for "PSKU1" product
    Then I should see "Product1`\"'&йёщ®&reg;>"
    And I should not see "Product1`\"'&йёщ®®>"
    When I click on "Product View Gallery Trigger"
    Then I should see gallery image with alt "Product1`\"'&йёщ®&reg;>"
    And I click "Popup Gallery Widget Close"

  @skip
#  Unskip when BB-20324 will be fixed
  Scenario: Check that product name is displayed properly in shopping lists widget
    When I click "Add to Shopping List"
    And click "In Shopping List"
    Then I should see "UiDialog" with elements:
      | Title | Product1`"'&йёщ®&reg;> |
    And I close ui dialog

  Scenario: Check that product name is displayed properly in "Wide template" layout view
    Given I proceed as the Admin
    When I go to System / Theme Configurations
    And I click "Edit" on row "Refreshing Teal" in grid
    And I fill "Theme Configuration Form" with:
      | Page Template | wide |
    And I save and close form
    Then I should see "Theme Configuration" flash message

    When I proceed as the Buyer
    And I reload the page
    Then I should see "Product1`\"'&йёщ®&reg;>"
    And I should not see "Product1`\"'&йёщ®®>"

    When I click on "Product View Gallery Trigger"
    Then I should see gallery image with alt "Product1`\"'&йёщ®&reg;>"
    And I click "Popup Gallery Widget Close"

  @skip
#  Unskip when BB-20324 will be fixed
  Scenario: Check that product name is displayed properly in shopping lists widget
    When click "In Shopping List"
    Then I should see "UiDialog" with elements:
      | Title | Product1`"'&йёщ®&reg;> |
    And I close ui dialog

  Scenario: Check that product name is displayed properly in "List page" layout view
    Given I proceed as the Admin
    When I go to System / Theme Configurations
    And I click "Edit" on row "Refreshing Teal" in grid
    And I fill "Theme Configuration Form" with:
      | Page Template | tabs |
    And I save and close form
    Then I should see "Theme Configuration" flash message

    When I proceed as the Buyer
    And I reload the page
    Then I should see "Product1`\"'&йёщ®&reg;>"
    And I should not see "Product1`\"'&йёщ®®>"

    When I click on "Product View Gallery Trigger"
    Then I should see gallery image with alt "Product1`\"'&йёщ®&reg;>"
    And I click "Popup Gallery Widget Close"

  @skip
#  Unskip when BB-20324 will be fixed
  Scenario: Check that product name is displayed properly in shopping lists widget
    When click "In Shopping List"
    Then I should see "UiDialog" with elements:
      | Title | Product1`"'&йёщ®&reg;> |
