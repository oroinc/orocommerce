@ticket-BB-15962
@fixture-OroShoppingListBundle:ProductFixture.yml
# Important! This scenario is added in separate feature (file) because related bug reproduced only on first page request
# per user session.
Feature: Guest adds products on frontstore homepage
  In order to allow guest user to for with shopping list
  As a Guest
  I want to have possibility to add products to shopping list from frontstore homepage without shopping list duplication

  Scenario: Feature Background
    Given sessions active:
      | Guest | first_session  |
      | User  | second_session |
    And I enable configuration options:
      | oro_shopping_list.availability_for_guests |
      | oro_checkout.guest_checkout               |
      | oro_shopping_list.create_shopping_list_for_new_guest |

  Scenario: As Guest check shopping lists are not duplicated in dropdowns of other products
    Given I am on the homepage
    And I click "Add to Shopping List"
    And I click "Shopping List Dropdown"
    And I click "Remove From Shopping List"
    And I should not see an "Shopping List Dropdown" element
