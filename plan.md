# Task Filters Implementation Plan

Status: Awaiting approval. Saving this plan does not authorize implementation.

## Project review

AGENTS.md has been read. docs/map.md does not exist. No application files were changed during planning.

The specification differs from current code:
- Stored statuses: to do, in progress, done.
- Stored priorities: low, medium, high.
- No enum classes or User::tasks() relationship currently exist.
- Supervisors currently see everyone's tasks on the task list.
- The list already has 10-task pagination and inline subactivities.

## Feature specification

Users: a signed-in staff member on the task list page.

1. Optional query parameters: status, priority, overdue (1), q (text in the title).
2. Filters can be combined.
3. Only the signed-in user's own tasks are ever shown.
4. Overdue means not Complete and due before today.
5. Unknown status or priority is ignored.
6. The form keeps chosen values.
7. The count says "(filtered)"; no match says "No tasks match these filters."

Acceptance examples:
- Amina has 3 Not started and 2 Complete tasks. /?status=Complete shows only the 2.
- Daniel's task "Budget" never appears for Amina on /?q=Budget.
- A Complete task due yesterday is not shown under /?overdue=1.
- A task due today is not overdue.

## 1. Files to change and files not to touch

| File | Planned change |
| --- | --- |
| app/Models/User.php | Add tasks() relationship using user_id |
| app/Http/Requests/TaskFilterRequest.php (new) | Normalise and validate filter input separately from task creation |
| app/Http/Controllers/TaskController.php | Apply combined filters to the signed-in user's task relationship |
| resources/views/tasks/index.blade.php | GET filter form, retained selections, "(filtered)" count and no-match message |
| lang/ms.json | Translate new labels and messages |
| tests/Feature/TaskFiltersTest.php (new) | Acceptance, ownership, validation, combination and escaping tests |

Do not touch .env, database files, migrations, seeders, authentication, dashboard, reminder logic, task policies, subactivity forms, shared styling or existing tests. Reuse the existing route and Task overdue scope.

Enum files would be added only after resolving the naming question below.

## 2. Ordered, independently testable steps

### Step 1: Establish baseline
- Follow the existing feature suite and authenticated task-list route.
- Run php artisan test.
- Document failures without weakening existing tests.

### Step 2: Scope listing to ownership
- Follow the user_id relationship already used by assignedUser().
- Add User::tasks() and use the signed-in user's relationship as the query base.
- Test that Amina never sees Daniel's tasks, including when Amina is a supervisor.

### Step 3: Normalise filter input
- Follow existing Rule::in validation and Task constants.
- Keep task-filter validation separate from task-creation validation.
- Test recognised values, ignored unknown status/priority, absent filters and malformed/array input.
- Ensure malformed input cannot cause server errors or bypass ownership.

### Step 4: Apply filters together
- Follow the existing query builder and overdue() scope.
- Combine active filters with AND conditions.
- Test each filter and combinations.
- Test that Complete/done and due-today tasks never count as overdue.

### Step 5: Add filter controls
- Follow existing Blade layout, named routes and form classes.
- Use a GET form, with a reset link to the unfiltered named route.
- Test selected values, escaped search text, "(filtered)" count and exact no-match message.

### Step 6: Regression check
- Follow existing 10-task paging and inline subactivity behaviour.
- Verify filter parameters survive page links and subactivity return navigation.
- Run the full suite and vendor/bin/pint --dirty for changed PHP files.

Run tests after every step. AGENTS.md also requests a commit after every step; use small scoped commits without unrelated changes.

## 3. Existing code to reuse

- app/Models/Task.php: status/priority constants, TIMEZONE, today(), open() and overdue() scopes.
- app/Models/User.php: existing authenticated user model; add its missing task relationship.
- app/Http/Controllers/TaskController.php: index query, eager-loaded activities, existing due-date/id order and paginate(10).
- routes/web.php: existing authenticated named tasks.index route; no new route needed.
- resources/views/tasks/index.blade.php: existing shared layout, task table, count and pagination navigation.
- resources/views/tasks/inline-activities.blade.php and TaskActivityController: existing same-page subactivity submission behaviour, to regression-test without broadening feature scope.
- app/Policies/TaskPolicy.php: preserve write permissions.
- Existing feature tests: retain all assertions and run as regressions.

## 4. Project rules and their protection

| Rule | Test or verification |
| --- | --- |
| Own tasks only | Two-user fixtures; title search and combined filters cannot reveal Daniel's task |
| Policy-authorised changes | Leave policies and write actions unchanged; run existing permission tests |
| Allowed status/priority only | Known-value cases and ignored unknown values; no invalid value is persisted |
| Complete never overdue | Completed task due yesterday excluded |
| Strict overdue boundary | Open task due yesterday included; task due today excluded |
| Escaped output | HTML/script-like q remains escaped in the form input |
| Validated input only | Use only normalised filter values; query-string fields cannot mutate tasks |
| Combined filters | Only tasks satisfying every active filter appear |
| Thin controller, existing layout and named routes | Dedicated filter request; rendered-form assertions and code review |
| config() for settings | No new environment reads; code review |
| No packages, schema changes or real data | Fake example.test fixtures and changed-file review |
| Existing tests remain intact | Full suite after every step; report conflicts rather than weakening tests |

No raw SQL assembled from request input. Bind query values through Eloquent/query builder. Do not fill models from request->all(). Filter requests are read-only. Existing POST forms retain CSRF protection.

Some rules, such as no package installation or secret-file access, are protected through workflow and diff review rather than a feature test.

## 5. Out of scope

- New sorting controls or changed default order.
- New pagination features; retain existing pagination.
- Saved filters.
- New packages, JS frameworks or CSS frameworks.
- Database changes or status-data migration.
- Dashboard filters.
- Role changes beyond the explicitly requested own-task listing restriction.
- Task edit/delete changes.
- Subactivity search.

Preserving filter parameters through existing pagination is compatibility work, not a new pagination feature.

## 6. Risks and questions requiring confirmation

1. Status naming: May filter values map Not started -> to do, In progress -> in progress, Complete -> done, and Low/Medium/High -> lowercase, without changing stored data? Recommended. Enum-backed filter values can represent the specification's vocabulary. Confirm before adding enum files.
2. Supervisor visibility: The new specification requires own tasks only. This removes team-wide visibility from the task-list page for supervisors while leaving Dashboard unchanged. Confirm this intentional change.
3. Pagination: Confirm the existing 10-per-page behaviour should remain.
4. Search semantics: Proposed case-insensitive title substring matching, trimmed input, with % and _ treated literally rather than as wildcards. Confirm.
5. Filtered label: Proposed show "(filtered)" only when at least one effective filter is active; ignored unknown values alone do not activate it. Confirm.

Existing tests that require team-wide supervisor listing may conflict with the new specification. Report those conflicts rather than editing, skipping or weakening existing tests to make the change pass.

Further implementation risk: retaining filters after inline subactivity creation may require changing return-navigation handling, which is currently listed as not to touch. If regression checks show this is necessary, propose the smallest file-scope adjustment before proceeding.

## Approval checkpoint

Wait for the user's approval and answers to the five questions above before implementing. No implementation, migrations, reseeding, dependency installation or application changes are authorized by saving this document.
