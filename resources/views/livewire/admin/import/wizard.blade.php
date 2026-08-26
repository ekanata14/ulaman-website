@php
    use App\Support\Money;
@endphp

<div>
    <x-header title="{{ __('Import Excel') }}" subtitle="{{ __('Migrate 12 months of purchase data') }}" separator />

    {{-- STEPPER --}}
    <ul class="steps w-full mb-8" data-tour="import-stepper">
        <li class="step {{ $step >= 1 ? 'step-primary' : '' }}">{{ __('Upload') }}</li>
        <li class="step {{ $step >= 2 ? 'step-primary' : '' }}">{{ __('Preview & Warnings') }}</li>
        <li class="step {{ $step >= 3 ? 'step-primary' : '' }}">{{ __('Execute') }}</li>
    </ul>

    {{-- STEP 1: UPLOAD --}}
    @if ($step === 1)
        <x-card class="bg-base-100 shadow-sm">
            <x-form wire:submit="toPreview">
                <div data-tour="import-file">
                    <x-file wire:model="file" label="{{ __('Excel File (.xlsx)') }}" accept=".xlsx,.xls" required
                        hint="{{ __('Max 20 MB. 12 monthly sheets; recap sheets are skipped. Download the example template below.') }}" />
                </div>
                <div wire:loading wire:target="file" class="text-sm text-gray-500 mt-2">
                    <x-loading class="loading-sm" /> {{ __('Uploading...') }}
                </div>
                <x-slot:actions>
                    <span data-tour="import-template">
                        <x-button label="{{ __('Download Template') }}" icon="o-arrow-down-tray"
                            wire:click="downloadTemplate" class="btn-outline" spinner="downloadTemplate" />
                    </span>
                    <x-button label="{{ __('Preview') }}" type="submit" icon="o-eye" class="btn-primary"
                        spinner="toPreview" />
                </x-slot:actions>
            </x-form>
        </x-card>
    @endif

    {{-- STEP 2: PREVIEW --}}
    @if ($step === 2 && $preview)
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
            <x-stat title="{{ __('Items') }}" value="{{ number_format($preview->totalNota, 0, ',', '.') }}"
                icon="o-document-text" />
            <x-stat title="{{ __('Item Rows') }}" value="{{ number_format($preview->totalItem, 0, ',', '.') }}"
                icon="o-cube" />
            <x-stat title="{{ __('Excel Total') }}" value="{{ Money::format($preview->totalNilai) }}"
                icon="o-banknotes" color="text-primary" />
        </div>

        @if (count($preview->warnings) > 0)
            <x-alert icon="o-exclamation-triangle" class="alert-warning mb-4">
                <span class="font-bold">{{ count($preview->warnings) }} {{ __('warnings (flagged, not auto-fixed)') }}:</span>
                <ul class="list-disc ml-5 mt-1 text-sm max-h-40 overflow-y-auto">
                    @foreach ($preview->warnings as $w)
                        <li>{{ $w }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <x-card class="bg-base-100 shadow-sm mb-4">
            <x-slot:title><span class="font-bold">{{ __('Excel Totals per Sheet') }}</span></x-slot:title>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Sheet') }}</th>
                            <th class="text-right">{{ __('Excel TOTAL') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview->perSheet as $row)
                            <tr>
                                <td>{{ $row['sheet'] }}</td>
                                <td class="text-right font-mono">{{ Money::format($row['excelTotal']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="flex justify-between">
            <x-button label="{{ __('Back') }}" icon="o-arrow-left" wire:click="$set('step', 1)" class="btn-ghost" />
            <x-button label="{{ __('Import Now') }}" icon="o-play" wire:click="confirmExecute" class="btn-primary"
                spinner="confirmExecute" />
        </div>
    @endif

    {{-- STEP 3: EXECUTE --}}
    @if ($step === 3)
        <x-card class="bg-base-100 shadow-sm">
            @if (! ($progress['finished'] ?? false))
                <div wire:poll.1500ms>
                    @php($p = $progress)
                    <div class="text-center py-6">
                        <div class="font-bold text-lg mb-2">{{ __('Importing...') }}</div>
                        <progress class="progress progress-primary w-full"
                            value="{{ $p['done'] ?? 0 }}" max="{{ max(1, $p['total'] ?? 1) }}"></progress>
                        <div class="text-sm text-gray-500 mt-2">
                            {{ $p['done'] ?? 0 }} / {{ $p['total'] ?? 0 }} {{ __('items') }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">
                            {{ __('Requires a running queue worker (php artisan queue:work).') }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- RECONCILIATION REPORT --}}
            @if ($reconciliation && isset($reconciliation['rows']))
                <div class="mb-4 flex flex-wrap gap-3">
                    <x-stat title="{{ __('Imported Items') }}" value="{{ number_format($reconciliation['stats']['items'] ?? 0, 0, ',', '.') }}" icon="o-cube" />
                    <x-stat title="{{ __('Subtotal (Gross)') }}" value="{{ Money::format($reconciliation['totalSystem'] ?? '0') }}" icon="o-cpu-chip" color="text-primary" />
                    <x-stat title="{{ __('Excel Total') }}" value="{{ Money::format($reconciliation['totalExcel'] ?? '0') }}" icon="o-document-text" />
                    <x-stat title="{{ __('Total after Discount') }}" value="{{ Money::format($reconciliation['netGrandTotal'] ?? '0') }}" icon="o-banknotes" color="text-success" />
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('Sheet') }}</th>
                                <th class="text-right">{{ __('Subtotal (Gross)') }}</th>
                                <th class="text-right">{{ __('Excel TOTAL') }}</th>
                                <th class="text-right">{{ __('Delta') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reconciliation['rows'] as $row)
                                @php($delta = (string) ($row['delta'] ?? '0'))
                                <tr class="{{ bccomp($delta, '0', 2) !== 0 ? 'bg-error/10' : '' }}">
                                    <td>{{ $row['sheet'] }}</td>
                                    <td class="text-right font-mono">{{ Money::format($row['system']) }}</td>
                                    <td class="text-right font-mono">{{ Money::format($row['excel']) }}</td>
                                    <td class="text-right font-mono {{ bccomp($delta, '0', 2) !== 0 ? 'text-error font-bold' : 'text-gray-400' }}">
                                        {{ bccomp($delta, '0', 2) === 0 ? '✓' : Money::format($delta) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-alert icon="o-information-circle" class="alert-info mt-4 text-sm">
                    {{ __('Discrepancies are flagged for physical-receipt review (not auto-fixed).') }}
                </x-alert>
                <div class="mt-4">
                    <x-button label="{{ __('View Items') }}" icon="o-arrow-right" link="{{ route('admin.purchases') }}"
                        class="btn-primary" />
                </div>
            @endif
        </x-card>
    @endif

    <x-modal-confirm wire:model="confirmModalOpen" :title="$confirmTitle" :text="$confirmMessage"
        :confirm-text="$confirmButton" :icon="$confirmIcon" :danger="$confirmDanger" method="confirmProceed" />
</div>
