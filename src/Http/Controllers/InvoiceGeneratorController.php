<?php

namespace ME\Utility\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ME\Http\Controllers\Controller;
use Illuminate\Support\Str;

class InvoiceGeneratorController extends Controller
{
    protected string $disk = 'local';
    protected string $folder = 'invoices';

    public function index()
    {
        return view('utility::invoice-generator.index');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'shop_name'      => 'required|string|max:255',
            'shop_address'   => 'nullable|string|max:500',
            'shop_phone'     => 'nullable|string|max:50',
            'shop_email'     => 'nullable|email|max:255',
            'logo'           => 'nullable|image|max:2048',
            'invoice_number' => 'nullable|string|max:50',
            'invoice_date'   => 'nullable|date',
            'customer_name'  => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_address' => 'nullable|string|max:500',
            'terms'          => 'nullable|string',
            'description'    => 'required|array|min:1',
            'description.*'  => 'required|string|max:255',
            'qty.*'          => 'nullable|numeric|min:0',
            'unit_price.*'   => 'nullable|numeric|min:0',
            'warranty.*'     => 'nullable|string|max:100',
        ]);

        $logoDataUri = null;
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $logoDataUri = 'data:'.$file->getMimeType().';base64,'.base64_encode(file_get_contents($file->getRealPath()));
        }

        $invoiceNumber = $request->invoice_number
            ? Str::slug($request->invoice_number)
            : 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));

        $items = [];
        foreach ($request->description as $index => $description) {
            if (trim($description) === '') {
                continue;
            }
            $qty = (float) ($request->qty[$index] ?? 0);
            $unitPrice = (float) ($request->unit_price[$index] ?? 0);
            $items[] = [
                'description' => $description,
                'qty'         => $qty,
                'unit_price'  => $unitPrice,
                'amount'      => $qty * $unitPrice,
                'warranty'    => $request->warranty[$index] ?? null,
            ];
        }

        $grandTotal = array_sum(array_column($items, 'amount'));

        $invoice = [
            'shop_name'         => $request->shop_name,
            'shop_address'      => $request->shop_address,
            'shop_phone'        => $request->shop_phone,
            'shop_email'        => $request->shop_email,
            'logo'              => $logoDataUri,
            'invoice_number'    => $invoiceNumber,
            'invoice_date'      => $request->invoice_date ? \Carbon\Carbon::parse($request->invoice_date)->toDateString() : now()->toDateString(),
            'customer_name'     => $request->customer_name,
            'customer_phone'    => $request->customer_phone,
            'customer_address'  => $request->customer_address,
            'terms'             => $request->terms,
            'items'             => $items,
            'grand_total'       => $grandTotal,
            'created_at'        => now()->toDateTimeString(),
        ];

        Storage::disk($this->disk)->put(
            "{$this->folder}/{$invoiceNumber}.json",
            json_encode($invoice, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $this->renderPrint($invoice);
    }

    public function history(Request $request)
    {
        $files = collect(Storage::disk($this->disk)->files($this->folder))
            ->filter(fn ($path) => str_ends_with($path, '.json'));

        $invoices = $files->map(function ($path) {
            $data = json_decode(Storage::disk($this->disk)->get($path), true);
            return $data ?: null;
        })->filter()->sortByDesc('created_at')->values();

        if ($request->search) {
            $search = strtolower($request->search);
            $invoices = $invoices->filter(function ($invoice) use ($search) {
                return str_contains(strtolower($invoice['invoice_number']), $search)
                    || str_contains(strtolower($invoice['customer_name'] ?? ''), $search);
            })->values();
        }

        return view('utility::invoice-generator.history', compact('invoices'));
    }

    public function print(string $invoiceNumber)
    {
        $path = "{$this->folder}/{$invoiceNumber}.json";
        abort_unless(Storage::disk($this->disk)->exists($path), 404);

        $invoice = json_decode(Storage::disk($this->disk)->get($path), true);

        return $this->renderPrint($invoice);
    }

    public function destroy(string $invoiceNumber)
    {
        $path = "{$this->folder}/{$invoiceNumber}.json";
        abort_unless(Storage::disk($this->disk)->exists($path), 404);

        Storage::disk($this->disk)->delete($path);

        return redirect()->back()->with('success', 'Invoice deleted!');
    }

    protected function renderPrint(array $invoice)
    {
        return view('utility::invoice-generator.print', [
            'shop_name'         => $invoice['shop_name'],
            'shop_address'      => $invoice['shop_address'],
            'shop_phone'        => $invoice['shop_phone'],
            'shop_email'        => $invoice['shop_email'],
            'logo'              => $invoice['logo'],
            'invoice_number'    => $invoice['invoice_number'],
            'invoice_date'      => \Carbon\Carbon::parse($invoice['invoice_date']),
            'customer_name'     => $invoice['customer_name'],
            'customer_phone'    => $invoice['customer_phone'],
            'customer_address'  => $invoice['customer_address'],
            'terms'             => $invoice['terms'],
            'items'             => $invoice['items'],
            'grandTotal'        => $invoice['grand_total'],
        ]);
    }
}
