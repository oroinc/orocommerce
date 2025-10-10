@regression
@ticket-BB-22505
@fixture-OroShoppingListBundle:ShoppingListFixture.yml

Feature: Guest should not see not own shopping lists
  In order to protect data in shopping lists
  As a Guest
  I should not be able to see shopping lists that was created by other users

  Scenario: Feature Background
    Given sessions active:
      | Buyer | second_session |
    And I enable configuration options:
      | oro_shopping_list.availability_for_guests |
      | oro_checkout.guest_checkout               |

  Scenario: Check access to Amanda's shopping list from the guest
    Given I login as AmandaRCole@example.org buyer
    And Buyer is on "Shopping List 1" shopping list
    And I remember current URL
    And I click "Account Dropdown"
    And I click "Sign Out"
    When I follow remembered URL
    Then I should not see "Shopping List 1"
    And I should see "Log In"
