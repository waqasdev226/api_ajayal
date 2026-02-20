<?php

namespace App\Http\Controllers;

use App\Models\Transactions;
use App\Models\User;
use App\Models\Withdraw;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $investor = $request->input('investor');
        $issue_date = $request->input('issue_date');
        $method = $request->input('method');

        $filter = new \stdClass();
        $filter->investor = $investor;
        $filter->issue_date = $issue_date;
        $filter->method = $method;
//
//        $data = Transactions::whereHas('fromUser', function ($query) use ($investor) {
//            $query->where('id',$investor);
//        });

        switch ($method) {
            case('transfer'):
                $data = Transactions::where('from', $investor)
                    ->where('type', '=', 'transfer')
                    ->with('fromUser', 'toUser')
                    ->orderBy('id', 'desc');
                break;

            case('withdraw'):
                $data = Transactions::where('from', $investor)
                    ->where('type', '=', 'withdraw')
                    ->with('fromUser')
                    ->orderBy('id', 'desc');
                break;

            case('profit'):
                $data = Transactions::where('to', $investor)
                    ->where('type', '=', 'profit')
                    ->with('fromUser')
                    ->orderBy('id', 'desc');
                break;

            default:
                $data = Transactions::where('from', $investor)
                    ->orWhere('to', $investor)
                    ->with('fromUser')
                    ->with('toUser')
                    ->orderBy('id', 'desc');
                break;
        }


//        return response()->json([
//            'data' => $data->paginate(10)->appends(request()->query()),
//        ], 201);

        return response()->json($data->paginate(10),201);
    }

    public function transfer(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'from' => 'required|numeric',
            'to' => 'required|string',
        ]);


        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!User::where('reference', $request->input('to'))->exists()) {
            return response()->json('user not found', 422);
        }

        if (User::where('id', $request->input('from'))->first()->profit < $request->input('amount')) {
            return response()->json('wallet balance not enough', 422);
        }

        $from = User::where('id', $request->input('from'))->first();

        $transaction_x = new Transactions();
        $transaction_x->from = $request->input('from');
        $transaction_x->to = User::where('reference', $request->input('to'))->first()->id;
        $transaction_x->amount = $request->input('amount');
        $transaction_x->current_profit = $from->profit;
        $transaction_x->type = 'transfer';
        $transaction_x->status = 0;
        $transaction_x->save();
        LogController::AuditLogUsers('store', 'Transactions', $transaction_x->id, null, $transaction_x, 'transfer profit from user: ' . $request->input('from'), $request, $from);

        return response()->json([
            'message' => 'transfer successfully',
        ], 201);
    }

    /** Minimum withdrawal: 150 USD or 150,000 IQD */
    private const MIN_WITHDRAW_USD = 150;
    private const MIN_WITHDRAW_IQD = 150_000;

    public function withdraw(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'user_id' => 'required|numeric',
        ], [
            'user_id.required' => 'حقل المستخدم مطلوب',
            'amount.required' => 'حقل المبلغ مطلوب',
            'amount.numeric' => 'حقل المبلغ يجب أن يكون رقمًا',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'يرجى التحقق من البيانات المدخلة', 'errors' => $validator->errors()], 422);
        }

        $from = User::where('id', $request->input('user_id'))->first();
        if (!$from) {
            return response()->json(['message' => 'المستخدم غير موجود'], 422);
        }

        $amount = (float) $request->input('amount');
        $currency = strtoupper((string) ($from->currency ?? 'IQD'));

        // Minimum: 150 USD or 150,000 IQD
        if ($currency === 'USD') {
            if ($amount < self::MIN_WITHDRAW_USD) {
                return response()->json(['message' => 'الحد الأدنى للسحب للحسابات بالدولار هو ' . self::MIN_WITHDRAW_USD . ' USD'], 422);
            }
        } else {
            if ($amount < self::MIN_WITHDRAW_IQD) {
                return response()->json(['message' => 'الحد الأدنى للسحب للحسابات بالدينار هو ' . number_format(self::MIN_WITHDRAW_IQD) . ' د.ع'], 422);
            }
        }

        if (is_null($from->profit) || (float) $from->profit < $amount) {
            return response()->json(['message' => 'الرصيد غير كافي. رصيدك الحالي: ' . number_format((float) $from->profit, 2)], 422);
        }

        // Only last 5 days of the month
        $today = Carbon::now();
        $lastDay = $today->daysInMonth;
        $allowedFrom = $lastDay - 4;
        if ($today->day < $allowedFrom) {
            return response()->json([
                'message' => 'يُسمح بطلب السحب فقط خلال آخر 5 أيام من الشهر (من يوم ' . $allowedFrom . ' إلى ' . $lastDay . ')',
            ], 422);
        }

        // One withdrawal per user per month (pending or approved)
        $hasThisMonth = Withdraw::where('user_id', $from->id)
            ->whereMonth('created_at', $today->month)
            ->whereYear('created_at', $today->year)
            ->whereIn('status', ['0', 0, '1', 1])
            ->exists();
        if ($hasThisMonth) {
            return response()->json(['message' => 'تم تقديم طلب سحب لهذا الشهر مسبقاً. يُسمح بطلب واحد فقط شهرياً.'], 422);
        }

        if (is_null($request->input('phone'))) {
            return response()->json(['message' => 'لايوجد رقم هاتف للسحب من خلاله'], 422);
        }

        $with_d = new Withdraw();
        $with_d->user_id = $request->input('user_id');
        $with_d->amount = $amount;
        $with_d->status = 0; // Pending – admin approves later
        $with_d->note = $request->input('note');
//        $with_d->method = $from->wdr_method;
//        $with_d->phone = $from->wdr_phone;
//        $with_d->name = $from->name;
        $with_d->method = $request->input('method');
        $with_d->phone = $request->input('phone');
        $with_d->name = $request->input('name');
        if ($request->input('method') == 'western_union') {
            if (is_null($request->input('passport'))) {
                return response()->json(['message' => 'لايوجد جواز سفر للسحب من خلاله'], 422);
            } else {
                $with_d->passport = $request->input('passport');
//                $with_d->passport = $from->wdr_passport;
            }
        }
        if ($request->input('method') == 'bank_account') {
            if (is_null($request->input('bank_account'))) {
                return response()->json(['message' => 'لايوجد حساب مصرفي للسحب من خلاله'], 422);
            } else {
                $with_d->bank_account = $request->input('bank_account');
                $with_d->swift = $request->input('swift');
//                $with_d->bank_account = $from->wdr_bank_account;
//                $with_d->swift = $from->wdr_swift;
            }

        }
        if ($request->input('method') == 'credit') {
            if (is_null($request->input('card_no'))) {
                return response()->json(['message' => 'لايوجد بطاقه للسحب من خلاله'], 422);
            } else {
                $with_d->card_no = $request->input('card_no');
//                $with_d->card_no = $from->wdr_card_no;
            }

        }
        $with_d->save();
        LogController::AuditLogUsers('store', 'Withdraw', $with_d->id, null, $with_d, 'withdraw profit request from user: ' . $request->input('user_id'), $request, $from);


        return response()->json([
            'message' => 'تم تقديم طلب السحب بنجاح. في انتظار الموافقة.',
        ], 201);
    }
}
