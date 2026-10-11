<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDonatorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'box_number'  => ['nullable', 'string', 'max:50', 'unique:donators,box_number'],
            'name'        => ['required', 'string', 'max:150'],
            'nik'         => ['nullable', 'digits:16', 'unique:donators,nik'],
            'phone'       => ['nullable', 'string', 'max:25'],
            'ranting_id'  => ['required', 'exists:rantings,id'],
            'relawan_id'  => ['nullable', 'exists:relawans,id'],
            'address'     => ['required', 'string', 'max:500'],
            'rt'          => ['nullable', 'string', 'max:10'],
            'rw'          => ['nullable', 'string', 'max:10'],
            'status'      => ['required', 'in:active,inactive'],
            'joined_date' => ['nullable', 'date'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom validation messages in Indonesian
     */
    public function messages(): array
    {
        return [
            'box_number.required'  => 'Nomor kaleng / kotak KOIN NU wajib diisi.',
            'box_number.unique'    => 'Nomor kaleng ini sudah terdaftar di sistem.',
            'name.required'        => 'Nama donatur / kepala keluarga wajib diisi.',
            'nik.digits'           => 'NIK harus berjumlah tepat 16 digit angka.',
            'nik.unique'           => 'NIK donatur ini sudah terdaftar.',
            'ranting_id.required'  => 'Ranting / Desa wilayah domisili wajib dipilih.',
            'ranting_id.exists'    => 'Ranting yang dipilih tidak terdaftar di sistem.',
            'relawan_id.exists'    => 'Petugas relawan pengampu yang dipilih tidak valid.',
            'address.required'     => 'Alamat rumah donatur wajib diisi.',
            'status.required'      => 'Status kotak infaq wajib ditentukan.',
            'status.in'            => 'Status harus bernilai aktif atau nonaktif.',
        ];
    }
}
