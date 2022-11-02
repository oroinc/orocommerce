@ticket-BB-16669
Feature: Upload product watermark image
  In order to manage product images
  As an Administrator
  I want to be able to set image watermark for the product images in the system configuration

  Scenario: Try upload watermark image with invalid MIME type
    Given I login as administrator
    And I go to System / Configuration
    And I follow "Commerce/Product/Product Images" on configuration sidebar
    And I fill "Product Image Watermark Config" with:
      | Use Default | false        |
      | File        | example1.xcf |
    And I save setting
    Then I should see "Product Image Watermark Config" validation errors:
      | File | This file is not a valid image. |

  Scenario: Upload watermark image with correct MIME type
    When I fill "Product Image Watermark Config" with:
      | File | cat1.jpg |
    And I save setting
    Then I should see "Configuration saved" flash message
    And I should see "cat1.jpg"
