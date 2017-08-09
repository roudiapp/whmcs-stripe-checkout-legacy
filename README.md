
- [WHMCS Payment gateway module for Stripe Checkout by RoudiApp](#whmcs-payment-gateway-module-for-stripe-checkout-by-roudiapp)
- [Features](#features)
- [Change Log](#change-log)
- [Installation Instruction](#installation-instruction)
- [Configuration](#configuration)

# WHMCS Payment gateway module for Stripe Checkout by RoudiApp
This is a native WHMCS Payment Gateway Module for Stripe Checkout using checkout.js by RoudiApp.com.
Latest Stripe PHP API is also used to finalise the charge process.

# Features
* Supports Card and Bitcoin Payments only.
* Suitable for one time payments only.
* 100% PCI compliance.
* Supports Full and Partial refund
* Supports Instant Payment Verification
* Supports Radar, enable address check and configure your Radar Rules to fully benefit form this feature.
* Supports all currencies supported by stripe.
* No Webhook configuration is required.
* Transaction Fee calculation, this is for finance report only and will not add any surcharge.

# Change Log
- Version 4 
    * Added Radar support
    * Stripe PHP API is upgraded
    * Bug Fix: Bitcoin js config set expected boolean value, string was passed.
    * Updated: js Address config set was updated to comply with new stripe checkout configuration setup.
    * Removed: Alipay support is removed as Stripe Checkout no longer supports Alipay. Alipay support is added to a new module.


# Installation Instruction
* **Please make sure that you have full backup from your existing installation and that a record is stored locally.**
* Unzip your package and find the stripeXXX and callback folders (XXX represents the Stripe PHP API version). 
* Upload stripeXXX folder (ROOT represents your WHMCS installation folder).
* Upload roudiappstripecheckout.php file to ROOT/modules/gateways 
* From callback folder find roudiappstripecheckout_payment.php file and upload to ROOT/modules/gateways/callback folder.
* Install and configure your RoudiApp Stripe Checkout module from Admin menu > Setup > Payments > Payment Gateways
* Find RoudiApp Stripe Checkout module to install.

# Configuration 
* Transaction Fee % and Transaction Fee Fix boxes are for calculating the transaction fee and will not add any surcharge.
