# Snipe-IT Customization Requirements Analysis

**Document Version:** 1.0  
**Date:** 2026-04-10  
**Analyst:** Kilo AI  
**Target System:** Snipe-IT (Open-Source Asset Management)

---

## EXECUTIVE SUMMARY

Snipe-IT provides a robust foundation for asset management but lacks several enterprise inventory management features. This analysis maps your 10 requirement areas against Snipe-IT's native capabilities and provides a phased implementation roadmap.

**Key Findings:**
- **3 Requirements:** Fully native support achievable through configuration
- **5 Requirements:** Partial support requiring custom development
- **2 Requirements:** No native support, requires significant custom development or third-party integration

**Estimated Total Development Effort:** 45-65 person-days for custom development

---

## 1. INVENTORY TRACKING AND CONTROL

| Requirement | Native Support | Configuration Steps | Gap/Custom Dev Needed | Priority |
|-------------|----------------|---------------------|----------------------|----------|
| Real-time inventory levels across locations | **Partial** | Use Locations, Categories, and Status Labels. Assets/Invenotry support rtd_location_id for default location. Accessory/Consumable/Component quantity tracking per location via `location_id` and checkout tracking. | No native multi-location stock quantity dashboard. Requires custom reporting view or API aggregation. | High |
| Minimum threshold alerts with automated notifications | **Partial** | Snipe-IT has email alerts for warranty expiry, audit due, license expiry. NO native min threshold alerts for stock qty. | Requires custom Laravel Observer on Consumable/Accessory models + custom notification class for low-stock alerts. Needs scheduled command to check thresholds daily. | High |
| Automated redistribution suggestions | **No** | N/A | No native support for usage-pattern-based redistribution. Requires custom algorithm analyzing checkout frequency by location + custom UI for suggestions. Consider ERP integration for this. | Medium |

### Configuration Steps for Real-Time Levels:
1. **Create Locations** for each warehouse/storage point
2. **Create Categories** for item classification
3. **Set Status Labels**: Deployable, Undeployable, Archived, Pending
4. **For Accessories/Consumables**: Track via checkout to users/locations
5. **Use Custom Fields** on asset models for additional tracking (cost center, project code)

### Custom Development Needed:
- **Low Stock Alert System**: 
  - New database table: `stock_thresholds` (item_id, item_type, min_qty, alert_email, created_by)
  - Laravel Artisan command: `snipeit:check-stock-thresholds`
  - Custom Notification: `LowStockAlert`
  - Observer on Consumable/Accessory checkout to decrement and check
  
- **Redistribution Engine** (if needed):
  - Custom module analyzing checkout patterns
  - Algorithm based on: location demand, checkout frequency, lead time
  - Integration with procurement system

### Workaround:
Use Snipe-IT's existing **Reports** with filters by location and scheduled exports via API to external BI tool (Power BI, Tableau) for stock level dashboards.

---

## 2. INVENTORY RECEIVING AND INSPECTION

| Requirement | Native Support | Configuration Steps | Gap/Custom Dev Needed | Priority |
|-------------|----------------|---------------------|----------------------|----------|
| Goods Received Notes (GRN) | **No** | N/A | No native GRN concept. Assets created directly via UI/API with supplier_id, purchase_date, purchase_cost, order_number. | High |
| Inspection workflows (accept/reject/partial) | **Partial** | Status Labels can represent states (Pending, Deployable, Undeployable). Can create custom statuses. | No approval workflow for inspection. No "partially accepted" status concept. No discrepancy flagging. | High |
| Editable posted GRNs with audit trail | **Partial** | Actionlog provides audit trail for asset changes. No concept of "posting" a GRN or locking edits. | Requires custom GRN module with status (draft/posted/locked), approval workflow, and revision history. | High |

### Native Support Analysis:
- **Supplier tracking**: Yes - `supplier_id` on Asset/Inventory
- **Purchase cost/date**: Yes - `purchase_cost`, `purchase_date` fields
- **Condition on receipt**: No native field, but can use Status Labels
- **Audit trail**: Yes - Actionlog tracks all changes

### Custom Development Needed:

**GRN Module Architecture:**
```
Database Tables:
- goods_received_notes (id, grn_number, supplier_id, received_by, received_date, status, notes, created_at, updated_at)
- grn_items (id, grn_id, item_type, item_id, expected_qty, received_qty, accepted_qty, rejected_qty, condition, notes, created_at)
- grn_inspections (id, grn_item_id, inspected_by, inspection_date, result, notes, created_at)
```

**Key Components:**
1. **GRNController** - CRUD operations
2. **InspectionWorkflow** - Accept/Reject/Partial logic
3. **GRNApprovalMiddleware** - Lock posted GRNs from edits
4. **GRNNotification** - Alert stakeholders on receipt
5. **GRNReport** - Printable GRN document

### Workaround:
1. Create assets with "Pending" status on receipt
2. Use Custom Fields for expected vs actual quantities
3. Use Notes field for discrepancies
4. Use Status Label changes to represent inspection outcomes
5. Full GRN workflow requires custom development

---

## 3. INVENTORY VALUATION AND COSTING

| Requirement | Native Support | Configuration Steps | Gap/Custom Dev Needed | Priority |
|-------------|----------------|---------------------|----------------------|----------|
| FIFO/LIFO/Weighted Average Cost | **No** | N/A | Snipe-IT depreciation is straight-line only. No inventory valuation methods. | High |
| Cost allocation across projects (pro-rata) | **No** | N/A | No project/cost center tracking on assets. Custom Fields can add but calculation engine needed. | Medium |
| Depreciation: Straight-line & Reducing balance | **Partial** | Straight-line depreciation is native via Depreciation model + asset model association. | Reducing balance not native. Custom depreciation calculation engine needed. | Medium |

### Native Depreciation Support:
```php
// Depreciation model supports:
// - term (months)
// - frequency (monthly, yearly)
// - depreciation_method (linear/straight-line only)
// Asset calculates: (cost - residual) / term
```

### Custom Development Needed:

**Costing Engine:**
```php
// New: app/Services/InventoryValuation.php
interface InventoryValuationInterface {
    public function calculateFIFO(array $lots): float;
    public function calculateLIFO(array $lots): float;
    public function calculateWeightedAverage(array $lots): float;
    public function calculateReducingBalance(float $cost, float $rate, int $period): float;
}

// New: app/Models/InventoryLot.php
- lot_number
- item_type (Asset/Consumable/Accessory)
- item_id
- quantity
- unit_cost
- received_date
- supplier_id
```

**Cost Allocation Module:**
- Project/Cost Center custom fields
- Allocation rules (percentage-based)
- Period allocation tracking
- Pro-rata calculation service

### Workaround:
1. Use native straight-line depreciation for asset reporting
2. Export to Excel for FIFO/LIFO/WAC calculations
3. Use Custom Fields for cost centers and manually allocate

---

## 4. STOCK TAKING AND INVENTORY AUDITS

| Requirement | Native Support | Configuration Steps | Gap/Custom Dev Needed | Priority |
|-------------|----------------|---------------------|----------------------|----------|
| Periodic physical stock takes with mobile support | **Partial** | Asset audit functionality exists: `next_audit_date`, `last_audit_date`. Web UI audit. No native mobile app. | Third-party mobile apps available (SnipeMate, Snipe-Scan). Custom mobile API needed for barcode scanning. | High |
| Automatic discrepancy identification | **No** | Audit creates comparison but no automatic variance calculation. | Custom audit workflow: physical count entry → system qty lookup → variance calculation → approval required | High |
| Configurable unit conversions | **No** | N/A | No native unit of measure conversion. Custom UOM table needed. | Low |
| Variance and reconciliation reports | **Partial** | Audit logs exist. No native variance report with causes/resolution. | Custom reconciliation report module with variance, cause, resolution fields | High |
| Digitized stock cards | **No** | N/A | No native stock movement tracking. Actionlog provides history but not stock card format. | Medium |
| Rights to delete/modify duplicates | **Partial** | Soft delete exists. Admin rights for delete. No duplicate detection. | Custom duplicate detection algorithm + merge functionality | Medium |
| Gate pass generation | **No** | N/A | No native gate pass. Third-party tool exists: snipe-it-gate-pass-system | Low |

### Native Audit Features:
```php
// Asset audit capabilities:
// - next_audit_date, last_audit_date fields
// - Audit action type in Actionlog
// - DueForAudit, OverdueForAudit query scopes
// - SendUpcomingAuditNotification
// - Asset audit warnings in settings
```

### Custom Development Needed:

**Stock Take Module:**
```php
// Database tables:
// - stock_takes (id, name, start_date, end_date, status, created_by)
// - stock_take_counts (id, stock_take_id, item_type, item_id, system_qty, counted_qty, variance, counted_by, counted_at)
// - stock_take_adjustments (id, stock_take_count_id, adjustment_qty, reason, approved_by, status)
```

**Mobile API Endpoints:**
```
GET  /api/v1/stock-take/{id}/items - Get items to count
POST /api/v1/stock-take/{id}/counts - Submit physical count
GET  /api/v1/stock-take/{id}/variances - Get calculated variances
POST /api/v1/stock-take/{id}/approve - Approve adjustments
```

**Unit Conversion:**
```php
// app/Models/UnitOfMeasure.php
- id, name, abbreviation, conversion_factor, base_unit_id
// app/Models/ItemUom.php  
- item_id, item_type, uom_id, is_default
```

### Third-Party Mobile Apps:
1. **SnipeMate** (iOS, Android) - Full featured
2. **Snipe-Scan** (iOS) - Barcode scanning
3. **AssetX** (iOS) - Snipe-IT companion

### Workaround:
1. Use web-based audit with tablet
2. Export asset list to CSV for offline counting
3. Import count results via CSV
4. Manual variance calculation in spreadsheet

---

## 5. INVENTORY REPORTING AND ANALYTICS

| Requirement | Native Support | Configuration Steps | Gap/Custom Dev Needed | Priority |
|-------------|----------------|---------------------|----------------------|----------|
| Customizable reports (stock levels, turnover, obsolete) | **Partial** | Native reports: Asset Report, Depreciation Report, Asset Listing. Limited customization. | Custom report builder module needed for flexible queries and templates | High |
| Analytics dashboards (demand forecasting, reorder suggestions) | **No** | N/A | No native analytics. Requires BI integration or custom dashboard. | Medium |
| Filters by category, location, funder, project, status | **Yes** | Use existing filtering on asset lists. Custom Fields for funder/project. | Native filtering sufficient for basic needs | Low |
| Lost asset reporting | **Yes** | Status Label "Lost" can be created. Asset marked as lost with action logged. | Native lost asset tracking via status + notes | Low |
| Clearance tracking | **No** | N/A | No native clearance concept. Use Status Labels + Custom Fields for clearance status | Medium |

### Native Reporting Capabilities:
- Asset Listing Report
- Asset Acceptance Report
- Depreciation Report
- License Report
- Inventory Report (for accessories, consumables, components)
- Audit Report
- Activity Report

### Custom Development Needed:

**Report Builder Module:**
```php
// app/Services/ReportBuilder.php
class ReportBuilder {
    public function buildStockLevelReport(array $filters): Report
    public function buildTurnoverReport(array $filters): Report
    public function buildObsoleteItemsReport(array $filters): Report
    public function buildReorderSuggestionReport(): Report
}

// app/Models/ReportTemplate.php
- id, name, description, query_definition, created_by, is_shared
```

**Analytics Dashboard:**
```php
// app/Services/DemandForecasting.php
// - Moving average calculation
// - Seasonal decomposition
// - Reorder point: (Lead Time × Average Usage) + Safety Stock

// app/Services/ReorderEngine.php
// - Configurable reorder points per item
// - Safety stock calculation
// - Purchase requisition generation
```

### Third-Party Integration Options:
1. **Power BI / Tableau** - Connect via Snipe-IT API
2. **Metabase** - Open-source BI, connect to Snipe-IT DB
3. **Grafana** - For operational dashboards

### Workaround:
1. Use native reports for standard asset reporting
2. Export data via API for external BI analysis
3. Use Custom Fields to add funder/project tracking
4. Scheduled API exports to cloud storage for BI tools

---

## 6. BATCH AND SERIAL NUMBER TRACKING

| Requirement | Native Support | Configuration Steps | Gap/Custom Dev Needed | Priority |
|-------------|----------------|---------------------|----------------------|----------|
| Track by batch/lot or serial numbers | **Partial** | `serial` field on Asset/Inventory. No native batch/lot field. Can use Custom Fields. | Batch/lot number tracking via Custom Field + batch history table | High |
| Recall management | **No** | N/A | No native recall. Query by batch/serial via custom module | High |
| Barcode and RFID integration | **Partial** | Barcode generation native (Code128, QR). No RFID. | RFID requires third-party hardware integration | Medium |
| Bulk updates via scan | **No** | N/A | No native bulk scan-to-update. Requires custom mobile API | High |

### Native Serial Tracking:
```php
// Asset/Inventory has:
// - serial field (unique per undeleted item)
// - Asset tag (unique identifier)
// - Barcode generation via Label templates
```

### Custom Development Needed:

**Batch Tracking Module:**
```php
// app/Models/BatchLot.php
- id, batch_number, supplier_id, received_date, expiry_date, status
- item_type, item_id

// app/Models/BatchLotHistory.php
- id, batch_lot_id, action_type, quantity_change, reference_id, notes
```

**Recall Management:**
```php
// app/Services/RecallManager.php
public function findItemsByBatch(string $batchNumber): Collection
public function findItemsBySerialRange(string $startSerial, string $endSerial): Collection
public function initiateRecall(int $batchId, RecallReason $reason): Recall
public function getRecallAffectedItems(int $recallId): Collection
```

**Bulk Scan API:**
```php
// Mobile API endpoints:
POST /api/v1/scan/bulk-lookup     // Scan multiple barcodes, return item details
POST /api/v1/scan/bulk-update     // Update location/status for scanned items
POST /api/v1/scan/bulk-checkout   // Checkout multiple items to user/location
```

### RFID Note:
Snipe-IT does NOT natively support RFID. For RFID:
- Requires custom hardware integration
- Third-party RFID middleware
- Custom API endpoints for RFID reader data

### Workaround:
1. Use serial numbers for tracking (native)
2. Use Custom Fields for batch numbers
3. Use third-party mobile apps (SnipeMate) for scanning
4. Use Label printing for barcodes

---

## 7. EXPIRY DATE MANAGEMENT

| Requirement | Native Support | Configuration Steps | Gap/Custom Dev Needed | Priority |
|-------------|----------------|---------------------|----------------------|----------|
| Track expiry dates (medicines, warranties) | **Partial** | `warranty_months` + calculated `warranty_expires` on Asset. `asset_eol_date` for explicit EOL. | No native expiry date field for consumables/medicines. Custom Field needed. | High |
| Automated alerts at 30/60/90 days | **Partial** | Warranty expiry notifications via `SendUpcomingAuditNotification` / `ExpiringLicenseNotification`. Only 30-day default. | Custom expiry alert system with configurable thresholds per item category | High |

### Native Expiry Features:
```php
// Asset model has:
// - warranty_months (numeric)
// - purchase_date
// - warranty_expires (computed accessor)
// - asset_eol_date (explicit EOL date)
// - eol_explicit (boolean flag)

// Notifications:
// - SendUpcomingAuditNotification (for audits)
// - ExpiringLicenseNotification (for licenses)
// - No warranty expiry notification in base system
```

### Custom Development Needed:

**Expiry Management Module:**
```php
// app/Models/ExpiryDate.php (Custom Field backed or separate table)
// - item_id, item_type, expiry_date, alert_thresholds (JSON)
// - created_by, updated_by

// app/Console/Commands/CheckExpiryDates.php
// - Daily scheduled command
// - Check all items with expiry dates
// - Send alerts based on thresholds

// app/Notifications/ExpiryAlertNotification.php
// - Configurable days: 30, 60, 90, custom
// - Recipients: assigned user, admin, category manager
```

**Configuration UI:**
- Per-category default alert thresholds
- Per-item override capability
- Email/SMS toggle per alert type

### Workaround:
1. Use warranty fields for expiry tracking on assets
2. Create Custom Fields for consumables expiry
3. Manually run reports for expiry items
4. Export to calendar system for reminders

---

## 8. INVENTORY RESERVATIONS

| Requirement | Native Support | Configuration Steps | Gap/Custom Dev Needed | Priority |
|-------------|----------------|---------------------|----------------------|----------|
| Reserve stock for projects/departments/users | **No** | N/A | No native reservation. Checkout provides some functionality but no soft reservation. | High |
| Real-time view of reserved vs available | **No** | N/A | No native reserved quantity tracking. Need reservation ledger. | High |
| Auto-release on reservation expiry | **No** | N/A | No native hold/release mechanism. Custom reservation expiry service needed. | High |

### Analysis:
Snipe-IT's checkout is a hard checkout (immediate assignment). There is no concept of:
- Soft reservation (promise without checkout)
- Reserved quantity tracking
- Reservation expiry and auto-release

### Third-Party Solution:
**SnipeScheduler** (mentioned in README):
> "An Asset Reservation/Checkout System for Snipe-IT"
> https://github.com/JSY-Ben/SnipeScheduler

This third-party module may provide reservation functionality. Evaluate before custom development.

### Custom Development Needed:

**Reservation Module:**
```php
// app/Models/Reservation.php
- id, reservation_number
- item_type, item_id
- reserved_by (user_id)
- department_id, project_id (custom)
- quantity (for consumables)
- reserved_at, expires_at, status
- created_by

// app/Models/ReservationLog.php
- Full audit trail of reservations

// app/Services/ReservationService.php
public function createReservation(...): Reservation
public function cancelReservation(int $id): bool
public function fulfillReservation(int $id): bool  // Convert to checkout
public function releaseExpiredReservations(): int  // Scheduled job
public function getAvailableQty(string $itemType, int $itemId): int
public function getReservedQty(string $itemType, int $itemId): int
```

**API Endpoints:**
```
POST   /api/v1/reservations          // Create reservation
GET    /api/v1/reservations/{id}      // Get reservation
DELETE /api/v1/reservations/{id}      // Cancel
POST   /api/v1/reservations/{id}/fulfill  // Convert to checkout
GET    /api/v1/items/{type}/{id}/availability  // Reserved vs available
```

**Scheduled Commands:**
```php
// app/Console/Commands/ReleaseExpiredReservations.php
// Run daily: Check expires_at < now(), status = 'active'
// Release reservations, notify users
```

### Workaround:
1. Use expected_checkin date to simulate reservations
2. Checkout to "Reservation" location
3. Manual tracking of reserved quantities in spreadsheet
4. Evaluate SnipeScheduler third-party module

---

## 9. BACKORDER MANAGEMENT

| Requirement | Native Support | Configuration Steps | Gap/Custom Dev Needed | Priority |
|-------------|----------------|---------------------|----------------------|----------|
| Track backordered items with delivery dates | **No** | N/A | No native backorder concept. No purchase order integration. | High |
| Automated fulfillment notifications | **No** | N/A | No native PO-to-receipt workflow. Custom notification on GRN receipt needed. | High |

### Analysis:
Snipe-IT does not have:
- Purchase Order management
- Backorder status tracking
- Supplier lead time tracking
- Automated PO-to-receipt workflow

### Custom Development Needed:

**Backorder Module:**
```php
// app/Models/Backorder.php
- id, backorder_number
- item_type, item_id
- supplier_id
- quantity_ordered
- quantity_received
- order_date
- expected_delivery_date
- actual_delivery_date
- status (pending, partial, fulfilled, cancelled)
- notes
- created_by

// app/Models/BackorderNotification.php
- id, backorder_id, notified_user, notification_type, sent_at

// app/Services/BackorderFulfillmentService.php
public function receiveItem(int $backorderId, int $qty): Receipt
public function notifyStakeholders(int $backorderId): void
public function checkBackorderStatus(): Collection
```

**Integration with GRN (from Section 2):**
When GRN is created for a backordered item:
1. Link GRN item to backorder
2. Update backorder quantity_received
3. If quantity_received >= quantity_ordered, mark fulfilled
4. Trigger fulfillment notification to requestor

### Workaround:
1. Use Status Labels to simulate backorder states
2. Manual tracking of expected deliveries in notes
3. Manual notification to stakeholders
4. Consider ERP integration (Odoo, ERPNext) for full PO management

---

## 10. REORDER POINT AND REPLENISHMENT

| Requirement | Native Support | Configuration Steps | Gap/Custom Dev Needed | Priority |
|-------------|----------------|---------------------|----------------------|----------|
| Configurable reorder points and safety stock | **No** | N/A | No native reorder point per item. Need configuration table. | High |
| Auto-generate purchase requisitions | **No** | N/A | No native PR/PO generation. Custom requisition module needed. | High |
| Requisition workflow with approval | **No** | N/A | Requestable assets exist but not full requisition-to-issue workflow. | High |

### Native Requestable Assets:
```php
// Asset model has:
// - requestable (boolean)
// Request workflow:
// 1. User requests asset
// 2. Admin approves/declines
// 3. Asset checked out to user

// Limitations:
// - Only for assets marked requestable
// - No multi-item requisition
// - No quantity-based requisition (for consumables)
// - No approval chain
// - No cost/budget tracking
```

### Custom Development Needed:

**Replenishment Module:**
```php
// app/Models/ReorderPoint.php
- id, item_type, item_id
- reorder_point, safety_stock
- reorder_quantity
- preferred_supplier_id
- auto_replenish (boolean)
- alert_threshold_days
- created_by, updated_by

// app/Models/PurchaseRequisition.php
- id, pr_number, requesting_user, department_id
- status (draft, submitted, approved, rejected, fulfilled)
- total_estimated_cost
- notes, created_at, updated_at

// app/Models/PurchaseRequisitionItem.php
- id, pr_id, item_type, item_id
- quantity, unit_cost, total_cost
- status

// app/Models/RequisitionApproval.php
- id, pr_id, approver_id, sequence
- status, comments, decided_at
```

**Services:**
```php
// app/Services/ReorderPointEngine.php
public function checkReorderPoints(): Collection  // Items below reorder point
public function calculateSafetyStock(string $itemType, int $itemId): float
public function generateRequisition(int $itemId): PurchaseRequisition

// app/Services\RequisitionWorkflowService.php
public function submit(PurchaseRequisition $pr): bool
public function approve(int $prId, User $approver, string $comments): bool
public function reject(int $prId, User $approver, string $comments): bool
public function fulfill(int $prId): bool
```

**Scheduled Commands:**
```php
// app/Console/Commands/CheckReorderPoints.php
// Daily: Check all items against reorder points
// Generate alerts or auto-create requisitions
```

### Integration with ERP:
For full procurement workflow, consider integrating with:
1. **Odoo** - Full ERP with purchase management
2. **ERPNext** - Open-source ERP with procurement
3. **Ghost** - Lightweight requisition tool

### Workaround:
1. Use existing "Requestable Assets" for simple workflows
2. Manual creation of purchase requisitions in spreadsheet
3. Email alerts to procurement team
4. Manual approval via email/chat

---

## IMPLEMENTATION ROADMAP

### Phase 1: Configure Native Features (Weeks 1-2)
**Effort:** 0 custom development days

| Task | Description |
|------|-------------|
| Location Setup | Create all warehouse/storage locations |
| Category Structure | Create categories matching inventory taxonomy |
| Status Labels | Create: Pending, Deployable, Undeployable, Lost, Under Repair, etc. |
| Custom Fields | Add: Project Code, Cost Center, Funder, Funding Source |
| User Roles | Configure permissions for inventory clerks, approvers, viewers |
| Import Data | CSV import for existing inventory |
| Label Templates | Configure barcode/label printing |
| Email Notifications | Configure SMTP, test notifications |

### Phase 2: Light Customization (Weeks 3-6)
**Effort:** 15-20 person-days

| Feature | Priority | Effort |
|---------|----------|--------|
| Low Stock Alerts | High | 3 days |
| GRN Module (Basic) | High | 5 days |
| Audit Enhancement | High | 3 days |
| Gate Pass Generation | Low | 2 days |
| Expiry Alerts Enhancement | Medium | 2 days |
| Report Builder | Medium | 5 days |

### Phase 3: Custom Development (Weeks 7-14)
**Effort:** 30-45 person-days

| Feature | Priority | Effort |
|---------|----------|--------|
| Full Reservation System | High | 8 days |
| Reorder Point Engine | High | 5 days |
| Purchase Requisition Workflow | High | 6 days |
| Batch/Lot Tracking | Medium | 4 days |
| Backorder Management | Medium | 4 days |
| Costing Engine (FIFO/LIFO/WAC) | Medium | 6 days |
| Analytics Dashboard | Low | 5 days |
| Mobile API for Scanning | Medium | 5 days |
| Reducing Balance Depreciation | Low | 2 days |

---

## DEVELOPMENT EFFORT SUMMARY

| Category | Complexity | Estimated Days |
|----------|------------|----------------|
| Configuration Only | None | 0 |
| Light Customization | Low | 15-20 |
| Core Custom Modules | Medium | 30-45 |
| Third-Party Integration | Varies | 5-15 |
| **Total** | - | **45-65** |

---

## THIRD-PARTY INTEGRATION RECOMMENDATIONS

### For Mobile Scanning:
| Product | Platform | Features | Cost |
|---------|----------|----------|------|
| SnipeMate | iOS, Android | Full asset management, scanning | Subscription |
| Snipe-Scan | iOS | Barcode scanning | Subscription |
| AssetX | iOS | Snipe-IT companion | Subscription |

### For ERP Integration:
| ERP | Strengths | Integration Method |
|-----|-----------|-------------------|
| Odoo | Full procurement, inventory, accounting | API |
| ERPNext | Open-source, comprehensive | API |
| Dolibarr | Lightweight, French-built | API |

### For Business Intelligence:
| Tool | Type | Integration |
|------|------|-------------|
| Metabase | Open-source BI | Direct DB or API |
| Power BI | Microsoft BI | API connector |
| Tableau | Enterprise BI | API |

### For Requisition Workflow:
Consider **SnipeScheduler** for reservation functionality before building custom:
- https://github.com/JSY-Ben/SnipeScheduler

---

## GAPS REQUIRING CUSTOM DEVELOPMENT

### Critical Gaps (Must Have):
1. **GRN/Receiving Module** - No native receiving workflow
2. **Reservation System** - No soft reservation capability
3. **Reorder Point Engine** - No automatic replenishment triggers
4. **Purchase Requisition Workflow** - No approval chain
5. **Low Stock Alerts** - No threshold-based notifications

### Important Gaps (Should Have):
1. **Batch/Lot Tracking** - Serial only, no batch
2. **Expiry Date Management** - Limited to warranty
3. **Variance Reports** - Manual reconciliation
4. **Unit of Measure Conversion** - Not supported

### Nice to Have (Can Defer):
1. **FIFO/LIFO/WAC Valuation** - Manual calculation possible
2. **Analytics Dashboard** - Use external BI
3. **RFID Integration** - Hardware required
4. **Reducing Balance Depreciation** - Straight-line sufficient for most

---

## CONCLUSION

Snipe-IT provides excellent asset management foundations but requires significant customization for enterprise inventory management. The recommended approach:

1. **Phase 1**: Maximize native features through proper configuration
2. **Phase 2**: Build critical custom modules (GRN, Alerts, Reservations)
3. **Phase 3**: Complete advanced features (Reorder, Requisitions, Costing)

For organizations requiring full ERP capabilities without extensive customization, consider integrating Snipe-IT with a dedicated ERP system (Odoo, ERPNext) rather than building all features custom.

---

## DOCUMENT APPENDIX

### A. Relevant Snipe-IT Models
- `app/Models/Asset.php` - Core asset model with checkout/checkin
- `app/Models/Inventory.php` - Parallel inventory model (custom extension)
- `app/Models/Consumable.php` - Consumable items
- `app/Models/Accessory.php` - Accessories
- `app/Models/Component.php` - Components
- `app/Models/Actionlog.php` - Audit trail
- `app/Models/Depreciation.php` - Depreciation methods
- `app/Models/Location.php` - Locations
- `app/Models/Statuslabel.php` - Status labels
- `app/Models/CustomField.php` - Custom fields

### B. Relevant Routes
- `routes/api.php` - REST API endpoints
- `routes/web.php` - Web UI routes

### C. Key Services
- `app/Services/PredefinedKitCheckoutService.php` - Kit checkout logic
- `app/Helpers/Helper.php` - Utility functions

### D. Notifications
- `app/Notifications/InventoryAlert.php` - Base alert notification
- `app/Notifications/SendUpcomingAuditNotification.php` - Audit alerts
- `app/Notifications/ExpiringLicenseNotification.php` - License expiry

---

**End of Document**
