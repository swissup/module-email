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

#### Gmail Service Setup

If `Type` selects `Gmail`. Use an [App Password](https://security.google.com/settings/security/apppasswords): Generate a new App Password for your Gmail account.
[Sign in with App Passwords](https://support.google.com/accounts/answer/185833)

#### Gmail API with Google OAuth 2.0 Support Service Setup
> - [Beginning September 30, 2024, third-party apps that use only a password to access Google Accounts and Google Sync will no longer be supported.](https://workspaceupdates.googleblog.com/2023/09/winding-down-google-sync-and-less-secure-apps-support.html)
>
> - [Transition from less secure apps to OAuth](https://support.google.com/a/answer/14114704?hl=en)

If the `Type` field is set to `Gmail OAuth 2.0`, please follow the [Google instructions](https://developers.google.com/identity/openid-connect/openid-connect#registeringyourapp) to create the required credentials. In your credentials, you need to add `Authorized redirect URIs` with at least one URI, such as `https://localhost/swissup_email/gmail/getOAuth2Token/` (replace `localhost` with your Magento store URL).
![Gmail OAuth2 Credential](https://github.com/swissup/module-email/assets/412612/47802486-2725-4642-91e2-8ff8ead58389)

###### Customize the User Consent Screen
In the `OAuth consent screen`, you need to enable the `Gmail API` scope. ![Add Scope](https://github.com/swissup/module-email/assets/412612/84204084-a0be-4c54-8e1b-72e8c53c08e8). Also, add your Gmail email address as a Test User.
After setting up your credentials, enter the following fields:
- `Client ID` in `User (key)`
- `Client secret` in `Password (secure key)`
In the `Email (from)` field, enter your Gmail email address.

### Logging

 1. Navigate "Stores → Configurations → Advanced → System → Mail Sending Settings"
 2. "Logging Enable" set Yes
 3. Navigate to "Marketing → Communications → Email Logs"


##### [Using MailHog via Docker for testing email](https://akrabat.com/using-mailhog-via-docker-for-testing-email/) 

I recently needed to modify the emails that a client project sends out. It was set up to send via SMTP and so the easiest way to do this for me was to add a local MailHog instance and point the application at it.

Manually running via Docker
The quickest and easiest way to do this is via Docker.

Manually, we can do:

```
$ docker run -p 8025:8025 -p 1025:1025 mailhog/mailhog
```
This will run MailHog with the SMTP port exposed on localhost port 1025 and the web interface on 8025.

Now you can configure the app’s SMTP config and away you go.

Option    | Value
----------|-------
Type      | Smtp
Host      | 0.0.0.0 or mailhog
Port      | 1025
Auth Type | None
Secure    | None
