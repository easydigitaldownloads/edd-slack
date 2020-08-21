=== Easy Digital Downloads - Slack ===
Author URI: https://easydigitaldownloads.com
Plugin URI: https://easydigitaldownloads.com/downloads/slack/
Contributors: d4mation
Requires at least: 4.4
Tested up to: 5.5
Stable Tag: 1.1.2

== Description ==

Slack Integration for Easy Digital Downloads

== Changelog ==
= v1.1.2, August 21, 2020 =
* Fix: Improved compatibility with EDD Software Licensing 3.6
* Fix: Improved PHP 7.3 and 7.4 compatibility.
* Fix: When reviews was active, but FES was not, a fatal error occured in the settings.
* Fix: Updated Slack icon on the welcome page.
* Fix: Removed dependency of markdown parser for changelog by linking directly to the changelog.
* Dev: Rebuilt the build system for Gulp 4.

= v1.1.1, February 7, 2018 =

* Fixed bug causing `%active_site%` to sometimes fail when using a license activation or deactivation trigger

= v1.1.0, September 22, 2017 =

* **New:** Slack Team Invites are now incorporated into EDD Slack
  * This is activated separately from Interactive Notifications/Slash Commands and must be done by a Slack User with the ability to invite other Users to the Team normally (This is often only Slack Team Admins)
  * Activating this functionality adds a Checkbox to the Customer Checkout Form as well as the Vendor Registration Form from EDD Frontend Submissions that will send a Slack Team Invite to that Email Address.
  * Customers/Vendors can also be added to the Slack Team manually by accessing the Tools tab on the Customer screen or the Profile tab under the Vendor screen respectively.
* **New:** Three New Slash Commands
  * `/edd version`: Outputs the current version of Easy Digital Downloads.
  * `/edd discount`: Outputs information about a Discount Code. This can also be used to create new Discount Codes.
  * `/edd customer`: Outputs information about a Customer. You can use either their Customer ID or their Primary Email Address for this command.
* **New:** Now you can choose Multiple Downloads for a single Notification
  * If "All Downloads" is chosen, you can also optionally set Exclusions
* **New:** EDD Reviews Triggers added
  * New Review Trigger
  * New Vendor Feedback Trigger (Requires EDD Frontend Submissions to be active as well)
* **New:** EDD Fraud Monitor Trigger added
  * New Suspected Fraudulent Transaction
      * This can also be made into an Interactive Notification to Accept the Payment as Valid or to Confirm it as Fraud directly from Slack
          * When the log is added to EDD for this, it will say that this action was processed by EDD Slack and by which Slack User
* **New:** Recurring Payments Triggers added
  * New Subscription Created Trigger
  * Subscription Cancelled Trigger
* **New:** Add a Message about additional SSL-only functionality on non-SSL sites
* **New:** Responsive Settings Screen
* **New:** Admin Color Scheme taken into account for Notification Delete buttons
* **Change:** Use Customer Information rather than User Information where applicable.
  * In cases where a Notification uses User information instead (New Vendor, etc.), the text replacement description text is changed accordingly
* **Change:** The layout of information for the `/edd sales` Slash Command has been updated to match the new Slash Commands in this release.
* **Change:** EDD Software Licenses Integration Changes
  * License Activation Triggers are now specific to Activation/Deactivation via EDD's web API. Activating/Deactivating manually from the Licenses screen no longer triggers these.
    * This can cause problems in some server configurations due to caching. `edd_action=activate` and `edd_action=deactivate` URL Parameters should be excluded from caching on the Home Page.
  * `%active_site%` text replacement for License Activation/Deactivation Triggers
  * `%license_link%` text replacement for License Generation/Activation/Deactivation Triggers
* **Change:** No longer using a PHP Constant for the Text Domain
* **Change:** Restrict Slash Commands to Slack Team Admins by default. Specific Users can be allowed via an interface on the Settings screen.
  * This requires the OAUTH Token to need new permissions, so unlinking and relinking the application is necessary.
* **Change**: Labels for Fields are now above the Fields in the Notification Modal
* **Fix:** When Saving a Notification, the "Save Notification" button now updates to show that it is in the process of Saving.
* **Fix:** Notification Forms can be re-filled out after failure to fill a required field
* **Fix**: Fix bug with the Purchase Limit Trigger when a Price ID was not set (No Variable Pricing enabled)
* **Fix**: `%site_count%` added to text replacements for License Activation/Deactivation Triggers. This existed in v1.0.X, but it was accidentally excluded from the list of text replacements.
* **Fix**: Fully bails on loading the plugin if base conditions weren't met. Before it just threw up a notice but still attempted to run.
* **Fix**: If no Message Text was defined for a Notification, while it *would* go through properly, the notification pop-up on a desktop client would show a weird error. This now no longer happens.

= v1.0.3, January 21, 2017 =

* **Fix:** `%purchase_link%` and `%download_link%` returning malformed URLs to Slack
* **Fix:** Stop Vendor Feedback triggering a New Comment.

= v1.0.2, January 4, 2017 =

* **Fix:** Plugin name did not match download name on easydigitaldownloads.com which would cause licensing to fail.

= v1.0.1, January 4, 2017 =

* **Fix:** Minor bug prevented Slash Commands from sending properly

= v1.0.0, January 4, 2017 =

* Initial Release