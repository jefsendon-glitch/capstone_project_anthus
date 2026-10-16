# UI and layout review

Reviewed the shared authenticated layout and components, plus Admin, Staff, and Customer record screens. Changes retain the system's blue palette and existing workflows.

| Area | Finding | Change |
| --- | --- | --- |
| Mobile notifications | Fixed desktop width could extend outside narrow screens. | Use a viewport-bounded mobile panel with a scroll limit and the existing desktop dropdown placement. |
| Page header | Fixed height and truncated descriptions could hide longer page instructions. | Allow the header to grow, wrap headings, and show up to two lines of supporting text. |
| Cards and fields | Intrinsic content widths could stretch grid cards and inline search controls. | Allow cards, inputs, selects, and textareas to shrink within their containers. |
| Visual consistency | Customer and Staff cards/buttons had heavier shadows, gradients, and motion than Admin. | Use simple borders, restrained shadows, solid blue actions, and consistent corner shapes. |
| Empty states | Large vertical padding took unnecessary space in short record panels. | Reduce padding while retaining a clear title and description. |
| Readability | Small dashboard descriptions and placeholders used faint text. | Improve light-mode text contrast while preserving dark-mode colors. |
| Order filtering | An empty filtered list said there were no orders yet. | Distinguish an empty account from a filter with no matches. |
| Financial wording | Cash and credit sales were labeled inconsistently as Loan, Credit, and Total Spent. | Display Credit consistently and label the customer total as Total Purchases. Internal payment values remain unchanged. |
| Keyboard navigation | Dropdown state, Escape handling, and a content shortcut were incomplete. | Add expanded-state attributes, controlled-panel IDs, Escape handling, a skip link, and consistent button focus styles. |
| Feedback | Success and error messages lacked explicit announcement roles. | Add status and alert roles. |

Verification uses the production asset build and existing page-rendering, account access, payment, notification, and pagination tests. These checks do not replace inspecting the deployed site at actual phone and desktop widths. Laravel Cloud must deploy the updated source before its UI changes appear online.
