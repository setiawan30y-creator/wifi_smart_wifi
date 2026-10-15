<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FinancialReportController extends Controller
{
    /**
     * Display the financial report dashboard.
     */
    public function index(Request $request)
    {
        // Set default date range to current month
        $startDateInput = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDateInput = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $startDate = Carbon::parse($startDateInput)->startOfDay();
        $endDate = Carbon::parse($endDateInput)->endOfDay();

        // Calculate Income: Paid Invoices in date range (based on paid_at)
        $totalIncome = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('amount');

        // Calculate Expenses in date range
        $totalExpense = Expense::whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $netProfit = $totalIncome - $totalExpense;
        $profitMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        // Expense breakdown by category in date range
        $categories = ['Pembelian Perangkat', 'Operasional', 'Gaji/Honor', 'Perbaikan', 'Lain-lain', 'ISP', 'Listrik', 'Perlengkapan'];
        $categoryBreakdown = [];
        
        foreach ($categories as $cat) {
            $amount = Expense::where('category', $cat)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');
                
            $percentage = $totalExpense > 0 ? ($amount / $totalExpense) * 100 : 0;
            
            $categoryBreakdown[] = [
                'category' => $cat,
                'amount' => $amount,
                'percentage' => round($percentage, 1)
            ];
        }

        // Sort breakdown by amount descending
        usort($categoryBreakdown, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        // 6-Month Monthly Breakdown (cross-db compatible)
        $monthlyBreakdown = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $year = $monthDate->year;
            $month = $monthDate->month;
            $monthName = $monthDate->translatedFormat('F Y');

            $mIncome = Invoice::where('status', 'paid')
                ->where('year', $year)
                ->where('month', $month)
                ->sum('amount');

            $mExpense = Expense::whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('amount');

            $monthlyBreakdown[] = [
                'label' => $monthName,
                'income' => $mIncome,
                'expense' => $mExpense,
                'profit' => $mIncome - $mExpense
            ];
        }

        // Recent Income Transactions in date range
        $recentInvoices = Invoice::with('customer')
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->orderBy('paid_at', 'desc')
            ->limit(10)
            ->get();

        // Recent Expense Transactions in date range
        $recentExpenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        return view('financial_reports.index', compact(
            'totalIncome',
            'totalExpense',
            'netProfit',
            'profitMargin',
            'categoryBreakdown',
            'monthlyBreakdown',
            'recentInvoices',
            'recentExpenses',
            'startDateInput',
            'endDateInput'
        ));
    }
}
