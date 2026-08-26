@php($role = auth()->user()->role)
<x-menu activate-by-route active-bg-color="bg-primary text-primary-content rounded" class="gap-1 px-3 mt-4">
    {{-- Admin & Super Admin: area operasional UPL --}}
    @if (in_array($role, ['super_admin', 'admin'], true))
        <x-menu-item title="{{ __('Dashboard') }}" icon="o-home" link="{{ route('admin.dashboard') }}"
            data-tour="nav-dashboard" />
        <x-menu-item title="{{ __('Purchase Items') }}" icon="o-document-text"
            link="{{ route('admin.purchases') }}" data-tour="nav-purchases" />
        <x-menu-item title="{{ __('Spreadsheet') }}" icon="o-table-cells"
            link="{{ route('admin.spreadsheet') }}" data-tour="nav-spreadsheet" />

        <x-menu-separator title="{{ __('Master Data') }}" />
        <x-menu-item title="{{ __('Suppliers') }}" icon="o-building-storefront"
            link="{{ route('admin.suppliers') }}" data-tour="nav-suppliers" />
        <x-menu-item title="{{ __('Items') }}" icon="o-cube" link="{{ route('admin.items') }}"
            data-tour="nav-items" />
        <x-menu-item title="{{ __('Units') }}" icon="o-scale" link="{{ route('admin.units') }}"
            data-tour="nav-units" />

        <x-menu-separator title="{{ __('Tools') }}" />
        <x-menu-item title="{{ __('Import Excel') }}" icon="o-arrow-up-tray"
            link="{{ route('admin.import') }}" data-tour="nav-import" />
        <x-menu-item title="{{ __('Audit Log') }}" icon="o-clipboard-document-list"
            link="{{ route('admin.audit-logs') }}" data-tour="nav-audit" />
    @endif

    {{-- Khusus Super Admin --}}
    @if ($role === 'super_admin')
        <x-menu-separator title="{{ __('Administration') }}" />
        <x-menu-item title="{{ __('Users') }}" icon="o-users" link="{{ route('admin.users') }}"
            data-tour="nav-users" />
    @endif

    <hr class="my-3 border-base-300">
    <x-menu-item title="{{ __('Settings') }}" icon="o-cog-6-tooth" link="{{ route('settings') }}" />
</x-menu>
