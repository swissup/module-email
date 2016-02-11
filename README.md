# Email

Magento2 module for email providers integrations (smtp, mandrill, amazon ses)

### Installation

Run the following commands:
```bash
cd <magento_root>
composer require swissup/email
bin/magento module:enable Swissup_Email
bin/magento setup:upgrade
```
### Guide

 1. In menu

    `Marketing -> Communications -> Email Services` or
    `Swissup -> Email Services.`

    > **Note:** Paths are identical

 2. Press "Add new" button

 3. Create service
    - Specify the Name
    - Choose Type from drop-down
    - Fill all required fields
    - Before enabling you have to click `Save and Continue` and `Check service` buttons
    - Enable/Disable

 4. Navigate to

    `Stores -> Configurations -> Swissup -> Email` or
    `Stores -> Configurations -> Advanced -> System -> Mail Sending Settings`

    > **Note:** Paths are identical

    And select transport email service in the "Default Transport Email Service" drop-down