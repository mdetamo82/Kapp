function confirmDelete(url, id, callback = null, isSoftDelete = false) { 
    const csrfName = $('meta[name="csrf-name"]').attr('content');
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const actionText = isSoftDelete ? 'deactivate' : 'delete';

    Swal.fire({
        title: `Confirm ${isSoftDelete ? 'Deactivation' : 'Deletion'}`,
        text: `This will ${actionText} the selected record`,
        icon: 'warning',
        showCancelButton: true,
        backdrop: true,
        confirmButtonText: `Confirm ${actionText}`,
        cancelButtonText: 'Cancel',
        confirmButtonColor: isSoftDelete ? '#ffc107' : '#dc3545',
        cancelButtonColor: '#6c757d',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return $.ajax({
                url: `${url}/${id}`,
                method: 'POST',
                data: {
                    [csrfName]: csrfToken,
                    soft_delete: isSoftDelete ? 1 : 0
                },
                dataType: 'json'
            }).fail(function(jqXHR) {
                let errorMsg = 'Request failed';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMsg = jqXHR.responseJSON.message;
                } else if (jqXHR.statusText) {
                    errorMsg += `: ${jqXHR.statusText}`;
                }
                Swal.showValidationMessage(errorMsg);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            const response = result.value;

            // Update CSRF token
            if (response.csrf_token) {
                $('meta[name="csrf-token"]').attr('content', response.csrf_token);
            }

            if (response.success) {
                const successOptions = {
                    title: 'Success',
                    text: response.message || `Record ${actionText}d successfully`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                };

               

                Swal.fire(successOptions).then(() => {
                    if (typeof callback === 'function') {
                        callback(response);
                    } else {
                        // Always reload if no callback provided
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message || `Failed to ${actionText} record`,
                    icon: 'error'
                });
            }
        }
    });
}


// // assets/js/delete_handler.js

// function confirmDelete(url, id, callback = null) {
//     const csrfName = $('meta[name="csrf-name"]').attr('content');
//     const csrfToken = $('meta[name="csrf-token"]').attr('content');

//     Swal.fire({
//         title: 'Are you sure?',
//         text: "You won't be able to undo this!",
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonText: 'Yes, delete it!',
//         cancelButtonText: 'Cancel',
//         confirmButtonColor: '#3085d6',
//         cancelButtonColor: '#d33'
//     }).then((result) => {
//         if (result.isConfirmed) {
//             let postData = {};
//             postData[csrfName] = csrfToken;

//             $.ajax({
//                 url: `${url}/${id}`,
//                 method: 'POST',
//                 data: postData,
//                 success: function(response) {
//                     let res = {};
//                     try {
//                         res = JSON.parse(response);
//                     } catch (e) {
//                         Swal.fire('Error!', 'Invalid server response.', 'error');
//                         return;
//                     }

//                     // ✅ Update CSRF token
//                     if (res.csrf_token) {
//                         $('meta[name="csrf-token"]').attr('content', res.csrf_token);
//                     }

//                     if (res.status === 'success') {
//                         Swal.fire('Deleted!', 'Item has been deleted.', 'success').then(() => {
//                             if (typeof callback === 'function') {
//                                 callback();
//                             } else {
//                                 location.reload();
//                             }
//                         });
//                     } else {
//                         Swal.fire('Error!', 'Failed to delete item.', 'error');
//                     }
//                 },
//                 error: function() {
//                     Swal.fire('Error!', 'AJAX request failed.', 'error');
//                 }
//             });
//         }
//     });
// }
