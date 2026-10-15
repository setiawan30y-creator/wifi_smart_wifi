<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ExpenseResource;
use App\Http\Resources\V1\InvoiceResource;
use App\Models\Expense;
use App\Models\Invoice;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FinancialReportController extends Controller
{
    public function summary(Request $request)
    {
        $startDateInput = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDateInput = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $startDate = Carbon::parse($startDateInput)->startOfDay();
        $endDate = Carbon::parse($endDateInput)->endOfDay();

        $totalIncome = Invoice::where('status', 'paid')->whereBetween('paid_at', [$startDate, $endDate])->sum('amount');
        $totalExpense = Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');
        $netProfit = $totalIncome - $totalExpense;

        $categories = ['Pembelian Perangkat', 'Operasional', 'Gaji/Honor', 'Perbaikan', 'Lain-lain', 'ISP', 'Listrik', 'Perlengkapan'];
        $categoryBreakdown = collect($categories)->map(function ($category) use ($startDate, $endDate, $totalExpense) {
            $amount = Expense::where('category', $category)->whereBetween('date', [$startDate, $endDate])->sum('amount');

            return [
                'category' => $category,
                'amount' => $amount,
                'percentage' => $totalExpense > 0 ? round(($amount / $totalExpense) * 100, 1) : 0,
            ];
        })->sortByDesc('amount')->values();

        $monthlyBreakdown = collect(range(5, 0))->map(function ($index) {
            $monthDate = Carbon::now()->subMonths($index);
            $income = Invoice::where('status', 'paid')->where('year', $monthDate->year)->where('month', $monthDate->month)->sum('amount');
            $expense = Expense::whereYear('date', $monthDate->year)->whereMonth('date', $monthDate->month)->sum('amount');

            return [
                'label' => $monthDate->translatedFormat('F Y'),
                'income' => $income,
                'expense' => $expense,
                'profit' => $income - $expense,
            ];
        });

        $recentInvoices = Invoice::with('customer')->where('status', 'paid')->whereBetween('paid_at', [$startDate, $endDate])->latest('paid_at')->limit(10)->get();
        $recentExpenses = Expense::with('deviceStock')->whereBetween('date', [$startDate, $endDate])->latest('date')->limit(10)->get();

        return ApiResponse::success([
            'period' => [
                'start_date' => $startDateInput,
                'end_date' => $endDateInput,
            ],
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_profit' => $netProfit,
                'profit_margin' => $totalIncome > 0 ? round(($netProfit / $totalIncome) * 100, 1) : 0,
            ],
            'category_breakdown' => $categoryBreakdown,
            'monthly_breakdown' => $monthlyBreakdown,
            'recent_invoices' => InvoiceResource::collection($recentInvoices)->resolve(),
            'recent_expenses' => ExpenseResource::collection($recentExpenses)->resolve(),
        ], 'Ringkasan laporan keuangan.');
    }
}
