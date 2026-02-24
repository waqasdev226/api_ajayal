<?php

namespace App\Http\Controllers;

use App\Models\Transactions;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ManualProfitController extends Controller
{
    /**
     * Manual profit credit: add profit to a user's wallet (admin only via API key).
     * Request: user_id, amount, currency (IQD|USD), note (optional).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:IQD,USD',
            'note' => 'nullable|string|max:500',
        ], [
            'user_id.required' => 'حقل المستخدم مطلوب',
            'user_id.exists' => 'المستخدم غير موجود',
            'amount.required' => 'حقل المبلغ مطلوب',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر',
            'currency.required' => 'حقل العملة مطلوب',
            'currency.in' => 'العملة يجب أن تكون IQD أو USD',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'يرجى التحقق من البيانات', 'errors' => $validator->errors()], 422);
        }

        $user = User::find($request->input('user_id'));
        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 422);
        }

        $amount = (float) $request->input('amount');
        $currency = strtoupper((string) $request->input('currency'));
        $note = $request->input('note');

        $currentProfit = (float) ($user->profit ?? 0);
        $currentTotal = (float) ($user->total_profit ?? 0);

        $user->profit = $currentProfit + $amount;
        $user->total_profit = $currentTotal + $amount;
        $user->save();

        $transaction = new Transactions();
        $transaction->from = null;
        $transaction->to = $user->id;
        $transaction->amount = $amount;
        $transaction->current_profit = $user->profit;
        $transaction->type = 'profit';
        $transaction->status = 1;
        $transaction->note = $note;
        $transaction->method = 'manual_credit';
        $transaction->currency = $currency;
        $transaction->created_by_guard = $request->header('X-Admin-Guard', 'api');
        $transaction->created_by_id = $request->header('X-Admin-Id') ? (int) $request->header('X-Admin-Id') : null;
        $transaction->save();

        return response()->json([
            'message' => 'تم إضافة الربح بنجاح',
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $currency,
            'new_balance' => (float) $user->profit,
        ], 201);
    }
}
