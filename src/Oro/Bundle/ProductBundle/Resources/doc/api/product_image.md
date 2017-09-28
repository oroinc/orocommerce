# Oro\Bundle\ProductBundle\Entity\ProductImage

## ACTIONS

### create

Create a new Product image record.
The created record is returned in the response.

{@request:json_api}
Example:

</api/productimages>

```JSON
    {
      "data": {
        "type": "productimages",
        "id": "product-image-1",
        "relationships": {
          "product": {
            "data": {
              "type": "products",
              "id": "1"
            }
          },
          "types": {
            "data": [
              {
                "type": "productimagetypes",
                "id": "product-image-type-1"
              }
            ]
          },
          "image": {
            "data": {
              "type": "files",
              "id": "file-1"
            }
          }
        }
      },
      "included": [
        {
          "type": "files",
          "id": "file-1",
          "attributes": {
            "mimeType": "image/jpeg",
            "originalFilename": "onedot.jpg",
            "fileSize": 631,
            "content": "/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAIBAQIBAQICAgICAgICAwUDAwMDAwYEBAMFBwYHBwcGBwcICQsJCAgKCAcHCg0KCgsMDAwMBwkODw0MDgsMDAz/2wBDAQICAgMDAwYDAwYMCAcIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAz/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD+f+iiigD/2Q=="
          }
        },
        {
          "type": "productimagetypes",
          "id": "product-image-type-1",
          "attributes": {
            "productImageTypeType": "main"
          },
          "relationships": {
            "productImage": {
              "data": {
                "type": "productimages",
                "id": "product-image-1"
              }
            }
          }
        }
      ]
    }
```
{@/request}

The example above also creates product image mandatory subresources : files and types.
The subresources can be managed also within there specific: files [here](#get--admin-api-files) ,
and types [here](#get--admin-api-productimagetypes). 

### get

{@inheritdoc}

### get_list

{@inheritdoc}

### update

To update a existing product image the new field needs to be specified in the **"data"** section.

Example , update image file:

      "image": {
        "data": {
          "type": "files",
          "id": "1"
        }
      }

After adding the new existing file record to the **"data"** section , the **"included"** section need to be updated also
with the new record details using the meta update flag.

Example:

        {
          "meta":{
            "update": true
          },
          "type": "files",
          "id": "1",
          "attributes": {
            "mimeType": "image/jpeg",
            "originalFilename": "onedot.jpg",
            "fileSize": 631,
            "content": "/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAIBAQIBAQICAgICAgICAwUDAwMDAwYEBAMFBwYHBwcGBwcICQsJCAgKCAcHCg0KCgsMDAwMBwkODw0MDgsMDAz/2wBDAQICAgMDAwYDAwYMCAcIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAz/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD+f+iiigD/2Q=="
          }
        }

{@request:json_api}
Complete request example:

</api/productimages/1>

```JSON
    {
      "data": {
        "type": "productimages",
        "id": "1",
        "attributes": {
          "updatedAt": "2017-09-07T08:14:35Z"
        },
        "relationships": {
          "product": {
            "data": {
              "type": "products",
              "id": "1"
            }
          },
          "types": {
            "data": [
              {
                "type": "productimagetypes",
                "id": "1"
              },
              {
                "type": "productimagetypes",
                "id": "2"
              },
              {
                "type": "productimagetypes",
                "id": "3"
              }
            ]
          },
          "image": {
            "data": {
              "type": "files",
              "id": "1"
            }
          }
        }
      },
      "included": [
        {
          "meta":{
            "update": true
          },
          "type": "files",
          "id": "1",
          "attributes": {
            "mimeType": "image/jpeg",
            "originalFilename": "onedot.jpg",
            "fileSize": 631,
            "content": "/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAIBAQIBAQICAgICAgICAwUDAwMDAwYEBAMFBwYHBwcGBwcICQsJCAgKCAcHCg0KCgsMDAwMBwkODw0MDgsMDAz/2wBDAQICAgMDAwYDAwYMCAcIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAz/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD+f+iiigD/2Q=="
          }
        }
      ]
    }
```
{@/request}

### delete

{@inheritdoc}

### delete_list

{@inheritdoc}

## FIELDS

### product

The product for the productImage

### types

The imaget types for the productImage

### image

The image file for the productImage

## SUBRESOURCES

### product

#### get_subresource

Retrieve product of a specific productImage record. 

#### get_relationship

Retrieve the ID of the product for a specific productImage.

#### update_relationship

Replace the product for a specific productImage.

{@request:json_api}
Example:

</api/productimages/2/relationships/product>

```JSON
    {
      "data": {
        "type": "products",
        "id": "2"
      }
    }
```
{@/request}

### types

#### get_subresource

Retrieve the records for the types of a specific productImage record.

#### get_relationship

Retrieve a list of IDs for the types of a specific productImage record. 

#### update_relationship

Replace the types for a specific productImage.

{@request:json_api}
Example:

</api/productimages/2/relationships/types>

```JSON
    {
      "data": [
        {
          "type": "productimagetypes",
          "id": "4"
        }
      ]
    }
```
{@/request}


#### add_relationship

Set the types of a specific productImage record.

{@request:json_api}
Example:

</api/productimages/2/relationships/types>

```JSON
    {
      "data": [
        {
          "type": "productimagetypes",
          "id": "16"
        }
      ]
    }
```
{@/request}

#### delete_relationship

Remove the types of a specific productImage record.

### image

#### get_subresource

Retrieve the image file of a specific productImage record.  

#### get_relationship

Retrieve the ID of the image file for a specific productImage.

#### update_relationship

Replace the image file for a specific productImage.

{@request:json_api}
Example:

</api/productimages/2/relationships/image>

```JSON
    {
      "data": {
        "type": "files",
        "id": "1"
      }
    }
```
{@/request}
