# Changelog

## Version 4.0.0 - 02 June 2025

- Compatible with Magento 2.4.8
- New Payment Method: Google Pay.


## Version 3.0.2 - 23 May 2024

- Compatible with Magento 2.4.7
- Compatible with PHP 8.3
- General improvements.

## Version 3.0.1 - 07 April 2023

- Compatible with PHP 8.2

## Version 3.0.0 - 31 March 2023

- Compatible with Magento 2.4.6
- eCheck new payment method. (ACH) Beta version.
- New payment action "Order only". Use to generate token only at time of order.
- $0 Authorization feature added.
- SameSite Cookie issue with an embedded iFrame payment method is solved.
- Always save credit card feature.
- Allow editing saved cards from the My Account.
- Allow AcceptUI.js (Hosted payment Form as popup)
- General improvements.

## Version 2.2.2 - 24 Sep, 2022

- Compatible with Magento 2.4.5

## Version 2.2.1 - 13 Aug, 2022

- Allow to Cancel the order from the Magento admin even if a transaction is expired in the Authorize.net.
- Removed AVS and CVV response code from the email notification of embedded iframe payment method order.
- More user-friendly error messages.
- Fixed issue of special characters passing in request data.
- When save card is disabled, do not even create the customer profile in the authorize.net account.

## Version 2.2.0 - 06 May, 2022

- Compatible with 2.4.4
- IOS Devices fixes: fix the issue of customers automatically getting logout from IOS and Safari when iFrame payment or Apple Pay payment method is enabled.
- Accept and deny fraud transaction error message handling, and display the exact error message from the server.
- General improvements

## Version 2.1.0 - 20 Nov, 2021

- Save credit card token from the Magento admin order.
- Increase API connection timeout limit to 45.
- Updated character maximum limit as per current API guide.

## Version 2.0.6 - 22 Oct, 2021

- Fixed issue of street address not passing some time with a specific version of the Magento and admin settings.

## Version 2.0.5 - 20 Sep, 2021

- Compatible with Magento 2.4.3
- Updated log code to make it compatible with Magento 2.4.3
- Security Updates and general Improvements.

## Version 2.0.4 - 04 Sep, 2021

- Compatible with Magento 2.3.4
- Fixed issue of Accept and Deny fraud status payment (Review Payment) when using multi-store setup.
- Fixed conflict of saved credit card feature enabled for both embed iFrame and on-site payment. It’s creating an issue when the same customer saved a card from both payment methods.

## Version 2.0.3 - 31 July, 2021

- Fixed conflict with PayPal Pro JS in Magento 2.4.2

## Version 2.0.2 - 25 June, 2021

- Fixed length issue of shipping method.

## Version 2.0.1 - 08 April, 2021

- Compatible with 2.4.2
- Added New Payment Method, VISA CHECKOUT.
- API validation and display message whether entered API is correct or not.
- Apple Pay bug fix when term and condition is enabled.
- General improvements.

## Version 2.0.0 - 14 Oct, 2020

- Compatible with 2.4.0
- Order status = "Payment Review" if the transaction is Fraud.
- Embedded iFrame Payment Method.
- Google reCAPTCHA Verification.
- Supports generating order from admin using the saved credit card.
- General improvements.

## Version 1.0.2 - 05 June, 2020

- Fixed issue of virtual product order.
- Fixed issue of admin order not working when Accept.js is enabled.

## Version 1.0.1 - 19 May, 2020

- Compatible with 2.3.5
- Added "Content Security Policy" files required for Magento 2.3.5
- Instant Purchase from the product page without entering any detail.
- Supports orders with the multi-address shipment.
- General improvements.

## Version 1.0.0 - 04 Jan, 2020

- Initial release.
- Capture payment without leaving the website.
- Save credit card to Magento Vault. (Tokenization)
- Order using saved credit cards.
