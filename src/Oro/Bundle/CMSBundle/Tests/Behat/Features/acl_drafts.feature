@regression
@ticket-BB-17908
@fixture-OroCMSBundle:AclDraftsUsers.yml
Feature: ACL Drafts
  In order to restrict access to Draft operations
  As an Administrator
  I need to be able to manage permissions on Draft operations

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create Landing Page
    Given I proceed as the Admin
    And I login as administrator
    And I go to Marketing/ Landing Pages
    And I click "Create Landing Page"
    And I fill "CMS Page Form" with:
      | Titles | Page |
    Then I should see URL Slug field filled with "page"
    When I save and close form
    Then I should see "Page has been saved" flash message

  Scenario: Prepare draft from John Doe
    When I click "Create draft"
    Then I should see "UiWindow" with elements:
      | Title        | Action Confirmation                                                                                                                                    |
      | Content      | Only the changes from the following fields will be transferred to a draft: metaDescriptions, metaTitles, metaKeywords, slugPrototypes, titles, content |
      | okButton     | Yes                                                                                                                                                    |
      | cancelButton | Cancel                                                                                                                                                 |
    When I click "Yes" in confirmation dialogue
    Then I should see "Draft has been saved" flash message
    And I should see URL Slug field filled with "page"
    When I fill "CMS Page Form" with:
      | Titles | John Doe Draft |
    And I save and close form
    Then I should see "Draft has been saved" flash message

  Scenario: Prepare draft from Misty
    Given I proceed as the Buyer
    And I login as "misty" user
    And I go to Marketing/ Landing Pages
    And I click view "Page" in grid
    When I click "Create draft"
    And I click "Yes" in confirmation dialogue
    Then I should see "Draft has been saved" flash message
    And I should see URL Slug field filled with "page"
    When I fill "CMS Page Form" with:
      | Titles | Misty Draft |
    And I save and close form
    Then I should see "Draft has been saved" flash message

  Scenario: Check draft grid actions from John Doe
    Given I proceed as the Admin
    And I go to Marketing/ Landing Pages
    When I click view "Page" in grid
    Then I should see following grid:
      | Title          | Slug           | Owner       |
      | John Doe Draft | john-doe-draft | John Doe    |
      | Misty Draft    | misty-draft    | Misty Grant |
    And I should see following actions for John Doe Draft in grid:
      | View          |
      | Edit          |
      | Delete        |
      | Duplicate     |
      | Publish draft |
    And I should see following actions for Misty Draft in grid:
      | View          |
      | Edit          |
      | Delete        |
      | Duplicate     |
      | Publish draft |

  Scenario: Check draft grid actions from Misty
    Given I proceed as the Buyer
    And I go to Marketing/ Landing Pages
    When I click view "Page" in grid
    Then I should see following grid:
      | Title          | Slug           | Owner       |
      | John Doe Draft | john-doe-draft | John Doe    |
      | Misty Draft    | misty-draft    | Misty Grant |
    And I should see following actions for John Doe Draft in grid:
      | View          |
      | Edit          |
      | Delete        |
      | Duplicate     |
      | Publish draft |
    And I should see following actions for Misty Draft in grid:
      | View          |
      | Edit          |
      | Delete        |
      | Duplicate     |
      | Publish draft |

  Scenario: Disable permissions
    Given I proceed as the Admin
    And I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Landing Page | Create Drafts:None | Delete All Drafts:None | Delete Own Drafts:None | Edit All Drafts:None | Publish Drafts:None | View All Drafts:None |
    When I save and close form
    Then should see "Role saved" flash message

  Scenario: Check John Doe permission
    Given I proceed as the Admin
    And I go to Marketing/ Landing Pages
    When I click view "Page" in grid
    Then I should not see "Create draft"
    And I should see following grid:
      | Title          | Slug           | Owner    |
      | John Doe Draft | john-doe-draft | John Doe |
    And number of records in "CMS Page Drafts Grid" should be 1
    And I should see following actions for John Doe Draft in grid:
      | View |
      | Edit |
    And I should not see following actions for John Doe Draft in grid:
      | Delete        |
      | Duplicate     |
      | Publish draft |

  Scenario: Check Misty permission
    Given I proceed as the Buyer
    And I go to Marketing/ Landing Pages
    When I click view "Page" in grid
    Then I should not see "Create draft"
    And I should see following grid:
      | Title       | Slug        | Owner       |
      | Misty Draft | misty-draft | Misty Grant |
    And number of records in "CMS Page Drafts Grid" should be 1
    And I should see following actions for Misty Draft in grid:
      | View |
      | Edit |
    And I should not see following actions for Misty Draft in grid:
      | Delete        |
      | Duplicate     |
      | Publish draft |

  Scenario: Enable View Drafts permissions
    Given I proceed as the Admin
    And I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Landing Page | View All Drafts:Organization |
    When I save and close form
    Then should see "Role saved" flash message

  Scenario: Check John Doe view permission
    Given I proceed as the Admin
    And I go to Marketing/ Landing Pages
    When I click view "Page" in grid
    Then I should not see "Create draft"
    And I should see following grid:
      | Title          | Slug           | Owner       |
      | John Doe Draft | john-doe-draft | John Doe    |
      | Misty Draft    | misty-draft    | Misty Grant |
    And I should see following actions for John Doe Draft in grid:
      | View |
      | Edit |
    And I should see following actions for Misty Draft in grid:
      | View |

  Scenario: Check Misty view permission
    Given I proceed as the Buyer
    And I go to Marketing/ Landing Pages
    When I click view "Page" in grid
    Then I should not see "Create draft"
    And I should see following grid:
      | Title          | Slug           | Owner       |
      | John Doe Draft | john-doe-draft | John Doe    |
      | Misty Draft    | misty-draft    | Misty Grant |
    And I should see following actions for John Doe Draft in grid:
      | View |
    And I should see following actions for Misty Draft in grid:
      | View |
      | Edit |

  Scenario: Enable Create Drafts permissions
    Given I proceed as the Admin
    And I go to System/User Management/Roles
    And click edit "Administrator" in grid
    When select following permissions:
      | Landing Page | Create Drafts:Organization |
    When I save and close form
    Then should see "Role saved" flash message

  Scenario: Check duplicate permission
    Given I proceed as the Buyer
    And I go to Marketing/ Landing Pages
    When I click view "Page" in grid
    Then I should see following actions for Misty Draft in grid:
      | View      |
      | Edit      |
      | Duplicate |
    When I click view "Misty Draft" in grid
    And I click "Duplicate"
    And I click "Yes" in confirmation dialogue
    Then I should see "Draft has been saved" flash message
    When I fill "CMS Page Form" with:
      | Titles | Misty Draft (Duplicated) |
    And I save and close form
    Then I should see "Draft has been saved" flash message

  Scenario: Enable Delete All Drafts permissions
    Given I proceed as the Admin
    And I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Landing Page | Delete All Drafts:Organization |
    When I save and close form
    Then should see "Role saved" flash message

  Scenario: Check John Doe Delete All Drafts permission
    Given I proceed as the Admin
    And I go to Marketing/ Landing Pages
    When I click view "Page" in grid
    Then I should see following grid:
      | Title                    | Slug                   | Owner       |
      | John Doe Draft           | john-doe-draft         | John Doe    |
      | Misty Draft              | misty-draft            | Misty Grant |
      | Misty Draft (Duplicated) | misty-draft-duplicated | Misty Grant |
    And I should see following actions for John Doe Draft in grid:
      | View      |
      | Edit      |
      | Duplicate |
    And I should see following actions for Misty Draft in grid:
      | View      |
      | Duplicate |
      | Delete    |
    And I should see following actions for Misty Draft (Duplicated) in grid:
      | View      |
      | Duplicate |
      | Delete    |
    When I click view "Misty Draft" in grid
    And I click "Delete"
    Then I should see "Are you sure you want to delete this Landing Page?"
    When I click "Yes, Delete"
    Then I should see "Landing Page deleted" flash message

  Scenario: Enable Delete Own Drafts permissions
    Given I proceed as the Admin
    And I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Landing Page | Delete Own Drafts:Organization |
    When I save and close form
    Then should see "Role saved" flash message

  Scenario: Check John Doe Delete Own Drafts permission
    Given I proceed as the Admin
    And I go to Marketing/ Landing Pages
    When I click view "Page" in grid
    Then I should see following grid:
      | Title                    | Slug                   | Owner       |
      | John Doe Draft           | john-doe-draft         | John Doe    |
      | Misty Draft (Duplicated) | misty-draft-duplicated | Misty Grant |
    And I should see following actions for John Doe Draft in grid:
      | View      |
      | Edit      |
      | Duplicate |
      | Delete    |
    And I should see following actions for Misty Draft (Duplicated) in grid:
      | View      |
      | Duplicate |
      | Delete    |
    When I click view "John Doe Draft" in grid
    And I click "Delete"
    Then I should see "Are you sure you want to delete this Landing Page?"
    When I click "Yes, Delete"
    Then I should see "Landing Page deleted" flash message

  Scenario: Enable Edit All Drafts permissions
    Given I proceed as the Admin
    And I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Landing Page | Edit All Drafts:Organization |
    When I save and close form
    Then should see "Role saved" flash message

  Scenario: Check John Doe Edit All Drafts permission
    Given I proceed as the Admin
    And I go to Marketing/ Landing Pages
    When I click view "Page" in grid
    Then I should see following grid:
      | Title                    | Slug                   | Owner       |
      | Misty Draft (Duplicated) | misty-draft-duplicated | Misty Grant |
    And I should see following actions for Misty Draft (Duplicated) in grid:
      | View      |
      | Duplicate |
      | Delete    |
      | Edit      |
    When I click edit "Misty Draft" in grid
    And I fill "CMS Page Form" with:
      | Titles | Misty Draft (Edited) |
    And I click "Save as new draft"
    Then I should see "Draft has been saved" flash message
    And I should see Landing Page with:
      | Title | Misty Draft (Edited) |

  Scenario: Enable Publish Drafts permissions
    Given I proceed as the Admin
    And I go to System/User Management/Roles
    And click edit "Administrator" in grid
    And select following permissions:
      | Landing Page | Publish Drafts:Organization |
    When I save and close form
    Then should see "Role saved" flash message

  Scenario: Check John Doe Publish Drafts permission
    Given I proceed as the Admin
    And I go to Marketing/ Landing Pages
    When I click view "Page" in grid
    Then I should see following grid:
      | Title                    | Slug                   | Owner       |
      | Misty Draft (Duplicated) | misty-draft-duplicated | Misty Grant |
      | Misty Draft (Edited)     | misty-draft-edited     | John Doe    |
    And I should see following actions for Misty Draft (Edited) in grid:
      | View          |
      | Duplicate     |
      | Delete        |
      | Edit          |
      | Publish draft |
    And I should see following actions for Misty Draft (Duplicated) in grid:
      | View          |
      | Duplicate     |
      | Delete        |
      | Edit          |
      | Publish draft |
    When I click view "Misty Draft (Edited)" in grid
    And I click "Publish draft"
    Then I should see "Are you sure you want to publicate draft?"
    When I click "Yes" in confirmation dialogue
    Then I should see "Draft has been published" flash message
    And I should see Landing Page with:
      | Title | Misty Draft (Edited) |
    And I should see following grid:
      | Title                    | Slug                   | Owner       |
      | Misty Draft (Duplicated) | misty-draft-duplicated | Misty Grant |
