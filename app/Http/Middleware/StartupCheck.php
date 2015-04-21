<?php namespace app\Http\Middleware;

use Closure;
use Utils;
use App;
use Auth;
use Input;
use Redirect;
use Cache;
use Session;
use Event;
use App\Models\Language;
use App\Events\UserSettingsChanged;

class StartupCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Ensure all request are over HTTPS in production
        if (App::environment() == ENV_PRODUCTION) {
            if (!Request::secure()) {
                return Redirect::secure(Request::getRequestUri());
            }
        }

        // If the database doens't yet exist we'll skip the rest
        if (!Utils::isNinja() && !Utils::isDatabaseSetup()) {
            return $next($request);
        }

        // Check data has been cached
        $cachedTables = [
            'currencies' => 'App\Models\Currency',
            'sizes' => 'App\Models\Size',
            'industries' => 'App\Models\Industry',
            'timezones' => 'App\Models\Timezone',
            'dateFormats' => 'App\Models\DateFormat',
            'datetimeFormats' => 'App\Models\DatetimeFormat',
            'languages' => 'App\Models\Language',
            'paymentTerms' => 'App\Models\PaymentTerm',
            'paymentTypes' => 'App\Models\PaymentType',
        ];
        foreach ($cachedTables as $name => $class) {
            if (!Cache::has($name)) {
                if ($name == 'paymentTerms') {
                    $orderBy = 'num_days';
                } elseif (in_array($name, ['currencies', 'sizes', 'industries', 'languages'])) {
                    $orderBy = 'name';
                } else {
                    $orderBy = 'id';
                }
                Cache::forever($name, $class::orderBy($orderBy)->get());
            }
        }

        // check the application is up to date and for any news feed messages
        if (Auth::check()) {
            $count = Session::get(SESSION_COUNTER, 0);
            Session::put(SESSION_COUNTER, ++$count);

            if (!Utils::startsWith($_SERVER['REQUEST_URI'], '/news_feed') && !Session::has('news_feed_id')) {
                $data = false;
                if (Utils::isNinja()) {
                    $data = Utils::getNewsFeedResponse();
                } else {
                    $file = @file_get_contents(NINJA_APP_URL.'/news_feed/'.Utils::getUserType().'/'.NINJA_VERSION);
                    $data = @json_decode($file);
                }
                if ($data) {
                    if ($data->version != NINJA_VERSION) {
                        $params = [
                    'user_version' => NINJA_VERSION,
                    'latest_version' => $data->version,
                    'releases_link' => link_to(RELEASES_URL, 'Invoice Ninja', ['target' => '_blank']),
                  ];
                        Session::put('news_feed_id', NEW_VERSION_AVAILABLE);
                        Session::put('news_feed_message', trans('texts.new_version_available', $params));
                    } else {
                        Session::put('news_feed_id', $data->id);
                        if ($data->message && $data->id > Auth::user()->news_feed_id) {
                            Session::put('news_feed_message', $data->message);
                        }
                    }
                } else {
                    Session::put('news_feed_id', true);
                }
            }
        }

        // Check if we're requesting to change the account's language
        if (Input::has('lang')) {
            $locale = Input::get('lang');
            App::setLocale($locale);
            Session::set(SESSION_LOCALE, $locale);

            if (Auth::check()) {
                if ($language = Language::whereLocale($locale)->first()) {
                    $account = Auth::user()->account;
                    $account->language_id = $language->id;
                    $account->save();
                }
            }
        } elseif (Auth::check()) {
            $locale = Session::get(SESSION_LOCALE, DEFAULT_LOCALE);
            App::setLocale($locale);
        }

        // Make sure the account/user localization settings are in the session
        if (Auth::check() && !Session::has(SESSION_TIMEZONE)) {
            Event::fire(new UserSettingsChanged());
        }

        // Check if the user is claiming a license (ie, additional invoices, white label, etc.)
        $claimingLicense = Utils::startsWith($_SERVER['REQUEST_URI'], '/claim_license');
        if (!$claimingLicense && Input::has('license_key') && Input::has('product_id')) {
            $licenseKey = Input::get('license_key');
            $productId = Input::get('product_id');

            $data = trim(file_get_contents((Utils::isNinjaDev() ? 'http://ninja.dev' : NINJA_APP_URL)."/claim_license?license_key={$licenseKey}&product_id={$productId}"));

            if ($productId == PRODUCT_INVOICE_DESIGNS) {
                if ($data = json_decode($data)) {
                    foreach ($data as $item) {
                        $design = new InvoiceDesign();
                        $design->id = $item->id;
                        $design->name = $item->name;
                        $design->javascript = $item->javascript;
                        $design->save();
                    }

                    if (!Utils::isNinjaProd()) {
                        Cache::forget('invoice_designs_cache_'.Auth::user()->maxInvoiceDesignId());
                    }

                    Session::flash('message', trans('texts.bought_designs'));
                }
            } elseif ($productId == PRODUCT_WHITE_LABEL) {
                if ($data == 'valid') {
                    $account = Auth::user()->account;
                    $account->pro_plan_paid = NINJA_DATE;
                    $account->save();

                    Session::flash('message', trans('texts.bought_white_label'));
                }
            }
        }

        return $next($request);
    }
}
