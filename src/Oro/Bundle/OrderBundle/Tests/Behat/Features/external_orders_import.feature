@fixture-OroProductBundle:products.yml
@fixture-OroUserBundle:users.yml

Feature: External orders import
  In order to be able to have orders from the 3-party systems
  As an Administrator
  I want to be able to import orders in JSON format

  Scenario: Enable External orders import
    Given I login as administrator
    And I go to System/ Configuration
    And I follow "Commerce/Orders/External Order Import" on configuration sidebar
    When I uncheck "Use default" for "Enable JSON Order Import" field
    And I check "Enable JSON Order Import"
    And I save form
    Then I should see "Configuration saved" flash message
    And the "Enable JSON Order Import" checkbox should be checked

  Scenario: Data Template for Orders
    Given I login as administrator
    When I go to Sales/ Orders
    Then there is no records in grid
    When I download Data Template file with processor "external_order_import" and job "entity_export_template_to_json"
    Then I see "identifier" field in JSON template
    And I see "poNumber" field in JSON template
    And I see "lineItems" field in JSON template
    And I see "billingAddress" field in JSON template
    And I see "shippingAddress" field in JSON template

  Scenario: Import new Orders without data
    Given I go to Sales/ Orders
    When I import "external_orders/import_empty_orders.json" import file
    And I reload the page
    Then Email should contains the following "Import of the import_empty_orders.json was completed." text
    And Email should contains the following "Errors: 7 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. currency: This value should not be blank."
    And I should see "Error in row #1. lineItems.0.productSku: Please choose Product."
    And I should see "Error in row #1. lineItems.0.quantity: This value should not be blank."
    And I should see "Error in row #1. lineItems.0.productUnit: The product unit does not exist for the product."
    And I should see "Error in row #1. lineItems.0.currency: This value should not be blank."
    And I should see "Error in row #1. lineItems.0: No matching price found. Source: price."
    And I should see "Error in row #1. This value should not be blank. Source: /data/0/relationships/customer/data."

  Scenario: Import new Orders with full data
    Given I login as administrator
    And I go to Sales/ Orders
    When I import "external_orders/import_orders.json" import file
    And I reload the page
    Then Email should contains the following "Import of the import_orders.json was completed." text
    And Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text
    And I should see following grid:
      | Order Number | Owner     | Customer       | Total   |
      | 1000ORD1005  | Megan Fox | first customer | $100.00 |
    When I click View "1000ORD1005" in grid
    Then I shouldn't see Edit action
    And I should see "Owner: Megan Fox (Main)"
    And I should see "Created At: Jan 14, 2023, 12:00 AM"
    And I should see "Order #1000ORD1005"
    And I should see "Order Number 1000ORD1005"
    And I should see "PO Number P10O001005"
    And I should see "Customer first customer"
    And I should see "Customer User Amanda Cole"
    And I should see "Customer Notes Some note"
    And I should see "Billing Address John Doe 5th Avenue NYC NY US 10001"
    And I should see "Shipping Address Jane Smith Sunset Blvd LA NY US 90001"
    And I should see "Ship By Jan 15, 2023"
    And I should see "Shipping Status Shipped"
    And I should see "Subtotal $100.00"
    And I should see "Total $100.00"
    And I should see "UPS"
    And I should see "1Z123"
    And I should see following "Backend Order Line Items Grid" grid:
      | SKU  | Product   | Quantity | Product Unit Code  | Price  | Ship by      | Notes  |
      | TAG1 | Product X | 2        | item               | $50.00 | Jan 10, 2023 | Urgent |

  Scenario: Try to import the same file again
    Given I go to Sales/ Orders
    When I import "external_orders/import_orders.json" import file
    And I reload the page
    Then Email should contains the following "Import of the import_orders.json was completed." text
    And Email should contains the following "Errors: 1 processed: 0, read: 1, added: 0, updated: 0, replaced: 0" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. identifier: This value is already used."

  Scenario: Import Orders with minimal data
    Given I login as administrator
    And I go to Sales/ Orders
    When I import "external_orders/import_orders_minimal.json" import file
    And I reload the page
    Then I should see following grid:
      | Order Number | Owner     | Customer       | Total   |
      | 1000ORD1006  | John Doe  | first customer | $20.00  |
      | 1000ORD1005  | Megan Fox | first customer | $100.00 |
