@ticket-BB-19396
@fixture-OroCMSBundle:content_templates_permissions.yml
@fixture-OroCMSBundle:content_templates_permissions_tags.yml

Feature: Content Templates Permissions

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Revoke Content Template permissions
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/ User Management/ Roles
    And I filter Label as is equal to "Sales Rep"
    And click edit "Sales Rep" in grid
    When select following permissions:
      | Landing Page     | View:Organization | Create:Organization | Edit:Organization | Delete:Organization |
      | Content Template | View:None         | Create:None         | Edit:None         | Delete:None         |
    And I save and close form
    Then should see "Role saved" flash message

  Scenario: Check Content Templates are not enabled
    Given I proceed as the User
    And I login as "Charlie1@example.com" user
    And I should not see Marketing/Content Templates in main menu
    When I go to Marketing/Landing Pages
    And I click Edit "CMSPage1" in grid
    Then I should not see a "GrapesJs Content Templates Tab" element
    And I click "Cancel"

  Scenario: Grant Content Templates permissions on User level
    Given I proceed as the Admin
    And I go to System/ User Management/ Roles
    And I filter Label as is equal to "Sales Rep"
    And click edit "Sales Rep" in grid
    When select following permissions:
      | Content Template | View:User | Create:User | Edit:User | Delete:User |
    And I save and close form
    Then should see "Role saved" flash message

  Scenario: Check Content Templates grid
    Given I proceed as the User
    And I reload the page
    When I go to Marketing/Content Templates
    And I sort grid by "Name"
    Then should see following grid:
      | Name                 | Enabled | Tags                  |
      | TestContentTemplate3 | Enabled | tagAdmin1 tagCharlie3 |
      | TestContentTemplate4 | Enabled | tagCharlie4           |

  Scenario: Check Content Templates tab contains only user-owned content templates
    Given I go to Marketing/Landing Pages
    And I click Edit "CMSPage1" in grid
    When I click on "GrapesJs Content Templates Tab"
    Then I should see a "GrapesJs Content Template 3" element
    And I should see following content templates categories in GrapesJs:
      | tagAdmin1   |
      | tagCharlie3 |
      | tagCharlie4 |
    And there are 1 content template in category "tagAdmin1"
    And there are 1 content templates in category "tagCharlie3"
    And there are 1 content templates in category "tagCharlie4"
    And I click "Cancel"

  Scenario: Revoke Tag permissions
    Given I proceed as the Admin
    And I go to System/ User Management/ Roles
    And I filter Label as is equal to "Sales Rep"
    And click edit "Sales Rep" in grid
    When select following permissions:
      | Tag | View:None | Create:None | Edit:None | Delete:None |
    And I save and close form
    Then should see "Role saved" flash message

  Scenario: Check Content Templates grid
    Given I proceed as the User
    And I reload the page
    When I go to Marketing/Content Templates
    And I sort grid by "Name"
    Then I shouldn't see "Tags" column in grid
    And should see following grid:
      | Name                 | Enabled |
      | TestContentTemplate3 | Enabled |
      | TestContentTemplate4 | Enabled |

  Scenario: Check Tags field is absent
    When I click Edit "TestContentTemplate4" in grid
    Then I should not see a "Content Template Form Tags" element
    And I fill in WYSIWYG "Content Template Form Content" with "Updated content"
    When I save and close form
    Then I should see "Content template has been updated" flash message
    And I should not see a "Content Template Tags Field" element

  Scenario: Check Content Templates tab contains only General section
    Given I go to Marketing/Landing Pages
    And I click Edit "CMSPage1" in grid
    When I click on "GrapesJs Content Templates Tab"
    Then I should see a "GrapesJs Content Template 3" element
    And I should see following content templates categories in GrapesJs:
      | General |
    And there are 2 content template in category "General"
    And I click "Cancel"

  Scenario: Grant Tag permissions on User level
    Given I proceed as the Admin
    And I go to System/ User Management/ Roles
    And I filter Label as is equal to "Sales Rep"
    And click edit "Sales Rep" in grid
    When select following permissions:
      | Tag | View:User | Create:User | Edit:User | Delete:User |
    And I check "Assign/unassign tags" entity permission
    And I save and close form
    Then should see "Role saved" flash message

  Scenario: Check Content Templates grid contains only user-owned tags
    Given I proceed as the User
    And I reload the page
    When I go to Marketing/Content Templates
    And I sort grid by "Name"
    # tagAdmin1 must be removed from the expected list after BAP-21667
    Then should see following grid:
      | Name                 | Enabled | Tags                  |
      | TestContentTemplate3 | Enabled | tagAdmin1 tagCharlie3 |
      | TestContentTemplate4 | Enabled | tagCharlie4           |

  Scenario: Check Content Templates tab contains sections for only user-owned tags
    Given I go to Marketing/Landing Pages
    And I click Edit "CMSPage1" in grid
    When I click on "GrapesJs Content Templates Tab"
    Then I should see a "GrapesJs Content Template 3" element
    And I should see following content templates categories in GrapesJs:
      | tagCharlie3 |
      | tagCharlie4 |
    And there are 1 content templates in category "tagCharlie3"
    And there are 1 content templates in category "tagCharlie4"
    And I click "Cancel"

  Scenario: Check Tags field is present
    Given I go to Marketing/Content Templates
    When I click Edit "TestContentTemplate3" in grid
    Then I should see a "Content Template Form Tags" element
    # Must be removed from after BAP-21667
    And I should see "tagAdmin1"
    And I should see "tagCharlie3"
    When I save and close form
    Then I should see a "Content Template Tags Field" element
    # tagAdmin1 must be removed from the expected list after BAP-21667
    And I should see Content Template with:
      | Name | TestContentTemplate3  |
      | Tags | tagAdmin1 tagCharlie3 |
