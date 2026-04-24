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
- The note **"Period expired"**, if the withdrawal period has passed

Additionally, a **"Withdrawal Order"** button is displayed on the order details page.

**Withdrawal detail page**

Refore the actual withdrawal, the customer sees a summary of their order:

- Order number, date, status, total amount
- All ordered items with name, SKU, quantity, and price
- The deadline until which withdrawal is possible, calculated from the date of the last shipment
- A button for final submission ₓ with a preceding security confirmation

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
- Date of order and date of withdrawal
- Direct link to the respective order view

All columns can be filtered and sorted.

**Automatic email notification**

As soon as a withdrawal is received, two emails are sent:

1. **To the customer** ₓ Confirmation with order details
2. **To you** – Notification with all relevant data

In addition, you receive a BCC copy of the customer email. The email templates can be customized in the admin panel.

**Note in the order**

Every withdrawal is automatically added as a comment in the order history. This way, it is immediately apparent in the order view that a withdrawal exists.

**Configurable**

In the admin under *Stores > Configuration > Sales > Withdrawal Settings*:

- Enable/Disable the module
- Set recipient address for notifications
- Set withdrawal period in days, counted from the last shipment date (Default: 14)
- Select email sender and templates
- **Configure excluded product attributes** (see below)

---

## Hyvä Theme Compatibility

If you are using the Hyvä Theme, please install the Hyvä compatibility module:

https://github.com/lindbaum/module-withdrawal-hyva

This module adds the required Hyvä frontend integration for the withdrawal button and ensures compatibility with the Hyvä template system.

The base module remains required.

## Excluding Products from Withdrawal

### Overview

Certain products cannot be subject to withdrawal according to EU law (e.g., personalized items, perishable goods, custom-made products). This module allows you to exclude products from withdrawal based on product attributes.

### Configuration

1. Navigate to **Stores > Configuration > Sales > Withdrawal Settings**
2. In the **"Excluded Product Attributes"** field, enter a comma-separated list of product attribute codes
3. Example: `is_personalized,is_perishable,custom_made`

### How It Works

- Products with any of the configured attributes set to `Yes`, `1`, or `true` will be **excluded from withdrawal**
- If an order contains both withdrawable and non-withdrawable items, customers can perform a **partial withdrawal**
- Customers can submit **multiple partial withdrawals** for the same order until all withdrawable items have been withdrawn
- The withdrawal button text changes to **"Withdraw More Items"** after a partial withdrawal

### Partial Withdrawal

When an order contains mixed items:

- **Withdrawable items** are displayed normally and can be withdrawn
- **Non-withdrawable items** are shown in a separate section with the note: "This product cannot be withdrawn"
- **Already withdrawn items** (from previous partial withdrawals) are displayed with a strikethrough and badge

E-mails for partial withdrawals include:
- List of withdrawn items
- List of non-withdrawable items
- Withdrawal type (Full/Partial)
- Item count (X of Y)

**Email Templates for Partial/Update Withdrawals:**
- For new withdrawals, the standard templates are used
- For partial/update withdrawals, special `*_update_template` variants are automatically selected:
  - `zwernemann_withdrawal_email_customer_update_template` - Sent to customer on update
  - `zwernemann_withdrawal_email_admin_update_template` - Sent to admin on update
- Templates can be customized in System > Email Templates

### Admin Grid

The withdrawal overview (*Sales > Withdrawals*) displays:
- **Withdrawal Type** column: Full Withdrawal or Partial Withdrawal
- **Withdrawn Items** column: Number of items withdrawn
- Filterable and sortable by both columns

### Debug Logging

If a configured attribute does not exist on a product, a debug log entry is created with details about the missing attribute. Check `var/log/debug.log` if you suspect configuration issues.

### REST API

The REST API endpoint `/V1/zwernemann/withdrawals` now includes:
- `withdrawn_items`: Array of withdrawn item IDs
- `withdrawal_type`: "full" or "partial"
- `withdrawn_item_count`: Number of withdrawn items

---

### REST API

Withdrawal entries can also be retrieved programmatically:

```bash
GET /rest/V1/zwernemann/withdrawals
```

Access is protected by ACL permission (`Zwernemann_Withdrawal::withdrawals`).

### Multilingualism

Completely translated into **German** and **English** (97 strings). Further languages can be added via custom CSV files.

---

## System Requirements

|Component | Version|
|---|---|
| Magento 2 Open Source | 2.4.6 to 2.4.8-p1 |
| PHP | 7.4 or higher |

Are you using a different Magento version? Let us know – we are happy to test it.

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

```bash
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
3. Set **"Enable Module** to *Yes*
4. Enter **Notification Email** ₓ withdrawal notifications will be sent here
5. Adjust **Withdrawal Period** if the legal period differs
6. Configure email sender and templates if necessary
7. Save and flush cache

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

## Uninstallation

```bash
php bin/magento module:disable Zwernemann_Withdrawal
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

Then delete the directory `app/code/Zwernemann/Withdrawal/`.

The database table `zwernemann_withdrawal` remains and can be removed manually if needed.

---

## About This Release

**v1.3.0** - April 24, 2026

This release includes a comprehensive **code review and documentation update**:

- ✅ Full technical code review (Rating: 8.5/10)
- ✅ Production readiness assessment
- ✅ Implementation guide with API reference
- ✅ Complete changelog with version history
- ✅ Customization guide with 5 real-world examples
- ✅ Troubleshooting section

## Version History

### 1.3.0 
- Admin can now confirm or reject individual withdrawal requests directly from the grid
- Context-sensitive action links per row (Confirm / Reject) — only shown when a status change makes sense
- Bulk actions to confirm or reject multiple withdrawal requests at once
- Added getById() and updateStatus() methods to WithdrawalRepositoryInterface and WithdrawalRepository
- Comprehensive code review documentation added
- Implementation guide and API reference created

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

## Documentation

- **`README.md`** - This file (general overview)
- **`README_de.md`** - German version
- **`CHANGELOG.md`** - Version history and release notes