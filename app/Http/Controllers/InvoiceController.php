<?php namespace App\Http\Controllers;

use Auth;
use Session;
use Utils;
use View;
use Input;
use Cache;
use Redirect;
use DB;
use Event;
use URL;
use Datatable;
use finfo;
use Request;
use App\Models\Invoice;
use App\Models\Invitation;
use App\Models\Client;
use App\Models\Account;
use App\Models\Product;
use App\Models\Country;
use App\Models\TaxRate;
use App\Models\Currency;
use App\Models\Size;
use App\Models\Industry;
use App\Models\PaymentTerm;
use App\Models\InvoiceDesign;
use App\Models\AccountGateway;
use App\Models\Activity;
use App\Ninja\Mailers\ContactMailer as Mailer;
use App\Ninja\Repositories\InvoiceRepository;
use App\Ninja\Repositories\ClientRepository;
use App\Ninja\Repositories\TaxRateRepository;
use App\Events\InvoiceViewed;

class InvoiceController extends BaseController
{
    protected $mailer;
    protected $invoiceRepo;
    protected $clientRepo;
    protected $taxRateRepo;

    public function __construct(Mailer $mailer, InvoiceRepository $invoiceRepo, ClientRepository $clientRepo, TaxRateRepository $taxRateRepo)
    {
        parent::__construct();

        $this->mailer = $mailer;
        $this->invoiceRepo = $invoiceRepo;
        $this->clientRepo = $clientRepo;
        $this->taxRateRepo = $taxRateRepo;
    }

    public function index()
    {
        $data = [
            'title' => trans('texts.invoices'),
            'entityType' => ENTITY_INVOICE,
            'columns' => Utils::trans(['checkbox', 'invoice_number', 'client', 'invoice_date', 'invoice_total', 'balance_due', 'due_date', 'status', 'action']),
        ];

        $recurringInvoices = Invoice::scope()->where('is_recurring', '=', true);

        if (Session::get('show_trash:invoice')) {
            $recurringInvoices->withTrashed();
        }

        if ($recurringInvoices->count() > 0) {
            $data['secEntityType'] = ENTITY_RECURRING_INVOICE;
            $data['secColumns'] = Utils::trans(['checkbox', 'frequency', 'client', 'start_date', 'end_date', 'invoice_total', 'action']);
        }

        return View::make('list', $data);
    }

    public function clientIndex()
    {
        $invitationKey = Session::get('invitation_key');
        if (!$invitationKey) {
            return Redirect::to('/setup');
        }

        $invitation = Invitation::with('account')->where('invitation_key', '=', $invitationKey)->first();
        $color = $invitation->account->primary_color ? $invitation->account->primary_color : '#0b4d78';
        
        $data = [
            'color' => $color,
            'hideLogo' => Session::get('white_label'),
            'title' => trans('texts.invoices'),
            'entityType' => ENTITY_INVOICE,
            'columns' => Utils::trans(['invoice_number', 'invoice_date', 'invoice_total', 'balance_due', 'due_date']),
        ];

        return View::make('public_list', $data);
    }

    public function getDatatable($clientPublicId = null)
    {
        $accountId = Auth::user()->account_id;
        $search = Input::get('sSearch');

        return $this->invoiceRepo->getDatatable($accountId, $clientPublicId, ENTITY_INVOICE, $search);
    }

    public function getClientDatatable()
    {
        //$accountId = Auth::user()->account_id;
        $search = Input::get('sSearch');
        $invitationKey = Session::get('invitation_key');
        $invitation = Invitation::where('invitation_key', '=', $invitationKey)->first();

        if (!$invitation || $invitation->is_deleted) {
            return [];
        }

        $invoice = $invitation->invoice;

        if (!$invoice || $invoice->is_deleted) {
            return [];
        }

        return $this->invoiceRepo->getClientDatatable($invitation->contact_id, ENTITY_INVOICE, $search);
    }

    public function getRecurringDatatable($clientPublicId = null)
    {
        $query = $this->invoiceRepo->getRecurringInvoices(Auth::user()->account_id, $clientPublicId, Input::get('sSearch'));
        $table = Datatable::query($query);

        if (!$clientPublicId) {
            $table->addColumn('checkbox', function ($model) { return '<input type="checkbox" name="ids[]" value="'.$model->public_id.'" '.Utils::getEntityRowClass($model).'>'; });
        }

        $table->addColumn('frequency', function ($model) { return link_to('invoices/'.$model->public_id, $model->frequency); });

        if (!$clientPublicId) {
            $table->addColumn('client_name', function ($model) { return link_to('clients/'.$model->client_public_id, Utils::getClientDisplayName($model)); });
        }

        return $table->addColumn('start_date', function ($model) { return Utils::fromSqlDate($model->start_date); })
            ->addColumn('end_date', function ($model) { return Utils::fromSqlDate($model->end_date); })
            ->addColumn('amount', function ($model) { return Utils::formatMoney($model->amount, $model->currency_id); })
            ->addColumn('dropdown', function ($model) {
            if ($model->is_deleted) {
                return '<div style="height:38px"/>';
            }

            $str = '<div class="btn-group tr-action" style="visibility:hidden;">
                        <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
                            '.trans('texts.select').' <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" role="menu">';

            if (!$model->deleted_at || $model->deleted_at == '0000-00-00') {
                $str .= '<li><a href="'.URL::to('invoices/'.$model->public_id.'/edit').'">'.trans('texts.edit_invoice').'</a></li>
										    <li class="divider"></li>
										    <li><a href="javascript:archiveEntity('.$model->public_id.')">'.trans('texts.archive_invoice').'</a></li>
										    <li><a href="javascript:deleteEntity('.$model->public_id.')">'.trans('texts.delete_invoice').'</a></li>';
            } else {
                $str .= '<li><a href="javascript:restoreEntity('.$model->public_id.')">'.trans('texts.restore_invoice').'</a></li>';
            }

            return $str.'</ul>
                    </div>';

            })
            ->make();
    }

    public function view($invitationKey)
    {
        $invitation = Invitation::where('invitation_key', '=', $invitationKey)->firstOrFail();
        $invoice = $invitation->invoice;

        if (!$invoice || $invoice->is_deleted) {
            return View::make('invoices.deleted');
        }

        $invoice->load('user', 'invoice_items', 'invoice_design', 'account.country', 'client.contacts', 'client.country');
        $client = $invoice->client;
        $account = $client->account;

        if (!$client || $client->is_deleted) {
            return View::make('invoices.deleted');
        }

        if ($account->subdomain) {
            $server = explode('.', Request::server('HTTP_HOST'));
            $subdomain = $server[0];

            if (!in_array($subdomain, ['app', 'www']) && $subdomain != $account->subdomain) {
                return View::make('invoices.deleted');
            }
        }

        if (!Session::has($invitationKey) && (!Auth::check() || Auth::user()->account_id != $invoice->account_id)) {
            Activity::viewInvoice($invitation);
            Event::fire(new InvoiceViewed($invoice));
        }

        Session::set($invitationKey, true);
        Session::set('invitation_key', $invitationKey);
        Session::set('white_label', $account->isWhiteLabel());

        $account->loadLocalizationSettings();

        $invoice->invoice_date = Utils::fromSqlDate($invoice->invoice_date);
        $invoice->due_date = Utils::fromSqlDate($invoice->due_date);
        $invoice->is_pro = $account->isPro();
        
        $contact = $invitation->contact;
        $contact->setVisible([
            'first_name',
            'last_name',
            'email',
            'phone', ]);

        // Determine payment options
        $paymentTypes = [];
        if ($client->getGatewayToken()) {
            $paymentTypes[] = [
                'url' => URL::to("payment/{$invitation->invitation_key}/".PAYMENT_TYPE_TOKEN), 'label' => trans('texts.use_card_on_file')
            ];
        }
        foreach([PAYMENT_TYPE_CREDIT_CARD, PAYMENT_TYPE_PAYPAL, PAYMENT_TYPE_BITCOIN] as $type) {
            if ($account->getGatewayByType($type)) {
                $paymentTypes[] = [
                    'url' => URL::to("/payment/{$invitation->invitation_key}/{$type}"), 'label' => trans('texts.'.strtolower($type))
                ];
            }
        }
        
        $data = array(
            'isConverted' => $invoice->quote_invoice_id ? true : false,
            'showBreadcrumbs' => false,
            'hideLogo' => $account->isWhiteLabel(),
            'invoice' => $invoice->hidePrivateFields(),
            'invitation' => $invitation,
            'invoiceLabels' => $account->getInvoiceLabels(),
            'contact' => $contact,
            'paymentTypes' => $paymentTypes
        );

        return View::make('invoices.view', $data);
    }

    public function edit($publicId, $clone = false)
    {
        $invoice = Invoice::scope($publicId)->withTrashed()->with('invitations', 'account.country', 'client.contacts', 'client.country', 'invoice_items')->firstOrFail();
        $entityType = $invoice->getEntityType();

        $contactIds = DB::table('invitations')
            ->join('contacts', 'contacts.id', '=', 'invitations.contact_id')
            ->where('invitations.invoice_id', '=', $invoice->id)
            ->where('invitations.account_id', '=', Auth::user()->account_id)
            ->where('invitations.deleted_at', '=', null)
            ->select('contacts.public_id')->lists('public_id');

        if ($clone) {
            $invoice->id = null;
            $invoice->invoice_number = Auth::user()->account->getNextInvoiceNumber($invoice->is_quote);
            $invoice->balance = $invoice->amount;
            $invoice->invoice_status_id = 0;
            $invoice->invoice_date = date_create()->format('Y-m-d');
            $method = 'POST';
            $url = "{$entityType}s";
        } else {
            Utils::trackViewed($invoice->invoice_number.' - '.$invoice->client->getDisplayName(), $invoice->getEntityType());
            $method = 'PUT';
            $url = "{$entityType}s/{$publicId}";
        }

        $invoice->invoice_date = Utils::fromSqlDate($invoice->invoice_date);
        $invoice->due_date = Utils::fromSqlDate($invoice->due_date);
        $invoice->start_date = Utils::fromSqlDate($invoice->start_date);
        $invoice->end_date = Utils::fromSqlDate($invoice->end_date);
        $invoice->is_pro = Auth::user()->isPro();

        $data = array(
                'entityType' => $entityType,
                'showBreadcrumbs' => $clone,
                'account' => $invoice->account,
                'invoice' => $invoice,
                'data' => false,
                'method' => $method,
                'invitationContactIds' => $contactIds,
                'url' => $url,
                'title' => trans("texts.edit_{$entityType}"),
                'client' => $invoice->client, );
        $data = array_merge($data, self::getViewModel());

        // Set the invitation link on the client's contacts
        if (!$clone) {
            $clients = $data['clients'];
            foreach ($clients as $client) {
                if ($client->id == $invoice->client->id) {
                    foreach ($invoice->invitations as $invitation) {
                        foreach ($client->contacts as $contact) {
                            if ($invitation->contact_id == $contact->id) {
                                $contact->invitation_link = $invitation->getLink();
                            }
                        }
                    }
                    break;
                }
            }
        }

        return View::make('invoices.edit', $data);
    }

    public function create($clientPublicId = 0)
    {
        $client = null;
        $invoiceNumber = Auth::user()->account->getNextInvoiceNumber();
        $account = Account::with('country')->findOrFail(Auth::user()->account_id);

        if ($clientPublicId) {
            $client = Client::scope($clientPublicId)->firstOrFail();
        }

        $data = array(
                'entityType' => ENTITY_INVOICE,
                'account' => $account,
                'invoice' => null,
                'data' => Input::old('data'),
                'invoiceNumber' => $invoiceNumber,
                'method' => 'POST',
                'url' => 'invoices',
                'title' => trans('texts.new_invoice'),
                'client' => $client, );
        $data = array_merge($data, self::getViewModel());

        return View::make('invoices.edit', $data);
    }

    private static function getViewModel()
    {
        $recurringHelp = '';
        foreach (preg_split("/((\r?\n)|(\r\n?))/", trans('texts.recurring_help')) as $line) {
            $parts = explode("=>", $line);
            if (count($parts) > 1) {
                $line = $parts[0].' => '.Utils::processVariables($parts[0]);
                $recurringHelp .= '<li>'.strip_tags($line).'</li>';
            } else {
                $recurringHelp .= $line;
            }
        }

        return [
            'account' => Auth::user()->account,
            'products' => Product::scope()->orderBy('id')->get(array('product_key', 'notes', 'cost', 'qty')),
            'countries' => Cache::get('countries'),
            'clients' => Client::scope()->with('contacts', 'country')->orderBy('name')->get(),
            'taxRates' => TaxRate::scope()->orderBy('name')->get(),
            'currencies' => Cache::get('currencies'),
            'sizes' => Cache::get('sizes'),
            'paymentTerms' => Cache::get('paymentTerms'),
            'industries' => Cache::get('industries'),
            'invoiceDesigns' => InvoiceDesign::availableDesigns(),
            'frequencies' => array(
                1 => 'Weekly',
                2 => 'Two weeks',
                3 => 'Four weeks',
                4 => 'Monthly',
                5 => 'Three months',
                6 => 'Six months',
                7 => 'Annually',
            ),
            'recurringHelp' => $recurringHelp
        ];

    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        return InvoiceController::save();
    }

    private function save($publicId = null)
    {
        $action = Input::get('action');
        $entityType = Input::get('entityType');

        if (in_array($action, ['archive', 'delete', 'mark', 'restore'])) {
            return InvoiceController::bulk($entityType);
        }

        $input = json_decode(Input::get('data'));
        $invoice = $input->invoice;

        if ($errors = $this->invoiceRepo->getErrors($invoice)) {
            Session::flash('error', trans('texts.invoice_error'));

            return Redirect::to("{$entityType}s/create")
                ->withInput()->withErrors($errors);
        } else {
            $this->taxRateRepo->save($input->tax_rates);

            $clientData = (array) $invoice->client;
            $client = $this->clientRepo->save($invoice->client->public_id, $clientData);

            $invoiceData = (array) $invoice;
            $invoiceData['client_id'] = $client->id;
            $invoice = $this->invoiceRepo->save($publicId, $invoiceData, $entityType);

            $account = Auth::user()->account;
            if ($account->invoice_taxes != $input->invoice_taxes
                        || $account->invoice_item_taxes != $input->invoice_item_taxes
                        || $account->invoice_design_id != $input->invoice->invoice_design_id) {
                $account->invoice_taxes = $input->invoice_taxes;
                $account->invoice_item_taxes = $input->invoice_item_taxes;
                $account->invoice_design_id = $input->invoice->invoice_design_id;
                $account->save();
            }

            $client->load('contacts');
            $sendInvoiceIds = [];

            foreach ($client->contacts as $contact) {
                if ($contact->send_invoice || count($client->contacts) == 1) {
                    $sendInvoiceIds[] = $contact->id;
                }
            }

            foreach ($client->contacts as $contact) {
                $invitation = Invitation::scope()->whereContactId($contact->id)->whereInvoiceId($invoice->id)->first();

                if (in_array($contact->id, $sendInvoiceIds) && !$invitation) {
                    $invitation = Invitation::createNew();
                    $invitation->invoice_id = $invoice->id;
                    $invitation->contact_id = $contact->id;
                    $invitation->invitation_key = str_random(RANDOM_KEY_LENGTH);
                    $invitation->save();
                } elseif (!in_array($contact->id, $sendInvoiceIds) && $invitation) {
                    $invitation->delete();
                }
            }

            $message = trans($publicId ? "texts.updated_{$entityType}" : "texts.created_{$entityType}");
            if ($input->invoice->client->public_id == '-1') {
                $message = $message.' '.trans('texts.and_created_client');

                $url = URL::to('clients/'.$client->public_id);
                Utils::trackViewed($client->getDisplayName(), ENTITY_CLIENT, $url);
            }

            $pdfUpload = Input::get('pdfupload');
            if (!empty($pdfUpload) && strpos($pdfUpload, 'data:application/pdf;base64,') === 0) {
                $this->storePDF(Input::get('pdfupload'), $invoice);
            }

            if ($action == 'clone') {
                return $this->cloneInvoice($publicId);
            } elseif ($action == 'convert') {
                return $this->convertQuote($publicId);
            } elseif ($action == 'email') {
                if (Auth::user()->confirmed && !Auth::user()->isDemo()) {
                    $message = trans("texts.emailed_{$entityType}");
                    $this->mailer->sendInvoice($invoice);
                    Session::flash('message', $message);
                } else {
                    $errorMessage = trans(Auth::user()->registered ? 'texts.confirmation_required' : 'texts.registration_required');
                    Session::flash('error', $errorMessage);
                    Session::flash('message', $message);
                }
            } else {
                Session::flash('message', $message);
            }

            $url = "{$entityType}s/".$invoice->public_id.'/edit';

            return Redirect::to($url);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int      $id
     * @return Response
     */
    public function show($publicId)
    {
        Session::reflash();

        return Redirect::to('invoices/'.$publicId.'/edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int      $id
     * @return Response
     */
    public function update($publicId)
    {
        return InvoiceController::save($publicId);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int      $id
     * @return Response
     */
    public function bulk($entityType = ENTITY_INVOICE)
    {
        $action = Input::get('action');
        $statusId = Input::get('statusId', INVOICE_STATUS_SENT);
        $ids = Input::get('id') ? Input::get('id') : Input::get('ids');
        $count = $this->invoiceRepo->bulk($ids, $action, $statusId);

        if ($count > 0) {
            $key = $action == 'mark' ? "updated_{$entityType}" : "{$action}d_{$entityType}";
            $message = Utils::pluralize($key, $count);
            Session::flash('message', $message);
        }

        if ($action == 'restore' && $count == 1) {
            return Redirect::to("{$entityType}s/".$ids[0]);
        } else {
            return Redirect::to("{$entityType}s");
        }
    }

    public function convertQuote($publicId)
    {
        $invoice = Invoice::with('invoice_items')->scope($publicId)->firstOrFail();
        $clone = $this->invoiceRepo->cloneInvoice($invoice, $invoice->id);

        Session::flash('message', trans('texts.converted_to_invoice'));
        return Redirect::to('invoices/'.$clone->public_id);
    }

    public function cloneInvoice($publicId)
    {
        /*
        $invoice = Invoice::with('invoice_items')->scope($publicId)->firstOrFail();
        $clone = $this->invoiceRepo->cloneInvoice($invoice);
        $entityType = $invoice->getEntityType();

        Session::flash('message', trans('texts.cloned_invoice'));
        return Redirect::to("{$entityType}s/" . $clone->public_id);
        */

        return self::edit($publicId, true);
    }

    public function invoiceHistory($publicId)
    {
        $invoice = Invoice::withTrashed()->scope($publicId)->firstOrFail();
        $invoice->load('user', 'invoice_items', 'account.country', 'client.contacts', 'client.country');
        $invoice->invoice_date = Utils::fromSqlDate($invoice->invoice_date);
        $invoice->due_date = Utils::fromSqlDate($invoice->due_date);
        $invoice->is_pro = Auth::user()->isPro();
        $invoice->is_quote = intval($invoice->is_quote);

        $activityTypeId = $invoice->is_quote ? ACTIVITY_TYPE_UPDATE_QUOTE : ACTIVITY_TYPE_UPDATE_INVOICE;
        $activities = Activity::scope(false, $invoice->account_id)
                        ->where('activity_type_id', '=', $activityTypeId)
                        ->where('invoice_id', '=', $invoice->id)
                        ->orderBy('id', 'desc')
                        ->get(['id', 'created_at', 'user_id', 'json_backup', 'message']);

        $versionsJson = [];
        $versionsSelect = [];
        $lastId = false;

        foreach ($activities as $activity) {
            $backup = json_decode($activity->json_backup);
            $backup->invoice_date = Utils::fromSqlDate($backup->invoice_date);
            $backup->due_date = Utils::fromSqlDate($backup->due_date);
            $backup->is_pro = Auth::user()->isPro();
            $backup->is_quote = isset($backup->is_quote) && intval($backup->is_quote);
            $backup->account = $invoice->account->toArray();

            $versionsJson[$activity->id] = $backup;
            $key = Utils::timestampToDateTimeString(strtotime($activity->created_at)) . ' - ' . Utils::decodeActivity($activity->message);
            $versionsSelect[$lastId ? $lastId : 0] = $key;
            $lastId = $activity->id;
        }

        $versionsSelect[$lastId] = Utils::timestampToDateTimeString(strtotime($invoice->created_at)) . ' - ' . $invoice->user->getDisplayName();

        $data = [
            'invoice' => $invoice,
            'versionsJson' => json_encode($versionsJson),
            'versionsSelect' => $versionsSelect,
            'invoiceDesigns' => InvoiceDesign::availableDesigns(),
        ];

        return View::make('invoices.history', $data);
    }
    
    private function storePDF($encodedString, $invoice)
    {
        $encodedString = str_replace('data:application/pdf;base64,', '', $encodedString);
        file_put_contents($invoice->getPDFPath(), base64_decode($encodedString));
    }
}
