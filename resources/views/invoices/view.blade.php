@extends('public.header')

@section('head')
	@parent

		@include('script')		
		
		<script src="{{ asset('js/pdf_viewer.js') }}" type="text/javascript"></script>
		<script src="{{ asset('js/compatibility.js') }}" type="text/javascript"></script>

        @if ($invoice->client->account->utf8_invoices)
            <script src="{{ asset('vendor/pdfmake/build/pdfmake.min.js') }}" type="text/javascript"></script>
            <script src="{{ asset('js/vfs_fonts.js') }}" type="text/javascript"></script>
        @endif

		<style type="text/css">
			body {
				background-color: #f8f8f8;		
			}
		</style>
@stop

@section('content')

	<div class="container">

		<p>&nbsp;</p>
        <div class="pull-right" style="text-align:right">
        @if ($invoice->is_quote)            
            {!! Button::normal(trans('texts.download_pdf'))->withAttributes(['onclick' => 'onDownloadClick()'])->large() !!}&nbsp;&nbsp;
            @if (!$isConverted)
                {!! Button::success(trans('texts.approve'))->asLinkTo('/approve/' . $invitation->invitation_key)->large() !!}
            @endif
		@elseif ($invoice->client->account->isGatewayConfigured() && !$invoice->isPaid() && !$invoice->is_recurring)
            {!! Button::normal(trans('texts.download_pdf'))->withAttributes(['onclick' => 'onDownloadClick()'])->large() !!}&nbsp;&nbsp;
            @if (count($paymentTypes) > 1)
                {!! DropdownButton::success(trans('texts.pay_now'))->withContents($paymentTypes)->large() !!}
            @else
                {!! Button::success(trans('texts.pay_now'))->asLinkTo('/payment/' . $invitation->invitation_key)->large() !!}
            @endif            
		@else 
			{!! Button::normal('Download PDF')->withAttributes(['onclick' => 'onDownloadClick()'])->large() !!}
		@endif
		</div>        

		<div class="clearfix"></div><p>&nbsp;</p>

		<script type="text/javascript">

			window.invoice = {!! $invoice->toJson() !!};
			invoice.is_pro = {{ $invoice->client->account->isPro() ? 'true' : 'false' }};
			invoice.is_quote = {{ $invoice->is_quote ? 'true' : 'false' }};
			invoice.contact = {!! $contact->toJson() !!};

			function getPDFString(cb) {
    	  	    doc = generatePDF(invoice, invoice.invoice_design.javascript);
                doc.getDataUrl(cb);
			}

			$(function() {
				refreshPDF();
			});
			
			function onDownloadClick() {
				var doc = generatePDF(invoice, invoice.invoice_design.javascript, true);
                var fileName = invoice.is_quote ? invoiceLabels.quote : invoiceLabels.invoice;
				doc.save(fileName + '-' + invoice.invoice_number + '.pdf');
			}


		</script>

		@include('invoices.pdf', ['account' => $invoice->client->account])

		<p>&nbsp;</p>
		<p>&nbsp;</p>

	</div>	

@stop
