<?php

namespace App\Livewire\Forms;

use App\DTOs\Purchase\PurchaseBundleData;
use App\DTOs\Purchase\PurchaseData;
use App\DTOs\Purchase\PurchaseItemData;
use App\DTOs\Supplier\SupplierData;
use App\Enums\BundleType;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * §12.2 — Form Object nota. Menyimpan state form + validasi presentasi, lalu
 * merakit PurchaseData. TIDAK menghitung apa pun (§9.1); angka final dihitung
 * server oleh CalculatePurchaseTotals via StorePurchase/UpdatePurchase.
 */
class PurchaseForm extends Form
{
    public ?int $purchaseId = null;

    public string $tanggal = '';

    public ?int $supplierId = null;

    public string $nomorNota = '';

    public ?int $categoryId = null;

    public ?string $metodeBayar = null;

    public string $status = 'final';

    public ?string $remark = null;

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    /** @var array<int, array<string, mixed>> */
    public array $bundles = [];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date', 'before_or_equal:tomorrow'],
            'supplierId' => ['nullable', 'integer', 'exists:suppliers,id'],
            'nomorNota' => ['nullable', 'string', 'max:50'],
            'categoryId' => ['nullable', 'integer', 'exists:categories,id'],
            'metodeBayar' => ['nullable', Rule::enum(PaymentMethod::class)],
            'status' => ['required', Rule::enum(PurchaseStatus::class)],
            'remark' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.uid' => ['required', 'string'],
            'items.*.itemId' => ['nullable', 'integer', 'exists:items,id'],
            'items.*.deskripsi' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.unitId' => ['nullable', 'integer', 'exists:units,id'],
            'items.*.hargaSatuan' => ['nullable', 'numeric', 'min:0'],
            'items.*.diskonTipe' => ['required', Rule::enum(DiscountType::class)],
            'items.*.diskonNilai' => ['nullable', 'numeric', 'min:0'],
            'bundles' => ['nullable', 'array'],
            'bundles.*.nama' => ['required', 'string', 'max:255'],
            'bundles.*.tipe' => ['required', Rule::enum(BundleType::class)],
            'bundles.*.nilai' => ['required', 'numeric', 'min:0'],
            'bundles.*.itemUids' => ['required', 'array', 'min:2'],
        ];
    }

    public function setPurchase(Purchase $purchase): void
    {
        $this->purchaseId = $purchase->id;
        $this->tanggal = $purchase->tanggal->format('Y-m-d');
        $this->supplierId = $purchase->supplier_id;
        $this->nomorNota = $purchase->nomor_nota ?? '';
        $this->categoryId = $purchase->category_id;
        $this->metodeBayar = $purchase->metode_bayar?->value;
        $this->status = $purchase->status->value;
        $this->remark = $purchase->remark;

        $this->items = $purchase->items
            ->map(fn ($it): array => [
                'uid' => (string) $it->id,
                'id' => $it->id,
                'itemId' => $it->item_id,
                'deskripsi' => $it->deskripsi,
                'qty' => (string) $it->qty,
                'unitId' => $it->unit_id,
                'hargaSatuan' => $it->harga_satuan !== null ? (string) $it->harga_satuan : '',
                'diskonTipe' => $it->diskon_tipe->value,
                'diskonNilai' => (string) $it->diskon_nilai,
                'remark' => $it->remark,
            ])
            ->all();

        $this->bundles = $purchase->bundles
            ->map(fn ($b): array => [
                'uid' => (string) $b->id,
                'nama' => $b->nama,
                'tipe' => $b->tipe_diskon->value,
                'nilai' => (string) $b->nilai_diskon,
                'itemUids' => $b->bundleItems->map(fn ($bi): string => (string) $bi->purchase_item_id)->all(),
            ])
            ->all();
    }

    public function toDto(): PurchaseData
    {
        $items = [];
        foreach (array_values($this->items) as $i => $row) {
            $harga = (string) ($row['hargaSatuan'] ?? '');
            $items[] = new PurchaseItemData(
                uid: (string) $row['uid'],
                id: ! empty($row['id']) ? (int) $row['id'] : null,
                itemId: ! empty($row['itemId']) ? (int) $row['itemId'] : null,
                deskripsi: (string) $row['deskripsi'],
                qty: (string) $row['qty'],
                unitId: ! empty($row['unitId']) ? (int) $row['unitId'] : null,
                hargaSatuan: $harga === '' ? null : $harga,
                diskonTipe: DiscountType::from((string) $row['diskonTipe']),
                diskonNilai: (string) ($row['diskonNilai'] ?? '0'),
                remark: isset($row['remark']) && $row['remark'] !== '' ? (string) $row['remark'] : null,
                urutan: $i,
            );
        }

        $bundles = [];
        foreach ($this->bundles as $b) {
            $bundles[] = new PurchaseBundleData(
                nama: (string) $b['nama'],
                tipe: BundleType::from((string) $b['tipe']),
                nilai: (string) $b['nilai'],
                itemUids: array_values(array_map('strval', $b['itemUids'])),
            );
        }

        return new PurchaseData(
            id: $this->purchaseId,
            tanggal: $this->tanggal,
            supplier: $this->supplierId !== null ? new SupplierData(id: $this->supplierId, nama: '') : null,
            nomorNota: $this->nomorNota !== '' ? $this->nomorNota : null,
            categoryId: $this->categoryId,
            metodeBayar: $this->metodeBayar !== null && $this->metodeBayar !== '' ? PaymentMethod::from($this->metodeBayar) : null,
            remark: $this->remark,
            status: PurchaseStatus::from($this->status),
            diskonNotaTipe: null,
            diskonNotaNilai: '0',
            items: $items,
            bundles: $bundles,
        );
    }
}
