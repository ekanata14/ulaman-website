@props([
    'wireModel',
    'value' => '',
    'label' => null,
    'prefix' => 'Rp',
    'live' => false,
    'required' => false,
])

{{-- Input harga berformat ribuan. Tampilan diformat (id-ID), nilai MENTAH didorong
     ke Livewire via $wire.set — DB tetap menerima nominal asli. --}}
<div x-data="moneyInput(@js((string) $value))" class="w-full">
    @if ($label)
        <label class="fieldset-label text-xs text-gray-500 mb-1 block">{{ $label }}@if ($required) <span class="text-error">*</span>@endif</label>
    @endif

    <label class="input input-bordered flex items-center gap-1 w-full">
        @if ($prefix)
            <span class="text-gray-400 text-sm shrink-0">{{ $prefix }}</span>
        @endif
        <input type="text" inputmode="decimal" :value="display" class="grow min-w-0"
            @input="const r = unmask($event.target.value); display = format(r); $wire.set('{{ $wireModel }}', r, {{ $live ? 'true' : 'false' }})"
            {{ $attributes }} />
    </label>
</div>
