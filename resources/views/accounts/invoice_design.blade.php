@extends('accounts.nav')

@section('head')
	@parent

		<script src="{!! asset('js/pdf_viewer.js') !!}" type="text/javascript"></script>
		<script src="{!! asset('js/compatibility.js') !!}" type="text/javascript"></script>

        @if (Auth::user()->account->utf8_invoices)
            <script src="{{ asset('vendor/pdfmake/build/pdfmake.min.js') }}" type="text/javascript"></script>
            <script src="{{ asset('js/vfs_fonts.js') }}" type="text/javascript"></script>
        @endif

@stop

@section('content')	
	@parent
	@include('accounts.nav_advanced')

  <script>
    var invoiceDesigns = {!! $invoiceDesigns !!};
    var invoice = {!! json_encode($invoice) !!};      
      
    function getDesignJavascript() {
      var id = $('#invoice_design_id').val();
      if (id == '-1') {
        showMoreDesigns(); 
        $('#invoice_design_id').val(1);
        return invoiceDesigns[0].javascript;        
      } else {
        return invoiceDesigns[id-1].javascript;
      }
    }

    function getPDFString(cb) {
      invoice.is_pro = {!! Auth::user()->isPro() ? 'true' : 'false' !!};
      invoice.account.hide_quantity = $('#hide_quantity').is(":checked");
      invoice.account.hide_paid_to_date = $('#hide_paid_to_date').is(":checked");
      invoice.invoice_design_id = $('#invoice_design_id').val();

      NINJA.primaryColor = $('#primary_color').val();
      NINJA.secondaryColor = $('#secondary_color').val();

      doc = generatePDF(invoice, getDesignJavascript(), true);
      doc.getDataUrl(cb);
    }

    $(function() {   
      var options = {
        preferredFormat: 'hex',
        disabled: {!! Auth::user()->isPro() ? 'false' : 'true' !!},
        showInitial: false,
        showInput: true,
        allowEmpty: true,
        clickoutFiresChange: true,
      };

      $('#primary_color').spectrum(options);
      $('#secondary_color').spectrum(options);

      refreshPDF();
    });

  </script> 


  <div class="row">
    <div class="col-md-6">

      {!! Former::open()->addClass('warn-on-exit')->onchange('refreshPDF()') !!}
      {!! Former::populate($account) !!}
      {!! Former::populateField('hide_quantity', intval($account->hide_quantity)) !!}
      {!! Former::populateField('hide_paid_to_date', intval($account->hide_paid_to_date)) !!}

    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">{!! trans('texts.invoice_design') !!}</h3>
      </div>
        <div class="panel-body">    


          @if (!Utils::isPro() || \App\Models\InvoiceDesign::count() == COUNT_FREE_DESIGNS)      
            {!! Former::select('invoice_design_id')->style('display:inline;width:120px')->fromQuery($invoiceDesigns, 'name', 'id')->addOption(trans('texts.more_designs') . '...', '-1') !!}        
          @else 
            {!! Former::select('invoice_design_id')->style('display:inline;width:120px')->fromQuery($invoiceDesigns, 'name', 'id') !!}
          @endif

          

          {!! Former::text('primary_color') !!}
          {!! Former::text('secondary_color') !!}
          </div>
      </div>

    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">{!! trans('texts.invoice_options') !!}</h3>
      </div>
        <div class="panel-body">    
    
          {!! Former::checkbox('hide_quantity')->text(trans('texts.hide_quantity_help')) !!}
          {!! Former::checkbox('hide_paid_to_date')->text(trans('texts.hide_paid_to_date_help')) !!}
        </div>
    </div>


      @if (Auth::user()->isPro())
      {!! Former::actions( Button::success(trans('texts.save'))->submit()->large()->appendIcon(Icon::create('floppy-disk'))) !!}
      @else
      <script>
          $(function() {   
            $('form.warn-on-exit input').prop('disabled', true);
          });
      </script> 
      @endif

      {!! Former::close() !!}

    </div>
    <div class="col-md-6">

      @include('invoices.pdf', ['account' => Auth::user()->account, 'pdfHeight' => 800])

    </div>
  </div>

@stop