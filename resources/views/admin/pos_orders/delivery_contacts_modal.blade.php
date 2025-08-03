{{-- resources/views/admin/pos_orders/delivery_contacts_modal.blade.php --}}

<!-- Modal -->
<div class="modal fade" id="deliveryContactsModal" tabindex="-1" role="dialog" aria-labelledby="deliveryContactsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title" id="deliveryContactsModalLabel">
                    <i class="fa fa-phone"></i> Số điện thoại nhân viên giao hàng
                </h4>
            </div>
            
            <div class="modal-body">
                <!-- Loading spinner -->
                <div id="delivery-contacts-loading" class="text-center" style="display: none;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p class="text-muted">Đang tải dữ liệu...</p>
                </div>
                
                <!-- Order info -->
                <div id="delivery-contacts-order-info" class="row mb-3" style="display: none;">
                    <div class="col-sm-12">
                        <strong>Mã vận đơn:</strong> <span id="modal-shipment-id"></span>
                    </div>
                </div>
                
                <!-- No data message -->
                <div id="delivery-contacts-no-data" class="alert alert-info text-center" style="display: none;">
                    <i class="fa fa-info-circle"></i> Chưa có SĐT nhân viên giao hàng
                </div>
                
                <!-- Data table -->
                <div id="delivery-contacts-table-wrapper" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="bg-light">
                                <tr>
                                    <th width="40%">Tên nhân viên</th>
                                    <th width="35%">Số điện thoại</th>
                                    <th width="25%">Thời gian</th>
                                </tr>
                            </thead>
                            <tbody id="delivery-contacts-table-body">
                                <!-- Data will be inserted here via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-muted text-center mt-2">
                        <small>Tổng cộng: <span id="delivery-contacts-total">0</span> liên hệ</small>
                    </div>
                </div>
                
                <!-- Error message -->
                <div id="delivery-contacts-error" class="alert alert-danger" style="display: none;">
                    <i class="fa fa-exclamation-triangle"></i> <span id="delivery-contacts-error-message"></span>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for delivery contacts modal */
#deliveryContactsModal .modal-header {
    border-bottom: 1px solid #dee2e6;
}

#deliveryContactsModal .modal-header .close {
    color: white;
    opacity: 0.8;
}

#deliveryContactsModal .modal-header .close:hover {
    opacity: 1;
}

#deliveryContactsModal .table th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

#deliveryContactsModal .table td {
    vertical-align: middle;
}

#deliveryContactsModal .table-responsive {
    max-height: 400px;
    overflow-y: auto;
}

/* Loading animation */
#delivery-contacts-loading .fa-spinner {
    color: #3c8dbc;
}

/* Responsive */
@media (max-width: 768px) {
    #deliveryContactsModal .modal-dialog {
        margin: 10px;
        width: auto;
    }
    
    #deliveryContactsModal .table-responsive {
        max-height: 300px;
    }
}
</style>