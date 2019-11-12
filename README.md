# Paystation payment module for Hikashop

This integration is currently only tested up to Joomla 3.3.6 with Hikashop Starter 2.3.4

## Requirements
* An account with [Paystation](https://www2.paystation.co.nz/..
* An HMAC key for your Paystation account, contact our support team if you do not already have this <support@paystation.co.nz>

## Installation

These instructions will guide you through installing the module and conducting a test transaction.

After you have installed Joomla and Hikashop correctly:

1. Log in to the admin pages of your Joomla site

2. Under the Extensions menu, select Extensions Manager

3. Under the "Upload Package File" tab, select this ZIP file. Click "Upload & Install". The message "Installing plugin was successful" should appear.

4. On the left hand side of the page, select "Manage"

5. Find "Hikashop Paystation Three-party payment Plugin". Check the box beside this.

6. Click the "Enable" button near the top-left of the screen. The message "1 extension successfully enabled" should appear.

7. Under the Components menu, select Hikashop Configuration

8. From the Hikashop System menu, select "Payment Methods". (This is not the same as the Joomla "System" menu, which appears at the very top-left of the page. The Hikashop System menu is separate and appears on the left-hand side below the "Configuration" heading.

9. Above the list of payment methods, there is a green "New" button. Click it.

10. The list of available Hikashop payment plugins will appear. Click on the link "Hikashop Paystation Three-party payment Plugin"

11. In the "Main information" panel on the left, you can optionally change the Description, which is the text that appears beside the Paystation payment method in the checkout.

12. In the "Generic configuration" panel on the right, change Published to Yes.

13. Optionally, you may choose which images alongside the description in the checkout.

14. Refer Hikashop documentation o. settings for Price and Percentage.

15. In the Paystation ID field, put your Paystation ID provided by Paystation.

16. In the Gateway field, put the Gateway ID provided by Paystation.

17. In the HMAC field, put the HMAC key provided by Paystation.

18. Set Transaction Mode to Test.

19. We strongly suggest setting Postback to 'Enabled' as it will allow the cart to capture payment results even if your customers re-direct is interrupted. However, if your development/test environment is local or on a network that cannot receive connections from the internet, you must set ' Postback' to 'Disabled'.

Your Paystation account needs to reflect your Hikashop settings accurately, otherwise order status will not update correctly. Email support@paystation.co.nz with your Paystation ID and advise whether 'Enable Postback' is set to 'Yes' or 'No' in your Hikashop settings.

20. Use "Successful payment status" and "Unsuccessful payment status" to choose the status of transactions that are successful of unsuccessful, respectively.

21. Refer to Hikashop documentation for information on the "Restrictions" and "Access level" panels.

22. Click the "Save & Close" button. The message "Successfully Saved" will appear.

23. Optionally, if you have more than one payment option method, you may change the order in which they appear.

24. Go to your online store.

25. To do a successful test transaction, make a purchase where the final cost will have the cent value set to .00, for example $1.00, this will return a successful test transaction. To do an unsuccessful test transaction make a purchase where the final cost will have the cent value set to anything other than .00, for example $1.01-$1.99, this will return an unsuccessful test transaction. 

Important: You can only use the test Visa and Mastercards supplied by Paystation for test transactions. They can be found here [Visit the Test Card Number page](https://www2.paystation.co.nz/developers/test-cards/).

26. When you go to checkout - make sure you choose Paystation Payment Gateway in the Payment method section.  If everything works ok, go back to the 'Payment Methods' page, find the Paystation module, and click the Configure link. 
 Change the mode from 'Test' to 'Live', and click the Update button

27. Fill in the form found on https://www2.paystation.co.nz/go-live so that Paystation can test and set your account into Production Mode.

28. Congratulations - you can now process online credit card payments
