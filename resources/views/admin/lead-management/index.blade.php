@extends('layouts.admin')

@section('content')

<div class="leads-page">

	<div class="card leads-card">
		<div class="card-header d-flex justify-content-between align-items-center">
			<h6 class="mb-0 fw-bold">All Leads</h6>
		
			<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLeadModal">
				<i class="bi bi-plus"></i> Add Lead
			</button>
		</div>
		
{{-- Date Range and Source Filters --}}
<div class="row g-3 mb-3 align-items-end mx-0 px-3 pt-2">

    {{-- Left 50%: Date Range --}}
    <div class="col-md-8">
        <label class="form-label">Date Range</label>

        <div class="d-flex align-items-center gap-2">
            <input type="date"
                   id="leadDateFrom"
                   class="form-control"
                   aria-label="From date">

            <span class="text-muted">to</span>

            <input type="date"
                   id="leadDateTo"
                   class="form-control"
                   aria-label="To date">
        </div>
    </div>

    {{-- Right 40%: Source --}}
    <div class="col-md-4">
        <label class="form-label">Source</label>

        
<select id="leadSourceFilter" class="form-select">
    <option value="">All Sources</option>

    @foreach($leadSources as $source)
        <option value="{{ $source }}">
            {{ $source }}
        </option>
    @endforeach

</select>

    </div>

</div>

		
<!-- Lead Status Filters -->
<div class="px-3 pt-2 pb-3 border-bottom">
    <div id="leadFilterTabs"
         class="d-flex flex-wrap align-items-center gap-2"
         role="group"
         aria-label="Lead status filters">

        <button type="button"
                class="btn btn-sm btn-primary lead-filter-btn"
                data-view="total">
            All
        </button>

        <button type="button"
                class="btn btn-sm btn-outline-secondary lead-filter-btn"
                data-view="active">
            Active Leads
        </button>

        <button type="button"
                class="btn btn-sm btn-outline-secondary lead-filter-btn"
                data-view="follow-ups">
            Follow Ups
        </button>

        <button type="button"
                class="btn btn-sm btn-outline-secondary lead-filter-btn"
                data-view="expired-follow-ups">
            Expired Leads
        </button>

        <button type="button"
                class="btn btn-sm btn-outline-secondary lead-filter-btn"
                data-view="booked">
            Booked Leads
        </button>

    </div>
</div>
<!-- End Lead Status Filters -->

		<div class="card-body">
			<div class="table-responsive">
				<table id="leads-table" class="table leads-table">
					<thead>
						<tr>
							<th>S.No</th>
							<th>Name</th>
							<th>Phone</th>
							<th>Email</th>
							<th>Message</th>
							<th>Source</th>
							<th>Page</th>
							<th>Status</th>
							<th>Assigned To</th>
							<th>Type</th>
							<th>Follow Up</th>
							<th>Created</th>
							<th>Action</th>
						</tr>
					</thead>    
					<tbody></tbody>			
				</table>
			</div>
		</div>
	</div>
<div class="modal fade" id="addLeadModal">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">

				<div class="modal-header">
					<h5 class="modal-title fw-bold">Add Lead</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>

				<div class="modal-body">
					<form id="addLeadForm">

						<div class="row g-3">

							<div class="col-md-6">
								<label class="form-label">Name <span class="text-danger">*</span></label>
								<input type="text" id="add_name" class="form-control" required>
							</div>

							<div class="col-md-6">
								<label class="form-label">Phone <span class="text-danger">*</span></label>
								<input type="text" id="add_phone" class="form-control" required>
							</div>

							<div class="col-md-6">
								<label class="form-label">Email</label>
								<input type="email" id="add_email" class="form-control">
							</div>

							<div class="col-md-6">
								<label class="form-label">Status</label>
								<select id="add_status" class="form-select">
									<option value="not_available">Not Available</option>
									<option value="active">Active</option>
									<option value="in_process">In Process</option>
									<option value="booked">Booked</option>
									<option value="deactive">Deactive</option>
									<option value="follow_up">Follow Up</option>
									<option value="follow_up_expired">Expired</option>
									<option value="follow_up_complete">Completed</option>
								</select>
							</div>

							<div class="col-md-6">
								<label class="form-label">Source</label>
								<input type="text" id="add_source" class="form-control">
							</div>

							<div class="col-md-6">
								<label class="form-label">Assigned To</label>
								<select id="add_assigned" class="form-select"></select>
							</div>

							<div class="col-md-6">
								<label class="form-label">Follow Up</label>
								<input type="date" id="add_followup" class="form-control">
							</div>
                            <div class="col-md-6">
								<label class="form-label">Type</label>
								 <option value="cold" selected>Cold</option>
    <option value="warm">Warm</option>
    <option value="hot">Hot</option>
							</div>
							<div class="col-md-12">
								<label class="form-label">Message</label>
								<textarea id="add_message" class="form-control" rows="3"></textarea>
							</div>

						</div>

					</form>
				</div>

				<div class="modal-footer d-flex justify-content-between">
					<button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
					<button class="btn btn-primary" id="saveNewLead">Save Lead</button>
				</div>

			</div>
		</div>
	</div>
	<!-- MODAL -->
	<div class="modal fade" id="editLeadModal">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">

				<div class="modal-header">
					<h5 class="modal-title fw-bold">Edit Lead</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>

				<div class="modal-body">
					<form id="editLeadForm">

						<input type="hidden" id="lead_id">

						<div class="row g-3">

							<div class="col-md-6">
								<label class="form-label">Name</label>
								<input type="text" id="name" class="form-control">
							</div>

							<div class="col-md-6">
								<label class="form-label">Phone</label>
								<input type="text" id="phone" class="form-control">
							</div>

							<div class="col-md-6">
								<label class="form-label">Email</label>
								<input type="email" id="email" class="form-control">
							</div>

							<div class="col-md-6">
								<label class="form-label">Status</label>
								<select id="status" class="form-select">
									<option value="not_available">Not Available</option>
									<option value="active">Active</option>
									<option value="in_process">In Process</option>
									<option value="booked">Booked</option>
									<option value="deactive">Deactive</option>
									<option value="follow_up">Follow Up</option>
									<option value="follow_up_expired">Expired</option>
									<option value="follow_up_complete">Completed</option>
								</select>
							</div>

							<div class="col-md-6">
								<label class="form-label">Source</label>
								<input type="text" id="source" class="form-control">
							</div>

							<div class="col-md-6">
								<label class="form-label">Assigned To</label>
								<select id="assigned" class="form-select"></select>
							</div>

							<div class="col-md-6">
								<label class="form-label">Follow Up</label>
								<input type="date" id="followup" class="form-control">
							</div>
                            <div class="col-md-6">
								<label class="form-label">Type</label>
								<select id="type" class="form-select">
									<option value="hot">Hot</option>
    <option value="cold">Cold</option>
    <option value="warm">Warm</option>
								</select>
							</div>
							<div class="col-md-12">
								<label class="form-label">Message</label>
								<textarea id="message" class="form-control" rows="3"></textarea>
							</div>

						</div>

					</form>
				</div>

				<div class="modal-footer d-flex justify-content-between">
					<button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
					<button class="btn btn-primary" id="updateLead">Update Lead</button>
				</div>

			</div>
		</div>
	</div>
</div>


@endsection

@push('scripts')
<script>

//added
const leadViewTitles = {
    'total': 'Total Leads',
    'active': 'Active Leads',
    'follow-ups': 'Follow Ups',
    'expired-follow-ups': 'Expired Follow Ups',
    'booked': 'Booked Leads'
};

let currentLeadView =
    sessionStorage.getItem('selectedLeadView') || 'total';

if (!leadViewTitles[currentLeadView]) {
    currentLeadView = 'total';
}
//...
	$(document).ready(function () {
    updateLeadFilterButtons(currentLeadView);
    loadAjaxList();
});

	function loadAjaxList() {
		$("#leads-table").DataTable({
			processing: true,
			serverSide: true,
			destroy: true,
			
			//added
			
ajax: {
    url: '{{ route("lead-management.ajax-list") }}',
    type: "GET",
   //added
     data: function (d) {
    d.page = (d.start / d.length) + 1;
    d.view = currentLeadView;
	 // New filters
	d.date_from = $('#leadDateFrom').val();
d.date_to = $('#leadDateTo').val();
d.source = $('#leadSourceFilter').val();
}
},
//...

			

			columns: [

				// Serial Number
				{
					data: null,
					orderable: false,
					searchable: false,
					render: function (data, type, row, meta) {
						return meta.row + meta.settings._iDisplayStart + 1;
					}
				},

				{ data: 'name' },

				{ data: 'phone' },

				{ data: 'email' },

				{ data: 'message' },

				// Source
				{ data: 'source_website' },
				{ data: 'source_page_name' },
				
				

				// Status (already HTML from backend)
				{
					data: 'status',
					orderable: false,
					searchable: false
				},

				// Assigned To
				{ data: 'assigned_to' },
				{
					data: 'type',
					orderable: false,
					searchable: false,
					render: function(data){
						return data;
					}
				},

				// Follow Up
				{ data: 'follow_up_date' },

				// Created At (already formatted from backend)
				{ data: 'created_at' },

				// Action buttons
				{
					data: 'action',
					orderable: false,
					searchable: false
				}
			],

			dom: 'Bfrtip',

			lengthMenu: [
				[20, 25, 50, 100],
				['10 rows', '25 rows', '50 rows', '100 rows']
			],

			buttons: [
				'pageLength',
				{
					extend: 'copyHtml5',
					title: "Leads",
					exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10] }
				},
				{
					extend: 'excelHtml5',
					title: "Leads",
					exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10] }
				},
				{
					extend: 'csv',
					title: "Leads",
					exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10] }
				},
				{
					extend: 'pdfHtml5',
					title: "Leads",
					exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10] }
				},
				{
					extend: 'print',
					title: "Leads",
					exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10] }
				},
				'colvis'
			],

			language: {
				search: "<i class='fa fa-search'></i>",
				searchPlaceholder: " Search leads..."
			}
		});
	}
	//added	
function updateLeadFilterButtons(view) {
    $('.lead-filter-btn').each(function () {
        const active = this.dataset.view === view;

        $(this)
            .toggleClass('btn-primary', active)
            .toggleClass('btn-outline-secondary', !active)
            .attr('aria-pressed', String(active));
    });
}

window.setLeadView = function (view) {

    if (!Object.prototype.hasOwnProperty.call(
        leadViewTitles, view
    )) {
        console.error('Invalid lead view:', view);
        return;
    }

    currentLeadView = view;

    sessionStorage.setItem('selectedLeadView', view);

    updateLeadFilterButtons(view);

    if ($.fn.DataTable.isDataTable('#leads-table')) {
        $('#leads-table')
            .DataTable()
            .ajax.reload(null, true);
    }
};


$(document).on('click', '.lead-filter-btn', function () {
    const view = this.dataset.view;

    window.setLeadView(view);
});


// Reload leads when Date Range or Source changes
$(document).on(
    'change',
    '#leadDateFrom, #leadDateTo, #leadSourceFilter',
    function () {

        const from = $('#leadDateFrom').val();
        const to = $('#leadDateTo').val();

        // Avoid sending an invalid date range
        if (from && to && from > to) {
            return;
        }

        if ($.fn.DataTable.isDataTable('#leads-table')) {
            $('#leads-table')
                .DataTable()
                .ajax.reload(null, true);
        }
    }
);
//---
	$(document).on('click', '.editBtn', function () {

		$('#lead_id').val($(this).data('id'));
		$('#name').val($(this).data('name'));
		$('#phone').val($(this).data('phone'));
		$('#email').val($(this).data('email'));
		$('#message').val($(this).data('message'));
		$('#source').val($(this).data('source'));
		$('#status').val($(this).data('status'));
		$('#type').val($(this).data('type'));
		$('#assigned').val($(this).data('assigned'));
		$('#followup').val($(this).data('followup'));
		 //  destroy old instance (important)
		$('#assigned').val(null).trigger('change');
		if ($('#assigned').hasClass("select2-hidden-accessible")) {
			$('#assigned').select2('destroy');
		}

		// init select2 with preselected value
		initSalesPersonSelect($(this).data('assigned'), $(this).data('assigned-name'));
		$('#editLeadModal').modal('show');
	});
	
	$('#updateLead').click(function () {
		confirmAdminAction({
			title: 'Save lead changes?',
			text: 'The lead details will be updated.',
			confirmButtonText: 'Save changes',
			onConfirm: function () {
				$.ajax({
					url: '{{route("lead-managment.update")}}',
					type: 'POST',
					data: {
						id: $('#lead_id').val(),
						name: $('#name').val(),
						phone: $('#phone').val(),
						email: $('#email').val(),
						message: $('#message').val(),
						source_website: $('#source').val(),
						status: $('#status').val(),
						type: $('#type').val(),
						assigned_to: $('#assigned').val() ? $('#assigned').val() : null,
						follow_up_date: $('#followup').val(),
						_token: '{{ csrf_token() }}'
					}
				}).done(function (res) {
					if (!res.status) {
						Swal.fire({
							icon: 'error',
							title: 'Could not update lead',
							text: res.message || 'The server could not update this lead.'
						});
						return;
					}

					$('#editLeadModal').modal('hide');
					$('#leads-table').DataTable().ajax.reload(null, false);
					Swal.fire({
						icon: 'success',
						title: 'Lead updated',
						text: res.message || 'The lead was updated successfully.',
						confirmButtonColor: '#5548d9',
						customClass: { popup: 'admin-swal-popup' }
					});
				}).fail(function (xhr) {
					Swal.fire({
						icon: 'error',
						title: 'Could not update lead',
						text: xhr.responseJSON?.message || 'The server could not update this lead. Please try again.'
					});
				});
			}
		});
	});
	function initSalesPersonSelect(selectedId = null, selectedText = null) {

		$('#assigned').select2({
			dropdownParent: $('#editLeadModal'), // important for modal
			placeholder: 'Search Sales Person',
			allowClear: true,
			 width: '100%',

			ajax: {
				url: '{{route("admin.users.index")}}',
				dataType: 'json',
				delay: 250,

				data: function (params) {
					return {
						'search[value]': params.term,   // search keyword
						page: params.page || 1,
						role_id: 4
					};
				},

				processResults: function (data, params) {
					params.page = params.page || 1;

					let results = data.data.map(function (item) {
						return {
							id: item.id,
							text: item.name
						};
					});

					return {
						results: results,
						pagination: {
							// ⭐ THIS IS THE FIX
							more: (params.page < data.last_page) && results.length > 0
						}
					};
				}
			}
		});

		// Preselect value when editing
		if (selectedId && selectedText) {
			let option = new Option(selectedText, selectedId, true, true);
			$('#assigned').append(option).trigger('change');
		}
	}

	// Initialize Select2 for Add Lead Modal
	function initAddSalesPersonSelect() {
		$('#add_assigned').select2({
			dropdownParent: $('#addLeadModal'), // important for modal
			placeholder: 'Search Sales Person',
			allowClear: true,
			width: '100%',
			ajax: {
				url: '{{route("admin.users.index")}}',
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return {
						'search[value]': params.term,
						page: params.page || 1,
						role_id: 4
					};
				},
				processResults: function (data, params) {
					params.page = params.page || 1;
					let results = data.data.map(function (item) {
						return { id: item.id, text: item.name };
					});
					return {
						results: results,
						pagination: {
							more: (params.page < data.last_page) && results.length > 0
						}
					};
				}
			}
		});
	}

	// Init on Add button click
	$('[data-bs-target="#addLeadModal"]').click(function() {
		$('#addLeadForm')[0].reset();
		$('#add_assigned').val(null).trigger('change');
		if ($('#add_assigned').hasClass("select2-hidden-accessible")) {
			$('#add_assigned').select2('destroy');
		}
		initAddSalesPersonSelect();
	});

	// Handle Save New Lead
	$('#saveNewLead').click(function () {
		let name = $('#add_name').val().trim();
		let phone = $('#add_phone').val().trim();
		
		if (!name || !phone) {
			Swal.fire({
				icon: 'warning',
				title: 'Required details missing',
				text: 'Enter both a name and phone number to continue.',
				confirmButtonColor: '#5548d9',
				customClass: { popup: 'admin-swal-popup' }
			});
			return;
		}

		$.ajax({
			url: '{{route("lead-managment.store")}}',
			type: 'POST',
			data: {
				name: name,
				phone: phone,
				email: $('#add_email').val(),
				status: $('#add_status').val(),
				source_website: $('#add_source').val(),
				assigned_to: $('#add_assigned').val() ? $('#add_assigned').val() : null,
				follow_up_date: $('#add_followup').val(),
				type: $('#add_type').val(),
				message: $('#add_message').val(),
				_token: '{{ csrf_token() }}'
			},
			success: function (res) {
				if (res.status) {
					$('#addLeadModal').modal('hide');
					$('#addLeadForm')[0].reset();
					$('#leads-table').DataTable().ajax.reload(null, false);
					Swal.fire({
						icon: 'success',
						title: 'Lead created',
						text: res.message || 'The lead was created successfully.',
						confirmButtonColor: '#5548d9',
						customClass: { popup: 'admin-swal-popup' }
					});
				} else {
					Swal.fire({
						icon: 'error',
						title: 'Could not create lead',
						text: res.message || 'The server could not create this lead.'
					});
				}
			},
			error: function (xhr) {
				let errors = xhr.responseJSON?.errors;
				if (errors) {
					const errorMsg = Object.values(errors).flat().join(' ');
					Swal.fire({
						icon: 'warning',
						title: 'Check the lead details',
						text: errorMsg,
						confirmButtonColor: '#5548d9',
						customClass: { popup: 'admin-swal-popup' }
					});
				} else {
					Swal.fire({
						icon: 'error',
						title: 'Could not create lead',
						text: xhr.responseJSON?.message || 'The server could not create this lead. Please try again.'
					});
				}
			}
		});
	});	
</script>
@endpush