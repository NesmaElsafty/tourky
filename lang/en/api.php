<?php

return [
    'auth' => [
        'wrong_credentials' => 'Wrong credentials.',
        'unauthorized' => 'Unauthorized.',
    ],

    'role' => [
        'unauthorized_admin' => 'Unauthorized for admin role.',
        'unauthorized_captain' => 'Unauthorized for captain role.',
        'unauthorized_client' => 'Unauthorized for client role.',
    ],

    'admin' => [
        'registered' => 'Admin registered successfully.',
        'logged_in' => 'Admin logged in successfully.',
        'logged_out' => 'Admin logged out successfully.',
        'profile_retrieved' => 'Admin profile retrieved successfully.',
        'update_profile_success' => 'Admin profile updated successfully.',
    ],

    'roles' => [
        'list_retrieved' => 'Roles retrieved successfully.',
        'created' => 'Role created successfully.',
        'updated' => 'Role updated successfully.',
        'retrieved' => 'Role retrieved successfully.',
        'deleted' => 'Role deleted successfully.',
        'server_error' => 'Something went wrong. Please try again later.',
    ],

    'captain' => [
        'registered' => 'Captain registered successfully.',
        'registration_failed' => 'Captain registration failed.',
        'logged_in' => 'Captain logged in successfully.',
        'login_failed' => 'Captain login failed.',
        'profile_failed' => 'Captain profile failed.',
        'update_profile_failed' => 'Captain update profile failed.',
        'logged_out' => 'Captain logged out successfully.',
        'logout_failed' => 'Captain logout failed.',
    ],

    'client' => [
        'registered' => 'Client registered successfully.',
        'logged_in' => 'Client logged in successfully.',
        'logged_out' => 'Client logged out successfully.',
    ],

    'media' => [
        'avatar_uploaded' => 'Avatar uploaded.',
        'avatar_removed' => 'Avatar removed.',
        'file_uploaded' => 'File uploaded.',
        'file_deleted' => 'File deleted.',
    ],

    'cars' => [
        'list_retrieved' => 'Cars retrieved successfully.',
        'created' => 'Car created successfully.',
        'updated' => 'Car updated successfully.',
        'retrieved' => 'Car retrieved successfully.',
        'deleted' => 'Car deleted successfully.',
        'server_error' => 'Something went wrong. Please try again later.',
        'type_labels' => [
            'sedan' => 'Sedan',
            'microbus' => 'Microbus',
        ],
    ],

    'routes' => [
        'list_retrieved' => 'Routes retrieved successfully.',
        'created' => 'Route created successfully.',
        'updated' => 'Route updated successfully.',
        'retrieved' => 'Route retrieved successfully.',
        'deleted' => 'Route deleted successfully.',
        'not_found' => 'Route not found or unavailable.',
        'server_error' => 'Something went wrong. Please try again later.',
    ],

    'points' => [
        'list_retrieved' => 'Points retrieved successfully.',
        'created' => 'Point created successfully.',
        'updated' => 'Point updated successfully.',
        'retrieved' => 'Point retrieved successfully.',
        'deleted' => 'Point deleted successfully.',
        'not_found' => 'Point not found or unavailable.',
        'server_error' => 'Something went wrong. Please try again later.',
    ],

    'times' => [
        'list_retrieved' => 'Pickup times retrieved successfully.',
        'created' => 'Pickup time created successfully.',
        'updated' => 'Pickup time updated successfully.',
        'retrieved' => 'Pickup time retrieved successfully.',
        'deleted' => 'Pickup time deleted successfully.',
        'not_found' => 'Pickup time not found or unavailable.',
        'server_error' => 'Something went wrong. Please try again later.',
    ],

    'terms' => [
        'list_retrieved' => 'Terms retrieved successfully.',
        'created' => 'Term created successfully.',
        'updated' => 'Term updated successfully.',
        'retrieved' => 'Term retrieved successfully.',
        'deleted' => 'Term deleted successfully.',
        'not_found' => 'Term not found or unavailable.',
        'server_error' => 'Something went wrong. Please try again later.',
        'type_labels' => [
            'terms_conditions' => 'Terms & conditions',
            'privacy_policy' => 'Privacy policy',
            'FAQ' => 'FAQ',
        ],
    ],

    'notifications' => [
        'list_retrieved' => 'Notifications retrieved successfully.',
        'created' => 'Notification created successfully.',
        'updated' => 'Notification updated successfully.',
        'retrieved' => 'Notification retrieved successfully.',
        'deleted' => 'Notification deleted successfully.',
        'not_found' => 'Notification not found or unavailable.',
        'server_error' => 'Something went wrong. Please try again later.',
    ],

    'reservations' => [
        'admin_list_retrieved' => 'Reservations retrieved successfully.',
        'admin_status_updated' => 'Reservation status updated successfully.',
        'client_upcoming_retrieved' => 'Upcoming reservations retrieved successfully.',
        'client_history_retrieved' => 'Past reservations retrieved successfully.',
        'created' => 'Reservation created successfully.',
        'cancelled' => 'Reservation cancelled successfully.',
        'deleted' => 'Reservation deleted successfully.',
        'not_found' => 'Reservation not found.',
        'server_error' => 'Something went wrong. Please try again later.',
        'status_invalid' => 'Status must be confirmed or cancelled.',
        'invalid_time' => 'The selected pickup time is invalid.',
        'inactive_time' => 'This pickup time is not available.',
        'inactive_route' => 'This route is not available for booking.',
        'invalid_date_past' => 'The selected date and time must be in the future.',
        'duplicate_reservation' => 'You already have a reservation for this pickup time on this date.',
        'already_cancelled' => 'This reservation is already cancelled.',
        'cannot_cancel' => 'This reservation cannot be cancelled.',
        'client_only' => 'Only client accounts can create reservations.',
        'validation_scope_required' => 'Please choose upcoming or history.',
        'validation_scope_invalid' => 'Scope must be upcoming or history.',
        'validation_date_past' => 'The date must be today or a future day.',
        'validation_time_id' => 'Please choose a valid pickup time.',
        'validation_status_required' => 'Status is required.',
        'validation_status_in' => 'Status must be confirmed or cancelled.',
        'status_labels' => [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
        ],
    ],
];
