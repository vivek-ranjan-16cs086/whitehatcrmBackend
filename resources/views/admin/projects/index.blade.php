@extends('layouts.admin')

@section('title', 'Projects')
@section('page-title', 'Project Management')
@section('page-subtitle', 'Manage all your projects')

@section('content')
<div class="fade-in">

    {{-- ── Page Header ── --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="mb-0 fw-semibold">All Projects</h5>
            <small class="text-muted">View, add, edit or delete projects</small>
        </div>
        <div class="d-flex gap-2">
           
            <button class="btn btn-primary" id="btnAddProject">
                <i data-lucide="plus" class="me-2"></i> Add Project
            </button>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Projects Table ── --}}
    <div class="card" id="projectsCard">
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
               <table class="table" id="projectsTable">
					<thead>
						<tr>
							<th>S.No.</th>
							<th>Project Name</th>
							<th>Email</th>
							<th>Phone</th>
							<th>Address</th>
							<th>Status</th>
							<th>Created Date</th> 
							<th>Actions</th>
						</tr>
					</thead>
				</table>
            </div>
        </div>
        
    </div>

</div>

<div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectModalLabel">
                    <i data-lucide="folder-plus" class="me-2"></i><span id="projectModalTitle">Add New Project</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="projectForm" action="" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST" disabled>
                <input type="hidden" id="project_id" name="project_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="name">Project Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                   placeholder="Enter project name" required>
                            <div class="invalid-feedback">Project name is required.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="Enter email" required>
                            <div class="invalid-feedback">A valid email is required.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="phone">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone"
                                   placeholder="Enter phone number" required>
                            <div class="invalid-feedback">Phone number is required.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="enable">Status</label>
                            <select class="form-select" id="enable" name="enable">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
						<div class="col-md-6">
							<label class="form-label fw-semibold" for="inventory">Inventory Type</label>
							<select class="form-select" id="inventory" name="inventory">
								<option value="">Select Type</option>
								<option value="1 BHK">1 BHK</option>
								<option value="2 BHK">2 BHK</option>
								<option value="3 BHK">3 BHK</option>
								<option value="4 BHK">4 BHK</option>
								<option value="5 BHK">5 BHK</option>
								<option value="6 BHK">6 BHK</option>
								<option value="Plots">Plots</option>
								<option value="Villa">Villa</option>
								<option value="Studio Apartment">Studio Apartment</option>
								<option value="Shops">Shops</option>
							</select>
						</div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="address">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"
                                      placeholder="Enter project address"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveProject">
                        Save Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    loadAjaxList();
});
function loadAjaxList() {

    $('#projectsTable').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,

        ajax: {
            url: "{{ route('admin.projects.ajax') }}",
            type: "GET"
        },

        columns: [
            { data: 'id', orderable: false, searchable: false },

            {
                data: 'name',
                render: function (data) {
                    return `
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle">
                                ${data ? data.charAt(0).toUpperCase() : 'P'}
                            </div>
                            <span class="fw-semibold">${data ?? '-'}</span>
                        </div>
                    `;
                }
            },

            { data: 'email', orderable: true },

            { data: 'phone', orderable: true },

            {
                data: 'address',
                render: function (data) {
                    return data ? data.substring(0, 30) + '...' : '-';
                }
            },

            { data: 'enable', orderable: true, searchable: false },

            { data: 'created_at', orderable: true },

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

        language: {
            processing: `
                <div class="d-flex justify-content-center py-3">
                    <div class="spinner-border text-primary"></div>
                </div>
            `,
            search: "_INPUT_",
            searchPlaceholder: "Search projects..."
        }
    });
}

</script>
<script>
$(function () {

    $(document).on('click', '.btn-edit-project', function () {
        const id      = $(this).data('id');
        const name    = $(this).data('name');
        const email   = $(this).data('email');
        const phone   = $(this).data('phone');
        const address = $(this).data('address');
        const enable  = $(this).data('enable');
		const inventory = $(this).data('inventory');

        $('#projectForm')[0].reset();
        $('#projectModalTitle').text('Edit Project');
        $('#formMethod').val('PUT').prop('disabled', false);
        $('#project_id').val(id);
        
        $('#name').val(name);
        $('#email').val(email);
        $('#phone').val(phone);
        $('#address').val(address);
		$('#inventory').val(inventory);
        //$('#enable').val(enable);
        $('#enable').val(enable == 1 ? '1' : '0');
        $('#projectForm').attr('action', '{{ url("projects") }}/' + id);
        $('#projectModal').modal('show');
    });

   
    $('#btnAddProject').on('click', function() {
        $('#projectForm')[0].reset();
        $('#projectModalTitle').text('Add New Project');
        $('#formMethod').val('POST').prop('disabled', true);
        $('#project_id').val('');
        $('#projectForm').attr('action', '{{ route("admin.projects.store") }}');
        $('#projectModal').modal('show');
    });

    
    $('#projectForm').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
        let url = form.attr('action');
        let btn = $('#btnSaveProject');
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        
        $.ajax({
            url: url,
            type: 'POST', // POST handles 
            data: form.serialize(),
            success: function(res) {
                btn.prop('disabled', false).text('Save Project');
                if(res.status) {
                    $('#projectModal').modal('hide');
                    Swal.fire({
                        title: 'Success!',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#projectsTable').DataTable().ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text('Save Project');
                let errors = xhr.responseJSON.errors;
                let errorMsg = 'An error occurred. Please try again.';
                if(errors) {
                    errorMsg = Object.values(errors).map(err => err[0]).join('<br>');
                } else if(xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    title: 'Error!',
                    html: errorMsg,
                    icon: 'error'
                });
            }
        });
    });

    lucide.createIcons();
});
</script>
@endpush
