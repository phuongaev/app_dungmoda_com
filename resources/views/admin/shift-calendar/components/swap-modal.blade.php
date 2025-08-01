{{-- resources/views/admin/shift-calendar/components/swap-modal.blade.php --}}

<!-- Modal quản lý ca trực -->
<div class="modal fade" id="manageShiftModal" tabindex="-1" role="dialog" aria-labelledby="manageShiftModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="manageShiftModalLabel">
                    <i class="fa fa-edit"></i> Quản lý ca trực
                </h4>
            </div>
            <div class="modal-body">
                <!-- Thông tin ca trực hiện tại (chỉ hiển thị khi edit) -->
                <div class="form-group" id="currentShiftInfoGroup" style="display: none;">
                    <label>Ca trực hiện tại:</label>
                    <div id="currentShiftInfo" class="form-control-static">
                        <!-- Thông tin ca trực hiện tại sẽ được load bởi JS -->
                    </div>
                </div>

                <!-- Thông tin ngày được chọn (khi tạo mới) -->
                <div class="form-group" id="selectedDateInfoGroup" style="display: none;">
                    <label>Ngày được chọn:</label>
                    <div id="selectedDateInfo" class="form-control-static">
                        <!-- Ngày được chọn sẽ được load bởi JS -->
                    </div>
                </div>

                <!-- Tabs để chọn hành động -->
                <ul class="nav nav-tabs" role="tablist" id="shiftModalTabs">
                    <li role="presentation" class="active" id="addShiftTabLi">
                        <a href="#addShiftTab" aria-controls="addShiftTab" role="tab" data-toggle="tab">
                            <i class="fa fa-plus"></i> Thêm ca trực
                        </a>
                    </li>
                    <li role="presentation" id="changePersonTabLi">
                        <a href="#changePersonTab" aria-controls="changePersonTab" role="tab" data-toggle="tab">
                            <i class="fa fa-user"></i> Thay đổi người trực
                        </a>
                    </li>
                    <li role="presentation" id="swapShiftTabLi">
                        <a href="#swapShiftTab" aria-controls="swapShiftTab" role="tab" data-toggle="tab">
                            <i class="fa fa-exchange"></i> Hoán đổi ca trực
                        </a>
                    </li>
                </ul>

                <!-- Tab contents -->
                <div class="tab-content" style="margin-top: 15px;">
                    <!-- Tab thêm ca trực mới -->
                    <div role="tabpanel" class="tab-pane active" id="addShiftTab">
                        <div class="form-group">
                            <label for="addShiftUserSelect">Chọn nhân viên trực:</label>
                            <select id="addShiftUserSelect" class="form-control" style="width: 100%;">
                                <option value="">-- Chọn nhân viên --</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-success">
                            <i class="fa fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Thêm ca trực mới cho ngày được chọn. Một ngày có thể có nhiều người trực.
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-success" id="confirmAddShiftBtn">
                                <i class="fa fa-plus"></i> Thêm ca trực
                            </button>
                        </div>
                    </div>

                    <!-- Tab thay đổi người trực -->
                    <div role="tabpanel" class="tab-pane" id="changePersonTab">
                        <div class="form-group">
                            <label for="newPersonSelect">Chọn người trực mới:</label>
                            <select id="newPersonSelect" class="form-control" style="width: 100%;">
                                <option value="">-- Chọn nhân viên --</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Thay đổi người trực sẽ gán ca trực này cho nhân viên được chọn.
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-primary" id="confirmChangePersonBtn">
                                <i class="fa fa-save"></i> Thay đổi người trực
                            </button>
                        </div>
                    </div>

                    <!-- Tab hoán đổi ca trực -->
                    <div role="tabpanel" class="tab-pane" id="swapShiftTab">
                        <div class="form-group">
                            <label for="targetShiftSelect">Hoán đổi với ca trực:</label>
                            <select id="targetShiftSelect" class="form-control" style="width: 100%;">
                                <option value="">-- Chọn ca trực để hoán đổi --</option>
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Hoán đổi sẽ thay đổi nhân viên trực của hai ca được chọn. Một ngày có thể có nhiều người trực.
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-primary" id="confirmSwapBtn">
                                <i class="fa fa-exchange"></i> Xác nhận hoán đổi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <!-- NEW: Nút xóa ca trực - chỉ hiển thị khi edit -->
                <button type="button" class="btn btn-danger pull-left" id="deleteShiftBtn" style="display: none;">
                    <i class="fa fa-trash"></i> Xóa ca trực
                </button>
                
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Đóng
                </button>
            </div>
        </div>
    </div>
</div>