// resources/views/admin/pos_orders/delivery_contacts_script.blade.php

<script>
$(document).ready(function() {
    
    /**
     * Handle click on delivery contacts button
     */
    $(document).on('click', '.btn-delivery-contacts', function(e) {
        e.preventDefault();
        
        var shipmentId = $(this).data('shipment-id');
        
        // Kiểm tra shipment_id
        if (!shipmentId) {
            alert('Không có mã vận đơn để tìm kiếm');
            return;
        }
        
        // Show modal
        $('#deliveryContactsModal').modal('show');
        
        // Load delivery contacts data
        loadDeliveryContacts(shipmentId);
    });
    
    /**
     * Load delivery contacts via AJAX
     */
    function loadDeliveryContacts(shipmentId) {
        // Reset modal state
        resetModalState();
        
        // Show loading
        $('#delivery-contacts-loading').show();
        
        // Set order info
        $('#modal-shipment-id').text(shipmentId || 'N/A');
        $('#delivery-contacts-order-info').show();
        
        // AJAX request
        $.ajax({
            url: '{{ admin_url("delivery/contacts") }}',
            type: 'GET',
            data: {
                shipment_id: shipmentId
            },
            dataType: 'json',
            success: function(response) {
                $('#delivery-contacts-loading').hide();
                
                if (response.success) {
                    if (response.data && response.data.length > 0) {
                        // Has data - show table
                        renderDeliveryContactsTable(response.data);
                        $('#delivery-contacts-total').text(response.total);
                        $('#delivery-contacts-table-wrapper').show();
                    } else {
                        // No data
                        $('#delivery-contacts-no-data').show();
                    }
                } else {
                    // Error from server
                    showError(response.message || 'Có lỗi xảy ra khi tải dữ liệu');
                }
            },
            error: function(xhr, status, error) {
                $('#delivery-contacts-loading').hide();
                
                var errorMessage = 'Có lỗi xảy ra khi tải dữ liệu';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMessage = 'Không tìm thấy API endpoint';
                } else if (xhr.status === 500) {
                    errorMessage = 'Lỗi máy chủ nội bộ';
                } else if (xhr.status === 400) {
                    errorMessage = 'Dữ liệu không hợp lệ';
                }
                
                showError(errorMessage);
            }
        });
    }
    
    /**
     * Render delivery contacts table
     */
    function renderDeliveryContactsTable(contacts) {
        var tbody = $('#delivery-contacts-table-body');
        tbody.empty();
        
        $.each(contacts, function(index, contact) {
            var row = '<tr>' +
                '<td>' + escapeHtml(contact.delivery_name) + '</td>' +
                '<td><span class="phoneNumber">' + escapeHtml(contact.delivery_phone) + '</span></td>' +
                '<td><small class="text-muted">' + escapeHtml(contact.created_at) + '</small></td>' +
                '</tr>';
            tbody.append(row);
        });
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        $('#delivery-contacts-error-message').text(message);
        $('#delivery-contacts-error').show();
    }
    
    /**
     * Reset modal state
     */
    function resetModalState() {
        $('#delivery-contacts-loading').hide();
        $('#delivery-contacts-order-info').hide();
        $('#delivery-contacts-no-data').hide();
        $('#delivery-contacts-table-wrapper').hide();
        $('#delivery-contacts-error').hide();
        $('#delivery-contacts-table-body').empty();
        $('#delivery-contacts-total').text('0');
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }
    
    /**
     * Reset modal when closed
     */
    $('#deliveryContactsModal').on('hidden.bs.modal', function() {
        resetModalState();
    });
});
</script>