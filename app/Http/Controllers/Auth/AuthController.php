<?php namespace App\Http\Controllers\Auth;

use Auth;
use Event;
use Utils;
use Illuminate\Http\Request;
use App\Models\User;
use App\Events\UserLoggedIn;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Registrar;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

class AuthController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Registration & Login Controller
	|--------------------------------------------------------------------------
	|
	| This controller handles the registration of new users, as well as the
	| authentication of existing users. By default, this controller uses
	| a simple trait to add these behaviors. Why don't you explore it?
	|
	*/

	use AuthenticatesAndRegistersUsers;

    protected $loginPath = '/login';
    protected $redirectTo = '/dashboard';

	/**
	 * Create a new authentication controller instance.
	 *
	 * @param  \Illuminate\Contracts\Auth\Guard  $auth
	 * @param  \Illuminate\Contracts\Auth\Registrar  $registrar
	 * @return void
	 */
	public function __construct(Guard $auth, Registrar $registrar)
	{
		$this->auth = $auth;
		$this->registrar = $registrar;

		$this->middleware('guest', ['except' => 'getLogout']);
	}

    public function getLoginWrapper()
    {
        if (!Utils::isNinja() && !User::count()) {
            return redirect()->to('invoice_now');
        }

        return self::getLogin();
    }

    public function postLoginWrapper(Request $request)
    {
        $response = self::postLogin($request);

        if (Auth::check()) {
            Event::fire(new UserLoggedIn());
        }

        return $response;
    }

}
