@extends('layouts.admin')

@section('body-class', 'leads-management-page')

@section('content')

<div class="leads-page">

	<div class="card leads-card">
		<div class="card-header d-flex justify-content-between align-items-center">
			<h6 class="mb-0 fw-bold">All Leads</h6>
			<div class="d-flex gap-2 flex-wrap">
				@can('create.lead')
					<button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importLeadsModal">
						<i class="bi bi-upload"></i> Import CSV
					</button>
					<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLeadModal">
						<i class="bi bi-plus"></i> Add Lead
					</button>
				@endcan
			</div>
		</div>
		
		<div class="lead-filter-bar">
			<div class="lead-date-filter">
				<label class="form-label" for="leadDateFrom">Date range</label>
				<div class="lead-date-inputs">
					<input type="date" id="leadDateFrom" class="form-control" aria-label="From date">
					<span aria-hidden="true">to</span>
					<input type="date" id="leadDateTo" class="form-control" aria-label="To date">
				</div>
			</div>
			<div class="lead-source-filter">
				<label class="form-label" for="leadSourceFilter">Source</label>
				<select id="leadSourceFilter" class="form-select">
					<option value="">All Sources</option>
					@foreach($leadSources as $source)
						<option value="{{ $source }}">{{ $source }}</option>
					@endforeach
				</select>
			</div>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#leadMoreFilters" aria-expanded="false" aria-controls="leadMoreFilters"><i class="bi bi-plus"></i> More filters</button>
                <button type="button" id="resetLeadFilters" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Reset filters</button>

		</div>

        <div class="lead-list-secondary">			<div id="leadFilterTabs" class="lead-status-filters" role="group" aria-label="Lead status filters">
				<button type="button" class="btn btn-sm btn-primary lead-filter-btn" data-view="total">All</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="active">Active Leads</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="follow-ups">Follow Ups</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="expired-follow-ups">Expired Leads</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="booked">Booked Leads</button>
			</div><div id="leadSearchSlot"></div></div>

        <div class="collapse" id="leadMoreFilters">
            <div class="row g-2 px-3 py-2 border-bottom">
                <div class="col-sm-6 col-lg-3"><label class="form-label small" for="leadStatusFilter">Status</label><select id="leadStatusFilter" class="form-select form-select-sm lead-extra-filter"><option value="">All statuses</option>@foreach(['not_available', 'active', 'in_process', 'booked', 'deactive', 'follow_up', 'follow_up_expired', 'follow_up_complete', 'visit'] as $status)<option value="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label small" for="leadProjectFilter">Project</label><select id="leadProjectFilter" class="form-select form-select-sm lead-extra-filter"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</select></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label small" for="leadInventoryFilter">Inventory contains</label><input id="leadInventoryFilter" class="form-control form-control-sm lead-extra-filter" maxlength="255" placeholder="e.g. 3 BHK"></div>
                <div class="col-sm-6 col-lg-3"><label class="form-label small" for="leadVisitFilter">Visit status contains</label><input id="leadVisitFilter" class="form-control form-control-sm lead-extra-filter" maxlength="255" placeholder="e.g. scheduled"></div>
            </div>
        </div>

		<div class="card-body">
			<div class="table-responsive">
				<table id="leads-table" class="table leads-table">
					<thead>
						<tr>
							<th>S.No</th>
							<th>Name</th>
							<th>Phone</th>
							<th>Source</th>
							<th>Message</th>
							<th>Page Name</th>
							<th>Project</th>
							<th>Assigned To</th>
							<th>Status</th>
							<th>Inventory</th>
							<th>Type</th>
							<th>Visit</th>
							<th>Visit Status</th>
							<th>Action</th>
						</tr>
					</thead>    
					<tbody></tbody>			
				</table>
			</div>
		</div>
	</div>
	@can('create.lead')
		<div class="modal fade" id="importLeadsModal" tabindex="-1" aria-labelledby="importLeadsModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title fw-bold" id="importLeadsModalLabel">Import Leads from CSV</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<p class="text-muted">
							Choose the source for this upload. All leads in the CSV will be saved under that source.
							Use the template to see all supported lead fields. Leave optional fields blank when unknown.
						</p>
						<a class="btn btn-sm btn-outline-secondary mb-3" href="{{ route('lead-managment.import-template') }}" download>
							<i class="bi bi-download"></i> Download CSV template
						</a>
						<form id="importLeadsForm" enctype="multipart/form-data">
							@csrf
							<div class="mb-3">
								<label for="import_source" class="form-label">Source <span class="text-danger">*</span></label>
								<select id="import_source" name="source_website" class="form-select" required>
									<option value="">Select a source</option>
									@foreach($manualLeadSources as $label => $value)
										<option value="{{ $value }}">{{ $label }}</option>
									@endforeach
								</select>
							</div>
							<div class="mb-3">
								<label for="import_file" class="form-label">CSV file <span class="text-danger">*</span></label>
								<input type="file" id="import_file" name="file" class="form-control" accept=".csv,text/csv" required>
								<div class="form-text">
									Maximum size: 10 MB. Add <code>source_page_name</code> to include the Facebook page or form name.
									Existing phone numbers are rejected as duplicates.
								</div>
							</div>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" form="importLeadsForm" id="importLeadsButton" class="btn btn-primary">Import Leads</button>
					</div>
				</div>
			</div>
		</div>
	@endcan
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
								<label class="form-label">Source <span class="text-danger">*</span></label>
								<select id="add_source" class="form-select" required>
									<option value="">Select a source</option>
									@foreach($manualLeadSources as $label => $value)
										<option value="{{ $value }}">{{ $label }}</option>
									@endforeach
								</select>
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
		<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content">

				<div class="modal-header">
					<h5 class="modal-title fw-bold">Edit Lead</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>

				<div class="modal-body">
					<form id="editLeadForm">
						@csrf
						<input type="hidden" name="id" id="edit_id">

						<div class="row g-3">
							<div class="col-12"><h6 class="mb-0 text-primary">Lead details</h6></div>
							<div class="col-md-6">
								<label class="form-label" for="edit_name">Name</label>
								<input type="text" name="name" id="edit_name" class="form-control" required>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_phone">Phone</label>
								<input type="text" name="phone" id="edit_phone" class="form-control">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_email">Email</label>
								<input type="email" name="email" id="edit_email" class="form-control">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_external_id">External ID</label>
								<input type="number" name="external_id" id="edit_external_id" class="form-control">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_status">Status</label>
								<select name="status" id="edit_status" class="form-select">
									<option value="not_available">Not Available</option>
									<option value="active">Active</option>
									<option value="booked">Booked</option>
									<option value="deactive">Deactive</option>
									<option value="follow_up">Follow Up</option>
									<option value="follow_up_expired">Expired</option>
									<option value="follow_up_complete">Completed</option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_type">Lead type</label>
								<select name="type" id="edit_type" class="form-select">
									<option value="hot">Hot</option>
									<option value="warm">Warm</option>
									<option value="cold">Cold</option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_source_website">Source</label>
								<select name="source_website" id="edit_source_website" class="form-select" required>
									@foreach($manualLeadSources as $label => $value)
										<option value="{{ $value }}">{{ $label }}</option>
									@endforeach
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_source_page_name">Source page name</label>
								<input type="text" name="source_page_name" id="edit_source_page_name" class="form-control">
							</div>
							<div class="col-md-12">
								<label class="form-label" for="edit_message">Message</label>
								<textarea name="message" id="edit_message" class="form-control" rows="3"></textarea>
							</div>

							<div class="col-12 mt-4"><h6 class="mb-0 text-primary">Assignment and follow-up</h6></div>
							<div class="col-md-6">
								<label class="form-label" for="edit_assigned">Assigned to</label>
								<select name="assigned_to" id="edit_assigned" class="form-select"></select>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_follow_up_date">Follow-up date</label>
								<input type="date" name="follow_up_date" id="edit_follow_up_date" class="form-control">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_visit_status">Visit status</label>
								<select name="visit_status" id="edit_visit_status" class="form-select">
									<option value="not_scheduled">Not scheduled</option>
									<option value="scheduled">Scheduled</option>
									<option value="completed">Completed</option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_visit_date">Visit date</label>
								<input type="date" name="visit_date" id="edit_visit_date" class="form-control">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_project_id">Project</label>
								<select name="project_id" id="edit_project_id" class="form-select">
									<option value="">No project</option>
									@foreach($projects as $project)
										<option value="{{ $project->id }}">{{ $project->name }}</option>
									@endforeach
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_sub_response_id">Response</label>
								<select name="sub_response_id" id="edit_sub_response_id" class="form-select">
									<option value="">No response</option>
									@foreach($subResponses as $subResponse)
										<option value="{{ $subResponse->id }}">{{ $subResponse->name }}</option>
									@endforeach
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_deactivate_reason">Deactivation reason</label>
								<select name="deactivate_reason" id="edit_deactivate_reason" class="form-select">
									<option value="">None</option>
									@foreach(['Plot or Villa', 'Budget', 'Location', 'Ready To Move', 'Rent', 'Invalid Mobile Number', 'Others', 'Switch Off'] as $reason)
										<option value="{{ $reason }}">{{ $reason }}</option>
									@endforeach
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_lead_source">Lead source</label>
								<input type="text" name="lead_source" id="edit_lead_source" class="form-control">
							</div>
							<div class="col-md-12">
								<label class="form-label" for="edit_notes">Notes</label>
								<textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
							</div>

							<div class="col-12 mt-4"><h6 class="mb-0 text-primary">Property requirements</h6></div>
							<div class="col-md-6">
								<label class="form-label" for="edit_inventory">Inventory</label>
								<input type="text" name="inventory" id="edit_inventory" class="form-control">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_budget_min">Minimum budget</label>
								<input type="number" name="budget_min" id="edit_budget_min" class="form-control" min="0" step="0.01">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="edit_budget_max">Maximum budget</label>
								<input type="number" name="budget_max" id="edit_budget_max" class="form-control" min="0" step="0.01">
							</div>
							<div class="col-md-12">
								<label class="form-label" for="edit_remarks">Remarks</label>
								<textarea name="remarks" id="edit_remarks" class="form-control" rows="2"></textarea>
							</div>

							<div class="col-12 mt-4"><h6 class="mb-0 text-primary">Source and campaign details</h6></div>
							@foreach([
								'source_page_id' => 'Source page ID',
								'source_form_id' => 'Source form ID',
								'source_form_name' => 'Source form name',
								'campaign_id' => 'Campaign ID',
								'campaign_name' => 'Campaign name',
								'adset_id' => 'Ad set ID',
								'adset_name' => 'Ad set name',
								'ad_id' => 'Ad ID',
								'ad_name' => 'Ad name',
							] as $field => $label)
								<div class="col-md-6">
									<label class="form-label" for="edit_{{ $field }}">{{ $label }}</label>
									<input type="text" name="{{ $field }}" id="edit_{{ $field }}" class="form-control">
								</div>
							@endforeach
							<div class="col-md-6">
								<label class="form-label" for="edit_source_submitted_at">Source submitted at</label>
								<input type="datetime-local" name="source_submitted_at" id="edit_source_submitted_at" class="form-control">
							</div>

							<div class="col-12 mt-4"><h6 class="mb-0 text-primary">Buyer qualification</h6></div>
							<div class="col-md-4">
								<label class="form-label" for="edit_purchase_timeline">Purchase timeline</label>
								<input type="text" name="purchase_timeline" id="edit_purchase_timeline" class="form-control">
							</div>
							<div class="col-md-4">
								<label class="form-label" for="edit_site_visit_preference">Site visit preference</label>
								<input type="text" name="site_visit_preference" id="edit_site_visit_preference" class="form-control">
							</div>
							<div class="col-md-4">
								<label class="form-label" for="edit_funding_method">Funding method</label>
								<input type="text" name="funding_method" id="edit_funding_method" class="form-control">
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

<div class="modal fade" id="viewLeadModal" data-details-url="{{ url('lead-managment') }}" tabindex="-1" aria-labelledby="viewLeadModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="viewLeadModalLabel">Lead details</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="leadViewStatus" role="status" aria-live="polite"></div>
                <div id="leadViewContent" hidden>
                    <p id="leadViewSummary" class="small text-muted"></p>
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#leadDetailsPane" type="button" role="tab">Details</button></li>
                        @foreach(['timeline' => 'Timeline', 'responses' => 'Responses & calls', 'ownership' => 'Ownership history'] as $section => $label)
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#lead-{{ $section }}" type="button" role="tab">{{ $label }} <span data-count="{{ $section }}" class="badge bg-light text-dark">0</span></button></li>
                        @endforeach
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="leadDetailsPane" role="tabpanel"><dl class="row mb-0" id="leadViewDetails" style="overflow-wrap:anywhere;white-space:pre-wrap"></dl></div>
                        @foreach(['timeline', 'responses', 'ownership'] as $section)
                        <div class="tab-pane fade" id="lead-{{ $section }}" role="tabpanel">
                            @if($section === 'ownership')<p class="small text-muted">Recorded assignments and transfers, newest first. Earlier changes appear only if they were recorded.</p>@endif
                            <div data-history="{{ $section }}"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" data-load-history="{{ $section }}" hidden>Load more</button>
                        </div>
                        @endforeach
                    </div>
                </div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>


@endsection

@push('scripts')
<script src="{{ asset('admin/js/lead-details.js') }}?v={{ filemtime(public_path('admin/js/lead-details.js')) }}"></script>
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

const attentionFilters = ['unassigned', 'overdue', 'no-follow-up', 'stale'];
let pendingAttention = new URLSearchParams(window.location.search).get('attention');
if (!attentionFilters.includes(pendingAttention)) {
    pendingAttention = null;
}

if (pendingAttention) {
    currentLeadView = 'total';
}

if (!leadViewTitles[currentLeadView]) {
    currentLeadView = 'total';
}

let pendingLeadId = new URLSearchParams(window.location.search).get('open_lead');
if (pendingLeadId && !/^\d+$/.test(pendingLeadId)) {
    pendingLeadId = null;
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
    d.attention = pendingAttention;
	 // New filters
	d.date_from = $('#leadDateFrom').val();
d.date_to = $('#leadDateTo').val();
d.source = $('#leadSourceFilter').val();
d.filter_status = $('#leadStatusFilter').val();
d.filter_project = $('#leadProjectFilter').val();
d.filter_inventory = $('#leadInventoryFilter').val();
d.filter_visit = $('#leadVisitFilter').val();
d.open_lead_id = pendingLeadId;
}
},
//...

			

			columns: [

				{
					data: null,
					orderable: false,
					searchable: false,
					render: function (data, type, row, meta) {
						return meta.row + meta.settings._iDisplayStart + 1;
					}
				},

				{
                    data: 'name',
                    render: function (data, type, row) {
                        if (type !== 'display') return data;
                        const name = $('<div class="lead-name-text">').html(data || '-');
                        const arrival = $('<small class="lead-arrival-time text-muted">').text(row.arrived_at_ist || '');
                        return $('<div>').append(name, arrival).html();
                    }
                },

				{ data: 'phone' },

				{ data: 'source_website' },
				{ data: 'message' },

				{
					data: 'source_page_name',
					className: 'lead-page-cell',
					render: function (data, type, row) {
						const pageName = data || '';
						if (type !== 'display') {
							return pageName;
						}

						if (!pageName.trim()) {
							return '<span class="lead-page-empty">-</span>';
						}

						const safePageName = $('<div>').text(pageName).html();
						const icon = row.source_website === 'facebook.com'
							? 'bi-facebook'
							: 'bi-file-earmark-text';

						return '<span class="lead-page-badge" title="' + safePageName + '">'
							+ '<i class="bi ' + icon + '" aria-hidden="true"></i>'
							+ '<span>' + safePageName + '</span>'
							+ '</span>';
					}
				},

				{ data: 'project' },
				{ data: 'assigned_to' },
				{
					data: 'status',
					orderable: false,
					searchable: false
				},
                { data: 'inventory' },

				{
					data: 'type',
					orderable: false,
					searchable: false,
					render: function(data){
						return data;
					}
				},

				{ data: 'visit_date' },

				{ data: 'visit_status' },

				{
					data: 'action',
					orderable: false,
					searchable: false
				}
			],

			dom: '<"lead-controls-row"<"lead-controls-left"B><"lead-controls-right">>frtip',
            initComplete: function () {
                const wrapper = $(this.api().table().container());
                wrapper.find('.lead-controls-right').append($('.lead-filter-bar'));
                const buttons = wrapper.find('.dt-buttons');
                buttons.find('.buttons-colvis').insertAfter(buttons.find('.buttons-page-length'));
                $('#leadSearchSlot').empty().append(wrapper.find('.dataTables_filter'));
                $('.lead-list-secondary').insertAfter(wrapper.find('.lead-controls-row'));
                $('#leadMoreFilters').insertAfter(wrapper.find('.lead-controls-row'));
            },

			lengthMenu: [
				[20, 25, 50, 100],
				['20 rows', '25 rows', '50 rows', '100 rows']
			],

			buttons: [
				'pageLength',
				{
					extend: 'collection',
					text: '<i class="bi bi-download"></i> Export',
					autoClose: true,
					buttons: [
						{
							extend: 'copyHtml5',
							title: 'Leads',
							exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11,12] }
						},
						{
							extend: 'excelHtml5',
							title: 'Leads',
							exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11,12] }
						},
						{
							extend: 'csv',
							title: 'Leads',
							exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11,12] }
						},
						{
							extend: 'pdfHtml5',
							title: 'Leads',
							exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11,12] }
						},
						{
							extend: 'print',
							title: 'Leads',
							exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11,12] }
						}
					]
				},
				{
					extend: 'colvis',
					text: '<i class="bi bi-layout-three-columns"></i> Columns'
				}
			],

			language: {
				search: "",
				searchPlaceholder: "Search leads..."
			},

			drawCallback: function () {
				if (!pendingLeadId) {
					return;
				}

				const requestedLeadId = pendingLeadId;
				pendingLeadId = null;

				const destination = new URL(window.location.href);
				destination.searchParams.delete('open_lead');
				history.replaceState(history.state, '', destination.pathname + destination.search + destination.hash);

				const leadRow = this.api().rows().data().toArray()
					.find(function (row) {
						return String(row.lead_id) === requestedLeadId;
					});

				if (leadRow) {
					$('#leads-table .viewBtn[data-id="' + requestedLeadId + '"]').trigger('click');
					return;
				}

				Swal.fire({
					icon: 'info',
					title: 'Lead unavailable',
					text: 'This lead could not be found or is not available to your account.'
				});
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
    pendingAttention = null;

    sessionStorage.setItem('selectedLeadView', view);
    const destination = new URL(window.location.href);
    destination.searchParams.delete('attention');
    history.replaceState(history.state, '', destination.pathname + destination.search + destination.hash);

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


let extraFilterTimer;
$(document).on('input change', '.lead-extra-filter', function () {
    clearTimeout(extraFilterTimer);
    if (this.id === 'leadStatusFilter' && this.value) {
        currentLeadView = 'total'; pendingAttention = null;
        sessionStorage.setItem('selectedLeadView', 'total');
        updateLeadFilterButtons('total');
    }
    extraFilterTimer = setTimeout(() => $('#leads-table').DataTable().ajax.reload(null, true), 300);
});
$(document).on('click', '#resetLeadFilters', function () {
    clearTimeout(extraFilterTimer);
    $('#leadDateFrom, #leadDateTo, #leadSourceFilter, .lead-extra-filter').val('');
    const table = $('#leads-table').DataTable();
    table.search(''); table.columns().search('');
    window.setLeadView('total');
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
		const row = $('#leads-table').DataTable().row($(this).closest('tr')).data();
		const lead = row?.edit_data || {};
		const fields = [
			'name', 'phone', 'email', 'message', 'external_id', 'status', 'type',
			'source_page_name', 'follow_up_date', 'visit_status', 'visit_date',
			'project_id', 'sub_response_id', 'deactivate_reason', 'lead_source',
			'notes', 'inventory', 'budget_min', 'budget_max', 'remarks',
			'source_page_id', 'source_form_id', 'source_form_name', 'campaign_id',
			'campaign_name', 'adset_id', 'adset_name', 'ad_id', 'ad_name',
			'purchase_timeline', 'site_visit_preference', 'funding_method',
			'source_submitted_at'
		];

		$('#editLeadForm')[0].reset();
		$('#edit_id').val($(this).data('id'));
		fields.forEach(function (field) {
			let value = lead[field] ?? '';
			if (field === 'source_submitted_at' && value) {
				value = String(value).replace(' ', 'T').substring(0, 16);
			}
			$('#edit_' + field).val(value);
		});

		const sourceSelect = $('#edit_source_website');
		const source = lead.source_website || $(this).data('source') || '';
		if (source && !sourceSelect.find('option').toArray().some(option => option.value === source)) {
			sourceSelect.append(new Option(source, source));
		}
		sourceSelect.val(source);

		if ($('#edit_assigned').hasClass("select2-hidden-accessible")) {
			$('#edit_assigned').select2('destroy');
		}

		initSalesPersonSelect(
			lead.assigned_to ?? $(this).data('assigned'),
			row?.assigned_user_name ?? $(this).data('assigned-name')
		);
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
					data: $('#editLeadForm').serialize()
				}).done(function (res) {
					if (!res.success) {
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
						confirmButtonColor: '#5146d8',
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

		$('#edit_assigned').select2({
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
			$('#edit_assigned').append(option).trigger('change');
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
				confirmButtonColor: '#5146d8',
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
						confirmButtonColor: '#5146d8',
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
						confirmButtonColor: '#5146d8',
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

	$('#importLeadsForm').on('submit', function (event) {
		event.preventDefault();

		const formData = new FormData(this);
		const importButton = $('#importLeadsButton');
		importButton.prop('disabled', true).text('Importing...');

		$.ajax({
			url: '{{ route("lead-managment.import") }}',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function (res) {
				$('#importLeadsModal').modal('hide');
				$('#importLeadsForm')[0].reset();
				$('#leads-table').DataTable().ajax.reload(null, false);
				Swal.fire({
					icon: 'success',
					title: 'Import complete',
					text: res.message || 'Leads imported successfully.',
					confirmButtonColor: '#5146d8',
					customClass: { popup: 'admin-swal-popup' }
				});
			},
			error: function (xhr) {
				const message = xhr.responseJSON?.message || 'Could not import the CSV file.';
				Swal.fire({
					icon: 'error',
					title: 'Import failed',
					text: message,
					confirmButtonColor: '#5146d8',
					customClass: { popup: 'admin-swal-popup' }
				});
			},
			complete: function () {
				importButton.prop('disabled', false).text('Import Leads');
			}
		});
	});
</script>
@endpush
