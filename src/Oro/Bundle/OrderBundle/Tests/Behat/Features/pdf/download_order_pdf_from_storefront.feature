@fixture-OroOrderBundle:pdf/download_order_pdf.yml
@regression
@behat-test-env

Feature: Download Order PDF from Storefront

  Scenario: Feature Background
    Given sessions active:
      | Buyer | first_session  |
      | Guest | second_session |

  Scenario: Buyer downloads order PDF from storefront view page
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Order History"
    And I click View PdfSuccessOrder in grid
    When I download the PDF file from the "Download" link
    Then the PDF response status code should be 200
    And the downloaded PDF file should be a valid PDF
    And the downloaded PDF file should contain "Hello, PDF World!"
    And I remember the PDF file URL from the "Download" link

  Scenario: Buyer fails to download order PDF
    Given I am on homepage
    And I click "Account Dropdown"
    And I click "Order History"
    And I click View PdfFailureOrder in grid
    When I download the PDF file from the "Download" link
    Then the PDF response status code should be 404

  Scenario: Guest user cannot download order PDF via direct link
    Given I proceed as the Guest
    When I follow the remembered PDF file URL
    Then the url should match "/customer/user/login"

  Scenario: Disabling enable Order PDF download in storefront option
    Given login as administrator
    When go to System / Configuration
    And I follow "Commerce/Orders/Order Creation" on configuration sidebar
    And I fill "Configuration Order Creation Form" with:
      | Enable Order PDF Download in Storefront Use Default | false |
      | Enable Order PDF Download in Storefront             | false |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check that the PDF is not downloaded from the storefront view page
    Given I proceed as the Buyer
    When I reload the page
    Then I should not see an "Order Download Button" element
