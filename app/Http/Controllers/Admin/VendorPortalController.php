<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorPortalToken;
use App\Services\VendorPortalService;
use Illuminate\Http\Request;

class VendorPortalController extends Controller
{
    public function __construct(
        private VendorPortalService $vendorPortalService
    ) {}

    public function tokens()
    {
        $companyId = auth()->user()->company_id;

        $tokens = VendorPortalToken::whereHas('vendor', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->with('vendor')
            ->orderByDesc('created_at')
            ->paginate(20);

        $vendors = Vendor::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();

        return view('admin.vendor-portal.tokens', compact('tokens', 'vendors'));
    }

    public function generateToken(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'email'     => 'required|email',
        ]);

        $vendor = Vendor::findOrFail($request->vendor_id);

        if ($vendor->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $token = $this->vendorPortalService->generatePortalToken($vendor, $request->email);

        return redirect()->route('admin.vendor-portal.tokens')
            ->with('success', "Portal token generated for {$vendor->name}. Token: {$token->token}");
    }

    public function revokeToken(VendorPortalToken $token)
    {
        // Verify the token belongs to a vendor in this company
        if ($token->vendor->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $token->update(['is_active' => false]);

        return redirect()->route('admin.vendor-portal.tokens')
            ->with('success', 'Token revoked successfully.');
    }
}
