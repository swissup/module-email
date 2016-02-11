# Email
### Description

Magento2 for email providers integrations (smtp, mandrill, amazon ses)

### Installation

1. Open `<magento_root>/composer.json` and change `minimum-stability` setting to `dev`.
2. Run the following commands:
```bash
cd <magento_root>
composer config repositories.swissup composer http://swissup.github.io/packages/
composer require swissup/email
bin/magento module:enable Swissup_Email
bin/magento setup:upgrade
```
### Guide

 1. In menu
    Marketing -> Communications -> Email Services
    or
    Swissup -> Email Services.

    > **Note:** Pathes are identical

 2. Add new

 3. Specify the Name
    Choose Type from drop-down
    Fill all required fields
    Before enabling you have to click Save and Continue and Check service buttons
    Enable/Disable

 4. Navigate to

    Stores -> Configurations -> Swissup -> Email
    or
    Stores -> Configurations -> Advanced -> System -> Mail Sending Settings

    > **Note:** Pathes are identical

    And select transport email service in the "Default Transport Email Service" drop-down