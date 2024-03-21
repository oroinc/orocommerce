@behat-test-env
@ticket-BB-15962
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroShoppingListBundle:ProductFixture.yml
# Important! This scenario is added in separate feature (file) because related bug reproduced only on first page request
# per user session.
Feature: Guest adds products on frontstore homepage
  In order to allow guest user to for with shopping list
  As a Guest
  I want to have possibility to add products to shopping list from frontstore homepage without shopping list duplication

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Guest | system_session |
    And I enable configuration options:
      | oro_shopping_list.availability_for_guests |
      | oro_checkout.guest_checkout               |
      | oro_shopping_list.create_shopping_list_for_new_guest |
    And I add Featured Products widget after content for "Homepage" page
    And I update settings for "featured-products" content widget:
      | minimum_items | 1 |

  Scenario: As Guest check shopping lists are not duplicated in dropdowns of other products
    Given I proceed as the Guest
    When I am on the homepage
    Then I should see "1GB81" and continue checking the condition is met for maximum 10 seconds
    When I click "Add to Shopping List" for "1GB81" product
    And I click "Shopping List Dropdown"
    And I click "Remove From Shopping List"
    Then I should not see an "Shopping List Dropdown" element
