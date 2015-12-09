# Email
### Description

Magento2 email providers integartion (smtp, mandrill, amazon ses) 

### Installation

```bash
cd <magento_root>
composer config repositories.swissup/email vcs git@github.com:swissup/email.git
composer require swissup/email
bin/magento module:enable Swissup_Email
bin/magento setup:upgrade
```
