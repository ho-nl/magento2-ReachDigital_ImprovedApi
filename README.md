# magento2-ReachDigital_ImprovedApi

## Update Product
Allows updating attribute data only for the given attributes, without requiring all other attribute data to be passed as well. Extension attributes are not supported and should be updated using their own API call.

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

`productDefault` is optional. It's a product object that contains a list of default values.
These values will be used _only_ if they don't exist already. 
