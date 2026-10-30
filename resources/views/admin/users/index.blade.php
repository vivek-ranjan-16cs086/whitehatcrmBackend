@extends('layouts.admin')

@section('title', 'Users')
@section('page-title', 'Users')
@section('page-subtitle', 'Manage system user accounts')

@section('content')

<div class="users-page">
    <div class="card users-card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">
                <i class="bi bi-people-fill me-2 text-primary"></i>
                All Users
            </h6>

            <button type="button"
                    class="btn btn-primary btn-sm"
                    onclick="openUserModal()">
                <i class="bi bi-plus-lg me-1"></i>
                New User
            </button>
        </div>

        <div class="card-body">

            <table id="users-table" class="table table-striped">

                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                </tbody>

            </table>

        </div>
    </div>
</div>


{{-- =========================================================
     USER MODAL
========================================================= --}}

<div class="modal fade"
     id="userModal"
     tabindex="-1"
     aria-labelledby="userModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header">

                <h5 class="modal-title fw-bold" id="userModalLabel">

                    <i class="bi bi-person-plus-fill me-2 text-primary"></i>

                    <span id="modalTitle">
                        New User
                    </span>

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                </button>

            </div>


            {{-- Form --}}
            <form id="userForm" novalidate>

                @csrf

                <div class="modal-body p-4">

                    {{-- =================================================
                         NAME + USERNAME
                    ================================================== --}}
                    <div class="row g-3 mb-3">

                        {{-- Full Name --}}
                        <div class="col-sm-6">

                            <label class="form-label fw-semibold">
                                Full Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control"
                                placeholder="John Doe"
                                autocomplete="name"
                                required
                            >

                        </div>


                        {{-- Username --}}
                        <div class="col-sm-6">

                            <label class="form-label fw-semibold">
                                Username
                            </label>

                            <div class="input-group">

                                <input
                                    type="text"
                                    name="username"
                                    id="username"
                                    class="form-control"
                                    placeholder="john.doe"
                                    autocomplete="off"
                                    required
                                >

                                <span class="input-group-text"
                                      id="usernameStatus">

                                    <i class="bi bi-person"></i>

                                </span>

                            </div>

                            <div id="usernameMessage"
                                 class="form-text">
                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         EMAIL
                    ================================================== --}}
                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control"
                            placeholder="user@example.com"
                            autocomplete="email"
                            required
                        >

                    </div>


                    {{-- =================================================
                         PASSWORD
                    ================================================== --}}
                    <div class="row g-3 mb-3"
                         id="passwordFields">

                        <div class="col-sm-6">

                            <label class="form-label fw-semibold"
                                   id="passwordLabel">

                                Password

                            </label>

                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                placeholder="Min 8 characters"
                                required
                            >

                            <div class="form-text"
                                 id="passwordHelp"
                                 style="display:none;">

                                (leave blank to keep current)

                            </div>

                        </div>


                        <div class="col-sm-6">

                            <label class="form-label fw-semibold"
                                   id="confirmPasswordLabel">

                                Confirm Password

                            </label>

                            <input
                                type="password"
                                name="password_confirmation"
                                id="password_confirmation"
                                class="form-control"
                                placeholder="Repeat password"
                                required
                            >

                        </div>

                    </div>


                    {{-- =================================================
                         ROLE
                    ================================================== --}}
                    <div class="mb-4">

                        <label class="form-label fw-semibold">
                            Role
                        </label>

                        <select
                            name="role"
                            id="role"
                            class="form-select"
                            required
                        >

                            <option value="">
                                — Select a role —
                            </option>

                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">
                                    {{ $role->name }}
                                </option>
                            @endforeach

                        </select>

                    </div>

                </div>


                {{-- Footer --}}
                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="submitBtn">

                        <i class="bi bi-check-lg me-1"></i>

                        <span id="submitBtnText">
                            Create User
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>

    // =========================================================
    // GLOBAL VARIABLES
    // =========================================================

    let isEditMode = false;
    let editUserId = null;

    let usernameManuallyChanged = false;
    let usernameAvailable = false;
    let usernameCheckTimer = null;


    // =========================================================
    // DOCUMENT READY
    // =========================================================

    $(document).ready(function () {

        loadAjaxList();

    });

    $(document).on('click', '.js-edit-user', function () {

        const user = JSON.parse(this.dataset.user);

        openUserModal(
            user.id,
            user.name,
            user.username,
            user.email,
            user.role
        );

    });


    // =========================================================
    // OPEN USER MODAL
    // =========================================================

    function openUserModal(
        userId = null,
        name = '',
        username = '',
        email = '',
        role = ''
    ) {

        resetForm();

        isEditMode = userId !== null;
        editUserId = userId;

        // =====================================================
        // EDIT MODE
        // =====================================================

        if (isEditMode) {

            $('#name').val(name);

            $('#username').val(username);

            $('#email').val(email);

            $('#role').val(role);

            // Existing username belongs to this user
            usernameManuallyChanged = true;

            $('#modalTitle').text('Edit User');

            $('#submitBtnText').text('Update User');

            $('#passwordFields').show();

            $('#password').prop('required', false);

            $('#password_confirmation').prop('required', false);

            $('#passwordLabel').text('New Password');

            $('#confirmPasswordLabel').text('Confirm New Password');

            $('#password').attr(
                'placeholder',
                'Leave blank to keep current'
            );

            $('#password_confirmation').attr(
                'placeholder',
                'Repeat new password'
            );

            $('#passwordHelp').show();

            // Check existing username
            if (username) {
                checkUsernameAvailability();
            }

        }


        // =====================================================
        // CREATE MODE
        // =====================================================

        else {

            $('#modalTitle').text('New User');

            $('#submitBtnText').text('Create User');

            $('#passwordFields').show();

            $('#password').prop('required', true);

            $('#password_confirmation').prop('required', true);

            $('#passwordLabel').text('Password');

            $('#confirmPasswordLabel').text('Confirm Password');

            $('#password').attr(
                'placeholder',
                'Min 8 characters'
            );

            $('#password_confirmation').attr(
                'placeholder',
                'Repeat password'
            );

            $('#passwordHelp').hide();

        }


        $('#userModal').modal('show');

    }


    // =========================================================
    // RESET FORM
    // =========================================================

    function resetForm() {

        $('#userForm')[0].reset();

        $('#submitBtn').prop('disabled', false);

        isEditMode = false;
        editUserId = null;

        usernameManuallyChanged = false;
        usernameAvailable = false;

        clearTimeout(usernameCheckTimer);


        // Reset username UI

        $('#username')
            .removeClass('is-valid is-invalid');

        $('#usernameStatus').html(
            '<i class="bi bi-person"></i>'
        );

        $('#usernameMessage')
            .removeClass(
                'text-success text-danger text-warning'
            )
            .text('');


        // Reset modal

        $('#modalTitle').text('New User');

        $('#submitBtnText').text('Create User');

        $('#passwordFields').show();

        $('#password').prop('required', true);

        $('#password_confirmation').prop('required', true);

        $('#passwordLabel').text('Password');

        $('#confirmPasswordLabel').text('Confirm Password');

        $('#password').attr(
            'placeholder',
            'Min 8 characters'
        );

        $('#password_confirmation').attr(
            'placeholder',
            'Repeat password'
        );

        $('#passwordHelp').hide();

    }


    // =========================================================
    // GENERATE USERNAME FROM NAME
    // =========================================================

    $('#name').on('input', function () {

        // If user has manually changed username,
        // don't overwrite it.
        if (usernameManuallyChanged) {
            return;
        }

        const name = $(this).val();

        let username = name
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '.');

        $('#username').val(username);

        checkUsernameAvailability();

    });


    // =========================================================
    // USERNAME MANUAL INPUT
    // =========================================================

    $('#username').on('input', function () {

        usernameManuallyChanged = true;

        let username = $(this).val()
            .toLowerCase()
            .replace(/\s+/g, '.')
            .replace(/[^a-z0-9._-]/g, '');

        $(this).val(username);

        checkUsernameAvailability();

    });


    // =========================================================
    // CHECK USERNAME AVAILABILITY
    // =========================================================

    function checkUsernameAvailability() {

        clearTimeout(usernameCheckTimer);

        const username = $('#username').val().trim();

        usernameAvailable = false;


        // Empty username

        if (!username) {

            $('#username')
                .removeClass('is-valid is-invalid');

            $('#usernameStatus').html(
                '<i class="bi bi-person"></i>'
            );

            $('#usernameMessage')
                .removeClass(
                    'text-success text-danger text-warning'
                )
                .text('');

            return;
        }


        // Minimum length

        if (username.length < 3) {

            $('#username')
                .removeClass('is-valid')
                .addClass('is-invalid');

            $('#usernameStatus').html(
                '<i class="bi bi-exclamation-circle text-warning"></i>'
            );

            $('#usernameMessage')
                .removeClass('text-success text-danger')
                .addClass('text-warning')
                .text('Username must be at least 3 characters.');

            return;
        }


        // Show checking state

        $('#usernameStatus').html(
            '<span class="spinner-border spinner-border-sm"></span>'
        );

        $('#usernameMessage')
            .removeClass('text-success text-danger')
            .addClass('text-muted')
            .text('Checking username...');


        // Wait 400ms before AJAX request

        usernameCheckTimer = setTimeout(function () {

            $.ajax({

                url: '{{ route("admin.users.checkUsername") }}',

                type: 'GET',

                data: {
                    username: username,
                    user_id: editUserId
                },


                success: function (response) {

                    if (response.available) {

                        usernameAvailable = true;

                        $('#username')
                            .removeClass('is-invalid')
                            .addClass('is-valid');

                        $('#usernameStatus').html(
                            '<i class="bi bi-check-circle-fill text-success"></i>'
                        );

                        $('#usernameMessage')
                            .removeClass(
                                'text-danger text-warning text-muted'
                            )
                            .addClass('text-success')
                            .text('Username is available.');

                    }

                    else {

                        usernameAvailable = false;

                        $('#username')
                            .removeClass('is-valid')
                            .addClass('is-invalid');

                        $('#usernameStatus').html(
                            '<i class="bi bi-x-circle-fill text-danger"></i>'
                        );

                        $('#usernameMessage')
                            .removeClass(
                                'text-success text-warning text-muted'
                            )
                            .addClass('text-danger')
                            .text('Username is already taken.');

                    }

                },


                error: function () {

                    usernameAvailable = false;

                    $('#usernameStatus').html(
                        '<i class="bi bi-exclamation-circle text-danger"></i>'
                    );

                    $('#usernameMessage')
                        .removeClass(
                            'text-success text-warning'
                        )
                        .addClass('text-danger')
                        .text('Unable to check username.');

                }

            });

        }, 400);

    }


    // =========================================================
    // FORM SUBMISSION
    // =========================================================

    $('#userForm').on('submit', function(e) {

        e.preventDefault();


        const username = $('#username').val().trim();


        // Username required

        if (!username) {

            $('#username')
                .addClass('is-invalid');

            $('#usernameMessage')
                .removeClass('text-success')
                .addClass('text-danger')
                .text('Username is required.');

            return;
        }


        // Username availability

        if (!usernameAvailable) {

            $('#username')
                .addClass('is-invalid');

            $('#usernameMessage')
                .removeClass('text-success')
                .addClass('text-danger')
                .text('Please enter an available username.');

            return;
        }
//added

const password = $('#password').val();
const confirmation = $('#password_confirmation').val();

if (password !== confirmation) {
    Swal.fire({
        icon: 'warning',
        title: 'Passwords do not match',
        text: 'Please enter the same password in both fields.'
    });
    return;
}

if (!isEditMode && password.length < 8) {
    Swal.fire({
        icon: 'warning',
        title: 'Invalid password',
        text: 'Password must contain at least 8 characters.'
    });
    return;
}


//...

        $('#submitBtn').prop('disabled', true);


        const formData = new FormData(this);


        const url = isEditMode

            ? `{{ url('admin/users') }}/${editUserId}`

            : '{{ route("admin.users.store") }}';


        const method = 'POST';


        if (isEditMode) {

            formData.append('_method', 'PUT');

        }


        $.ajax({

            url: url,

            type: method,

            data: formData,

            processData: false,

            contentType: false,

            headers: {

                'X-CSRF-TOKEN':
                    $('meta[name="csrf-token"]').attr('content')

            },


            success: function(response) {

                $('#userModal').modal('hide');

                loadAjaxList();


                Swal.fire({

                    icon: 'success',

                    title: 'Success!',

                    text: isEditMode
                        ? 'User updated successfully!'
                        : 'User created successfully!',

                    timer: 2000,

                    showConfirmButton: false

                });

            },


           //added
		   
error: function(xhr) {

    if (xhr.status === 422) {
        const errors = xhr.responseJSON?.errors || {};
        displayValidationErrors(errors);
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Something went wrong. Please try again.'
        });
    }
},

complete: function() {
    $('#submitBtn').prop('disabled', false);
}


        });

    });


    // =========================================================
    // SERVER VALIDATION ERRORS
    // =========================================================

    function displayValidationErrors(errors) {

        $('.is-invalid')
            .removeClass('is-invalid');

        $('.invalid-feedback')
            .remove();


        Object.keys(errors).forEach(field => {

            const element = $(`#${field}`);

            element.addClass('is-invalid');

            element.after(
                `<div class="invalid-feedback">
                    ${errors[field][0]}
                </div>`
            );

        });

    }


    // =========================================================
    // USERS DATATABLE
    // =========================================================

    function loadAjaxList() {

        $("#users-table").DataTable({

            processing: true,

            serverSide: true,

            destroy: true,


            ajax: {

                url: '{{ route("admin.users.index") }}',

                type: 'GET'

            },


            columns: [

                {
                    data: 'serialNo',
                    orderable: false,
                    searchable: false
                },


                {
                    data: 'name',

                    render: function(data, type, row) {

                        return `
                            <div class="d-flex align-items-center gap-2">

                                <div class="avatar-circle">
                                   
									
									${(data ?? '').charAt(0).toUpperCase()}
                                </div>

                                <div>

                                    
									<div class="fw-semibold">
    ${data ?? ''}
</div>

                                    <small class="text-muted">
                                        @${row.username ?? ''}
                                    </small>

                                </div>

                            </div>
                        `;

                    }

                },


                {
                    data: 'email',
                    orderable: true
                },


                {
                    data: 'role',

                    orderable: false,

                    render: function(data) {

                        var roleClass =
                            'role-' +
                            data
                                .replace(/[\s_]/g, '-')
                                .toLowerCase();

                        return `
                            <span class="role-badge
                                         ${roleClass}
                                         role-default">
                                ${data}
                            </span>
                        `;

                    }

                },


                {
                    data: 'created_at',
                    orderable: true
                },


                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }

            ],


            pageLength: 20,

            lengthMenu: [10, 15, 25, 50],

            order: [[4, 'desc']],


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

                processing:
                    '<div class="d-flex justify-content-center">' +
                    '<div class="spinner-border"></div>' +
                    '</div>'

            }

        });

    }

</script>

@endpush