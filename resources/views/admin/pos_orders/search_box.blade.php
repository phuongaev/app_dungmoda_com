{{-- resources/views/admin/pos_orders/search_box.blade.php --}}
<div class="pos-orders-search-wrapper" style="display: inline-block; margin-right: 10px;">
    <!-- Ô tìm kiếm quick_search hiện tại -->
    <form id="pos-orders-search-form" method="GET" style="display: inline-block; margin-right: 15px;">
        <div class="input-group" style="width: 300px; display: inline-table;">
            <input 
                type="text" 
                id="pos-orders-search-input"
                name="quick_search" 
                class="form-control" 
                placeholder="Tìm kiếm nhanh: Mã đơn, SĐT, Mã vận đơn..."
                value="{{ request('quick_search') }}"
                style="border-radius: 4px 0 0 4px;"
            >
            <span class="input-group-btn">
                <button 
                    type="submit" 
                    class="btn btn-default" 
                    style="border-radius: 0 4px 4px 0; border-left: none;"
                >
                    <i class="fa fa-search"></i>
                </button>
                @if(request('quick_search'))
                <button 
                    type="button" 
                    class="btn btn-default" 
                    id="clear-search-btn"
                    style="border-left: none; margin-left: -1px;"
                    title="Xóa tìm kiếm"
                >
                    <i class="fa fa-times"></i>
                </button>
                @endif
            </span>
        </div>
    </form>

    <!-- Ô tìm kiếm mã vận đơn mới -->
    <div id="shipment-search-form" style="display: inline-block;">
        <div class="input-group" style="width: 280px; display: inline-table;">
            <input 
                type="text" 
                id="shipment-search-input"
                class="form-control" 
                placeholder="Nhập mã vận đơn để tìm SĐT nhân viên..."
                style="border-radius: 4px 0 0 4px;"
            >
            <span class="input-group-btn">
                <button 
                    type="button" 
                    class="btn btn-info" 
                    id="shipment-search-btn"
                    style="border-radius: 0 4px 4px 0; border-left: none;"
                    title="Tìm SĐT nhân viên giao hàng"
                >
                    <i class="fa fa-phone"></i>
                </button>
                <button 
                    type="button" 
                    class="btn btn-default" 
                    id="clear-shipment-search-btn"
                    style="border-left: none; margin-left: -1px; display: none;"
                    title="Xóa mã vận đơn"
                >
                    <i class="fa fa-times"></i>
                </button>
            </span>
        </div>
    </div>
</div>

<style>
.pos-orders-search-wrapper .input-group {
    vertical-align: middle;
}

.pos-orders-search-wrapper .form-control:focus {
    border-color: #3c8dbc;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(60, 141, 188, .6);
}

.pos-orders-search-wrapper .btn:hover {
    background-color: #e6e6e6;
}

.pos-orders-search-wrapper .btn-info:hover {
    background-color: #31b0d5;
}

/* Responsive */
@media (max-width: 768px) {
    .pos-orders-search-wrapper {
        display: block !important;
        margin-bottom: 10px;
        margin-right: 0 !important;
    }
    
    .pos-orders-search-wrapper form,
    .pos-orders-search-wrapper #shipment-search-form {
        display: block !important;
        margin-bottom: 10px;
        margin-right: 0 !important;
    }
    
    .pos-orders-search-wrapper .input-group {
        width: 100% !important;
    }
}

@media (max-width: 1200px) and (min-width: 769px) {
    .pos-orders-search-wrapper form {
        margin-right: 10px;
    }
    
    .pos-orders-search-wrapper .input-group {
        width: 250px !important;
    }
}
</style>

<script>
$(document).ready(function() {
    // === XỬ LÝ TÌM KIẾM QUICK_SEARCH HIỆN TẠI ===
    
    // Xử lý submit form tìm kiếm
    $('#pos-orders-search-form').on('submit', function(e) {
        e.preventDefault();
        
        var searchValue = $('#pos-orders-search-input').val().trim();
        var currentUrl = new URL(window.location.href);
        
        if (searchValue) {
            currentUrl.searchParams.set('quick_search', searchValue);
        } else {
            currentUrl.searchParams.delete('quick_search');
        }
        
        // Reset page về 1 khi tìm kiếm mới
        currentUrl.searchParams.delete('page');
        
        window.location.href = currentUrl.toString();
    });
    
    // Xử lý nút clear search
    $('#clear-search-btn').on('click', function() {
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete('quick_search');
        currentUrl.searchParams.delete('page');
        window.location.href = currentUrl.toString();
    });
    
    // Auto focus vào search box khi nhấn Ctrl+F hoặc Cmd+F
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 70) {
            e.preventDefault();
            $('#pos-orders-search-input').focus().select();
        }
    });
    
    // Enter để tìm kiếm
    $('#pos-orders-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            $('#pos-orders-search-form').submit();
        }
    });

    // === XỬ LÝ TÌM KIẾM MÃ VẬN ĐƠN MỚI ===
    
    // Xử lý nút tìm kiếm mã vận đơn
    $('#shipment-search-btn').on('click', function(e) {
        e.preventDefault();
        handleShipmentSearch();
    });
    
    // Enter để tìm kiếm mã vận đơn
    $('#shipment-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            handleShipmentSearch();
        }
    });
    
    // Xử lý nút clear shipment search
    $('#clear-shipment-search-btn').on('click', function() {
        $('#shipment-search-input').val('');
        $(this).hide();
    });
    
    // Hiển thị nút clear khi có text
    $('#shipment-search-input').on('input', function() {
        var value = $(this).val().trim();
        if (value) {
            $('#clear-shipment-search-btn').show();
        } else {
            $('#clear-shipment-search-btn').hide();
        }
    });

    /**
     * Xử lý tìm kiếm mã vận đơn
     */
    function handleShipmentSearch() {
        var shipmentId = $('#shipment-search-input').val().trim();
        
        // Kiểm tra input
        if (!shipmentId) {
            alert('Vui lòng nhập mã vận đơn');
            $('#shipment-search-input').focus();
            return;
        }
        
        // Hiển thị loading trên button
        var $btn = $('#shipment-search-btn');
        var originalIcon = $btn.find('i').attr('class');
        $btn.prop('disabled', true);
        $btn.find('i').attr('class', 'fa fa-spinner fa-spin');
        
        // Hiển thị modal và load dữ liệu
        $('#deliveryContactsModal').modal('show');
        loadDeliveryContactsFromSearch(shipmentId, function() {
            // Callback khi hoàn thành (thành công hoặc lỗi)
            $btn.prop('disabled', false);
            $btn.find('i').attr('class', originalIcon);
        });
    }
    
    /**
     * Load delivery contacts via AJAX (sử dụng lại logic đã có sẵn)
     * Kiểm tra xem function đã tồn tại chưa, nếu chưa thì khai báo
     */
    function loadDeliveryContactsFromSearch(shipmentId, callback) {
        // Kiểm tra và sử dụng function đã có sẵn nếu tồn tại
        if (typeof window.loadDeliveryContacts === 'function') {
            window.loadDeliveryContacts(shipmentId);
            if (callback) callback();
            return;
        }
        
        // Nếu chưa có, tạo function mới (fallback)
        // Reset modal state
        if (typeof window.resetModalState === 'function') {
            window.resetModalState();
        } else {
            $('#delivery-contacts-loading').hide();
            $('#delivery-contacts-order-info').hide();
            $('#delivery-contacts-no-data').hide();
            $('#delivery-contacts-table-wrapper').hide();
            $('#delivery-contacts-error').hide();
            $('#delivery-contacts-table-body').empty();
            $('#delivery-contacts-total').text('0');
        }
        
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
                        if (typeof window.renderDeliveryContactsTable === 'function') {
                            window.renderDeliveryContactsTable(response.data);
                        } else {
                            // Fallback render
                            var tbody = $('#delivery-contacts-table-body');
                            tbody.empty();
                            $.each(response.data, function(index, contact) {
                                var row = '<tr>' +
                                    '<td>' + (contact.delivery_name || '') + '</td>' +
                                    '<td><span class="phoneNumber">' + (contact.delivery_phone || '') + '</span></td>' +
                                    '<td><small class="text-muted">' + (contact.created_at || '') + '</small></td>' +
                                    '</tr>';
                                tbody.append(row);
                            });
                        }
                        $('#delivery-contacts-total').text(response.total);
                        $('#delivery-contacts-table-wrapper').show();
                    } else {
                        // No data
                        $('#delivery-contacts-no-data').show();
                    }
                } else {
                    // Error from server
                    if (typeof window.showError === 'function') {
                        window.showError(response.message || 'Có lỗi xảy ra khi tải dữ liệu');
                    } else {
                        $('#delivery-contacts-error-message').text(response.message || 'Có lỗi xảy ra khi tải dữ liệu');
                        $('#delivery-contacts-error').show();
                    }
                }
                
                if (callback) callback();
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
                
                if (typeof window.showError === 'function') {
                    window.showError(errorMessage);
                } else {
                    $('#delivery-contacts-error-message').text(errorMessage);
                    $('#delivery-contacts-error').show();
                }
                
                if (callback) callback();
            }
        });
    }
});
</script>