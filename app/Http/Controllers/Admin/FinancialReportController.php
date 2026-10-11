<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialReport;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function index()
    {
        $reports = FinancialReport::orderByDesc('report_year')
            ->orderByDesc('published_at')
            ->paginate(10);

        return view('admin.reports.index', compact('reports'));
    }

    public function create()
    {
        return view('admin.reports.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'report_year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'report_period' => 'required|string|max:50',
            'total_inflow' => 'required|numeric|min:0',
            'total_outflow' => 'required|numeric|min:0',
            'audit_status' => 'required|string|max:100',
            'file_pdf' => 'nullable|string|max:255',
            'pdf_file' => 'nullable|mimes:pdf|max:10240',
            'published_at' => 'required|date',
        ]);

        if ($request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('reports', 'public');
            $validated['file_pdf'] = '/storage/' . $path;
        } elseif (empty($validated['file_pdf'])) {
            $validated['file_pdf'] = '#';
        }

        FinancialReport::create($validated);

        return redirect()->route('admin.financial-reports.index')->with('success', 'Laporan Keuangan berhasil ditambahkan.');
    }

    public function edit(FinancialReport $financialReport)
    {
        return view('admin.reports.edit', compact('financialReport'));
    }

    public function update(Request $request, FinancialReport $financialReport)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'report_year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'report_period' => 'required|string|max:50',
            'total_inflow' => 'required|numeric|min:0',
            'total_outflow' => 'required|numeric|min:0',
            'audit_status' => 'required|string|max:100',
            'file_pdf' => 'nullable|string|max:255',
            'pdf_file' => 'nullable|mimes:pdf|max:10240',
            'published_at' => 'required|date',
        ]);

        if ($request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('reports', 'public');
            $validated['file_pdf'] = '/storage/' . $path;
        }

        $financialReport->update($validated);

        return redirect()->route('admin.financial-reports.index')->with('success', 'Laporan Keuangan berhasil diperbarui.');
    }

    public function destroy(FinancialReport $financialReport)
    {
        $financialReport->delete();

        return redirect()->route('admin.financial-reports.index')->with('success', 'Laporan Keuangan berhasil dihapus.');
    }
}
