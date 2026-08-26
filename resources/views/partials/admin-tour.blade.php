{{-- Konfigurasi spotlight tour onboarding admin (dibaca oleh resources/js/tour.js). --}}
@auth
    @if (in_array(auth()->user()->role, ['super_admin', 'admin'], true))
        @php
            $route = Route::currentRouteName();
            $completed = (bool) (auth()->user()->preferences['tour_completed'] ?? false);

            // Langkah per-route. Selektor menunjuk elemen ber-atribut data-tour.
            // Langkah yang elemennya tidak ada akan otomatis dilewati di sisi klien.
            $steps = [
                'admin.dashboard' => [
                    ['selector' => '[data-tour="dash-stats"]', 'title' => __('Welcome!'), 'description' => __('This is your Dashboard. These cards summarize total spending, purchase items, master items, and suppliers at a glance.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="dash-chart"]', 'title' => __('Monthly Trend'), 'description' => __('Track how your purchase spending moves month by month, plus your top suppliers.'), 'side' => 'top'],
                    ['selector' => '[data-tour="dash-recent"]', 'title' => __('Recent Items'), 'description' => __('The latest purchase items recorded in the system appear here.'), 'side' => 'top'],
                    ['selector' => '[data-tour="nav-purchases"]', 'title' => __('Purchase Items'), 'description' => __('Open this menu to record, edit, and browse every purchase item.'), 'side' => 'right'],
                    ['selector' => '[data-tour="nav-import"]', 'title' => __('Import from Excel'), 'description' => __('Have months of data in a spreadsheet? Bulk-import it here in a few steps.'), 'side' => 'right'],
                    ['selector' => '[data-tour="help-button"]', 'title' => __('Need this again?'), 'description' => __('Click this button on any page to replay the tutorial for that page.'), 'side' => 'bottom'],
                ],
                'admin.purchases' => [
                    ['selector' => '[data-tour="purchase-add"]', 'title' => __('Add a Purchase Item'), 'description' => __('Click here to create a new purchase item with lines, discounts, and photos.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="purchase-filter"]', 'title' => __('Search & Filter'), 'description' => __('Narrow the list by code, date range, supplier, or review status.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="purchase-export"]', 'title' => __('Export'), 'description' => __('Download the current list as Excel, CSV, or PDF.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="purchase-table"]', 'title' => __('Items Table'), 'description' => __('Every row is a purchase item. Use the action buttons to edit, duplicate, or delete it.'), 'side' => 'top'],
                ],
                'admin.purchases.create' => [
                    ['selector' => '[data-tour="form-header"]', 'title' => __('Item Header'), 'description' => __('Fill in the date, supplier, and item number here.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="form-items"]', 'title' => __('Line Items'), 'description' => __('Add each purchased item with quantity, unit price, and optional discount. Use "Add Row" for more.'), 'side' => 'top'],
                    ['selector' => '[data-tour="form-preview"]', 'title' => __('Live Total'), 'description' => __('The grand total is always computed on the server for accuracy — check it before saving.'), 'side' => 'top'],
                ],
                'admin.purchases.edit' => [
                    ['selector' => '[data-tour="form-header"]', 'title' => __('Item Header'), 'description' => __('Fill in the date, supplier, and item number here.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="form-items"]', 'title' => __('Line Items'), 'description' => __('Add each purchased item with quantity, unit price, and optional discount. Use "Add Row" for more.'), 'side' => 'top'],
                    ['selector' => '[data-tour="form-preview"]', 'title' => __('Live Total'), 'description' => __('The grand total is always computed on the server for accuracy — check it before saving.'), 'side' => 'top'],
                ],
                'admin.suppliers' => [
                    ['selector' => '[data-tour="supplier-add"]', 'title' => __('Add Supplier'), 'description' => __('Register a new supplier so you can pick it when recording items.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="supplier-table"]', 'title' => __('Supplier List'), 'description' => __('View, activate/deactivate, edit, or remove suppliers here.'), 'side' => 'top'],
                ],
                'admin.items' => [
                    ['selector' => '[data-tour="item-add"]', 'title' => __('Add Item'), 'description' => __('Maintain a master list of items to speed up entry.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="item-table"]', 'title' => __('Item List'), 'description' => __('See the last price and last supplier for each item at a glance.'), 'side' => 'top'],
                ],
                'admin.units' => [
                    ['selector' => '[data-tour="unit-add"]', 'title' => __('Add Unit'), 'description' => __('Define measurement units (kg, m, pcs) used by your items.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="unit-table"]', 'title' => __('Unit List'), 'description' => __('Manage all measurement units here.'), 'side' => 'top'],
                ],
                'admin.import' => [
                    ['selector' => '[data-tour="import-stepper"]', 'title' => __('3-Step Import'), 'description' => __('Importing works in three steps: Upload, Preview & Warnings, then Execute.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="import-file"]', 'title' => __('Upload Excel'), 'description' => __('Select your .xlsx file. Monthly sheets are read; recap sheets are skipped automatically.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="import-template"]', 'title' => __('Template'), 'description' => __('Not sure about the format? Download the example template first.'), 'side' => 'top'],
                ],
                'admin.audit-logs' => [
                    ['selector' => '[data-tour="audit-filter"]', 'title' => __('Filter Activity'), 'description' => __('Filter the change history by action, entity, or date range.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="audit-table"]', 'title' => __('Audit Trail'), 'description' => __('Every create, update, and delete is logged here with who did it and when.'), 'side' => 'top'],
                ],
                'admin.users' => [
                    ['selector' => '[data-tour="user-add"]', 'title' => __('Add User'), 'description' => __('Create accounts and assign roles. Only Super Admins can manage users.'), 'side' => 'bottom'],
                    ['selector' => '[data-tour="user-table"]', 'title' => __('User List'), 'description' => __('Manage every registered account, its role, and departments here.'), 'side' => 'top'],
                ],
            ];

            $tourConfig = [
                'route' => $route,
                'autoStart' => $route === 'admin.dashboard' && ! $completed,
                'labels' => [
                    'next' => __('Next'),
                    'prev' => __('Back'),
                    'done' => __('Done'),
                    'progress' => __('Step :current of :total', ['current' => '{{current}}', 'total' => '{{total}}']),
                ],
                'steps' => $steps,
            ];
        @endphp

        <script>
            window.__adminTour = @json($tourConfig);
        </script>
    @endif
@endauth
