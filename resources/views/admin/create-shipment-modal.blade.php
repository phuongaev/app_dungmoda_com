<!-- Modal -->
<div class="modal fade" id="createShipmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tạo vận đơn nhanh</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="createShipmentForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Mã vận đơn *</label>
                        <input type="text" class="form-control" name="tracking_code" required>
                    </div>
                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Kiện hàng</label>
                        <select class="form-control" name="package_code">
                            <option value="">Không chọn</option>
                            @foreach(\App\Models\Package::all() as $package)
                                <option value="{{ $package->package_code }}">{{ $package->package_code }} ({{ $package->created_at->format('d/m/Y') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select class="form-control" name="shipment_status">
                            <option value="pending">Chờ xử lý</option>
                            <option value="processing">Đang xử lý</option>
                            <option value="shipped">Đã gửi</option>
                            <option value="delivered">Đã giao</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                    </div>
                    <input type="hidden" name="import_order_id" value="{{ request()->route('import_order') }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">Tạo vận đơn</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#createShipmentForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '/admin/import-orders/create-shipment',
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status) {
                    toastr.success(response.message);
                    $('#createShipmentModal').modal('hide');
                    $('#createShipmentForm')[0].reset();
                    location.reload(); // Refresh page to show new shipment
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                if (errors) {
                    for (var field in errors) {
                        toastr.error(errors[field][0]);
                    }
                } else {
                    toastr.error('Có lỗi xảy ra!');
                }
            }
        });
    });
});
</script>