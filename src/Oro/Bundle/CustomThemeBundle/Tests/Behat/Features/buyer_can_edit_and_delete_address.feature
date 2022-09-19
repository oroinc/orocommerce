@ticket-BB-13185
@fixture-OroCustomerBundle:CustomerUserAddressNancyJSalleeFixture.yml
Feature: Buyer can Edit and Delete address
  In order to respect role permissions for addresses on the front store
  As a Buyer
  I should have buttons to edit and delete addresses only when I have such permissions

  Scenario: Check default permissions
    Given I login as NancyJSallee@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    And I go to Customers/Customer User Roles
    When I click View Buyer in grid
    Then the role has following active permissions:
      | Customer Address      | View:Department (Same Level) | Create:None | Edit:None | Delete:None |
      | Customer User Address | View:User (Own)              | Create:None | Edit:None | Delete:None |

  Scenario: Ensure that editing and deleting addresses is not allowed in custom theme
    Given go to System/Configuration
    And follow "Commerce/Design/Theme" on configuration sidebar
    And uncheck "Use default" for "Theme" field
    And fill in "Theme" with "Custom theme"
    And save form
    And I continue as the Buyer
    And follow "Account"
    When click "Address Book"
    Then I should not see "Item Edit Button" element inside "Customer Company Addresses List" element
    And I should not see "Item Delete Button" element inside "Customer Company Addresses List" element
    And I should not see "Item Edit Button" element inside "Customer Company User Addresses List" element
    And I should not see "Item Delete Button" element inside "Customer Company User Addresses List" element
