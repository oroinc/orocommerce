@fixture-OroProductBundle:ProductBrandFilterFixture.yml

Feature: Multiselect Filter Toggle
    Check the multisect filter so that it opens and closes correctly
    
    Scenario: Create different window session
        Given sessions active:
            | Admin | first_session  |
            | Buyer | second_session |

    Scenario: Enable Filter by Brand
        Given I proceed as the Admin
        And I login as administrator
        And I go to Products/Product Attributes
        And I click edit "brand" in grid
        When I fill form with:
            | Filterable | Yes |
        And I save and close form
        Then I should see "Attribute was successfully saved" flash message

    Scenario: Filter dropdown should toggle click by click 
        Given I proceed as the Buyer
        And I signed in as AmandaRCole@example.org on the store frontend
        And I click "NewCategory"
        And I click "FrontendGridActionFilterButton"
        And I should not see an "FrontendGridFilterManager" element
        When I click "Brand Filter"
        And I should see an "FrontendGridFilterManager" element
        And I click "Brand Filter"
        And I should not see an "FrontendGridFilterManager" element
        And I click "Brand Filter Toggle Icon"
        And I should see an "FrontendGridFilterManager" element
        And I click "Brand Filter Toggle Icon"
        Then I should not see an "FrontendGridFilterManager" element
