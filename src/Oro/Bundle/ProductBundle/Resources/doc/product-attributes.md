Product attributes
==================
This topic contains the following sections: 
* [Understanding Product Attributes](#understanding-product-attributes) 
* [Creating Product Attributes](#creating-product-attributes)
* [Using Product Attributes in Product Families](#using-product-attributes-in-product-families)
* 

Understanding Product Attributes
--------------------------------

Product attribute in Product bundle is a special type of custom entity field that enables easy management for groups of attributes that are unique to a special product family. To limit the product data to the necessary characteristics, you can bind the attribute groups to the product families they fit.

For example, when your OroCommerce store sells TVs and T-shirts, these items share some generic attributes (e.g. name, vendor), and differ in the remaining attributes set. For example, there might be a *Screen properties* group that contains *resolution*, *diagonal*, and *matrix* that should be linked to the products in the TV product family. For the T-shirts family, the linked attribute group may have color, size, material, fit and care guidance (washing, ironing, dry cleaning, etc).

With product attributes functionality that extends the Product bundle, you can:
* add product attributes (extend fields)
* use product attributes in the scope of Product families (similar to categories) and attribute groups.
* organize and distinguish products of different types, which actually have different sets of characteristics applicable to. 

On 'Products -> Product Attributes' page, there is a grid of attributes created for the product entity. By defaults, there are only generic predefined attributes (sku, description, names).

**Note**: System attributes (sku, description, names) are shared among all product families. Delete is disabled.

Creating Product Attributes
---------------------------

To create a new Landing Page:

1. Navigate to **Products > Product Attributes** in the main menu.
   ![Product Attributes page](/images/ProductAttributes.png)
2. Click **Create attribute**. The following page opens:
   ![Create Product Attribute page. Step 1](/images/ProductAttributesCreate.png)
3. Fill in the field name using only alphabetic symbols, underscore and numbers. It should be at least 2 characters long.
4. Select an attribute type (bigint, select, string, etc) and click **Continue**. The following page opens:
   ![Create Product Attribute page. Step 2](/images/ProductAttributesCreate2_1.png)
5. Fill in remaining general information:
   - Attribute label
   - Attribute description
   - Additional information for the product attributes of the following types:
      - **Select**, **Multi-Select**:
        ![Create Product Attribute page. Step 2. Select options: Add](/images/ProductAttributesCreate2_1_Select1.png)
        
        ![Create Product Attribute page. Step 2. Select options: Update](/images/ProductAttributesCreate2_1_Select2.png)
        
        ![Create Product Attribute page. Step 2. Select options: All](/images/ProductAttributesCreate2_1_Select3.png)
      - **Image**: 
        ![Create Product Attribute page. Step 2. Image options](/images/ProductAttributesCreate2_1_Image.png)
       
      - **File**: file size
         ![Create Product Attribute page. Step 2. File options](/images/ProductAttributesCreate2_1_File.png)
6. Fill in details for the product attribute import/export:
   - Column Name
   - Column position
   - Use As Identity Field (options: **No**, Only when not empty, Always)
   - Exclude Column (options: **No**, Yes)
   ![Create Product Attribute page. Step 2. Import group](/images/ProductAttributesCreate2_1_Import_group.png)
7. In the Other section, specify the configuration options for the product attribute view, search and use in other areas in OroCommerce:
   - Available In Email Templates (options: **Yes**, No)
   - Contact Information 
   - Add To Grid Settings (**Yes and display**, )
   - Show Grid Filter (**No**)
   - Show On Form (**Yes**,)
   - Show On View (**Yes**,)
   - Priority 
   - Searchable (**No**,) - includes the attribute into the search options on the backend.
   - Auditable (**No**, )
   - Applicable Organizations (All) 
   - Searchable (**No**, Yes) - includes the attribute into the search options on the OroCommerce web store.
   - Filterable (**No**, Yes)
   - Sortable (**No**, Yes)
   - Enabled (**No**, Yes)

   ![Create Product Attribute page. Step 2. Import group](/images/ProductAttributesCreate2_1_Import_group.png)
8. Once all options and information are provided, click **Save**.
9. If the created attribute is of a *table column* storage type, click **Update Schema** to reindex the data for search and filter. 
   The product attribute storage type is set to *table column* for the attribute with Select of Multi-Select data type, and also for attribute of any type with *Filterable* or *Sortable* option enabled.
   If this step is ommited, the newly created attribute will not appear in the select attribute options in other areas of OroCommerce (e.g. product families configuration).
 
Using Product Attributes in Product Families
--------------------------------------------

On 'Products -> Product Families' there is a grid of Product Families created for product entity. Initially there is only
one predefined family with set of default groups with system attributes assigned. Groups are 'General', 'Product Prices',
'Inventory', 'Images'. You can move any system attribute from one to another group or assign new attribute simply choosing
needed one in groups select. Also you can create new group as well as remove existing. If there were any system attributes
confirmation dialog will appear. After confirm deletion all system attributes will be automatically moved to first group 
in the list. System attributes should be always assigned to family and it is not possible to remove such attribute only
move to another group.

Creating new Product Family
---------------------------
Default Product Family may not be enough to cover all your needs. To create new one click 'Create Product Family' button.
On form there are several required fields - 'Code' and 'Label'. Also required condition for creating family is to 
have at least one group with all system attributes assigned. Initially there is 'Default group'. For adding new one
click on 'Add' button. After filling label of new group (which should be unique in scope of one family) you can assign
new attribute or move already assigned from other groups. After saving changes new Product Family will be created.

Assigning Product Family to product
-----------------------------------

On first step of product creating form you need to choose Product Family in select (all newly created families
will appear here). After you click 'Continue' button all Attribute Groups will be available on the form.
Each group occupies own tab with all attributes assigned to it. All manipulations with attributes is reflected on edit/
create product pages and on view page as well. Depending on attribute type each attribute will have corresponding input 
(datepicker, file, textarea, select) so you can set value to it and save product.
It is possible to customize attribute groups displaying on frontend - [Customize products using layouts](./Resources/doc/customize-products.md). For quick examples, see the following sections:
* [Product Family](./customize-pdp.md#product-family)
  - [Attribute Set (example 1)](./customize-pdp.md#attribute-set-example-1)
  - [Attribute Set (example 2)](./customize-pdp.md#attribute-set-example-2)
        - [Attribute Groups](./customize-pdp.md#attribute-groups)
