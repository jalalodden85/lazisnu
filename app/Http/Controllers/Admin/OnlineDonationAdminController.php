<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\OnlineDonation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnlineDonationAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = OnlineDonation::with(['campaign', 'verifier']);

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('donor_name', 'like', "%{$q}%")
                  ->orWhere('donation_code', 'like', "%{$q}%")
                  ->orWhere('donor_phone', 'like', "%{$q}%");
            });
        }

        $donations = $query->orderByDesc('created_at')->paginate(15);
        $campaigns = Campaign::orderBy('title')->get();

        $stats = [
            'total_verified' => OnlineDonation::where('payment_status', 'verified')->sum('total_amount'),
            'count_verified' => OnlineDonation::where('payment_status', 'verified')->count(),
            'count_pending' => OnlineDonation::where('payment_status', 'pending')->count(),
        ];

        return view('admin.online_donations.index', compact('donations', 'campaigns', 'stats'));
    }

    public function show(OnlineDonation $donation)
    {
        $donation->load(['campaign', 'verifier']);
        return view('admin.online_donations.show', compact('donation'));
    }

    public function verify(Request $request, OnlineDonation $donation, \App\Services\DonationVerificationService $service)
    {
        $nominal = $request->filled('nominal_terverifikasi')
            ? (float) $request->nominal_terverifikasi
            : null;

        try {
            $result = $service->verifyByCode($donation->donation_code, $nominal);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'               => true,
                    'status'                => 'verified',
                    'kode_donasi'           => $donation->donation_code,
                    'nominal_terverifikasi' => $result['nominal_terverifikasi'],
                    'message'               => $result['message'],
                ]);
            }

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, OnlineDonation $donation, \App\Services\DonationVerificationService $service)
    {
        try {
            $result = $service->cancelByCode($donation->donation_code);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'     => true,
                    'status'      => 'cancelled',
                    'kode_donasi' => $donation->donation_code,
                    'message'     => $result['message'],
                ]);
            }

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(OnlineDonation $donation)
    {
        if ($donation->payment_status === 'verified') {
            $donation->campaign->decrement('collected_amount', $donation->amount);
        }

        $donation->delete();

        return redirect()->route('admin.online-donations.index')->with('success', 'Catatan donasi berhasil dihapus.');
    }
}
