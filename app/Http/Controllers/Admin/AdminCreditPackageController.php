<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\CreditPackage;
use App\Models\User;
use App\Services\StripeCurrency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminCreditPackageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/CreditPackages', [
            'packages' => CreditPackage::query()
                ->orderByDesc('is_active')
                ->orderBy('credits_amount')
                ->get(['id', 'name', 'credits_amount', 'price_amount', 'currency', 'is_active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);
        $data = $this->validatedPackage($request);

        DB::transaction(function () use ($admin, $data, $request): void {
            $package = CreditPackage::query()->create($data);

            AdminAuditLog::query()->create([
                'admin_id' => $admin->id,
                'action' => 'credit_package.created',
                'target_type' => CreditPackage::class,
                'target_id' => $package->id,
                'metadata' => ['package' => $data],
                'ip_address' => $request->ip(),
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Credit package created.']);

        return back();
    }

    public function update(Request $request, CreditPackage $creditPackage): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);
        $data = $this->validatedPackage($request);

        DB::transaction(function () use ($admin, $creditPackage, $data, $request): void {
            $package = CreditPackage::query()->lockForUpdate()->findOrFail($creditPackage->id);
            $previous = $package->only(['name', 'credits_amount', 'price_amount', 'currency', 'is_active']);
            $package->update($data);

            AdminAuditLog::query()->create([
                'admin_id' => $admin->id,
                'action' => 'credit_package.updated',
                'target_type' => CreditPackage::class,
                'target_id' => $package->id,
                'metadata' => ['before' => $previous, 'after' => $data],
                'ip_address' => $request->ip(),
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Credit package updated.']);

        return back();
    }

    /**
     * @return array{name: string, credits_amount: int, price_amount: string, currency: string, is_active: bool}
     */
    private function validatedPackage(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'credits_amount' => ['required', 'integer', 'min:1', 'max:1000000'],
            'price_amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'currency' => ['required', 'string', 'regex:/^[A-Za-z]{3}$/'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['currency'] = strtoupper($data['currency']);

        try {
            StripeCurrency::toMinorUnits((string) $data['price_amount'], $data['currency']);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'price_amount' => $exception->getMessage(),
            ]);
        }

        return $data;
    }
}
