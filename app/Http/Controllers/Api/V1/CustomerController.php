<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\V1\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers
    ) {
    }

    public function index(Request $request)
    {
        $paginator = $this->customers->paginate(
            filters: $request->only(['search', 'status', 'sort_by', 'sort_dir']),
            perPage: min((int) $request->input('per_page', 15), 100)
        );

        return ApiResponse::paginated(
            $paginator,
            CustomerResource::collection($paginator->items())->resolve(),
            'Daftar pelanggan.'
        );
    }

    public function store(StoreCustomerRequest $request, CustomerService $customerService)
    {
        $customer = $customerService->storeCustomer($request->validated(), $request->allFiles());
        $customer->load(['package', 'odp', 'statusHistories.user']);

        return ApiResponse::success(new CustomerResource($customer), 'Pelanggan berhasil dibuat.', 201);
    }

    public function show(Customer $customer)
    {
        $customer->load(['package', 'odp', 'latestInvoice', 'statusHistories.user']);

        return ApiResponse::success(new CustomerResource($customer), 'Detail pelanggan.');
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, CustomerService $customerService)
    {
        $customer = $customerService->updateCustomer($customer, $request->validated(), $request->allFiles());
        $customer->load(['package', 'odp', 'statusHistories.user']);

        return ApiResponse::success(new CustomerResource($customer), 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $this->customers->delete($customer);

        return ApiResponse::success(null, 'Pelanggan berhasil dihapus.');
    }
}
