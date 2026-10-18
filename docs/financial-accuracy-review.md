# Financial transaction accuracy review

Reviewed source code for sales, delivery completion, customer payments, credit allocation, purchase order expenses, report summaries, date filters, and saved reports. This is a code and automated test review, not a reconciliation of production records against receipts or bank statements.

## Confirmed behavior

- Revenue comes from recorded sale lines. Placed delivery orders are not counted again as sales; completion creates the sale lines.
- Customer collections are reported separately from sales revenue. Cash Sales Plus Collections does not add credit sales a second time.
- Saved reports store a summary snapshot rather than recalculating when source records change.
- PO expenses use one linked record per purchase order and exclude drafts and cancelled orders. The amount represents ordered quantities and costs, not proof of supplier payment.

## Applied corrections

- Sales cards and rows follow the same payment and channel filters. Expense totals follow the selected category.
- Sales summary and detail queries use an exclusive next-day boundary to include the entire final day.
- One-sided date inputs resolve to a valid reporting range.
- Payment requests and the payment service reject nonpositive amounts and fractions of a cent. Valid amounts are normalized to two decimals before posting.
- Report notes explain filter scope and PO expense treatment.

- Payment allocation now considers unpaid amounts for both supported credit labels, even when legacy status metadata is missing or stale, with dated debts before undated debts.
- POS line and order totals are calculated in integer cents to avoid false insufficient-cash errors from floating point multiplication.
- Collections display a reconciliation warning for differences between account balances and tracked unpaid credit.

- Delivery completion now locks the customer record before updating credit balances, consistent with POS and payment posting. Automated tests verify posting behavior; database concurrency under production load was not simulated.
- Delivery cancellation and status changes reload and lock the current order so stale requests cannot cancel a completed sale.

## Unresolved findings

- Opening balances, previously deleted records, and historical allocations still require reconciliation against original documents. The system now displays a difference between customer balances and tracked unpaid credit without automatically rewriting historical data.
- Financial reports use current customer balances and current overdue credit alongside period totals. These are explicitly current metrics, not historical balances as of the selected report end date.
- Reset actions can delete settled sales or collection history while preserving customer balances and saved report snapshots. Live totals and saved reports may then differ. Restoring deleted financial evidence requires backups or original documents; no source transactions were deleted or reconstructed during this review.
- Sales Less Recorded Expenses is not a complete net profit calculation. PO commitments and manual expenses do not provide a verified cash balance or supplier payment ledger.

## Validation

- Follow-up run: 49 tests passed across FinancialModuleTest, CustomerLedgerTest, POSTest, DeliveryOrderTest, and PurchaseOrderTest.
- Frontend production build passed.

- FinancialModuleTest and CustomerLedgerTest: 8 existing tests passed.
- Added filter reconciliation, one-sided date, and cent-precision payment checks: 3 tests passed.
- Additional regression coverage checks partial/full legacy credit payments, exact-cent POS tender, and non-destructive reconciliation warnings.
- No production data audit or live deployment was performed.
