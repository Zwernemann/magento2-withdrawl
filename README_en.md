# Withdrawal Button for Magento 2

> Magento 2 extension to implement the EU right of withdrawal via a simple button click.  
> Developed by **Zwernemann Medienentwicklung**.

---

## What is this about?

The EU Directive **(EU) 2023/2673** requires that consumers must be able to cancel online purchase contracts **as easily as they concluded them**.  
From **June 19, 2026** onwards, a clearly visible **withdrawal button** becomes mandatory in online shops within the EU.

This Magento 2 module provides exactly that:  
Your customers can revoke orders with just a few clicks — directly from their customer account or via a dedicated form for guest orders.  
As a shop owner, you retain full visibility and control in the admin area.

---

## Features

### For your customers

**Withdrawal button in the order overview**

In *My Account → My Orders*, a new column appears for each order showing:

- A **Withdraw** link while the withdrawal period is active
- Status **"Withdrawal submitted"** once cancellation has been sent
- Status **"Withdrawal period expired"** when the deadline has passed

Additionally, a prominent **"Withdraw this order"** button is displayed on the order detail page.

**Withdrawal confirmation page**

Before final submission, customers see a summary of their order:

- Order number, date, status, grand total
- All ordered items with name, SKU, quantity and price
- Clear information until when withdrawal is possible
- Final submit button with a JavaScript confirmation dialog

**Guest orders**

Customers without an account can initiate withdrawal via a dedicated search form.  
They only need to enter **order number** and **email address**.

Accessible at: `/withdrawal/guest/search`

**Success page**

After submission, the customer is redirected to a confirmation page.  
It confirms receipt of the withdrawal and informs them that an email has been sent.

### For shop owners

**Admin grid – all withdrawals**

Under *Sales → Withdrawals* you get a clear tabular overview of all received withdrawals:

- ID, order number, customer name, email
- Status (Pending / Confirmed / Rejected)
- Order date & withdrawal date
- Direct link to the corresponding order view

All columns are filterable and sortable.

**Automatic email notifications**

When a withdrawal is submitted, **two emails** are sent automatically:

1. **To the customer** – confirmation including order details
2. **To you (admin)** – notification with all relevant information

Additionally, your shop email receives a **BCC copy** of the customer email.  
Email templates are fully customizable in the admin area.

**Comment in order history**

Every withdrawal is automatically added as a comment in the order history — visible immediately in the order view.

**Configuration**

Located at: *Stores → Configuration → Sales → Withdrawal Settings*

- Enable / disable the module
- Notification recipient email address
- Withdrawal period in days (default: 14)
- Email sender & template selection

### REST API

Withdrawal records can be retrieved programmatically:
