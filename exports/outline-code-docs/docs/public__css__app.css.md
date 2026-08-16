# Datei: public\css\app.css

> **Kommentar:** Automatischer Export des finalen Dateistands fuer Dokumentationszwecke.

- **Quelle:** `public\css\app.css`
- **Stand:** 2026-06-27 13:24:32
- **Typ:** css

## Code

```css
:root {
    --bg: #f6f7f9;
    --panel: #ffffff;
    --line: #d9dee7;
    --text: #20242b;
    --muted: #667085;
    --brand: #0f766e;
    --brand-dark: #115e59;
    --accent: #b45309;
    --danger: #b42318;
    --ok: #027a48;
    --shadow: 0 10px 25px rgba(16, 24, 40, .08);
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--bg);
    color: var(--text);
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    font-size: 15px;
    line-height: 1.45;
}

a {
    color: var(--brand-dark);
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

.app-shell {
    min-height: 100vh;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 16px 28px;
    border-bottom: 1px solid var(--line);
    background: #fff;
    position: sticky;
    top: 0;
    z-index: 20;
}

.brand {
    font-size: 18px;
    font-weight: 700;
    color: var(--text);
}

.nav {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.nav a {
    padding: 8px 10px;
    border-radius: 6px;
    color: var(--muted);
}

.nav a.active,
.nav a:hover {
    background: #eef7f5;
    color: var(--brand-dark);
    text-decoration: none;
}

.page {
    max-width: 1480px;
    margin: 0 auto;
    padding: 28px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
}

h1,
h2,
h3 {
    margin: 0;
    line-height: 1.2;
}

h1 {
    font-size: 28px;
}

h2 {
    font-size: 20px;
}

h3 {
    font-size: 16px;
}

.muted {
    color: var(--muted);
}

.panel {
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 8px;
    box-shadow: var(--shadow);
}

.panel-body {
    padding: 18px;
}

.stack {
    display: grid;
    gap: 14px;
}

.grid {
    display: grid;
    gap: 18px;
}

.grid-2 {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
}

.grid-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.ticket-layout {
    display: grid;
    grid-template-columns: minmax(360px, 1fr) minmax(420px, 1fr);
    gap: 22px;
    align-items: start;
}

.section {
    padding: 16px 0;
    border-top: 1px solid var(--line);
}

.section:first-child {
    border-top: 0;
    padding-top: 0;
}

.section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

label {
    display: block;
    font-weight: 650;
    margin-bottom: 6px;
}

input,
select,
textarea {
    width: 100%;
    border: 1px solid #cfd6e1;
    background: #fff;
    border-radius: 6px;
    padding: 10px 11px;
    font: inherit;
    color: var(--text);
}

textarea {
    min-height: 92px;
    resize: vertical;
}

input:focus,
select:focus,
textarea:focus {
    outline: 3px solid rgba(15, 118, 110, .16);
    border-color: var(--brand);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.check-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.check-row input {
    width: auto;
}

.button-row {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 9px 13px;
    border: 1px solid transparent;
    border-radius: 6px;
    background: var(--brand);
    color: #fff;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
}

.btn:hover {
    background: var(--brand-dark);
    color: #fff;
    text-decoration: none;
}

.btn.secondary {
    background: #fff;
    color: var(--text);
    border-color: var(--line);
}

.btn.secondary:hover {
    background: #f2f4f7;
}

.btn.danger {
    background: var(--danger);
}

.btn.ghost {
    background: transparent;
    color: var(--brand-dark);
    border-color: transparent;
}

.badge {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    border-radius: 999px;
    padding: 3px 9px;
    font-size: 12px;
    font-weight: 700;
    background: #eef2f6;
    color: #344054;
}

.badge.open {
    background: #fff7e6;
    color: #8a4b00;
}

.badge.in_progress {
    background: #e8f1ff;
    color: #1849a9;
}

.badge.done,
.badge.synced {
    background: #e7f8ef;
    color: var(--ok);
}

.badge.error {
    background: #fee4e2;
    color: var(--danger);
}

.alert {
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 8px;
    border: 1px solid var(--line);
    background: #fff;
}

.alert.success {
    border-color: #a6f4c5;
    background: #f0fdf4;
}

.alert.warning {
    border-color: #fedf89;
    background: #fffbeb;
}

.alert.error {
    border-color: #fecdca;
    background: #fff1f3;
}

.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 14px;
}

.ticket-card {
    display: grid;
    gap: 12px;
    padding: 16px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 8px;
    box-shadow: var(--shadow);
    color: var(--text);
}

.ticket-card:hover {
    border-color: var(--brand);
    text-decoration: none;
}

.ticket-card-head {
    display: flex;
    justify-content: space-between;
    gap: 8px;
}

.ticket-number {
    font-weight: 800;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

th,
td {
    padding: 10px;
    border-bottom: 1px solid var(--line);
    text-align: left;
    vertical-align: top;
}

th {
    font-size: 12px;
    color: var(--muted);
    text-transform: uppercase;
}

.lookup-results {
    display: grid;
    gap: 8px;
    margin-top: 8px;
}

.lookup-item {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: center;
    border: 1px solid var(--line);
    border-radius: 6px;
    padding: 9px;
    background: #fbfcfe;
}

.selected-value {
    margin-top: 8px;
    padding: 9px 10px;
    background: #eef7f5;
    border: 1px solid #b7dfd8;
    color: var(--brand-dark);
    border-radius: 6px;
    font-weight: 700;
}

.errors {
    margin: 0 0 16px;
    padding: 12px 16px;
    border: 1px solid #fecdca;
    background: #fff1f3;
    border-radius: 8px;
}

.pagination {
    margin-top: 18px;
}

details {
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #fff;
}

summary {
    cursor: pointer;
    padding: 12px 14px;
    font-weight: 800;
}

details .details-body {
    border-top: 1px solid var(--line);
    padding: 14px;
}

@media (max-width: 980px) {
    .ticket-layout,
    .grid-2,
    .grid-3,
    .form-row {
        grid-template-columns: 1fr;
    }

    .topbar,
    .page-header {
        align-items: stretch;
        flex-direction: column;
    }

    .page {
        padding: 18px;
    }
}

.ticket-board {
    display: grid;
    gap: 18px;
}

.ticket-week {
    overflow: hidden;
}

.ticket-week-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--line);
}

.ticket-week-head h2 {
    margin: 0;
    font-size: 18px;
}

.ticket-lane {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    min-height: 165px;
    padding: 16px;
}

.ticket-lane .ticket-card {
    min-width: 310px;
    max-width: 360px;
}

.ticket-card {
    cursor: grab;
    user-select: none;
}

.ticket-card.dragging {
    opacity: .45;
    cursor: grabbing;
}

.ticket-card-success {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.ticket-card-warning {
    background: #fffbeb;
    border-color: #fde68a;
}

.ticket-card-danger {
    background: #fef2f2;
    border-color: #fecaca;
}

.ticket-card-neutral {
    background: #f8fafc;
}

.badge.order-required {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.history-grid {
    align-items: start;
}

.narrow-page {
    max-width: 1120px;
}

.nav-button {
    padding: 8px 10px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: var(--muted);
    font: inherit;
    cursor: pointer;
}

.nav-button:hover {
    background: #eef7f5;
    color: var(--brand-dark);
}

.definition-list {
    display: grid;
    grid-template-columns: 180px minmax(0, 1fr);
    gap: 9px 14px;
    margin: 0;
}

.definition-list dt {
    color: var(--muted);
    font-weight: 700;
}

.definition-list dd {
    margin: 0;
}

.pagination-wrap {
    margin-top: 16px;
}

```
