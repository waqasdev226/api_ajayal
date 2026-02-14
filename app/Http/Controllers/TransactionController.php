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

    public function withdraw(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:150',
            'user_id' => 'required|numeric',
//            'phone' => 'required|string',
//            'method' => 'required|string',
        ],array(
                'user_id.required' => 'حقل المستخدم مطلوب',
                'amount.required' => 'حقل المبلغ مطلوب',
                'amount.numeric' => 'حقل المبلغ يجب أن يكون رقمًا'
            )
        );


        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!User::where('id', $request->input('user_id'))->exists()) {
            return response()->json('المستخدم غير موجود', 422);
        }

        if (User::where('id', $request->input('user_id'))->first()->profit < $request->input('amount')) {
            return response()->json('الرصيد غير كافي', 422);
        }

        if ((int)$request->input('amount') < 150) {
            $curr = User::where('id', $request->input('user_id'))->first()->currency;
            if ($curr == 'IQD') {
                return response()->json(' لا يمكن سحب اقل من ١٥٠ الف دينار', 422);
            } else {
                return response()->json(' لا يمكن سحب اقل من ١٥٠ دولار', 422);
            }
        }

        error_log('------- 1');

        $from = User::where('id', $request->input('user_id'))->first();
        error_log('------- 2');

        if (is_null($from->profit)){
            return response()->json('لايوجد ارباح', 422);
        }

        $last_withdraw = Withdraw::where('user_id', $from->id)->orderBy('id', 'desc')->first();


        if (isset($last_withdraw)){
//            $last = Carbon::createFromFormat('Y-m', $last_withdraw->created_at);
            $last = Carbon::parse($last_withdraw->created_at)->format('Y-m');
            $now = Carbon::parse(Carbon::now())->format('Y-m');
            if ($last == $now) {
                return response()->json('لايمكن السحب اكثر من مرة في الشهر', 422);

            }
        }

//        $transaction_x = new Transactions();
//        $transaction_x->from = $from->id;
//        $transaction_x->to = 0;
//        $transaction_x->amount = 0;
//        $transaction_x->current_profit = $from->profit;
//        $transaction_x->type = 'withdraw';
//        $transaction_x->status = 0;
//        $transaction_x->save();
//        LogController::AuditLogUsers('store', 'Transactions', $transaction_x->id, null, $transaction_x, 'withdraw profit from user: ' . $request->input('from'), $request, $from);
        if (is_null($request->input('phone'))){
            return response()->json('لايوجد رقم هاتف للسحب من خلاله', 422);
        }



        $with_d = new Withdraw();
        $with_d->user_id = $request->input('user_id');
        $with_d->amount = $request->input('amount');
        $with_d->status = 1;
        $with_d->note = $request->input('note');
//        $with_d->method = $from->wdr_method;
//        $with_d->phone = $from->wdr_phone;
//        $with_d->name = $from->name;
        $with_d->method = $request->input('method');
        $with_d->phone = $request->input('phone');
        $with_d->name = $request->input('name');
        if ($request->input('method') == 'western_union'){
            if (is_null($request->input('passport'))){
                return response()->json('لايوجد جواز سفر للسحب من خلاله', 422);
            } else {
                $with_d->passport = $request->input('passport');
//                $with_d->passport = $from->wdr_passport;
            }
        }
        if ($request->input('method') == 'bank_account'){
            if (is_null($request->input('bank_account'))){
                return response()->json('لايوجد حساب مصرفي للسحب من خلاله', 422);
            } else {
                $with_d->bank_account = $request->input('bank_account');
                $with_d->swift = $request->input('swift');
//                $with_d->bank_account = $from->wdr_bank_account;
//                $with_d->swift = $from->wdr_swift;
            }

        }
        if ($request->input('method') == 'credit'){
            if (is_null($request->input('card_no'))){
                return response()->json('لايوجد بطاقه للسحب من خلاله', 422);
            } else {
                $with_d->card_no = $request->input('card_no');
//                $with_d->card_no = $from->wdr_card_no;
            }

        }
        $with_d->save();
        LogController::AuditLogUsers('store', 'Withdraw', $with_d->id, null, $with_d, 'withdraw profit request from user: ' . $request->input('user_id'), $request, $from);


        return response()->json([
            'message' => 'تم السحب بنجاح',
        ], 201);
    }
}
