@extends('accounts.nav')

@section('content')	
	@parent
	
	<style type="text/css">

	#logo {
		padding-top: 6px;
	}

	</style>

	{!! Former::open_for_files()->addClass('col-md-10 col-md-offset-1 warn-on-exit')->rules(array(
  		'name' => 'required',
  		'email' => 'email|required'
	)) !!}

	{{ Former::populate($account) }}
	@if ($showUser)
		{{ Former::populateField('first_name', $account->users()->first()->first_name) }}
		{{ Former::populateField('last_name', $account->users()->first()->last_name) }}
		{{ Former::populateField('email', $account->users()->first()->email) }}	
		{{ Former::populateField('phone', $account->users()->first()->phone) }}
	@endif
	
	<div class="row">
		<div class="col-md-5">

			{!! Former::legend('details') !!}
			{!! Former::text('name') !!}
            {!! Former::text('id_number') !!}
            {!! Former::text('vat_number') !!}
			{!! Former::text('work_email') !!}
			{!! Former::text('work_phone') !!}
			{!! Former::file('logo')->max(2, 'MB')->accept('image')->inlineHelp(trans('texts.logo_help')) !!}

			@if (file_exists($account->getLogoPath()))
				<center>
					{!! HTML::image($account->getLogoPath(), "Logo") !!} &nbsp;
					<a href="#" onclick="deleteLogo()">{{ trans('texts.remove_logo') }}</a>
				</center><br/>
			@endif

			{!! Former::select('size_id')->addOption('','')->fromQuery($sizes, 'name', 'id') !!}
			{!! Former::select('industry_id')->addOption('','')->fromQuery($industries, 'name', 'id') !!}

			{!! Former::legend('address') !!}	
			{!! Former::text('address1') !!}
			{!! Former::text('address2') !!}
			{!! Former::text('city') !!}
			{!! Former::text('state') !!}
			{!! Former::text('postal_code') !!}
			{!! Former::select('country_id')->addOption('','')
				->fromQuery($countries, 'name', 'id') !!}

		</div>
	
		<div class="col-md-5 col-md-offset-1">		

			@if ($showUser)
				{!! Former::legend('users') !!}
				{!! Former::text('first_name') !!}
				{!! Former::text('last_name') !!}
                {!! Former::text('email') !!}
				{!! Former::text('phone') !!}
                @if (Auth::user()->confirmed)                
                    {!! Former::actions( Button::primary(trans('texts.change_password'))->small()->withAttributes(['onclick'=>'showChangePassword()'])) !!}
                @elseif (Auth::user()->registered)
                    {!! Former::actions( Button::primary(trans('texts.resend_confirmation'))->asLinkTo('/resend_confirmation')->small() ) !!}
                @endif
			@endif

			{!! Former::legend('localization') !!}
			{!! Former::select('language_id')->addOption('','')
				->fromQuery($languages, 'name', 'id') !!}			
			{!! Former::select('currency_id')->addOption('','')
				->fromQuery($currencies, 'name', 'id') !!}			
			{!! Former::select('timezone_id')->addOption('','')
				->fromQuery($timezones, 'location', 'id') !!}
			{!! Former::select('date_format_id')->addOption('','')
				->fromQuery($dateFormats, 'label', 'id') !!}
			{!! Former::select('datetime_format_id')->addOption('','')
				->fromQuery($datetimeFormats, 'label', 'id') !!}


		</div>
	</div>
	
	<center>
        {!! Button::success(trans('texts.save'))->submit()->large()->appendIcon(Icon::create('floppy-disk')) !!}
	</center>

    {!! Former::close() !!}


    <div class="modal fade" id="passwordModal" tabindex="-1" role="dialog" aria-labelledby="passwordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="passwordModalLabel">{{ trans('texts.change_password') }}</h4>
                </div>

                <div style="background-color: #fff" id="changePasswordDiv" onkeyup="validateChangePassword()" onclick="validateChangePassword()" onkeydown="checkForEnter(event)">
                    &nbsp;

                    {!! Former::password('current_password')->style('width:300px') !!}
                    {!! Former::password('newer_password')->style('width:300px')->label(trans('texts.new_password')) !!}
                    {!! Former::password('confirm_password')->style('width:300px') !!}

                    &nbsp;
                    <br/>
                    <center>
                        <div id="changePasswordError"></div>    
                    </center>                    
                    <br/>
                </div>

                <div style="padding-left:40px;padding-right:40px;display:none;min-height:130px" id="working">
                    <h3>{{ trans('texts.working') }}...</h3>
                    <div class="progress progress-striped active">
                        <div class="progress-bar"  role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
                    </div>
                </div>

                <div style="background-color: #fff; padding-right:20px;padding-left:20px; display:none" id="successDiv">
                    <br/>
                    <h3>{{ trans('texts.success') }}</h3>                    
                    {{ trans('texts.updated_password') }}
                    <br/>
                    &nbsp;
                    <br/>
                </div>

                <div class="modal-footer" style="margin-top: 0px" id="changePasswordFooter">
                    <button type="button" class="btn btn-default" id="cancelChangePasswordButton" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="submitChangePassword()" id="changePasswordButton" disabled>
                        {{ trans('texts.save') }}
                        <i class="glyphicon glyphicon-floppy-disk"></i>
                    </button>           
                </div>

            </div>
        </div>
    </div>


	{!! Form::open(['url' => 'remove_logo', 'class' => 'removeLogoForm']) !!}	
	{!! Form::close() !!}


	<script type="text/javascript">

		$(function() {
			$('#country_id').combobox();

            $('#passwordModal').on('hidden.bs.modal', function () {                
                $(['current_password', 'newer_password', 'confirm_password']).each(function(i, field) {
                    var $input = $('form #'+field);
                    $input.val('');
                    $input.closest('div.form-group').removeClass('has-success');                    
                });
                $('#changePasswordButton').prop('disabled', true);
            })

            $('#passwordModal').on('shown.bs.modal', function () {                
                $('#current_password').focus();
            })

		});
		
		function deleteLogo() {
			if (confirm("{{ trans('texts.are_you_sure') }}")) {
				$('.removeLogoForm').submit();
			}
		}

        function showChangePassword() {
            $('#passwordModal').modal('show');         
        }

        function checkForEnter(event)
        {
            if (event.keyCode === 13){
                event.preventDefault();               
                return false;
            }
        }

        function validateChangePassword(showError) 
        {
            var isFormValid = true;
            $(['current_password', 'newer_password', 'confirm_password']).each(function(i, field) {
                var $input = $('form #'+field),
                val = $.trim($input.val());
                var isValid = val && val.length >= 6;

                if (isValid && field == 'confirm_password') {
                    isValid = val == $.trim($('#newer_password').val());
                }

                if (isValid) {
                    $input.closest('div.form-group').removeClass('has-error').addClass('has-success');
                } else {
                    isFormValid = false;
                    $input.closest('div.form-group').removeClass('has-success');
                    if (showError) {
                        $input.closest('div.form-group').addClass('has-error');
                    }
                }
            });

            $('#changePasswordButton').prop('disabled', !isFormValid);

            return isFormValid;
        }

        function submitChangePassword()
        {
            if (!validateChangePassword(true)) {
                return;
            }

            $('#changePasswordDiv, #changePasswordFooter').hide();
            $('#working').show();

            $.ajax({
              type: 'POST',
              url: '{{ URL::to('/users/change_password') }}',
              data: 'current_password=' + encodeURIComponent($('form #current_password').val()) + 
              '&new_password=' + encodeURIComponent($('form #newer_password').val()) + 
              '&confirm_password=' + encodeURIComponent($('form #confirm_password').val()),
              success: function(result) { 
                if (result == 'success') {
                  $('#changePasswordButton').hide();
                  $('#successDiv').show();
                  $('#cancelChangePasswordButton').html('{{ trans('texts.close') }}');
                } else {
                  $('#changePasswordError').html(result);
                  $('#changePasswordDiv').show();                    
                }
                $('#changePasswordFooter').show();
                $('#working').hide();
              }
            });     
        }

	</script>


@stop