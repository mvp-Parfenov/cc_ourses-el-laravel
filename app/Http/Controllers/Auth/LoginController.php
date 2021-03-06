<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Entity\User;
use App\Services\Sms\SmsSender;
use Auth;
use Dotenv\Exception\ValidationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use function random_int;
use function redirect;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LoginController extends Controller
{

    use ThrottlesLogins;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';
    /**
     * @var SmsSender
     */
    private $sms;

    public function __construct(SmsSender $sms)
    {
        $this->middleware('guest')->except('logout');

        $this->sms = $sms;
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    // юзер аунтифицировался
//    protected function authenticated(Request $request, $user)
//    {
//        if($user->status !== User::STATUS_ACTIVE){
//            $this->guard()->logout();
//            return (
//                back()->with('error', 'You need to confirm your account. Please check your email.')
//            );
//        }
//
//        return redirect()->intended($this->redirectPath());
//    }

    public function login(LoginRequest $request)
    {
        if($this->hasTooManyLoginAttempts($request)){
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        $authenticate = Auth::attempt(
            $request->only(['email', 'password']),
            $request->filled('remember')
        );

        if($authenticate) {
            $request->session()->regenerate();
            $this->clearLoginAttempts($request);
            $user = Auth::user();

            if($user->status !== User::STATUS_ACTIVE){
                Auth::logout();
                return
                back()->with(
                    'error', 'You need to confirm your account. Please check your email.'
                );
            }

            if($user->isPhoneAuthEnabled()){
                Auth::logout();

                $token = (string)random_int(10000, 99999);
                $request->session()->put('auth', [
                    'id' => $user->id,
                    'token' => $token,
                    'remember' => $request->filled('remember'),
                ]);

                $this->sms->send($user->phone, 'Login code:' . $token);
                return redirect()->route('login.phone');
            }

            return redirect()->intended(route('cabinet.home'));
        }

        $this->incrementLoginAttempts($request);
        throw ValidationException::withMessages(['email' => [trans('auth.failed')]]);
    }

    public function logout(Request $request)
    {
        Auth::guard()->logout();
        $request->session()->invalidate();
        return redirect()->route('home');
    }

    protected function username()
    {
        return 'email';
    }

    public function phone()
    {
        return view('auth.phone');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function verify(Request $request)
    {
        if($this->hasTooManyLoginAttempts($request))
        {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        $this->validate($request, [
            'token' => 'required|string'
        ]);

        if(!$session = $request->session()->get('auth')){
            throw new BadRequestHttpException('Missing token info.');
        }

        /** @var User $user*/
        $user = User::findOrFail($session['id']);

        if($request['token'] === $session['token']){
            $request->session()->flush();
            $this->clearLoginAttempts($request);
            Auth::login($user, $session['remember']);
            return redirect()->intended(route('cabinet.home'));
        }

        $this->incrementLoginAttempts($request);

        throw \Illuminate\Validation\ValidationException::withMessages(['token' => ['Invalid auth token.']]);
    }

}
