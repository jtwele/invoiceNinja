@extends('accounts.nav')

@section('head')
    @parent

    <style type="text/css">
        textarea {
            min-height: 150px !important;
        }
    </style>

@stop

@section('content')
    @parent
    @include('accounts.nav_advanced')

    {!! Former::open()->addClass('col-md-10 col-md-offset-1 warn-on-exit') !!}
    {!! Former::populateField('email_template_invoice', $invoiceEmail) !!}
    {!! Former::populateField('email_template_quote', $quoteEmail) !!}
    {!! Former::populateField('email_template_payment', $paymentEmail) !!}

    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">{!! trans('texts.invoice_email') !!}</h3>
      </div>
        <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                {!! Former::textarea('email_template_invoice')->raw() !!}
            </div>
            <div class="col-md-6" id="invoice_preview"></div>
        </div>
        </div>
    </div>

    
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">{!! trans('texts.quote_email') !!}</h3>
      </div>
        <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                {!! Former::textarea('email_template_quote')->raw() !!}
            </div>
            <div class="col-md-6" id="quote_preview"></div>
        </div>
        </div>
    </div>


    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">{!! trans('texts.payment_email') !!}</h3>
      </div>
        <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                {!! Former::textarea('email_template_payment')->raw() !!}
            </div>
            <div class="col-md-6" id="payment_preview"></div>
        </div>
        </div>
    </div>

    @if (Auth::user()->isPro())
        <center>
            {!! Button::success(trans('texts.save'))->submit()->large()->appendIcon(Icon::create('floppy-disk')) !!}
        </center>
    @else
        <script>
            $(function() {
                $('form.warn-on-exit input').prop('disabled', true);
            });
        </script>
    @endif

    {!! Former::close() !!}

    <script type="text/javascript">

        $(function() {
            $('#email_template_invoice').keyup(refreshInvoice);
            $('#email_template_quote').keyup(refreshQuote);
            $('#email_template_payment').keyup(refreshPayment);

            refreshInvoice();
            refreshQuote();
            refreshPayment();
        });

        function refreshInvoice() {
            $('#invoice_preview').html(processVariables($('#email_template_invoice').val()));
        }

        function refreshQuote() {
            $('#quote_preview').html(processVariables($('#email_template_quote').val()));
        }

        function refreshPayment() {
            $('#payment_preview').html(processVariables($('#email_template_payment').val()));
        }

        function processVariables(str) {
            if (!str) {
                return '';
            }

            keys = ['footer', 'account', 'client', 'amount', 'link'];
            vals = [{!! json_encode($emailFooter) !!}, '{!! Auth::user()->account->getDisplayName() !!}', 'Client Name', formatMoney(100), '{!! NINJA_WEB_URL !!}']

            for (var i=0; i<keys.length; i++) {
                var regExp = new RegExp('\\$'+keys[i], 'g');
                str = str.replace(regExp, vals[i]);
            }

            return str;
        }

    </script>

@stop
