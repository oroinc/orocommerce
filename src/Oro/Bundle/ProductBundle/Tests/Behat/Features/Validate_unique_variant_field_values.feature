@fixture-Products_validate_unique_variant_field_values.yml
Feature: Validate unique variant field values when changing simple products or extended fields

  #Case 1:
  #Validate unique variant field values in configurable product in case of change product variant
  #Case 2:
  #Changing/Removing extended fields or enum values from entity configuration
  #Case 3:
  #Changing/Removing attributes from product family

  # -------------- v Done in story BB-7110 v---------------------------

  #  Preconditions:
  #   Product attribute:
  #     Field Name: Color
  #     Type: Select
  #     Options:
  #       Green
  #       Red
  #       Yellow
  #   Product attribute:
  #     Field Name: Size
  #     Type: Select
  #     Options:
  #       L
  #       M
  #       S
  #    Product family: T_shirt
  #     Attribute: Color
  #     Attribute: Size
  #  Extended field in Product Entity:
  #   Field Name: Brand
  #   Storage Type: Table column
  #   Type: Select
  #   Options:
  #       Nike
  #       Lacoste


  #  Product1:
  #    Type: Simple
  #    Product Family: T_shirt
  #    name - Green shirt L
  #    sku - gtsh_l
  #    product status - enabled
  #    inventory status - in stock
  #    unit - item
  #    price list - default
  #
  #  Product2:
  #    Type: Simple
  #    Product Family: T_shirt
  #    name - Red_shirt M
  #    sku - rtsh_m
  #    product status - enabled
  #    inventory status - in stock
  #    unit - item
  #    price list - default
  #    Additional - Brand - Lacoste

  #   configurable product:
  #     Variant fields: Size, Color
  #    name - Shirt_1
  #    sku - shirt_101
  #    product status - enabled
  #    variant: Green shirt L
  #    variant: Red shirt M

  Scenario: Prepare product attributes
    Given I login as administrator

    # Create Color attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Color  |
      | Type       | Select |
    And I click "Continue"
    And I click "Add"
    And I click "Add"
    And I click "Add"
    And I fill in "oro_entity_config_type[enum][enum_options][1][label]" with "Green"
    And I fill in "oro_entity_config_type[enum][enum_options][2][label]" with "Red"
    And I fill in "oro_entity_config_type[enum][enum_options][3][label]" with "Yellow"
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Create Size attribute
    And I go to Products / Product Attributes
    And I click "Create Attribute"
    And I fill form with:
      | Field Name | Size  |
      | Type       | Select |
    And I click "Continue"
    And I click "Add"
    And I click "Add"
    And I click "Add"
    And I fill in "oro_entity_config_type[enum][enum_options][1][label]" with "L"
    And I fill in "oro_entity_config_type[enum][enum_options][2][label]" with "M"
    And I fill in "oro_entity_config_type[enum][enum_options][3][label]" with "S"
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Create Brand extended field
    And I go to System / Entities / Entity Management
    And filter Name as is equal to "Product"
    And I click View Product in grid
    And I click "Create field"
    And I fill form with:
      | Field Name   | Brand         |
      | Storage Type | Table column  |
      | Type         | Select        |
    And I click "Continue"
    And I click "Add"
    And I click "Add"
    And I fill in "oro_entity_config_type[enum][enum_options][1][label]" with "Nike"
    And I fill in "oro_entity_config_type[enum][enum_options][2][label]" with "Lacoste"
    And I save form
    Then I should see "Field saved" flash message

    # Update schema
    And I go to Products / Product Attributes
    And I confirm schema update

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit T_shirt in grid
    And I fill in "oro_attribute_family[attributeGroups][0][labels][values][default]" with "T-shirt group"
    And I additionally select "SKU" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I additionally select "Name" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I additionally select "Is Featured" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I additionally select "Description" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I additionally select "Short Description" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I additionally select "Images" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I additionally select "Inventory Status" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I additionally select "Meta description" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I additionally select "Meta keywords" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I additionally select "Product prices" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I additionally select "Color" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I additionally select "Size" from "oro_attribute_family[attributeGroups][0][attributeRelations][]"
    And I save form
    Then I should see "Successfully updated" flash message

  Scenario: Prepare configurable products
    And I go to Products / Products
    And I click Edit gtsh_l in grid
    And I fill form with:
      | Color | Green |
      | Size  | L     |
      | Brand | Nike  |
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And I click Edit rtsh_m in grid
    And I fill form with:
      | Color | Red     |
      | Size  | M       |
      | Brand | Lacoste |
    And I save form
    Then I should see "Product has been saved" flash message

    And I go to Products / Products
    And I click Edit shirt_101 in grid
    And I check "Color"
    And I check "Size"
    And I check "Brand"
    And I save form
    Then I should see "Product has been saved" flash message
    And I go to Products / Products
    And I click Edit shirt_101 in grid
    And I check gtsh_l and rtsh_m in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Check if attributes can't be deleted
    And I go to Products / Product Attributes
    And I click Remove Color in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Cannot remove field because it's used as a variant field in the following configurable products: shirt_101" error message
    Then I should see Color in grid

  Scenario: Check if Enum values can't be deleted on Attribute page
    And I go to Products / Product Attributes
    And I click Edit Color in grid
    Then I should see info tooltip for enum value "Red"
    Then I should see info tooltip for enum value "Green"
    And I click info tooltip for enum value "Red"
    Then I should see "This value can not be deleted because it is used in the following configurable products: shirt_101" popup
    And I click info tooltip for enum value "Green"
    Then I should see "This value can not be deleted because it is used in the following configurable products: shirt_101" popup

  Scenario: Check if Attribute not deleted from Product Family if it contained unique values
    And I go to Products / Product Families
    And I click Edit T_shirt in grid
    And I unselect "Color" option from "oro_attribute_family[attributeGroups][t_shirt_group][attributeRelations][]"
    And I save form
    Then I should see "Attributes Color used as configurable attributes in products: shirt_101" error message

  Scenario: Check if Enum value can't be deleted from Extended field
    And I go to System / Entities / Entity Management
    And filter Name as is equal to "Product"
    And I click View Product in grid
    And I click Remove Brand in grid
    Then I should see "Are you sure you want to delete this field?"
    And I click "Yes"
    Then I should see "Cannot remove field because it's used as a variant field in the following configurable products: shirt_101" error message
    Then I should see Brand in grid

  Scenario: Check if Enum value can be deleted if it is not unique
    And I go to Products / Product Attributes
    And I click on Color in grid
    And I should see info tooltip for enum value "Green"
    And I go to Products / Products
    And click Edit shirt_101 in grid
    And I uncheck first 2 records in grid
    And I save form
    Then I should see "Product has been saved" flash message
    And I go to Products / Product Attributes
    And I click on Color in grid
    And I delete enum value by name "Green"
    Then I should not see enum value "Green"
    And I save form
    Then I should see "Attribute was successfully saved" flash message

    # Restore state
    And I go to Products / Product Attributes
    And I click on Color in grid
    And I click "Add"
    And I fill in "oro_entity_config_type[enum][enum_options][2][label]" with "Green"
    And I save form
    Then I should see "Attribute was successfully saved" flash message
    And I go to Products / Products
    And I click Edit gtsh_l in grid
    And I fill form with:
      | Color | Green |
    And I save form
    Then I should see "Product has been saved" flash message
    And I go to Products / Products
    And click Edit shirt_101 in grid
    And I check gtsh_l and rtsh_m in grid
    And I save form
    Then I should see "Product has been saved" flash message

  Scenario: Check if Attribute can be deleted if it does not contain unique Enum values

    And I go to Products / Products
    And click Edit shirt_101 in grid
    And I uncheck "Color"
    And I save form
    Then I should see "Product has been saved" flash message
    And I go to Products / Product Attributes
    And I click Remove Color in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message

  Scenario: Check if Extended field can be deleted if it does not contain unique Enum values
    And I go to Products / Products
    And click Edit shirt_101 in grid
    And I uncheck "Brand"
    And I save form
    Then I should see "Product has been saved" flash message
    And I go to System / Entities / Entity Management
    And filter Name as is equal to "Product"
    And I click View Product in grid
    And I click Remove Brand in grid
    Then I should see "Are you sure you want to delete this field?"
    And I click "Yes"
    Then I should see "Field successfully deleted" flash message

  Scenario: Check if Enum value can be deleted if Product with unique Enum values was deleted
    And I go to Products / Products
    And I click delete shirt_101 in grid
    Then I should see "Are you sure you want to delete this Product?"
    And I click "Yes, Delete"
    Then I should see "Product deleted" flash message
    And I go to Products / Product Attributes
    And I click Remove Size in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message

    And I click Logout in user menu
    And I should see "Login"
