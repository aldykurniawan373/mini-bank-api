<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\IndexCustomerRequest;
use App\Http\Requests\Api\Customer\StoreCustomerRequest;
use App\Http\Requests\Api\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Api\Customer;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    use ApiResponse;

    public function index(IndexCustomerRequest $request)
    {
        if (!Auth::user()->hasPermissionTo('customer.view')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melihat data nasabah');
        }

        $query = Customer::query();
        if ($request->getSearch()) {
            $search = $request->getSearch();
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $query->orderBy($request->getSortBy(), $request->getSortDir());

        $customers = $query->paginate($request->getPerPage());

        return $this->paginatedResponse(
            'success',
            CustomerResource::collection($customers)
        );
    }

    public function store(StoreCustomerRequest $request)
    {
        if (!Auth::user()->hasPermissionTo('customer.create')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk membuat data nasabah');
        }

        $customer = DB::transaction(function () use ($request) {
            return Customer::create($request->validated());
        });

        return $this->createdResponse(
            'success',
            new CustomerResource($customer)
        );
    }

    public function show(Customer $customer)
    {
        if (!Auth::user()->hasPermissionTo('customer.view')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk melihat data nasabah');
        }

        $customer->load('accounts');

        return $this->successResponse(
            'success',
            new CustomerResource($customer)
        );
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        if (!Auth::user()->hasPermissionTo('customer.update')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk memperbarui data nasabah');
        }

        $customer = DB::transaction(function () use ($request, $customer) {
            $customer->update($request->validated());
            $customer->refresh();

            return $customer;
        });

        return $this->successResponse(
            'success',
            new CustomerResource($customer)
        );
    }

    public function destroy(Customer $customer)
    {
        if (!Auth::user()->hasPermissionTo('customer.delete')) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk menghapus data nasabah');
        }

        DB::transaction(function () use ($customer) {
            $customer->update([
                'deleted_by' => Auth::id(),
            ]);

            $customer->delete();
        });

        return $this->noContentResponse();
    }
}
