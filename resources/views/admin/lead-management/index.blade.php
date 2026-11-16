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
                <div class="lead-date-inputs">
                    <input type="date" id="leadDateFrom" class="form-control" aria-label="From date">
                    <span aria-hidden="true">to</span>
                    <input type="date" id="leadDateTo" class="form-control" aria-label="To date">
                </div>
            </div>
			<div class="lead-source-filter">

				<select id="leadSourceFilter" class="form-select" aria-label="Source">
					<option value="">All Sources</option>
					@foreach($leadSources as $source)
						<option value="{{ $source }}">{{ $source }}</option>
					@endforeach
				</select>
			</div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="leadMoreFiltersToggle" aria-expanded="false" aria-controls="leadMoreFilters"><i class="bi bi-plus"></i> More filters</button>
                <button type="button" id="resetLeadFilters" class="btn btn-sm lead-reset-button"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>

		</div>

        <div class="lead-list-secondary">			<div id="leadFilterTabs" class="lead-status-filters" role="group" aria-label="Lead status filters">
				<button type="button" class="btn btn-sm btn-primary lead-filter-btn" data-view="total">All</button>
                <button type="button" id="unassignedLeadsButton" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="total" data-attention="unassigned">Unassigned</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="active">Active Leads</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="follow-ups">Follow Ups</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="expired-follow-ups">Expired Leads</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="booked">Booked Leads</button>
			</div><div id="leadSearchSlot"></div></div>

        <div id="leadMoreFilters" class="lead-filter-popover" role="dialog" aria-label="More lead filters" hidden>
            <div class="d-flex justify-content-between align-items-center px-3 pt-3"><strong class="small">More filters</strong><button type="button" id="closeLeadMoreFilters" class="btn-close" aria-label="Close filters"></button></div>
            <div class="row g-2 p-3">
                <div class="col-sm-6"><label class="form-label small" for="leadStatusFilter">Status</label><select id="leadStatusFilter" class="form-select form-select-sm lead-extra-filter"><option value="">All statuses</option>@foreach($filterOptions['status'] as $status)<option value="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
                <div class="col-sm-6"><label class="form-label small" for="leadProjectFilter">Project</label><select id="leadProjectFilter" class="form-select form-select-sm lead-extra-filter"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</select></div>
                @foreach(\App\Support\LeadFormFields::FIELDS as $field => [$label, $kind])
                @if($kind === 'select' && !in_array($field, ['status', 'project_id', 'assigned_to', 'source_website'], true))
                <div class="col-sm-6"><label class="form-label small" for="lead-filter-{{ $field }}">{{ $label }}</label><select id="lead-filter-{{ $field }}" data-lead-filter="{{ $field }}" class="form-select form-select-sm lead-extra-filter"><option value="">All</option>@foreach($filterOptions[$field] as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select></div>
                @endif
                @endforeach
                <div class="col-sm-6"><label class="form-label small" for="filterAssigned">Assigned to</label><select id="filterAssigned" data-lead-filter="assigned_to" class="form-select form-select-sm lead-extra-filter"><option value="">Anyone</option><option value="unassigned">Unassigned</option>@foreach($filterUsers as $filterUser)<option value="{{ $filterUser->id }}">{{ $filterUser->name }}</option>@endforeach</select></div>
                @foreach($advancedFilterFields as $field)
                <div class="col-sm-6"><label class="form-label small" for="lead-filter-{{ $field }}">{{ ucwords(str_replace('_', ' ', $field)) }} contains</label><input id="lead-filter-{{ $field }}" data-lead-filter="{{ $field }}" class="form-control form-control-sm lead-extra-filter" maxlength="255"></div>
                @endforeach
                @foreach(['follow_up_date' => 'Follow-up', 'visit_date' => 'Visit'] as $field => $label)
                @if(in_array($field, $leadFilterColumns, true))
                @foreach(['from', 'to'] as $bound)
                <div class="col-sm-6"><label class="form-label small" for="lead-filter-{{ $field }}-{{ $bound }}">{{ $label }} {{ $bound }}</label><input type="date" id="lead-filter-{{ $field }}-{{ $bound }}" data-lead-filter="{{ $field }}_{{ $bound }}" class="form-control form-control-sm lead-extra-filter"></div>
                @endforeach
                @endif
                @endforeach
                @foreach(['budget_min' => 'Minimum budget at least', 'budget_max' => 'Maximum budget at most'] as $field => $label)
                @if(in_array($field, $leadFilterColumns, true))
                <div class="col-sm-6"><label class="form-label small" for="lead-filter-{{ $field }}">{{ $label }}</label><input type="number" min="0" step="0.01" id="lead-filter-{{ $field }}" data-lead-filter="{{ $field }}" class="form-control form-control-sm lead-extra-filter"></div>
                @endif
                @endforeach
            </div>
        </div>

		<div class="card-body">
            <label for="selectAllLeads" class="d-inline-flex align-items-center gap-2 mb-3" style="cursor:pointer">
                <span>Select All</span>
                <input type="checkbox" id="selectAllLeads" class="form-check-input m-0" style="width:20px;height:20px;cursor:pointer" aria-label="Select all leads on this page" title="Select all leads on this page">
            </label>
			<div class="table-responsive">
				<table id="leads-table" class="table leads-table">
					<thead>
						<tr>
							<th>Name</th>
							<th>Phone</th>
							<th>Source</th>
							<th>Message</th>
							<th>Page Name</th>
							<th>Assigned To</th>
							<th>Status</th>
							<th>Visit</th>
							<th>Visit Status</th>
							<th>Inventory</th>
							<th>Project</th>
							<th>Type</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
            <div class="d-flex align-items-center gap-2 mt-2">
                <span id="leadSelectionCount" class="small text-muted" role="status" aria-live="polite">0 selected</span>
                <button id="clearLeadSelection" type="button" class="btn btn-link btn-sm" hidden>Clear selection</button>
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
		<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
			<div class="modal-content">

				<div class="modal-header">
					<h5 class="modal-title fw-bold">Add Lead</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>

				<div class="modal-body">
					<form id="addLeadForm">

						@include('admin.lead-management.fields', ['editing' => false])
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

						@include('admin.lead-management.fields', ['editing' => true])
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
    function toggleDeactivationReason(prefix) {
        const visible = $('#' + prefix + 'status').val() === 'deactive';
        $('#' + prefix + 'deactivate_reason_group').prop('hidden', !visible);
        $('#' + prefix + 'deactivate_reason').prop('required', visible).prop('disabled', !visible);
    }
    $(document).on('change', '#add_status, #edit_status', function () {
        toggleDeactivationReason(this.id.startsWith('edit_') ? 'edit_' : 'add_');
    });


//added
const canAssignLeadsInline = @json(auth()->user()->hasRole('super-admin', 'web'));
const leadAssignmentUsers = @json($assignmentUsers);

function renderLeadAssignee(data, type, row) {
    if (type !== 'display' || !canAssignLeadsInline) return data;
    const assignedId = String(row.edit_data?.assigned_to ?? '');
    const select = $('<select>', {
        class: 'form-select form-select-sm lead-assignee-select',
        'aria-label': 'Assign lead ' + $('<div>').html(row.name || '').text(),
        'data-lead-id': row.lead_id,
        'data-current-value': assignedId,
        style: 'min-width:220px;max-width:320px'
    }).append(new Option('Unassigned', ''));
    leadAssignmentUsers.forEach(user => select.append(new Option(user.text, String(user.id))));
    // Preserve an owner absent from the available list until the admin changes it.
    if (assignedId && !leadAssignmentUsers.some(user => String(user.id) === assignedId)) {
        select.append(new Option(row.assigned_user_name || 'User #' + assignedId, assignedId));
    }
    select.find('option').each(function () { this.defaultSelected = this.value === assignedId; });
    return select.prop('outerHTML');
}

$(document).on('change', '.lead-assignee-select', async function () {
    const select = $(this);
    const previous = this.dataset.currentValue;
    const next = this.value;
    if (previous === next) return;
    const nextName = select.find(':selected').text();
    select.prop('disabled', true);
    try {
        const result = await Swal.fire({
            icon: 'warning',
            title: next ? 'Assign this lead?' : 'Unassign this lead?',
            text: next
                ? 'This lead will be assigned to ' + nextName + '. The previous owner will lose access unless their permissions allow it.'
                : 'This lead will become unassigned. The previous owner will lose access unless their permissions allow it.',
            showCancelButton: true,
            focusCancel: true,
            reverseButtons: true,
            confirmButtonText: next ? 'Assign lead' : 'Unassign lead',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#5146d8',
            customClass: { popup: 'admin-swal-popup' }
        });
        if (!result.isConfirmed) { select.val(previous); return; }
        const response = await $.ajax({
            url: '{{ route("lead-management.assign") }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { id: this.dataset.leadId, assigned_to: next || '' }
        });
        if (!response.success) throw new Error(response.message || 'Could not update assignment.');
        this.dataset.currentValue = next;
        $('#leads-table').DataTable().ajax.reload(null, false);
    } catch (error) {
        select.val(previous);
        await Swal.fire({ icon: 'error', title: 'Assignment failed', text: error.responseJSON?.message || error.message || 'Please try again.' });
    } finally {
        select.prop('disabled', false);
    }
});

const leadViewTitles = {
    'total': 'Total Leads',
    'active': 'Active Leads',
    'follow-ups': 'Follow Ups',
    'expired-follow-ups': 'Expired Follow Ups',
    'booked': 'Booked Leads'
};

const canEditLeadFields = @json(auth()->user()->hasCrmPermission('edit.lead'));
const inlineLeadOptions = @json($inlineLeadOptions);
const inlineLeadProjects = @json($projects);
const inlineFieldLabels = {status: 'Status', type: 'Type', inventory: 'Inventory', project_id: 'Project', visit_status: 'Visit status', visit_date: 'Visit date'};

function renderLeadField(field) {
    return function (data, type, row) {
        if (type !== 'display' || !canEditLeadFields) return data;
        const value = String(row.edit_data?.[field] ?? '');
        const attributes = {
            class: 'form-select form-select-sm lead-field-select',
            'aria-label': inlineFieldLabels[field] + ' for ' + $('<div>').html(row.name || '').text(),
            'data-lead-id': row.lead_id,
            'data-field': field,
            'data-current-value': value,
            style: 'min-width:140px'
        };
        if (field === 'visit_date') {
            return $('<input>', {...attributes, class: 'form-control form-control-sm lead-field-select', type: 'date', value: value.substring(0, 10)})
                .attr('data-current-value', value.substring(0, 10)).prop('outerHTML');
        }
        const choices = field === 'project_id'
            ? inlineLeadProjects.map(project => ({value: String(project.id), text: project.name}))
            : (inlineLeadOptions[field] || []).map(option => ({value: option, text: option.replace(/[_-]/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase())}));
        const select = $('<select>', attributes).append(new Option(field === 'project_id' ? 'No project' : 'None', ''));
        choices.forEach(option => select.append(new Option(option.text, option.value)));
        if (value && !choices.some(option => option.value === value)) {
            select.append(new Option($('<div>').html(data || value).text(), value));
        }
        select.find('option').each(function () { this.defaultSelected = this.value === value; });
        return select.prop('outerHTML');
    };
}

$(document).on('change', '.lead-field-select', async function () {
    const input = $(this);
    const previous = this.dataset.currentValue;
    const next = this.value;
    const field = this.dataset.field;
    if (previous === next) return;
    input.prop('disabled', true);
    try {
        const deactivating = field === 'status' && next === 'deactive';
        const warning = {
            icon: 'warning', title: 'Update ' + inlineFieldLabels[field].toLowerCase() + '?',
            text: 'This lead will be updated to ' + (this.tagName === 'SELECT' ? input.find(':selected').text() : (next || 'None')) + '.',
            showCancelButton: true, focusCancel: true, reverseButtons: true,
            confirmButtonText: 'Save change', cancelButtonText: 'Cancel',
            confirmButtonColor: '#5146d8', customClass: { popup: 'admin-swal-popup' }
        };
        if (deactivating) {
            warning.input = 'select';
            warning.inputOptions = Object.fromEntries(inlineLeadOptions.deactivate_reason.map(reason => [reason, reason]));
            warning.inputPlaceholder = 'Choose a deactivation reason';
            warning.inputValidator = reason => reason ? undefined : 'Select a deactivation reason.';
        }
        const result = await Swal.fire(warning);
        if (!result.isConfirmed) { input.val(previous); return; }
        const response = await $.ajax({
            url: '{{ route("lead-management.update-field") }}', type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { id: this.dataset.leadId, field: field, value: next || '', ...(deactivating ? {deactivate_reason: result.value} : {}) }
        });
        if (!response.success) throw new Error(response.message || 'Could not update lead.');
        this.dataset.currentValue = next;
        $('#leads-table').DataTable().ajax.reload(null, false);
    } catch (error) {
        input.val(previous);
        const errors = error.responseJSON?.errors;
        await Swal.fire({icon: 'error', title: 'Update failed', text: errors ? Object.values(errors).flat().join(' ') : (error.responseJSON?.message || error.message || 'Please try again.')});
    } finally {
        input.prop('disabled', false);
    }
});

const selectedLeadIds = new Set();

function renderLeadSelection(data, type, row) {
    if (type !== 'display') return '';
    return $('<input>', {
        type: 'checkbox', class: 'form-check-input lead-row-select m-0 flex-shrink-0', value: String(row.lead_id),
        style: 'width:20px;height:20px;cursor:pointer',
        'aria-label': 'Select lead ' + $('<div>').html(row.name || '').text()
    }).attr('checked', selectedLeadIds.has(String(row.lead_id)) ? 'checked' : null).prop('outerHTML');
}

function syncLeadSelection() {
    const checkboxes = $('#leads-table .lead-row-select');
    checkboxes.each(function () {
        this.checked = selectedLeadIds.has(this.value);
        $(this).closest('tr').toggleClass('table-active', this.checked);
    });
    const checkedCount = checkboxes.filter(':checked').length;
    $('#selectAllLeads').prop({
        checked: checkboxes.length > 0 && checkedCount === checkboxes.length,
        indeterminate: checkedCount > 0 && checkedCount < checkboxes.length,
        disabled: checkboxes.length === 0
    });
    $('#leadSelectionCount').text(selectedLeadIds.size + ' selected');
    $('#clearLeadSelection').prop('hidden', selectedLeadIds.size === 0);
}

$(document).on('change', '.lead-row-select', function () {
    if (this.checked) selectedLeadIds.add(this.value);
    else selectedLeadIds.delete(this.value);
    syncLeadSelection();
});
$(document).on('change', '#selectAllLeads', function () {
    const checked = this.checked;
    $('#leads-table .lead-row-select').each(function () {
        if (checked) selectedLeadIds.add(this.value);
        else selectedLeadIds.delete(this.value);
    });
    syncLeadSelection();
});
$(document).on('click', '#clearLeadSelection', function () {
    selectedLeadIds.clear();
    syncLeadSelection();
});

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
d.filters = {};
$('[data-lead-filter]').each(function () { if (this.value) d.filters[this.dataset.leadFilter] = this.value; });
d.filter_status = $('#leadStatusFilter').val();
d.filter_project = $('#leadProjectFilter').val();
d.open_lead_id = pendingLeadId;
}
},
//...



			columns: [

				{
                    data: 'name',
                    render: function (data, type, row) {
                        if (type !== 'display') return data;
                        const name = $('<div class="lead-name-text">').html(data || '-');
                        const arrival = $('<small class="lead-arrival-time text-muted">').text(row.arrived_at_ist || '');
                        const details = $('<div>').append(name, arrival);
                        return $('<div class="d-flex align-items-center gap-3">')
                            .append(renderLeadSelection(null, 'display', row), details).prop('outerHTML');
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

                { data: 'assigned_to', render: renderLeadAssignee },
                { data: 'status', orderable: false, searchable: false, render: renderLeadField('status') },
                { data: 'visit_date', render: renderLeadField('visit_date') },
                { data: 'visit_status', render: renderLeadField('visit_status') },
                { data: 'inventory', render: renderLeadField('inventory') },
                { data: 'project', render: renderLeadField('project_id') },
                { data: 'type', orderable: false, searchable: false, render: renderLeadField('type') },

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
                $('#leadMoreFilters').appendTo(document.body);
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
							exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11], orthogonal: 'export' }
						},
						{
							extend: 'excelHtml5',
							title: 'Leads',
							exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11], orthogonal: 'export' }
						},
						{
							extend: 'csv',
							title: 'Leads',
							exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11], orthogonal: 'export' }
						},
						{
							extend: 'pdfHtml5',
							title: 'Leads',
							exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11], orthogonal: 'export' }
						},
						{
							extend: 'print',
							title: 'Leads',
							exportOptions: { columns: [0,1,2,3,4,5,6,7,8,9,10,11], orthogonal: 'export' }
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
                syncLeadSelection();
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
        const active = this.dataset.attention
            ? this.dataset.attention === pendingAttention
            : !pendingAttention && this.dataset.view === view;

        $(this)
            .toggleClass('btn-primary', active)
            .toggleClass('btn-outline-secondary', !active)
            .attr('aria-pressed', String(active));
    });
}

window.setLeadView = function (view, attention = null) {

    if (!Object.prototype.hasOwnProperty.call(
        leadViewTitles, view
    )) {
        console.error('Invalid lead view:', view);
        return;
    }

    currentLeadView = view;
    pendingAttention = attention === 'unassigned' ? attention : null;

    sessionStorage.setItem('selectedLeadView', view);
    const destination = new URL(window.location.href);
    if (pendingAttention) destination.searchParams.set('attention', pendingAttention);
    else destination.searchParams.delete('attention');
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

    window.setLeadView(view, this.dataset.attention || null);
});


function closeLeadFilterPanel() {
    $('#leadMoreFilters').prop('hidden', true);
    $('#leadMoreFiltersToggle').attr('aria-expanded', 'false');
}
$(document).on('click', '#leadMoreFiltersToggle', function () {
    const panel = document.getElementById('leadMoreFilters');
    if (!panel.hidden) { closeLeadFilterPanel(); return; }
    const rect = this.getBoundingClientRect();
    panel.hidden = false;
    const width = Math.min(560, window.innerWidth - 24);
    Object.assign(panel.style, {width: width + 'px', left: Math.max(12, Math.min(rect.right - width, window.innerWidth - width - 12)) + 'px', top: Math.min(rect.bottom + 8, window.innerHeight - 160) + 'px', maxHeight: Math.max(140, window.innerHeight - rect.bottom - 24) + 'px'});
    $(this).attr('aria-expanded', 'true');
});
$(document).on('click', '#closeLeadMoreFilters', function () { closeLeadFilterPanel(); $('#leadMoreFiltersToggle').trigger('focus'); });
$(document).on('click', function (e) { if (!$(e.target).closest('#leadMoreFilters, #leadMoreFiltersToggle').length) closeLeadFilterPanel(); });
$(document).on('keydown', function (e) { if (e.key === 'Escape' && !$('#leadMoreFilters').prop('hidden')) { closeLeadFilterPanel(); $('#leadMoreFiltersToggle').trigger('focus'); } });
$(window).on('resize', closeLeadFilterPanel);

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
    closeLeadFilterPanel();
    const table = $('#leads-table').DataTable();
    table.search(''); table.columns().search('');
    window.setLeadView('total');
});

// Reload leads when the date range or source changes
$(document).on(
    'change',
    '#leadDateFrom, #leadDateTo, #leadSourceFilter',
    function () {
        const from = $('#leadDateFrom').val();
        const to = $('#leadDateTo').val();
        if (from && to && from > to) return;

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
			if (['source_submitted_at', 'follow_up_date'].includes(field) && value) {
				value = String(value).replace(' ', 'T').substring(0, 16);
			}
            const input = $('#edit_' + field);
            if (value && input.is('select') && !input.find('option').toArray().some(option => option.value === String(value))) {
                input.append(new Option(value, value));
            }
            input.val(value);
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
			row?.edit_data ? (lead.assigned_to ?? null) : ($(this).attr('data-assigned') || null),
			row?.assigned_user_name ?? $(this).data('assigned-name')
		);
        $('#editLeadForm').data('original-assigned', String($('#edit_assigned').val() || ''));
        toggleDeactivationReason('edit_');
		$('#editLeadModal').modal('show');
	});

	$('#updateLead').click(function () {
        const assignmentChanged = String($('#edit_assigned').val() || '') !== $('#editLeadForm').data('original-assigned');
        const nextOwner = $('#edit_assigned').val() ? $('#edit_assigned option:selected').text() : 'Unassigned';
        confirmAdminAction({
            icon: assignmentChanged ? 'warning' : 'question',
            title: 'Save lead changes?',
            text: assignmentChanged
                ? 'The lead details will be updated and ownership changed to ' + nextOwner + '. The previous owner may lose access.'
                : 'The lead details will be updated.',
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
        // Clear options from the previous lead, including Select2's retained selection.
        $('#edit_assigned').empty().append(new Option('Unassigned', '', true, true));

		$('#edit_assigned').select2({
			dropdownParent: $('#editLeadModal'), // important for modal
			placeholder: 'Unassigned',
			allowClear: true,
			 width: '100%',

			ajax: {
				url: '{{ route("lead-management.assignees") }}',
				dataType: 'json',
				delay: 250,

				data: function (params) {
					return {
						q: params.term,   // search keyword
						page: params.page || 1
					};
				},

				processResults: function (data) { return data;
				}
			}
		});

		// Preselect value when editing
		if (selectedId !== null && selectedId !== undefined && String(selectedId) !== '') {
			let option = new Option(selectedText || 'User #' + selectedId, selectedId, true, true);
			$('#edit_assigned').append(option).trigger('change');
		} else {
            $('#edit_assigned').val('').trigger('change');
        }
	}

	// Initialize Select2 for Add Lead Modal
	function initAddSalesPersonSelect() {
		$('#add_assigned').select2({
			dropdownParent: $('#addLeadModal'), // important for modal
			placeholder: 'Search users',
			allowClear: true,
			width: '100%',
			ajax: {
				url: '{{ route("lead-management.assignees") }}',
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return {
						q: params.term,
						page: params.page || 1
					};
				},
				processResults: function (data) { return data;
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
        toggleDeactivationReason('add_');
	});

	// Handle Save New Lead
	$('#saveNewLead').click(function () {
        if (!$('#addLeadForm')[0].reportValidity()) return;
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
                source_page_name: $('#add_source_page_name').val(),
                visit_status: $('#add_visit_status').val(),
                visit_date: $('#add_visit_date').val(),
                lead_source: $('#add_lead_source').val(),
                inventory: $('#add_inventory').val(),
                project_id: $('#add_project_id').val(),
                budget_min: $('#add_budget_min').val(),
                budget_max: $('#add_budget_max').val(),
                notes: $('#add_notes').val(),
                deactivate_reason: $('#add_deactivate_reason').val(),
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
