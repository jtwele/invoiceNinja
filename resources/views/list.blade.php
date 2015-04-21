@extends('header')

@section('content')

	{!! Former::open($entityType . 's/bulk')->addClass('listForm') !!}
	<div style="display:none">
		{!! Former::text('action') !!}
		{!! Former::text('statusId') !!}
		{!! Former::text('id') !!}
	</div>

	{!! DropdownButton::normal(trans('texts.archive'))->withContents([
		      ['label' => trans('texts.archive_'.$entityType), 'url' => "javascript:submitForm('archive')"],
		      ['label' => trans('texts.delete_'.$entityType), 'url' => "javascript:submitForm('delete')"],
		    ])->withAttributes(['class'=>'archive'])->split() !!}
	
	&nbsp;<label for="trashed" style="font-weight:normal; margin-left: 10px;">
		<input id="trashed" type="checkbox" onclick="setTrashVisible()" 
			{{ Session::get("show_trash:{$entityType}") ? 'checked' : ''}}/>&nbsp; {{ trans('texts.show_archived_deleted')}} {{ strtolower(trans('texts.'.$entityType.'s')) }}
	</label>

	<div id="top_right_buttons" class="pull-right">
		<input id="tableFilter" type="text" style="width:140px;margin-right:17px" class="form-control pull-left" placeholder="{{ trans('texts.filter') }}"/> 
		{!! Button::normal(trans("texts.new_$entityType"))->asLinkTo("/{$entityType}s/create")->withAttributes(array('class' => 'pull-right'))->appendIcon(Icon::create('plus-sign')) !!}	
        
	</div>

    @if (isset($secEntityType))
		{!! Datatable::table()		
	    	->addColumn($secColumns)
	    	->setUrl(route('api.' . $secEntityType . 's'))    	
	    	->setOptions('sPaginationType', 'bootstrap')
	    	->render('datatable') !!}    
	@endif	

	{!! Datatable::table()		
    	->addColumn($columns)
    	->setUrl(route('api.' . $entityType . 's'))    	
    	->setOptions('sPaginationType', 'bootstrap')
    	->render('datatable') !!}
    
    {!! Former::close() !!}

    <script type="text/javascript">

	function submitForm(action) {
		if (action == 'delete') {
			if (!confirm('Are you sure?')) {
				return;
			}
		}		

		$('#action').val(action);
		$('form.listForm').submit();		
	}

	function deleteEntity(id) {
		$('#id').val(id);
		submitForm('delete');
	}

	function archiveEntity(id) {
		$('#id').val(id);
		submitForm('archive');
	}

    function restoreEntity(id) {
        $('#id').val(id);
        submitForm('restore');
    }
    function convertEntity(id) {
        $('#id').val(id);
        submitForm('convert');
    }

	function markEntity(id, statusId) {
		$('#id').val(id);
		$('#statusId').val(statusId);
		submitForm('mark');
	}

	function setTrashVisible() {
		var checked = $('#trashed').is(':checked');
		window.location = '{{ URL::to('view_archive/' . $entityType) }}' + (checked ? '/true' : '/false');
	}

    </script>

@stop

@section('onReady')

	var tableFilter = '';
	var searchTimeout = false;

	var oTable0 = $('#DataTables_Table_0').dataTable();
	var oTable1 = $('#DataTables_Table_1').dataTable();	
	function filterTable(val) {	
		if (val == tableFilter) {
			return;
		}
		tableFilter = val;
		oTable0.fnFilter(val);
    	@if (isset($secEntityType))
    		oTable1.fnFilter(val);
		@endif
	}

	$('#tableFilter').on('keyup', function(){
		if (searchTimeout) {
			window.clearTimeout(searchTimeout);
		}

		searchTimeout = setTimeout(function() {
			filterTable($('#tableFilter').val());
		}, 1000);					
	})

	window.onDatatableReady = function() {		
		$(':checkbox').click(function() {
			setArchiveEnabled();
		});	

		$('tbody tr').click(function(event) {        
			if (event.target.type !== 'checkbox' && event.target.type !== 'button' && event.target.tagName.toLowerCase() !== 'a') {
				$checkbox = $(this).closest('tr').find(':checkbox:not(:disabled)');				
				var checked = $checkbox.prop('checked');
				$checkbox.prop('checked', !checked);
				setArchiveEnabled();
			}
		});

		$('tbody tr').mouseover(function() {
			$(this).closest('tr').find('.tr-action').css('visibility','visible');
		}).mouseout(function() {
			$dropdown = $(this).closest('tr').find('.tr-action');
			if (!$dropdown.hasClass('open')) {
				$dropdown.css('visibility','hidden');
			}			
		});

	}	

	$('.archive').prop('disabled', true);
	$('.archive:not(.dropdown-toggle)').click(function() {
		submitForm('archive');
	});

	$('.selectAll').click(function() {
		$(this).closest('table').find(':checkbox:not(:disabled)').prop('checked', this.checked);
	});

	function setArchiveEnabled() {
		var checked = $('tbody :checkbox:checked').length > 0;
		$('button.archive').prop('disabled', !checked);	
	}


	
@stop