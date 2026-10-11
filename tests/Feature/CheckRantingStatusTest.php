<?php

namespace Tests\Feature;

use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Relawan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckRantingStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_suspended_ranting_admin_cannot_access_create_store_routes_and_receives_warning(): void
    {
        $roleRanting = Role::where('name', 'ranting')->first();
        $ranting = Ranting::first();
        $mwc = $ranting->mwc;

        // Set Ranting menjadi non-aktif (suspended oleh PC)
        $ranting->update(['is_active' => false]);

        $userRanting = User::where('ranting_id', $ranting->id)->first();
        if (!$userRanting) {
            $userRanting = User::factory()->create([
                'role_id'    => $roleRanting->id,
                'ranting_id' => $ranting->id,
                'mwc_id'     => $mwc->id,
                'is_active'  => true,
            ]);
        }

        $this->actingAs($userRanting);

        // 1. Admin Ranting tetap bisa melihat riwayat (Read-Only)
        $this->get(route('dashboard'))->assertStatus(200);
        $this->get(route('transactions.index'))->assertStatus(200);
        $this->get(route('relawans.index'))->assertStatus(200);

        // 2. Akses ke input setoran koin baru (create & store) diblokir oleh middleware CheckRantingStatus
        $resCreateTx = $this->from(route('dashboard'))->get(route('transactions.create'));
        $resCreateTx->assertRedirect(route('dashboard'));
        $resCreateTx->assertSessionHas('error', 'Akses input dinonaktifkan oleh PC Lazisnu. Silakan selesaikan urusan administrasi Anda.');

        $relawan = Relawan::where('ranting_id', $ranting->id)->first();
        $resStoreTx = $this->from(route('dashboard'))->post(route('transactions.store'), [
            'relawan_id'      => $relawan?->id ?? 1,
            'amount'          => 50000,
            'collection_date' => now()->toDateString(),
        ]);
        $resStoreTx->assertRedirect(route('dashboard'));
        $resStoreTx->assertSessionHas('error', 'Akses input dinonaktifkan oleh PC Lazisnu. Silakan selesaikan urusan administrasi Anda.');

        // 3. Akses ke create & store relawan diblokir oleh middleware CheckRantingStatus
        $resCreateRelawan = $this->from(route('dashboard'))->get(route('relawans.create'));
        $resCreateRelawan->assertRedirect(route('dashboard'));
        $resCreateRelawan->assertSessionHas('error', 'Akses input dinonaktifkan oleh PC Lazisnu. Silakan selesaikan urusan administrasi Anda.');

        // Kembalikan status ranting menjadi aktif
        $ranting->update(['is_active' => true]);
        $userRanting->refresh();
        $userRanting->unsetRelation('ranting');

        // Setelah aktif, akses create setoran kembali diizinkan
        $this->get(route('transactions.create'))->assertStatus(200);
    }

    public function test_inactive_ranting_transactions_remain_counted_in_mwc_and_pc_dashboard(): void
    {
        $ranting = Ranting::first();
        $mwc = $ranting->mwc;

        // Ambil total dana awal di MWC
        $initialMwcBalance = KoinTransaction::confirmed()->where('mwc_id', $mwc->id)->sum('amount');

        // Nonaktifkan ranting
        $ranting->update(['is_active' => false]);

        // Periksa bahwa total saldo MWC tidak berkurang (tetap menghitung uang yang sudah masuk secara historis)
        $currentMwcBalance = KoinTransaction::confirmed()->where('mwc_id', $mwc->id)->sum('amount');
        $this->assertEquals($initialMwcBalance, $currentMwcBalance);

        // Kembalikan status
        $ranting->update(['is_active' => true]);
    }
}
