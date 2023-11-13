# magento2-ReachDigital_ImprovedApi

## Update Product

Allows updating attribute data only for the given attributes, without requiring all other attribute data to be passed as
well. Extension attributes are not supported and should be updated using their own API call.

### Interface

`reachDigitalImprovedApiProductRepositoryV1`

### Endpoint

`PUT /V1/products/default/:sku`

### Body

> SKU is mandatory in both the endpoint and the `product` body of the request.

```json
{
  "product": {
    "sku": "SCHROEF",
    "price": 249,
    "weight": 2,
    "custom_attributes": {
      "short_description": "Short Description"
    }
  },
  "productDefault": {
    "custom_attributes": {
      "description": "Default Description."
    }
  }
}
```

###

> There is an option to set fields to their default value by specifying the stores in REST url and values in the json

```json
{
  "product": {
    "weight": "4",
    "sku": "testing-muts",
    "price": 1224,
    "extension_attributes": {
      "use_default": [
        "name",
        "status"
      ]
    }
  }
}
```

`productDefault` is optional. It's a product object that contains a list of default values.
These values will be used _only_ if they don't exist already. 

## Update Product Media

This module introduces a new way to upload images. We can use the media_type
`url` to upload images from an url instead of a base64 encoded image string.

Uploading an image using the url is possible while updating a product or
creating a new gallery entry. The following example is a call example for the
`V1/products/:sku/media` endpoint.

See the
[official docs](https://developer.adobe.com/commerce/webapi/rest/quick-reference/)
for more information.

### Endpoints

`PUT /V1/products/:sku`

`POST /V1/products/:sku/media`

### Body

> `type` is optional, if not provided the type wil be determined by the response
> headers of the url.

> `name` is optional, if not provided a name wil be used based on the provided
> url.

```json
{
  "entry": {
    "media_type": "url",
    "label": "macbook",
    "position": 0,
    "disabled": false,
    "types": ["base", "small", "thumbnail"],
    "extension_attributes": {
      "url_content": {
        "url": "https://images.unsplash.com/flagged/1/apple-gear-looking-pretty.jpg",
        "type": "image/jpeg",
        "name": "example_image.jpeg"
      }
    }
  }
}
```
