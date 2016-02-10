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
