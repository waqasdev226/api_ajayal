<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserOTP;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use function Symfony\Component\Translation\t;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
//    public function __construct()
//    {
//        $this->middleware('jwt.verify', ['except' => ['login','loginOTP','register']]);
//    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = request(['phone', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        if ($user->enabled == 0){
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        LogController::Auditlog( 'login', 'User', Auth::id(), null, null, 'user login: '.Auth::user()->name, $request);

        $otp =$this->createOTP(Auth::id());

        \App\Classes\SMS::sendSMS($user->phone, " رمز تسجيل الدخول ".$otp->otp);
//        return $this->respondWithToken($token);
        return response()->json([
            'message' => 'User pending login',
            'otp_ref' => $otp->reference
        ], 201);
    }

    public function loginOTP(Request $request, $reference)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        $ootp = UserOTP::where('reference', $reference)->first();
        if ($ootp->otp != $request->input('otp')){
            return response()->json(['error' => 'not match'], 401);
        }

        if ($ootp->status == 1 ){
            return response()->json(['error' => 'not valid'], 401);
        }

        if ($ootp->finish_at < Carbon::now()){
            return response()->json(['error' => 'timeout'], 401);
        }

        $user = User::find($ootp->user_id);

        return $this->respondWithToken(JWTAuth::fromUser($user));

    }

    public function refreshOTP(Request $request, $reference)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        $ootp = UserOTP::where('reference', $reference)->first();
        if ($ootp->status == 1 ){
            return response()->json(['error' => 'not valid'], 401);
        }

        $now = Carbon::now();
//        $start = $now->subDay();
//        $end = $now->subMinute();
//
//        if ($now->between($start, $end)) {
//            return response()->json(['error' => 'timeout'], 401);
//        }

        if ($ootp->finish_at < $now){
            return response()->json(['error' => 'timeout'], 401);
        }

        $user = User::find($ootp->user_id);

        return $this->respondWithToken(JWTAuth::fromUser($user));

    }

    public function checkOTP(Request $request, $reference)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        $ootp = UserOTP::where('reference', $reference)->first();
        if ($ootp->otp != $request->input('otp')){
            return response()->json(['error' => 'not match'], 401);
        }

//        if ($ootp->finish_at < Carbon::now()){
//            return response()->json(['error' => 'timeout'], 401);
//        }

        if ($ootp->type == 'register'){
            User::withTrashed()->find($ootp->user_id)->restore();
            $user = User::find($ootp->user_id);
            return $this->respondWithToken(JWTAuth::fromUser($user));
        } else {
            return response()->json([
                'message' => ' successfully'
            ], 201);
        }


    }

    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string',
            'name' => 'required|string',
            'phone' => 'required|string|max:100|unique:users',
            'password' => 'required|string|confirmed|min:5',
            'password_confirmation' => 'required|string|same:password',
        ]);


        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }



        $user = User::create(array_merge(
            $validator->validated(),
            [
                'enabled'=>1,
                'reference' => 'AL_'.(string)((User::count() * 5) + 2500),
                'password' => Hash::make($request->password)
            ]
        ));

        $pre_data = User::find($user->id);

        if ($request->hasFile('id_back_image')) {
            $destinationPath = public_path() . '/attachments/'. $user->id . '/';
            $file = $request->file('id_back_image');
            $name = $pre_data->id.'-back_image-' .$pre_data->reference. '.' . $file->getClientOriginalExtension();
            $name = str_replace(' ', '-',$name);
            $file->move($destinationPath, $name);
            $pre_data['id_back_image'] = '/attachments/'. $user->id .'/'. $name;
        }

        if ($request->hasFile('id_front_image')) {
            $destinationPath = public_path() . '/attachments/'. $user->id . '/';
            $file = $request->file('id_front_image');
            $name = $pre_data->id.'-front_image-' .$pre_data->reference. '.' . $file->getClientOriginalExtension();
            $name = str_replace(' ', '-',$name);
            $file->move($destinationPath, $name);
            $pre_data['id_front_image'] = '/attachments/'. $user->id .'/'. $name;
        }




        $pre_data->update();
        LogController::AuditLogRegister( 'register', 'User', $user->id, null, $user, 'user register: '.Auth::user(), $request);

        User::where('id', $user->id)->delete();
        $otp = UserOTP::create([
            'user_id'   =>  $user->id,
            'otp'   =>  random_int(1000, 9999),
            'reference' =>  self::strUUID(10),
            'created_at'    =>  Carbon::now(),
            'finish_at' =>  Carbon::now()->addMinute(),
            'type'=>'register',
            'status'=>0,
        ]);

        \App\Classes\SMS::sendSMS($user->phone, " رمز تسجيل الدخول ".$otp->otp);


        return response()->json([
            'message' => 'User registered successfully',
            'otp_ref' => $otp->reference
        ], 201);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $user = Auth::user();
        if ($user->enabled == 1){
            return $this->respondWithToken(auth()->refresh());

        } else {
            $this->logout();
        }

    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    /**
     * Forgot password: send reset OTP to phone (used by website).
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = User::where('phone', $request->input('phone'))->first();

        if (!$user) {
            return response()->json(['message' => 'No account found for this phone number.'], 404);
        }

        if ($user->enabled == 0) {
            return response()->json(['error' => 'Unauthorized', 'message' => 'Account is disabled.'], 401);
        }

        $otp = UserOTP::create([
            'user_id'   => $user->id,
            'otp'       => random_int(1000, 9999),
            'reference' => self::strUUID(10),
            'created_at' => Carbon::now(),
            'finish_at' => Carbon::now()->addMinute(),
            'type'      => 'reset',
            'status'    => 0,
        ]);

        \App\Classes\SMS::sendSMS($user->phone, " رمز إعادة تعيين كلمة المرور " . $otp->otp);

        return response()->json([
            'message' => 'Reset code sent to your phone.',
            'otp_ref' => $otp->reference,
        ], 201);
    }

    protected function resetOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type = $request->input('type');
        $phone = $request->input('phone');

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json(['message' => 'No account found for this phone number.', 'error' => 'user_not_found'], 404);
        }

        if ($user->enabled == 0) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($type != 'reset') {
            return response()->json(['message' => 'Invalid type.', 'error' => 'invalid_type'], 400);
        }

        $otp = UserOTP::create([
            'user_id'   => $user->id,
            'otp'       => random_int(1000, 9999),
            'reference' => self::strUUID(10),
            'created_at' => Carbon::now(),
            'finish_at' => Carbon::now()->addMinute(),
            'type'      => 'reset',
            'status'    => 0,
        ]);

        \App\Classes\SMS::sendSMS($user->phone, " رمز تسجيل الدخول " . $otp->otp);

        return response()->json([
            'message' => 'User registered successfully',
            'otp_ref' => $otp->reference,
        ], 201);
    }

    protected function resendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required',
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $reference = $request->input('reference');

        $old_otp = UserOTP::where('reference', $reference)->first();
        UserOTP::where('reference', $reference)->update(['status'=>1]);
        $otp = UserOTP::create([
            'user_id'   =>  $old_otp->user_id,
            'otp'   =>  random_int(1000, 9999),
            'reference' =>  self::strUUID(10),
            'created_at'    =>  Carbon::now(),
            'finish_at' =>  Carbon::now()->addMinute(),
            'type'=>$old_otp->type,
            'status'=>0,
        ]);

        \App\Classes\SMS::sendSMS(User::where('id',$old_otp->user_id)->withTrashed()->first()->phone, " رمز تسجيل الدخول ".$otp->otp);

        return response()->json([
            'message' => 'User registered successfully',
            'otp_ref' => $otp->reference
        ], 201);

        return response()->json(['message' => $data], 200);
    }

    protected function createOTP($user_id){

        $data = UserOTP::create([
            'user_id'   =>  $user_id,
            'otp'   => 1234,// random_int(1000, 9999),
            'reference' =>  self::strUUID(10),
            'created_at'    =>  Carbon::now(),
            'finish_at' =>  Carbon::now()->addMinute(),
        ]);

        return $data;
//        return $data->reference;

    }

    public static function strUUID($entropy)
    {
        $s = uniqid("", $entropy);
        $num = hexdec(str_replace(".", "", (string) $s));
        $index = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($index);
        $out = '';
        for ($t = floor(log10($num) / log10($base)); $t >= 0; $t--) {
            $a = floor($num / pow($base, $t));
            $out = $out . substr($index, $a, 1);
            $num = $num - ($a * pow($base, $t));
        }
        return $out;
    }


    public function resetPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'otp' => 'required',
            'password' => 'min:6|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'min:6'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $otp = UserOTP::where('otp', $request->input('otp'))->first();

        if (!isset($otp)){
            return response()->json(['message' => 'not found'], 401);
        }

        if ($otp->status == 1){
            return response()->json(['message' => 'not working'], 401);
        }

        if ($otp->type != 'reset'){
            return response()->json(['message' => 'error'], 401);
        }

        error_log('enter reset pass');
        error_log($otp->user_id);
        error_log($request->input('password'));
        error_log($request->input('password_confirmation'));
        $user = User::find($otp->user_id);
        $user->password = Hash::make($request->input('password'));
        $user->save();

        $otp->status = 1;
        $otp->save();


        return response()->json(['message' => 'success'], 200);


    }

    /**
     * Change password for authenticated user (current password required).
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ], [
            'old_password.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.min' => 'New password must be at least 6 characters.',
            'password.confirmed' => 'New password and confirmation do not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.', 'error' => 'invalid_old_password'], 400);
        }

        $user->password = $request->input('password'); // hashed via User model cast
        $user->save();

        LogController::Auditlog('change_password', 'User', $user->id, null, null, 'user changed password', $request);

        return response()->json(['message' => 'Password changed successfully.'], 200);
    }

    public function update (Request $request) {
//        $validator = Validator::make($request->all(), [
//            'name' => 'required',
//            'email' => 'required',
//            'phone' => 'required',
//            'expire_contract' => 'required',
//        ])->validate();

        $post_data = User::find(Auth::id());
        $data = array(
//            'name' => $request->input('name'),
//            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'phone' => $request->input('phone'),
//            'enabled' => $request->input('enabled'),
//            'reference' => $request->input('reference'),
//            'profit' => $request->input('profit'),
//            'total_profit' => $request->input('total_profit'),
//            'min_ratio' => (float)$request->input('min_ratio'),
//            'max_ratio' => (float)$request->input('max_ratio'),
//            'currency' => $request->input('currency'),
//            'expire_contract' => $request->input('expire_contract'),
            'city' => $request->input('city'),
//            'insurance' => $request->input('insurance'),
            'wdr_method' => $request->input('wdr_method'),
            'wdr_phone' => $request->input('wdr_phone'),
            'wdr_name' => $request->input('wdr_name'),
            'wdr_passport' => $request->input('wdr_passport'),
            'wdr_bank_account' => $request->input('wdr_bank_account'),
            'wdr_swift' => $request->input('wdr_swift'),
            'wdr_card_no' => $request->input('wdr_card_no'),
        );




        $user = User::find(Auth::id());
        $user->update($data);

        LogController::Auditlog( 'update', 'User', Auth::id(), $user, $post_data, 'update user', $request);

        return response()->json(['message' => 'success'], 200);
    }
}
