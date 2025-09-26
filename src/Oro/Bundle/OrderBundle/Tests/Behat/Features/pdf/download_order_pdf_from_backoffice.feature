@fixture-OroOrderBundle:pdf/download_order_pdf.yml
@regression
@behat-test-env

Feature: Download Order PDF from Backoffice

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |

  Scenario: Admin downloads order PDF successfully
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales/Orders
    And I click View PdfSuccessOrder in grid
    And I download the PDF file from the "Download" link
    Then the PDF response status code should be 200
    And the downloaded PDF file should be a valid PDF
    And the downloaded PDF file should contain "Hello, PDF World!"
    And I remember the PDF file URL from the "Download" link

  Scenario: Admin fails to download order PDF
    Given I go to Sales/Orders
    And I click View PdfFailureOrder in grid
    When I download the PDF file from the "Download" link
    Then the PDF response status code should be 404

  Scenario: Unauthenticated user cannot download order PDF via direct link
    Given I proceed as the Guest
    When I follow the remembered PDF file URL
    Then the url should match "/admin/user/login"
