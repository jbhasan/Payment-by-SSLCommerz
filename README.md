# Payment by SSL Commerz

#### Made by Sayeed

## Integration

##### Run below command for installing the package

##### Step 1

```
composer require sayeed/payment-by-sslcommerz
```

##### Step 2

```
php artisan migrate
```

##### Step 3

Put below information in `.env` file

-   `IS_LOCALHOST=true` (for local environment TRUE other then FALSE)
-   `SSLCZ_TESTMODE=true` (for test environment TRUE and for LIVE FALSE)
-   `SSLCZ_STORE_ID=<STORE_ID>`
-   `SSLCZ_STORE_PASSWORD=<STORE_PASSWORD>`
-   `SSLCZ_SUCCESS_URL=/success_payment`
-   `SSLCZ_FAILED_URL=/failed_payment`
-   `SSLCZ_CANCEL_URL=/cancel_payment`

```
php artisan config:clear
```

##### Step 4 (Uses)

Submit your request to `/pay` route with params:

-   amount
-   customer_name
-   customer_email
-   customer_mobile
-   product_name
-   customer_address [optional]
-   customer_country [optional]

###### [Custom value on your requirement]

-   value_a
-   value_b
-   value_c
-   value_d

##### Step 5 (Uses)

After successfull request you will get a base64 encoded data with status and message, which is shown as:
`{"status":"completed","message":"Transaction is successfully Completed"}`
