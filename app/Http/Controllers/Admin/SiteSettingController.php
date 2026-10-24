<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::pluck('value', 'key')->toArray();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $allowedKeys = [
            'site_name',
            'site_tagline',
            'branch_name',
            'legal_kemenag',
            'audit_opinion',
            'office_address',
            'office_hours',
            'website_url',
            'phone_wa_hotline',
            'email_support',
            'instagram_url',
            'gold_price_per_gram',
            'bank_bsi_zakat',
            'bank_bsi_zakat_name',
            'bank_bri_infaq',
            'bank_bri_infaq_name',
            'bank_bsi_koin',
            'bank_bsi_koin_name',
            'bank_bri_zakat',
            'bank_bri_zakat_name',
            'bank_mandiri_infaq',
            'bank_mandiri_infaq_name',
            'bank_bca_donasi',
            'bank_bca_donasi_name',
        ];

        foreach ($allowedKeys as $key) {
            if ($request->has($key)) {
                SiteSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }

        return redirect()->route('admin.site-settings.index')->with('success', 'Pengaturan Portal & Rekening Donasi berhasil diperbarui.');
    }
}
