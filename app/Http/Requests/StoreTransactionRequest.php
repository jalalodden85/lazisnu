<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation (membersihkan format rupiah jika dikirim dari input masking)
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            // Hilangkan karakter non-numerik seperti "Rp.", titik, dan spasi
            $cleanAmount = preg_replace('/[^0-9]/', '', (string) $this->amount);
            $this->merge([
                'amount' => $cleanAmount,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'donator_id' => ['required', 'exists:donators,id'],
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:500'],
            'collection_date' => ['required', 'date', 'before_or_equal:today'],
            'period' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'notes' => ['nullable', 'string', 'max:500'],
            'proof_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    /**
     * Custom validation messages in Indonesian
     */
    public function messages(): array
    {
        return [
            'donator_id.required' => 'Pilih donatur / nomor kaleng terlebih dahulu.',
            'donator_id.exists' => 'Data donatur tidak ditemukan di sistem.',
            'user_id.required' => 'Petugas lapangan (JPZIS) wajib dipilih.',
            'user_id.exists' => 'Petugas lapangan tidak terdaftar.',
            'amount.required' => 'Nominal infaq kaleng wajib diisi.',
            'amount.numeric' => 'Nominal infaq harus berupa angka yang valid.',
            'amount.min' => 'Nominal infaq minimal Rp 500.',
            'collection_date.required' => 'Tanggal pengambilan koin wajib diisi.',
            'collection_date.before_or_equal' => 'Tanggal pengambilan tidak boleh melebihi hari ini.',
            'proof_image.image' => 'File bukti harus berupa gambar.',
            'proof_image.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
