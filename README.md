# Email

Magento2 module for email providers integration.

![example](https://user-images.githubusercontent.com/412612/40238625-6bdf426c-5abc-11e8-98ca-9b459efa3fa4.png)

Following email services are supported:

 -  Amazon SES
 -  Gmail
 -  Mandrill
 -  Sendmail
 -  Manual SMTP settings

### Installation

Run the following commands:

```bash
cd <magento_root>
composer require swissup/module-email
bin/magento module:enable Swissup_Email
bin/magento setup:upgrade
bin/magento setup:di:compile
```

### Usage

 1. Navigate to "Marketing → Communications → Email Services"
 ![example1](https://user-images.githubusercontent.com/412612/40238984-6f0b1262-5abd-11e8-89af-8de7a6a93fa7.png)
 2. Press "Add New" button
 3. Create service
    - Specify the Name
    - Choose Type from drop-down
    - Fill all required fields
    - Press "Check Service" button and wait for response
    - If everything works fine, press "Save" button
 ![example](https://user-images.githubusercontent.com/412612/40238625-6bdf426c-5abc-11e8-98ca-9b459efa3fa4.png)
 4. Navigate "Stores → Configurations → Advanced → System → Mail Sending Settings"
 5. Select new service in the "Default Transport Email Service" drop-down
 ![config](https://user-images.githubusercontent.com/412612/40239232-2b0aaf5e-5abe-11e8-94c5-b96862d7ccc9.png)
 6. Save
