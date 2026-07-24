# Withdrawal Button for Magento 2

> Magento 2 extension for implementing the EU right of withdrawal via button click.

---

## What is it about?

The EU Directive **(EU) 2023/2673** requires that in the future, consumers must be able to withdraw from online purchase contracts just as easily as they were concluded. **Starting June 19, 2026**, a clearly visible withdrawal button will be mandatory in online shops.

This Magento module provides exactly that: your customers can withdraw orders with just a few clicks – directly from their customer account or via a separate form for guest orders. As a shop operator, you maintain a full overview in the admin area.

---

## What does the module do?

### For your customers

**Withdrawal button in the order overview**

In the *My Account > My Orders* view, a new column appears for each order. There, the customer can see at a glance:

- A **Withdrawal link**, as long as the period is active
- The note **"Withdrawal submitted"**, if a withdrawal has already been made
- The note **"Withdrawal period expired"**, if the withdrawal period has passed

Additionally, a **"Withdrawal Order"** button is displayed on the order details page.

**Withdrawal detail page**

Before the actual withdrawal, the customer sees a summary of their order:

- Order number, date, status, total amount
- All ordered items with name, SKU, quantity, and price
- The deadline until which withdrawal is possible, calculated from the date of the last shipment
- A button for final submission – with a preceding security confirmation

**Partial withdrawal (optional)**

If the shop operator has activated the partial withdrawal option, the customer can select individual items from the order instead of withdrawing the entire order. Each item has a checkbox (pre-checked) and a quantity input field. Items that have already been withdrawn in a previous request are shown greyed out and cannot be selected again. This means several partial withdrawal requests can be submitted for the same order until all items are covered.

**Guest orders**

Customers who ordered without an account can access the withdrawal via a dedicated search form. Entering the order number and email address is sufficient to find the order and initiate the withdrawal.

Accessible at: `/withdrawal/guest/search`

**Confirmation page**

After submission, the customer is redirected to a success page. This confirms that the withdrawal has been received and that an email is on its way.

### For you as a shop operator

**Admin overview of all withdrawals**

Under *Sales > Withdrawals*, you will find a tabular overview of all received withdrawals:

- ID, order number, customer name, email
- Status (Pending / Confirmed / Rejected)
- Type (Full / Partial)
- Date of order and date of withdrawal
- Direct link to the respective order view

All columns can be filtered and sorted.

**Withdrawal detail page**

Each row in the grid has a *View Details* action that opens a dedicated detail page. It shows:

- All metadata: customer name and email, order number, withdrawal type, status, order date and withdrawal date
- Quick action buttons to confirm or reject the request directly on the page
- A complete table of the withdrawn items including product name, SKU and quantity – clearly labelled as full or partial withdrawal

This makes it possible to review exactly which items a customer has withdrawn without leaving the admin area.

**Automatic email notification**

Soon as a withdrawal is received, two emails are sent:

1. **To the customer** ₓ Confirmation with order details
2. **To you** – Notification with all relevant data

The email templates can be customized in the admin panel.

**Note in the order**

Every withdrawal is automatically added as a comment in the order history. This way, it is immediately apparent in the order view that a withdrawal exists.

**Configurable**

In the admin under *Stores > Configuration > Sales > Withdrawal Settings*:

- Enable/Disable the module
- Set recipient address for notifications
- Set withdrawal period in days, counted from the last shipment date (Default: 14)
- Enable/Disable partial withdrawal (Default: No)
- Select email sender and templates

---

## Hyvä Theme Compatibility

If you are using the Hyvä Theme, please install the Hyvä compatibility module:

https://github.com/Zwernemann/magento2-withdrawl-hyva

This module adds the required Hyvä frontend integration for the withdrawal button and ensures compatibility with the Hyvä template system.

The base module remains required.

### Multilingualism

Completely translated into all 24 languages of the EU (97 strings). Further languages can be added via custom CSV files.

---

## System Requirements

|Component | Version|
|---|---|
| Magento 2 Open Source | 2.4.6 to 2.4.8-p4 |
| PHP | 7.4 or higher |


---

## Installation

### Via ZIP file

1. Extract the ZIP file and copy the entire contents to:

   ```
   app/code/Zwernemann/Withdrawal/
   ```

 2. Ensure the structure looks like this:

   ```
   app/code/Zwernemann/Withdrawal/
       Api/
       Block/
       Controller/
       Helper/
       Model/
       Ui/
       etc/
       i18n/
       view/
       composer.json
       registration.php
   ```

3. Run the following commands in the Magento root:

   ```
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy de_DE en_US
   php bin/magento cache:flush
   ```

4. Check if the module is active:

   ```
   php bin/magento module:status zwernemann_Withdrawal
   ```
### Via Composer

```
composer require zwernemann/module-withdrawal
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy de_DE en_US
php bin/magento cache:flush
```

---

## Setup

1. Log into Magento Admin
2. Navigate to **Stores > Configuration > Sales > Withdrawal Settings**
3. Set **Enable Module** to *Yes*
4. Enter **Notification Email** – withdrawal notifications will be sent here
5. Adjust **Withdrawal Period** if the legal period differs
6. Set **Allow Partial Withdrawal** to *Yes* if customers should be able to withdraw individual items
7. Configure email sender and templates if necessary
8. Set **Enable REST API** to *Yes* if you use the public API endpoints
9. Configure further **REST API** settings if needed (order status, rate limiting)
10. Save and flush cache

### Linking the Guest Order Form

The search form for guest orders is located at:

```
https://www.your-shop.com/withdrawal/guest/search
```

Include this link, for example:

- In the footer of your shop
- In order confirmation emails
- On your withdrawal policy page

With Magento URL rewrites, you can adjust the address as desired, for example to `/withdrawal`.

---

## REST API

The module exposes REST endpoints for headless storefronts, custom forms, and integrations. **The public API is disabled by default** and must be explicitly enabled under **Withdrawal Settings > REST API > Enable REST API**. When disabled, the `can-withdraw` and `confirmation` endpoints respond with **HTTP 404** so they do not appear to exist.

Use a **valid store code** in the URL (e.g. `default`, `all`, or your store view code). An invalid store code returns *"Specified request cannot be processed."*

Base URL pattern:

```
https://www.your-shop.com/rest/<store_code>/V1/...
```

### Check withdrawal eligibility

Checks whether an order can be withdrawn. Returns only `true` or `false` — no order data is exposed.

```
POST /rest/<store_code>/V1/withdrawal/can-withdraw
Content-Type: application/json

{"email":"customer@example.com","orderNumber":"000000123"}
```

Response: `true` or `false`

Returns `false` when:

- The order number or email is missing or does not match
- The module is disabled
- The order status is not allowed
- The withdrawal period has expired (counted from the last shipment date)
- A withdrawal request already exists for this order

### Submit withdrawal confirmation

Registers a full-order withdrawal, sends customer and admin notification emails, and adds an entry to the admin withdrawal grid.

```
POST /rest/<store_code>/V1/withdrawal/confirmation
Content-Type: application/json

{"email":"customer@example.com","orderNumber":"000000123"}
```

Response: `true` on success

On success, the module:

1. Sends the customer confirmation email and admin notification
2. Creates a withdrawal record with all visible order items
3. Adds a comment to the order history
4. Optionally updates the order status (see configuration below)

If email delivery fails, nothing is persisted and the request can be retried.

Possible errors:

- API not enabled (HTTP 404)
- Order not found or not eligible (same rules as `can-withdraw`)
- Withdrawal already exists for this order
- Email delivery failure

No authentication token is required for these two endpoints.

### List withdrawals (admin)

Returns all withdrawal records. Requires an admin integration token and the `Zwernemann_Withdrawal::withdrawals` ACL permission.

```
GET /rest/<store_code>/V1/zwernemann/withdrawals
Authorization: Bearer <admin_token>
```

### API configuration

Under **Stores > Configuration > Sales > Withdrawal Settings > REST API**:

| Setting | Description |
|---|---|
| Enable REST API | **Default: No.** Must be set to Yes before the public endpoints accept requests. When No, endpoints return HTTP 404. |
| Order Status after API Confirmation | Optional. Sets the order status when a withdrawal is submitted via the API. Leave empty to keep the current status. |
| Enable API Rate Limiting | Second line of defence when the API is enabled. Limits requests per client (IP) per time window. Requires Redis in `app/etc/env.php` under `backpressure/logger`. |
| Rate Limit | Maximum requests per client per period (default: 10). |
| Rate Limit Period | Time window in seconds (default: 60). |

When rate limiting is enabled and the limit is exceeded, the API returns **HTTP 429 Too Many Requests**.

Eligibility for the public endpoints follows the same rules as the storefront: allowed order statuses, withdrawal period from the last shipment, and module enabled state.

---

## Uninstallation

```bash
php bin/magento module:disable Zwernemann_Withdrawal
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

Then delete the directory `app/code/Zwernemann/Withdrawal/`.

The database tables `zwernemann_withdrawal` and `zwernemann_withdrawal_items` remain and can be removed manually if needed.

---

## Version History

### 1.9.1
- New: customers can now see the status of their withdrawal (Pending / Confirmed / Rejected) in the order history, on the order view page, and on the withdrawal page, with a link to the withdrawal page (#57)
- Fix: withdrawal form line items now show gross prices (incl. VAT) according to the store's tax display setting instead of net prices (#64)
- Fix: the "Allowed Order Statuses" filter is now also enforced for orders that have not been shipped yet (previously they could bypass it) (#63)
- Fix: virtual orders are now handled correctly in the withdrawal eligibility check
- Fix: the withdrawal confirmation email now shows the exact submission date and time in the store timezone, as required by Art. 11a(4) of Directive 2011/83/EU (#61)

### 1.9.0
- Added  REST endpoints for headless storefronts, custom forms, and integrations
  
### 1.8.2
- Removed theme-specific CSS overrides and rely on native Magento button styling
- Fixed withdrawal checks for unshipped orders in pending payment state
- Fixed guest session data leaking into customer withdrawal requests
- Optimized CSS loading by restricting withdrawal.css to withdrawal pages
- Removed redundant BCC copy from customer withdrawal confirmation emails
 
### 1.8.0
- Admin: order history now records a comment when a withdrawal request is confirmed or rejected
- Admin: withdrawal detail page now links to the related shipment(s) and their packing slip PDF
- Security: the withdrawal success page is now bound to the customer session and no longer exposes an enumerable order ID in the URL; added robots noindex,nofollow
- Fix: corrected a timezone difference between the admin grid and the withdrawal detail page
- Completed and corrected translations across all 24 languages (withdrawal type label in customer email and the success page title are now translated)
- Clarified wording: customer order overview now shows "Withdrawal period expired"; clearer message when re-selecting already-withdrawn items
  
### 1.7.3
- Full PHP7.4 compatibility
  
### 1.7.2

- Fix multiple Magento 2 issues: refactor select-all into AMD component, remove inline styles in favor of CSS, rename reserved “select” class, persist guest email via session instead of URL, and correct withdrawal validation flow.

### 1.7.1

- Fix database table name resolution for shops with a table prefix
  
### 1.6.0

- Partial withdrawal (Teilwiderruf): customers can now select individual items and quantities when submitting a withdrawal request
- New backend setting *Allow Partial Withdrawal* (default: No) – full-order withdrawal remains the default behaviour
- Already withdrawn items are shown greyed out; further partial requests can be submitted for the remaining items
- New database table `zwernemann_withdrawal_items` stores the item-level withdrawal data
- Admin grid shows a *Type* column (Full / Partial) for each withdrawal entry
- New admin detail page (*View Details*) shows all withdrawal metadata, quick confirm/reject actions, and a full table of withdrawn items with name, SKU, and quantity
- Email notifications now include the list of withdrawn items

### 1.5.0
- Newly added languages: Bulgarian, Danish, Estonian, Finnish, French, Greek, Irish, Italian, Croatian, Latvian, Lithuanian, Maltese, Dutch, Polish, Portuguese, Romanian, Swedish, Slovak, Slovenian, Spanish, Czech, Hungarian. The module now supports all 24 official languages of the European Union. All translations use the legally correct term for the statutory right of withdrawal in accordance with the EU Consumer Rights Directive (2011/83/EU).

### 1.4.0
- Deleted the version attribute from composer.json. Composer has great integration with version control systems like Git, Mercurial and Subversion and there is no need to manually track version numbers in a text file for Composer at all. The field only exists for special situations where a version control system is not in use.

### 1.3.0 
- Admin can now confirm or reject individual withdrawal requests directly from the grid
- Context-sensitive action links per row (Confirm / Reject) — only shown when a status change makes sense
- Bulk actions to confirm or reject multiple withdrawal requests at once
- Added getById() and updateStatus() methods to WithdrawalRepositoryInterface and WithdrawalRepository

### 1.2.0

- Withdrawal period now starts from the date of the last shipment instead of the order date (legally correct under EU Directive 2011/83/EU)
- If an order has not been shipped yet, withdrawal is always allowed
- Withdrawal deadline display updated accordingly

### 1.1.0

- Complete withdrawal workflow for logged-in customers and guest orders
- Withdrawal button in order overview and on order details page
- Detail page with order summary and period display
- Confirmation page after successful withdrawal
- Email notifications to customer and shop operator (incl. BCC)
- Admin grid with filtering, sorting, paging, and direct link to order
- Configuration area for module, deadlines, and email settings
- ACL-based permissions and secured REST API
- CSRF protection and JavaScript confirmation dialog
- Full DE/EN translations

### 1.0.3

- Enabled withdrawal for guest orders
- Success page after submitting withdrawal

### 1.0.2

- Column "Order placed on" in admin grid
- Action "View Order" in admin grid
- Automatic comment in order history

### 1.0.1

- Shop email as BCC in confirmation email
- Order details above the withdrawal form

### 1.0.0

- Initial release
- Tested with Magento 2.4.6 to 2.4.8-p1

---

## Contact & Support

**Zwernemann Medienentwicklung**\
Martin Zwernemann\
79730 Murg, Germany

[To the website](https://www.zwernemann.de/widerrufsbutton-fuer-magento-2/)

If you have questions, problems, or ideas for new features – feel free to get in touch.

---

## License

OSL-3.0
