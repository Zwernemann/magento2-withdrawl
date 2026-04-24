# CHANGELOG

All notable changes to the Zwernemann Withdrawal Module are documented in this file.

Format based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.3.0] - 2026-04-24

### Added
- **Admin Grid Actions:** Confirm/reject individual withdrawal requests directly from the grid
- **Context-Sensitive Actions:** Action links per row only shown when a status change makes sense
- **Bulk Actions:** Confirm or reject multiple withdrawal requests at once
- **Repository Methods:** Added `getById()` and `updateStatus()` to `WithdrawalRepositoryInterface`
- **Code Review Documentation:** `CODE_REVIEW.md` with technical assessment and recommendations
- **Implementation Guide:** `IMPLEMENTATION_GUIDE.md` with API reference, customization guide, and troubleshooting
- **Email Templates for Updates:** Separate templates (`*_update_template`) for partial/update withdrawals

### Changed
- Improved email template handling to differentiate between new and update withdrawals
- Enhanced Admin UI grid with better filtering and sorting options
- Better documentation structure with separate guide files

### Fixed
- Minor typos in README files (Composer command formatting, REST API syntax)
- Email endpoint code snippets with proper markdown formatting

### Technical
- All code reviewed against PHP 7.4+ standards (declare(strict_types=1) throughout)
- Type hints verified across all public methods
- Circular dependency resolution pattern documented
- Dependency injection properly configured

---

## [1.2.0] - 2026-04-01

### Added
- **Shipment-Based Deadline:** Withdrawal period now starts from the date of the last shipment (legally correct per EU Directive 2011/83/EU)
- **Auto-Enable Order Statuses:** Status patcher automatically enables all valid order statuses for withdrawals
- **Database Patch:** Data migration for setting default allowed order statuses

### Changed
- Withdrawal deadline calculation moved from order date to shipment date
- Updated Ui component to show more accurate withdrawal periods
- Improved helper configuration for status checking

### Fixed
- Withdrawal period calculation was previously starting from order creation date
- Now correctly starts from last shipment date per EU requirements

---

## [1.1.0] - 2026-03-15

### Added
- **Partial Withdrawal Support:** Customers can partially withdraw orders and submit multiple withdrawals
- **Product Attribute Exclusion:** Configure product attributes to exclude items from withdrawal
- **Item Count Tracking:** Denormalized `withdrawn_item_count` and `withdrawal_type` fields
- **Admin Grid Columns:** "Withdrawal Type" and "Withdrawn Items" columns with filtering
- **Enhanced Email Templates:** Separate sections for withdrawn and non-withdrawable items
- **Status Tracking:** `withdrawal_type` field (full/partial) in withdrawal records
- **Items JSON Storage:** `withdrawn_items` field stores array of withdrawn item IDs
- **Update Notifications:** Special email templates for partial/update withdrawals
- **CreditMemo Observer:** Automatic status updates when withdrawal items are refunded

### Changed
- Withdrawal now supports multiple records per order (one per partial withdrawal)
- Email templates now include item categorization (withdrawn vs non-withdrawable)
- Admin interface updated to show withdrawal type and item counts
- Withdrawal button text changes to "Withdraw More Items" after partial withdrawal

### Fixed
- Items with excluded attributes no longer appear in withdrawal form
- Already withdrawn items properly tracked and excluded from subsequent withdrawals

---

## [1.0.3] - 2026-03-01

### Added
- **Guest Order Support:** Customers without an account can access withdrawal via search form
- **Guest Email Verification:** Order lookup by order number and email address
- **Guest Success Page:** Confirmation page after guest withdrawal submission

### Changed
- Withdrawal link now accessible to both registered and guest customers
- Guest workflow separated into dedicated controllers

### Fixed
- Guest email verification now case-insensitive

---

## [1.0.2] - 2026-02-15

### Added
- **Admin Grid Columns:** "Order placed on" date column
- **Admin Action:** "View Order" action link in withdrawal grid
- **Order History Comment:** Automatic comment added to order history upon withdrawal

### Changed
- Admin grid now shows comprehensive order and withdrawal information
- Order history provides audit trail for withdrawal events

---

## [1.0.1] - 2026-02-08

### Added
- **BCC Configuration:** Shop email automatically BCC'd on customer confirmation emails
- **Pre-Form Details:** Order details displayed above the withdrawal form

### Changed
- Email structure improved with pre-filled order information

---

## [1.0.0] - 2026-02-01

### Added
- **Initial Release**
- Core withdrawal functionality for EU Directive (EU) 2023/2673 compliance
- Complete withdrawal workflow for logged-in customers
- Withdrawal button in order overview and on order details page
- Withdrawal detail page with order summary and period display
- Confirmation page after successful withdrawal
- Email notifications to customer and shop operator
- Admin grid with filtering, sorting, paging, and direct links
- Configuration area for module, deadlines, and email settings
- ACL-based permissions and secured REST API
- CSRF protection and JavaScript confirmation dialog
- Full DE/EN translations (97 strings)
- Database schema with `zwernemann_withdrawal` table
- Setup patches and database migrations
- Tested with Magento 2.4.6 to 2.4.8-p1

### Technical
- Strict type declarations throughout
- Dependency injection pattern
- Observer and plugin patterns
- REST API endpoint: `GET /rest/V1/zwernemann/withdrawals`
- Translatable strings via CSV files

---

## Security & Compliance

### CVEs & Dependencies
- **Magento 2:** 2.4.6 - 2.4.8-p1 (actively maintained by Adobe)
- **PHP:** >= 7.4 (no known critical CVEs in Withdrawal module)
- **Dependencies:** All in `composer.json` are core Magento modules

### Legal Compliance
- ✅ EU Directive (EU) 2023/2673 - Right of Withdrawal
- ✅ EU Directive 2011/83/EU - Withdrawal period from shipment date
- ✅ GDPR - Email handling, data storage
- ✅ CSRF Protection - Form key validation
- ✅ XSS Prevention - HTML encoding in templates

---

## Roadmap

### 🔵 Planned for Future Releases

- [ ] **Write-Access REST API**
  - POST `/rest/V1/zwernemann/withdrawals` - Create withdrawal
  - PUT `/rest/V1/zwernemann/withdrawals/{id}` - Update withdrawal

- [ ] **GraphQL Support**
  - Modern frontend framework compatibility
  - Alternative to REST API

- [ ] **Automated Testing**
  - Unit tests (70%+ coverage)
  - Integration tests
  - PHPUnit test suite

- [ ] **Admin Enhancements**
  - Detailed withdrawal view with timeline
  - Batch import/export
  - Advanced reporting with charts

- [ ] **Customer Portal**
  - Withdrawal history dashboard
  - Download/print withdrawal confirmation
  - Tracking of refund status

- [ ] **Payment Gateway Integration**
  - Automatic refund via PayPal
  - Stripe integration
  - Other payment processor webhooks

---

## Contributors

- **Martin Zwernemann** - Initial development, maintenance
- **Zwernemann Medienentwicklung** - © 2026

---

## License

Open Software License (OSL) 3.0

See LICENSE file in module root.

---

## Support

For issues, questions, or feature requests:

**Website:** [https://www.zwernemann.de/widerrufsbutton-fuer-magento-2/](https://www.zwernemann.de/widerrufsbutton-fuer-magento-2/)

**Contact:** Martin Zwernemann  
**Location:** 79730 Murg, Germany

---

## References

- [Magento 2 Documentation](https://experienceleague.adobe.com/docs/commerce-admin/user-guides/home.html)
- [EU Directive (EU) 2023/2673](https://eur-lex.europa.eu/eli/dir/2023/2673/oj)
- [EU Directive 2011/83/EU - Consumer Rights](https://eur-lex.europa.eu/eli/dir/2011/83/oj)
- [GDPR Compliance Guide](https://gdpr-info.eu/)

---

## Notes

**Last Updated:** April 24, 2026

For version history prior to 1.0.0, refer to Git commits and release tags.

