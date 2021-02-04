*This is the developer repository for the bluem-woocommerce WordPress and WooCommerce plug-in. The stable, production-ready version of the plug-in will be available from the WordPress plug-in directory soon.*

Use this repository to get insight and possibly contribute to the development of this plug-in.

# Installation
<!-- If you want to install this plug-in, the easiest way is to use the WordPress plug-in directly from the WordPress plug-in directory here: -->

## Deploy from this source code repository
If you want to use this repository, follow the following steps to compile 

1. Download the contents of this repository as a ZIP file, or clone it to your computer
2. Install [Composer](https://getcomposer.org) on your local machine
3. Run the `composer update` command in the downloaded folder. This will generate a `vendor` folder and install all required dependencies and libraries.
4. Ensure the folder and its contents are located at the `./wp-content/plugins/bluem-woocommerce` path. (You could also compress the contents of this folder into a ZIP file and upload this into your site).
5. Your plug-in should now be visible within your WordPress plugin list. Activate the plug-in from this page

If possible, please run the installation procedure on a testing environment first, before installing the plug-in in a development environment.

If you are having trouble running the above commands, please contact us. We are glad to help or to provide a compiled version of the above See the Support section for instructions on how to do this.

# Configuration

Use the **Settings** -> **Bluem** page to configure the plug-in completely.

Please note the following:

- You have to activate the specific parts of the plug-in that you want to use. All separate services are indvidiually activatable. By default, they are not activated.


# important usage features

## adding additional data to request for mandates
see this example as how you could do this by adding a novel filter to the id `bluem_woocommerce_enhance_mandate_request`

```php
add_filter('bluem_woocommerce_enhance_mandate_request', 'nextdeli_administratie_bluem_add_customer_name_to_mandate', 10, 1);
/**
 * allow third parties to add additional data to the request object through this additional action
 *
 * @param [type] $request
 * @return void
 */
function nextdeli_administratie_bluem_add_customer_name_to_mandate(
    Bluem\BluemPHP\EmandateBluemRequest $request
)
{
    global $current_user;

    // or something like: $current_user->display_name;
    $request->addAdditionalData("CustomerName", $current_user->user_login);

    return $request;
}
```

## adding additional data to request for payments

see this example as how you could do this by adding a novel filter to the id `bluem_woocommerce_enhance_payment_request`
```php
add_filter('bluem_woocommerce_enhance_payment_request', 'nextdeli_administratie_bluem_add_customer_name_to_payment', 10, 1);
/**
 * allow third parties to add additional data to the request object through this additional action
 *
 * @param [type] $request
 * @return void
 */
function nextdeli_administratie_bluem_add_customer_name_to_payment(
    $request
)
{
    global $current_user;
    // or something like: $current_user->display_name;
    $request->addAdditionalData("CustomerName", $current_user->user_login);

    return $request;
}
```

## further process  IDIN response


by default, idin shortcode responses are simply saved but not validated to a user database. 


The identification is also stored as-is, so there is no check on whether a name or other piece of information exactly matches. You still have to do that yourself, via a filter or a piece of code that you can work with like this. This is because the validation is expected to be very domain and customer specific.

You can find out if the validation was successful by using the following PHP code in a plug-in or template:

```php
    if(function_exists('bluem_idin_user_validated')) {
        $validated = bluem_idin_user_validated();

        if($validated) {
            // validated
        } else {
            // not validated
        }
    }
```

These results can be obtained as an object by using the following PHP code in a plug-in or template:


```php
    if(function_exists('bluem_idin_retrieve_results')) {
        $results = bluem_idin_retrieve_results();
        // print, show or save the results
        // for example:
            echo $results->BirthDateResponse; // prints 1975-07-25
            echo $results->NameResponse->LegalLastName; // prints Vries
        }
```

## important notes when compiling:

- delete `vendor/daanrijpkema/bluem-php/examples` to be sure as it is not necessary in production.




# Support
If you have any questions, please send an email to [info@daanrijpkema.com](mailto:info@daanrijpkema.com).
