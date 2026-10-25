<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:normalize-phones', function () {
    $this->info('Memulai normalisasi nomor WhatsApp/kontak ke format standar 62...');

    $donations = \App\Models\OnlineDonation::all();
    foreach ($donations as $d) {
        $d->save();
    }
    $this->info("{$donations->count()} Online Donations diproses.");

    $mustahiqs = \App\Models\Mustahiq::all();
    foreach ($mustahiqs as $m) {
        $m->save();
    }
    $this->info("{$mustahiqs->count()} Mustahiq diproses.");

    $donators = \App\Models\Donator::all();
    foreach ($donators as $don) {
        $don->save();
    }
    $this->info("{$donators->count()} Donatur diproses.");

    $mwcs = \App\Models\Mwc::all();
    foreach ($mwcs as $mwc) {
        $mwc->save();
    }
    $this->info("{$mwcs->count()} Pengurus MWC diproses.");

    $rantings = \App\Models\Ranting::all();
    foreach ($rantings as $ranting) {
        $ranting->save();
    }
    $this->info("{$rantings->count()} Pengurus Ranting diproses.");

    $relawans = \App\Models\Relawan::all();
    foreach ($relawans as $rel) {
        $rel->save();
    }
    $this->info("{$relawans->count()} Relawan diproses.");

    $this->info('Selesai! Seluruh nomor kontak telah dinormalisasi ke format standar 62.');
})->purpose('Normalisasi nomor kontak/WhatsApp pada database ke format standar 62');

