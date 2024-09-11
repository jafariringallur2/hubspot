<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('HubSpot Contacts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    Manage Contacts
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
                        Add Contact
                    </button>
                </div>
                <div class="card-body">
                    <!-- Import Contacts Button -->
                    <button type="button" class="btn btn-primary" id="importContactsButton">
                        Import Contacts from HubSpot
                    </button>
                    <div id="importContactsLoading" class="d-none">Loading...</div>
                    <div id="importContactsError" class="text-danger d-none"></div>

                    <!-- Table of Contacts -->
                    <table class="table table-striped mt-4" id="contactsTable">
                        <thead>
                            <tr>
                                <th scope="col">First Name</th>
                                <th scope="col">Last Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Properties</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contacts as $contact)
                            <tr>
                                <td>{{ $contact->first_name }}</td>
                                <td>{{ $contact->last_name }}</td>
                                <td>{{ $contact->email }}</td>
                                <td>
                                    @if($contact->properties)
                                        <pre>{{ json_encode(json_decode($contact->properties), JSON_PRETTY_PRINT) }}</pre>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    <!-- Update Contact Button -->
                                    <button class="btn btn-warning btn-sm update-contact" 
                                            data-toggle="modal" data-target="#updateContactModal" 
                                            data-id="{{ $contact->id }}" 
                                            data-first-name="{{ $contact->first_name }}" 
                                            data-last-name="{{ $contact->last_name }}" 
                                            data-email="{{ $contact->email }}">
                                        Update
                                    </button>

                                    <!-- Delete Contact Button -->
                                    <button class="btn btn-danger btn-sm delete-contact" data-id="{{ $contact->id }}">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Contact Modal -->
    <div class="modal fade" id="addContactModal" tabindex="-1" role="dialog" aria-labelledby="addContactModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addContactModalLabel">Add Contact</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addContactForm" action="{{ route('hubspot.contacts.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Contact</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
   $(document).ready(function() {
    // Handle Add Contact form submission
    $('#addContactForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Contact added successfully.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload(); // Refresh the page
                });
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to add contact.',
                });
            }
        });
    });

    // Handle Import Contacts button click
    $('#importContactsButton').click(function() {
        // Assuming $accountId is passed from the backend as a JS variable
        var accountId = @json($accountId);

        $('#importContactsButton').attr('disabled', true);
        $('#importContactsLoading').removeClass('d-none');
        $('#importContactsError').addClass('d-none').text('');

        $.ajax({
            url: "{{ route('hubspot.contacts.import') }}", // Call your import route
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}", // CSRF protection
                accountId: accountId // Send the accountId
            },
            success: function(response) {
                $('#importContactsLoading').addClass('d-none');
                $('#importContactsButton').attr('disabled', false);
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Contacts imported successfully.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload(); // Refresh the page
                });
            },
            error: function(xhr) {
                $('#importContactsLoading').addClass('d-none');
                $('#importContactsButton').attr('disabled', false);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to import contacts.',
                });
            }
        });
    });
});
    </script>
</x-app-layout>
