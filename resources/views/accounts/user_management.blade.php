@extends('accounts.nav')

@section('content')
	@parent
	@include('accounts.nav_advanced')

  {!! Former::open('users/delete')->addClass('user-form') !!}
  {!! Former::legend('user_management') !!}

  <div style="display:none">
    {!! Former::text('userPublicId') !!}
  </div>
  {!! Former::close() !!}


  <div class="pull-right">  
    @if (Utils::isPro())    
        {!! Button::success(trans('texts.add_user'))->asLinkTo('/users/create')->appendIcon(Icon::create('plus-sign')) !!}
    @endif
    {!! Button::normal(trans('texts.api_tokens'))->asLinkTo('/company/advanced_settings/token_management')->appendIcon(Icon::create('cloud')) !!}
  </div>


    <label for="trashed" style="font-weight:normal; margin-left: 10px;">
        <input id="trashed" type="checkbox" onclick="setTrashVisible()"
            {!! Session::get('show_trash:user') ? 'checked' : ''!!}/> {!! trans('texts.show_deleted_users')!!}
    </label>


  {!! Datatable::table()
      ->addColumn(
        trans('texts.name'),
        trans('texts.email'),
        trans('texts.user_state'),
        trans('texts.action'))
      ->setUrl(url('api/users/'))
      ->setOptions('sPaginationType', 'bootstrap')
      ->setOptions('bFilter', false)
      ->setOptions('bAutoWidth', false)
      ->setOptions('aoColumns', [[ "sWidth"=> "20%" ], [ "sWidth"=> "45%" ], ["sWidth"=> "20%"], ["sWidth"=> "15%" ]])
      ->setOptions('aoColumnDefs', [['bSortable'=>false, 'aTargets'=>[3]]])
      ->render('datatable') !!}

  <script>
  window.onDatatableReady = function() {
    $('tbody tr').mouseover(function() {
      $(this).closest('tr').find('.tr-action').css('visibility','visible');
    }).mouseout(function() {
      $dropdown = $(this).closest('tr').find('.tr-action');
      if (!$dropdown.hasClass('open')) {
        $dropdown.css('visibility','hidden');
      }
    });
  }

    function setTrashVisible() {
        var checked = $('#trashed').is(':checked');
        window.location = '{!! URL::to('view_archive/user') !!}' + (checked ? '/true' : '/false');
    }

  function deleteUser(id) {
    if (!confirm('Are you sure?')) {
      return;
    }

    $('#userPublicId').val(id);
    $('form.user-form').submit();
  }
  </script>

@stop
