![Bluem](https://bluem.nl/img/BluemAboutIcon.svg)

**Bluem-WordPress and WooCommerce plug-in for Payment, Mandates, iDIN & IBAN-Name check**

*This is the developer repository and notes for the bluem WordPress and WooCommerce plug-in. 
The stable, production-ready version of the plug-in is available from the WordPress plug-in directory: [wordpress.org/plugins/bluem/](https://wordpress.org/plugins/bluem/).*

Use this repository to get insight and possibly contribute to the development of this plug-in.

# Requirements
This plug-in requires PHP >= 8.0.

If you use our plug-in files or databases in your own custom development, please disable auto-update and check each update manually before installing.
Our plug-in files or database tables structure may change during time.


## Development & testing
Work on the dev-master branch. This branch is used for development and testing.
The master branch is locked and should only be used for production-ready releases

### Before you start developing
Run 
```bash
make add_git_hooks
```
to enable git hooks, which will automatically run **unit** tests and CS linting (soon) before any commit.

- Also if you want to lint or use automatic lint-fixes, ensure that the PHP CS fixer is installed in the `tools` folder.

### Unit testing

```bash
make unit_test
```

### Acceptance testing
Your local environment (a WordPress website instance has to be running, at [localhost:8000]()).

```bash
make acceptance_test
```


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
Use the **Bluem** -> **Settings** page to configure the plug-in completely.

**Please note:** You have to activate the specific parts of the plug-in that you want to use. All separate services can be activated independently. 
By default, they are not activated.

# Important usage features

## Adding additional data to request for mandates
See this example as how you could do this by adding a novel filter to the id `bluem_woocommerce_enhance_mandate_request`:

```php
add_filter('bluem_woocommerce_enhance_mandate_request', 'bluem_add_customer_name_to_mandate', 10, 1);
/**
 * allow third parties to add additional data to the request object through this additional action
 *
 * @param [type] $request
 * @return void
 */
function bluem_add_customer_name_to_mandate(
    Bluem\BluemPHP\EmandateBluemRequest $request
)
{
    global $current_user;

    // or something like: $current_user->display_name;
    $request->addAdditionalData("CustomerName", $current_user->user_login);

    return $request;
}
```

## Adding additional data to request for payments
See this example as how you could do this by adding a novel filter to the id `bluem_woocommerce_enhance_payment_request`:

```php
add_filter('bluem_woocommerce_enhance_payment_request', 'bluem_add_customer_name_to_payment', 10, 1);
/**
 * allow third parties to add additional data to the request object through this additional action
 *
 * @param [type] $request
 * @return void
 */
function bluem_add_customer_name_to_payment(
    $request
)
{
    global $current_user;
    // or something like: $current_user->display_name;
    $request->addAdditionalData("CustomerName", $current_user->user_login);

    return $request;
}
```

## How to further process the IDIN response
By default, idin shortcode responses are simply saved but not validated to a user database. 

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
## Blocking a checkout procedure when using iDIN shortcodes
Add a filter for id `bluem_checkout_check_idin_validated_filter` if you want to add a filter to block the checkout procedure based on the IDIN validation procedure being completed.
If the injected function returns true, the checkout is enabled. If you return false, the checkout is blocked and a notice is shown.

Example that would block checkout if validation is not executed:

```php
add_filter(
    'bluem_checkout_check_idin_validated_filter', 
    'my_plugin_check_idin_validated_filter_function', 
    10, 
    1
);
function my_plugin_check_idin_validated_filter_function()
{
    if (!bluem_idin_user_validated()) {
      return false;
    }
    return true;
}
```

By default, this is disabled as it is quite context specific if the webshop is strict.

## Important notes when compiling:
- delete `vendor/bluem-development/bluem-php/examples` to be sure as it is not necessary in production.

# Instant Mandates
You can use our plug-in to initiate an eMandate transaction directly from, as example, within an App.
All u have to do is make a GET request to bluem-woocommerce/mandate_instant_request with the `debtorreference` as query.
Make sure the debtorreference is unique and refers to an customer/user.
Set the return URI within the Bluem plug-in settings.

# Shortcodes

## eMandate
It is possible to include our eMandate-meganism in a page, by using the `[bluem_machtigingsformulier]` shortcode.

# Integrations
Besides of the integration with WooCommerce, we also have integrations with the popular ContactForm 7 and Gravity Forms. Below you'll find the instructions to activate and use the integration.

## Important notes
- The integration(s) has to be enabled in the **Bluem** -> **Settings** page first before they will work.

## ContactForm 7
To activate our ContactForm 7 integration, in the form settings, go to the tab additional settings. Enter the following codesnippets to active the flow:
```php
bluem_mandate=true
bluem_mandate_reason="Mandate reason"
bluem_mandate_success="Bedankt voor het afgeven van de machtiging"
bluem_mandate_failure="De machtiging is mislukt. Probeer het opnieuw."
bluem_mandate_type="RCUR"
bluem_is_ajax=true
```
Also, add a checkbox with the name 'bluem_mandate_approve'. This will give the user-permission to perform the mandate request.
Otherwise, the form will be submitted but our mandate request wouldn't be executed. U can mark the checkbox within ContactForm 7 as required to always force the mandate request after form submission.

## Gravity Forms
To activate our Gravity Forms integration, you have to add some hidden fields to the form to activate the flow.
```php
bluem_mandate=true
bluem_mandate_reason="Lidmaatschap"
bluem_mandate_success="Bedankt voor het afgeven van de machtiging"
bluem_mandate_failure="De machtiging is mislukt. Probeer het opnieuw."
bluem_mandate_type="OOFF"
```

Also, add a checkbox with the name 'bluem_mandate_approve' (under 'Advanced' section, after enabling dynamic entries) and a label with the value 'true'. This will give the user-permission to perform the mandate request.
Otherwise, the form will be submitted but our mandate request wouldn't be executed. U can mark the checkbox within Gravity Forms as required to always force the mandate request after form submission.

Add a hidden field with this label and value to your form if the form is being called through AJAX. 
```
bluem_is_ajax=true
```
Also, if you want to store additional transaction details, add hidden fields with the following field names.
Our plug-in will fill these fields so that they are saved with the other form data.
The transaction and details are always visible through our plug-in page.
```php
bluem_mandate_accountname = Name of the accountholder
bluem_mandate_datetime = Date and time of registration
bluem_mandate_iban = IBAN of the account
bluem_mandate_request_id = MandateID
```
Our plug-in will store the above data in the fields with the corresponding names.

# Development

## Docker
The Dockerfile within this package is used to run composer with a specific PHP version.
Please follow the steps below to build a Docker environment and run composer.

1) Build the Docker environment.
```shell
docker build -t my-php8-composer .
```
2) Run composer in the created Docker environment.
```shell
docker run --rm -v $(pwd):/var/www my-php8-composer composer show
```
3) Prepare plug-in dependencies before deployment.
```shell
docker run --rm -v $(pwd):/var/www my-php8-composer composer update --no-dev
```

# Support
If you have any questions, please email [pluginsupport@bluem.nl](mailto:pluginsupport@bluem.nl).



# Sketchpad for todo's

// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking
// deprecate function bluem_db_create_link soon
