<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\GenerateInvoiceRequest;
use App\Http\Requests\Invoice\PayBatchInvoiceRequest;
use App\Http\Resources\V1\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->invoices->paginate(
            filters: $request->only(['month', 'year', 'status', 'search', 'sort_by', 'sort_dir']),
            perPage: min((int) $request->input('per_page', 15), 100)
        );

        return ApiResponse::paginated(
            $paginator,
            InvoiceResource::collection($paginator->items())->resolve(),
            'Daftar tagihan.'
        );
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer.package']);

        return ApiResponse::success(new InvoiceResource($invoice), 'Detail tagihan.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->invoices->delete($invoice);

        return ApiResponse::success(null, 'Tagihan berhasil dihapus.');
    }

    public function generate(GenerateInvoiceRequest $request, InvoiceService $invoiceService)
    {
        $data = $request->validated();
        $result = $invoiceService->generateMonthlyInvoices((int) $data['month'], (int) $data['year']);

        return ApiResponse::success($result, $result['message']);
    }

    public function payBatch(PayBatchInvoiceRequest $request, InvoiceService $invoiceService)
    {
        $data = $request->validated();
        $result = $invoiceService->payBatch(
            $data['invoice_ids'],
            $data['payment_method'],
            $request->file('payment_proof')
        );

        if ($result['status'] === 'error') {
            return ApiResponse::error($result['message'], 422, 'PAYMENT_FAILED');
        }

        return ApiResponse::success([
            'paid_count' => $result['count'],
            'total_amount' => $result['totalAmount'],
            'invoices' => InvoiceResource::collection($result['invoices'])->resolve(),
        ], $result['message']);
    }
}
