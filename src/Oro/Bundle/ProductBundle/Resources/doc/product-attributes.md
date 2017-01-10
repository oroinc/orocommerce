Product attributes
==================

Product attribute in Product bundle is a special type of custom entity field that enables easy management for groupes of attributes that are unique for a special product family. To limit the product data to the necessary characteristics, you can bind the attribute groups to the product families they fit.

For example, when your OroCommerce store sells TVs and T-shirts, these items share some generic attributes (e.g. name, vendor), and differ in the remaining attributes set. For example, there might be a *Screen properties* group that contains *resolution*, *diagonal*, and *matrix* that should be linked to the products in the TV product family. For the T-shirts family, the linked attribute group may have color, size, material, fit and care guidance (washing, ironing, dry cleaning, etc).

With product attributes functionality that extends the Product bundle, you can:
* add product attributes (extend fields)
* use product attributes in scope of Product families (similar to categories) and attribute groups.
* organize and distinguish products of different types, which actually have different sets of characteristics applicable to. 
 
Creating attributes
----------------------
On 'Products -> Product Attributes' page you can see grid of attributes created for product entity. There are several predefined 
attributes on grid (sku, description, names). These are system attributes that should be assigned to any family. Clicking
on 'Create attribute' you will be redirected on attribute creating two-step form. On first step you have to fill required 
options - field name and attribute type (bigint, select, string, etc). On second step only label is required. 
There are also options 'Filterable', 'Sortable', if one of it will be selected or complex field type chosen (select, multiselect)
'table column' storage type will be applied to attribute. Such attribute will appear in groups select only after
schema changes applied. Otherwise serialized field type will be applied to attribute and it does not require any additional
actions. All attributes created from UI is 'Custom'.
 
Manipulating attributes
---------------------------
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
---------------------------
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
