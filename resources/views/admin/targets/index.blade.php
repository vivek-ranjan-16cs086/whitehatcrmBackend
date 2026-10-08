@extends('layouts.admin')

@section('title', 'Targets')
@section('page-title', 'Target Management')
@section('page-subtitle', 'Manage employee targets')

@section('content')
<div class="fade-in">

    {{-- Header --}}
    <div class="d-flex justify-content-between mb-4">
        <h5 class="fw-semibold">All Targets</h5>
        <button class="btn btn-primary" id="btnAddTarget">
            <i data-lucide="plus" class="me-2"></i> Add Target
        </button>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table" id="targetsTable">
                    <thead>
                        <tr>
                            <th>S. No.</th>
                            <th>Employee</th>
                            <th>Project</th>
                            <th>Booking Set</th>
                            <th>Booking Done</th> 
                            <th>Site Visit</th> 
                            <th>Visited Done</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        <tr id="filterRow" class="table-filter-row">
                            <th></th>
                            <th>
                                <input type="text" id="filterEmployee" class="form-control form-control-sm dt-filter" placeholder="Search...">
                            </th>
                            <th>
                                <input type="text" id="filterProject" class="form-control form-control-sm dt-filter" placeholder="Search...">
                            </th>
                            <th>
                                <input type="number" id="filterBookingSet" class="form-control form-control-sm dt-filter" placeholder="≥">
                            </th>
                            <th>
                                <input type="number" id="filterBookingDone" class="form-control form-control-sm dt-filter" placeholder="≥">
                            </th>
                            <th>
                                <input type="number" id="filterSiteVisited" class="form-control form-control-sm dt-filter" placeholder="≥">
                            </th>
                            <th>
                                <input type="number" id="filterSiteVisitedDone" class="form-control form-control-sm dt-filter" placeholder="≥">
                            </th>
                            <th>
                                <input type="date" id="filterStartDay" class="form-control form-control-sm dt-filter">
                            </th>
                            <th>
                                <input type="date" id="filterEndDay" class="form-control form-control-sm dt-filter">
                            </th>
                            <th>
                                <select id="filterStatus" class="form-select form-select-sm dt-filter">
                                    <option value="">All</option>
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL --}}
<div class="modal fade" id="targetModal" tabindex="-1" aria-labelledby="targetModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="targetForm">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST" disabled>
                <input type="hidden" id="target_id">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="targetModalTitle">Add Target</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="user_id">Salesman <span class="text-danger">*</span></label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="" selected disabled>Select Salesman</option>
                                @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please select a salesman.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="project_id">Project <span class="text-danger">*</span></label>
                            <select name="project_id" id="project_id" class="form-select" required>
                                <option value="" selected disabled>Select Project</option>
                                @foreach($projects as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please select a project.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="booking_set">Booking Set <span class="text-danger">*</span></label>
                            <input type="number" name="booking_set" id="booking_set" class="form-control" placeholder="Target bookings" required min="0">
                            <div class="invalid-feedback">Booking set is required.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="booking_done">Booking Done</label>
                            <input type="number" name="booking_done" id="booking_done" class="form-control" placeholder="Completed bookings" min="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="site_visited">Site Visit <span class="text-danger">*</span></label>
                            <input type="number" name="site_visited" id="site_visited" class="form-control" placeholder="Target site visits" required min="0">
                            <div class="invalid-feedback">Site visit target is required.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="site_visited_done">Visited Done</label>
                            <input type="number" name="site_visited_done" id="site_visited_done" class="form-control" placeholder="Completed site visits" min="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="start_day">Start Day <span class="text-danger">*</span></label>
                            <input type="date" name="start_day" id="start_day" class="form-control" required>
                            <div class="invalid-feedback">Start day is required.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="end_day">End Day <span class="text-danger">*</span></label>
                            <input type="date" name="end_day" id="end_day" class="form-control" required>
                            <div class="invalid-feedback">End day is required.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium" for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="active" selected>Active</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                            </select>
                            <div class="invalid-feedback">Status is required.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0 mt-3 d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" id="btnSaveTarget">Save Target</button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection


@push('scripts')

<script>
$(document).ready(function () {
    loadTargetTable();
});

function loadTargetTable() {
    $('#targetsTable').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        ajax: {
            url: "{{ route('admin.targets.ajaxList') }}",
            type: "GET",
            data: function (d) {
                d.employee = $('#filterEmployee').val();
                d.project = $('#filterProject').val();
                d.booking_set = $('#filterBookingSet').val();
                d.booking_done = $('#filterBookingDone').val();
                d.site_visited = $('#filterSiteVisited').val();
                d.site_visited_done = $('#filterSiteVisitedDone').val();
                d.start_day = $('#filterStartDay').val();
                d.end_day = $('#filterEndDay').val();
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'id', orderable: false },
            { data: 'user' },
            { data: 'project' },
            { data: 'booking_set' },
            { data: 'booking_done' },
            { data: 'site_visited' },
            { data: 'site_visited_done' },
            { data: 'start_day' },
            { data: 'end_day' },
            { data: 'status' },
            { data: 'action', orderable: false, searchable: false }
        ],
		
		pageLength: 20,
        lengthMenu: [10, 25, 50, 100],

        order: [[6, 'desc']], 

        dom: 'Bfrtip',

        buttons: [
            'pageLength',
            'copy',
            'excel',
            'csv',
            'pdf',
            'print',
            'colvis'
        ],
    });
}

// Watch filters to trigger reloading instantly
$(document).ready(function() {
    let dtTimer;
    $('.dt-filter').on('keyup change', function () {
        clearTimeout(dtTimer);
        dtTimer = setTimeout(function() {
            $('#targetsTable').DataTable().ajax.reload(null, false);
        }, 500);
    });
});
</script>

<script>
$(function(){

// Reset form validations
function resetValidation() {
    $('#targetForm').removeClass('was-validated');
    $('.is-invalid').removeClass('is-invalid');
}

// ADD
$('#btnAddTarget').click(function(){
    $('#targetForm')[0].reset();
    resetValidation();
    $('#targetModalTitle').text('Add Target');
    $('#btnSaveTarget').text('Save Target');
    $('#formMethod').val('POST').prop('disabled', true);
    $('#targetForm').attr('action',"{{ route('admin.targets.store') }}");
    $('#targetModal').modal('show');
});

// EDIT
$(document).on('click','.btn-edit-target',function(){
    let id = $(this).data('id');
    $('#targetForm')[0].reset();
    resetValidation();

    $('#targetModalTitle').text('Edit Target');
    $('#btnSaveTarget').text('Update Target');
    $('#formMethod').val('PUT').prop('disabled', false);

    $('#user_id').val($(this).data('user_id') || $(this).data('user'));
    $('#project_id').val($(this).data('project_id') || $(this).data('project'));
    $('#booking_set').val($(this).data('booking_set'));
    $('#booking_done').val($(this).data('booking_done'));
    $('#site_visited').val($(this).data('site_visited'));
    $('#site_visited_done').val($(this).data('site_visited_done'));
    $('#start_day').val($(this).data('start_day'));
    $('#end_day').val($(this).data('end_day'));
    $('#status').val($(this).data('status') || 'active');

    $('#targetForm').attr('action','/targets/'+id);
    $('#targetModal').modal('show');
});

// SUBMIT
$('#targetForm').submit(function(e){
    e.preventDefault();

    let form = $(this);
    
    // HTML5 native validation fallback config
    if (!form[0].checkValidity()) {
        e.stopPropagation();
        form.addClass('was-validated');
        return;
    }

    let btn = $('#btnSaveTarget');
    let originalText = btn.text();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

    $.ajax({
        url: form.attr('action'),
        type:'POST',
        data: form.serialize(),
        success: function(res){
            btn.prop('disabled', false).text(originalText);
            if(res.status){
                $('#targetModal').modal('hide');
                $('#targetsTable').DataTable().ajax.reload(null,false);
            }
        },
        error: function(xhr){
            btn.prop('disabled', false).text(originalText);
            
        }
    });
});

// DELETE
$(document).on('click','.btn-delete-target',function(){
    let id=$(this).data('id');
    if(confirm('Delete?')){
        $.post('/targets/'+id,{
            _method:'DELETE',
            _token:$('meta[name="csrf-token"]').attr('content')
        },function(res){
            if(res.status){
                $('#targetsTable').DataTable().ajax.reload(null,false);
            }
        });
    }
});

});
</script>

@endpush