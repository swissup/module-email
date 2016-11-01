# Email

Magento2 module for email providers integration.

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
composer require swissup/email
bin/magento module:enable Swissup_Email
bin/magento setup:upgrade
```

### Usage

 1. Navigate to "Marketing → Communications → Email Services"
 2. Press "Add New" button
 3. Create service
    - Specify the Name
    - Choose Type from drop-down
    - Fill all required fields
    - Press "Check Service" button and wait for response
    - If everything works fine, press "Save" button
 4. Navigate "Stores → Configurations → Advanced → System → Mail Sending Settings"
 5. Select new service in the "Default Transport Email Service" drop-down
