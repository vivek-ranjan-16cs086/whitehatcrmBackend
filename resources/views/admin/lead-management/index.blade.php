@extends('layouts.admin')

@section('body-class', 'leads-management-page')

@section('content')

<div class="leads-page">

	<div class="card leads-card">
        <div id="leadToolbarActions" class="d-flex align-items-center gap-2 flex-wrap">
				@can('create.lead')
					<button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importLeadsModal">
						<i class="bi bi-upload"></i> Import CSV
					</button>
					<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLeadModal">
						<i class="bi bi-plus"></i> Add Lead
					</button>
				@endcan
        </div>

        <div id="leadCalendarFilter" class="lead-calendar-toolbar">
            <input type="hidden" id="leadDateFrom">
            <input type="hidden" id="leadDateTo">
            <button type="button" id="leadCalendarToggle" class="btn btn-outline-secondary btn-sm" aria-haspopup="dialog" aria-expanded="false" aria-controls="leadCalendarPanel">
                <i class="bi bi-calendar3" aria-hidden="true"></i> <span id="leadCalendarLabel">All dates</span> <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
        </div>
        <div id="leadCalendarPanel" class="lead-calendar-panel" role="dialog" aria-label="Filter leads by arrival date" hidden>
            <div class="lead-calendar-presets" aria-label="Quick date ranges">
                @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'last7' => 'Last 7 Days', 'last30' => 'Last 30 Days', 'month' => 'This Month', 'lastMonth' => 'Last Month', 'last3' => 'Last 3 Months', 'last6' => 'Last 6 Months'] as $range => $label)
                    <button type="button" data-calendar-preset="{{ $range }}">{{ $label }}</button>
                @endforeach
            </div>
            <div class="lead-calendar-months" id="leadCalendarMonths"></div>
            <div class="lead-calendar-footer">
                <span id="leadCalendarDraft" role="status" aria-live="polite">Choose a start and end date</span>
                <div class="d-flex gap-2">
                    <button type="button" id="leadCalendarClear" class="btn btn-outline-secondary btn-sm">Clear</button>
                    <button type="button" id="leadCalendarApply" class="btn btn-primary btn-sm" disabled>Apply</button>
                </div>
            </div>
        </div>

        <div class="lead-list-secondary">			<div id="leadFilterTabs" class="lead-status-filters" role="group" aria-label="Lead status filters">
				<button type="button" class="btn btn-sm btn-primary lead-filter-btn" data-view="total">All</button>
                <button type="button" id="unassignedLeadsButton" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="total" data-attention="unassigned">Unassigned</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="active">Active Leads</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="follow-ups">Follow Ups</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="expired-follow-ups">Expired Leads</button>
				<button type="button" class="btn btn-sm btn-outline-secondary lead-filter-btn" data-view="booked">Booked Leads</button>
			</div><div class="lead-search-actions"><div id="leadSearchSlot"></div><button type="button" id="resetLeadFilters" class="btn btn-outline-secondary btn-sm" title="Clear all filters"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</button></div></div>

		<div class="card-body">
			<div class="table-responsive">
				<table id="leads-table" class="table leads-table">
					<thead>
						<tr>
							<th class="lead-selection-cell"><input type="checkbox" id="selectAllLeads" class="form-check-input lead-select-checkbox" aria-label="Select all leads on this page" title="Select all leads on this page"></th>
							<th>Name</th>
							<th>Phone</th>
							<th>Message</th>
							<th class="lead-filter-heading"><span>Source</span>
                                <select class="form-select form-select-sm lead-column-filter" data-lead-column-filter="source_website" aria-label="Filter Source">
                                    <option value="">All</option>
                                    @foreach($headerFilters['source_website'] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </th>
							<th class="lead-filter-heading"><span>Page Name</span>
                                <select class="form-select form-select-sm lead-column-filter" data-lead-column-filter="source_page_name" aria-label="Filter Page Name">
                                    <option value="">All</option>
                                    @foreach($headerFilters['source_page_name'] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </th>
							<th>Meta App</th>
							<th class="lead-filter-heading"><span>Assigned To</span>
                                <select class="form-select form-select-sm lead-column-filter" data-lead-column-filter="assigned_to" aria-label="Filter Assigned To">
                                    <option value="">All</option>
                                    @foreach($headerFilters['assigned_to'] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </th>
							<th class="lead-filter-heading"><span>Status</span>
                                <select class="form-select form-select-sm lead-column-filter" data-lead-column-filter="status" aria-label="Filter Status">
                                    <option value="">All</option>
                                    @foreach($headerFilters['status'] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </th>
							<th class="lead-filter-heading"><span>Visit</span>
                                <select class="form-select form-select-sm lead-column-filter" data-lead-column-filter="visit_date" aria-label="Filter Visit">
                                    <option value="">All</option>
                                    @foreach($headerFilters['visit_date'] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </th>
							<th class="lead-filter-heading"><span>Visit Status</span>
                                <select class="form-select form-select-sm lead-column-filter" data-lead-column-filter="visit_status" aria-label="Filter Visit Status">
                                    <option value="">All</option>
                                    @foreach($headerFilters['visit_status'] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </th>
							<th class="lead-filter-heading"><span>Inventory</span>
                                <select class="form-select form-select-sm lead-column-filter" data-lead-column-filter="inventory" aria-label="Filter Inventory">
                                    <option value="">All</option>
                                    @foreach($headerFilters['inventory'] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </th>
							<th class="lead-filter-heading"><span>Project</span>
                                <select class="form-select form-select-sm lead-column-filter" data-lead-column-filter="project_id" aria-label="Filter Project">
                                    <option value="">All</option>
                                    @foreach($headerFilters['project_id'] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </th>
							<th class="lead-filter-heading"><span>Type</span>
                                <select class="form-select form-select-sm lead-column-filter" data-lead-column-filter="type" aria-label="Filter Type">
                                    <option value="">All</option>
                                    @foreach($headerFilters['type'] as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </th>
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
<script src="{{ asset('admin/js/lead-calendar.js') }}?v={{ filemtime(public_path('admin/js/lead-calendar.js')) }}"></script>
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
const currentLeadAssignee = @json(['id' => $currentUser->id, 'name' => $currentUser->name]);

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
const inlineLeadOptions = @json($leadOptions);
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
            ? inlineLeadProjects.map(project => ({value: String(project.id), label: project.name}))
            : inlineLeadOptions[field];
        const select = $('<select>', attributes).append(new Option(field === 'project_id' ? 'No project' : 'None', ''));
        choices.forEach(option => select.append(new Option(option.label, option.value)));
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
            warning.inputOptions = Object.fromEntries(inlineLeadOptions.deactivate_reason.map(reason => [reason.value, reason.label]));
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
        type: 'checkbox', class: 'form-check-input lead-row-select lead-select-checkbox', value: String(row.lead_id),
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
    $('input[id="selectAllLeads"]').prop({
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

function leadExportHeading(data, column, node) {
    const label = $(node).find('span').first();
    return label.length ? label.text() : $('<div>').html(data).text();
}

const leadHeaderFilters = {};
$(document).on('click keydown', '.lead-column-filter', function (event) { event.stopPropagation(); });
$(document).on('change', '.lead-column-filter', function () {
    const field = this.dataset.leadColumnFilter;
    if (this.value) leadHeaderFilters[field] = this.value;
    else delete leadHeaderFilters[field];
    $('.lead-column-filter').filter(function () { return this.dataset.leadColumnFilter === field; }).val(this.value);
    if (field === 'status' && this.value) {
        currentLeadView = 'total'; pendingAttention = null;
        sessionStorage.setItem('selectedLeadView', 'total');
        updateLeadFilterButtons('total');
    }
    $('#leads-table').DataTable().ajax.reload(null, true);
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
            order: [],
            autoWidth: true,
            columnDefs: [{ targets: [4,5,6,7,8,9,10,11,12,13,14], orderable: false }],
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
d.header_filters = {...leadHeaderFilters};


d.open_lead_id = pendingLeadId;
}
},
//...



			columns: [

                { data: null, orderable: false, searchable: false, width: '36px', className: 'lead-selection-cell', render: renderLeadSelection },

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

				{ data: 'message', className: 'lead-message-cell' },
				{ data: 'source_website' },

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

				{ data: 'meta_app_name' },

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

			dom: '<"lead-controls-row"<"lead-controls-left"B>>frtip',
            initComplete: function () {
                const wrapper = $(this.api().table().container());
                wrapper.find('.lead-controls-left').prepend($('#leadCalendarFilter'));
                wrapper.find('.lead-controls-row').append($('#leadToolbarActions'));
                $('#leadSearchSlot').empty().append(wrapper.find('.dataTables_filter'));
                $('.lead-list-secondary').insertAfter(wrapper.find('.lead-controls-row'));

            },

            pageLength: 20,
            lengthChange: false,

			buttons: [
				{
					extend: 'collection',
					text: '<i class="bi bi-download"></i> Export',
					autoClose: true,
					buttons: [
						{
							extend: 'copyHtml5',
							title: 'Leads',
							exportOptions: { columns: [1,2,3,4,5,6,7,8,9,10,11,12,13], orthogonal: 'export', format: { header: leadExportHeading } }
						},
						{
							extend: 'excelHtml5',
							title: 'Leads',
							exportOptions: { columns: [1,2,3,4,5,6,7,8,9,10,11,12,13], orthogonal: 'export' }
						},
						{
							extend: 'csv',
							title: 'Leads',
							exportOptions: { columns: [1,2,3,4,5,6,7,8,9,10,11,12,13], orthogonal: 'export' }
						},
						{
							extend: 'pdfHtml5',
							title: 'Leads',
							exportOptions: { columns: [1,2,3,4,5,6,7,8,9,10,11,12,13], orthogonal: 'export' }
						},
						{
							extend: 'print',
							title: 'Leads',
							exportOptions: { columns: [1,2,3,4,5,6,7,8,9,10,11,12,13], orthogonal: 'export' }
						}
					]
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

    if (view !== 'total') {
        delete leadHeaderFilters.status;
        $('.lead-column-filter[data-lead-column-filter="status"]').val('');
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


$(document).on('click', '#resetLeadFilters', function () {
    window.clearLeadDateRange(false);
    Object.keys(leadHeaderFilters).forEach(field => delete leadHeaderFilters[field]);
    $('.lead-column-filter').val('');
    pendingLeadId = null;
    const destination = new URL(window.location.href);
    destination.searchParams.delete('open_lead');
    history.replaceState(history.state, '', destination.pathname + destination.search + destination.hash);
    const table = $('#leads-table').DataTable();
    table.search('');
    table.columns().search('');
    window.setLeadView('total');
});

$(document).on('lead:date-range', function () {
    $('#leads-table').DataTable().ajax.reload(null, true);
});
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

		$('#editLeadForm option[data-current-lead-value]').remove();
		$('#editLeadForm')[0].reset();
		$('#edit_id').val($(this).data('id'));
		fields.forEach(function (field) {
			let value = lead[field] ?? '';
			if (['source_submitted_at', 'follow_up_date'].includes(field) && value) {
				value = String(value).replace(' ', 'T').substring(0, 16);
			}
            const input = $('#edit_' + field);
            if (value && input.is('select') && !input.find('option').toArray().some(option => option.value === String(value))) {
                const option = new Option(value, value);
                option.dataset.currentLeadValue = '';
                input.append(option);
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
		if (!@json($isSuperAdmin)) {
			$('#edit_assigned').empty().append(new Option(currentLeadAssignee.name, currentLeadAssignee.id, true, true));
			$('#edit_assigned').prop('disabled', true);
			return;
		}
		$('#edit_assigned').prop('disabled', false);

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
		if (!@json($isSuperAdmin)) {
			$('#add_assigned').val(currentLeadAssignee.id).prop('disabled', true);
			return;
		}
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
		$('#add_assigned').prop('disabled', false).val(null).trigger('change');
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
