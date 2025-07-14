{{-- resources/views/admin/widgets/upcoming-requests.blade.php --}}
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-calendar-check-o text-blue"></i> 
            Đơn xin nghỉ & Hoán đổi ca (7 ngày tới)
        </h3>
        <div class="box-tools pull-right">
            <span class="badge bg-blue">{{ $total_pending + $total_approved }}</span>
            <button type="button" class="btn btn-box-tool" onclick="window.location.reload()" data-toggle="tooltip" title="Refresh">
                <i class="fa fa-refresh"></i>
            </button>
        </div>
    </div>
    
    <div class="box-body">
        <!-- Summary Stats -->
        <div class="row text-center" style="margin-bottom: 15px;">
            <div class="col-md-3">
                <div class="info-box bg-yellow">
                    <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Chờ duyệt</span>
                        <span class="info-box-number">{{ $total_pending }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="fa fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Đã duyệt</span>
                        <span class="info-box-number">{{ $total_approved }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-blue">
                    <span class="info-box-icon"><i class="fa fa-bed"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Xin nghỉ</span>
                        <span class="info-box-number">{{ $pending_leaves + $approved_leaves }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-purple">
                    <span class="info-box-icon"><i class="fa fa-exchange"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Hoán đổi</span>
                        <span class="info-box-number">{{ $pending_swaps + $approved_swaps }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if($leave_requests->count() > 0 || $swap_requests->count() > 0)
            <!-- Tabs for different request types -->
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active">
                    <a href="#leave-tab" role="tab" data-toggle="tab">
                        <i class="fa fa-bed"></i> Đơn xin nghỉ 
                        <span class="badge">{{ $leave_requests->count() }}</span>
                    </a>
                </li>
                <li role="presentation">
                    <a href="#swap-tab" role="tab" data-toggle="tab">
                        <i class="fa fa-exchange"></i> Hoán đổi ca 
                        <span class="badge">{{ $swap_requests->count() }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content" style="margin-top: 15px;">
                <!-- Leave Requests Tab -->
                <div role="tabpanel" class="tab-pane active" id="leave-tab">
                    @if($leave_requests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th width="20%">Nhân viên</th>
                                        <th width="25%">Thời gian nghỉ</th>
                                        <th width="15%">Số ngày</th>
                                        <th width="25%">Lý do</th>
                                        <th width="15%">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leave_requests as $request)
                                        <tr>
                                            <td>
                                                <strong>{{ $request->employee->name ?? 'N/A' }}</strong>
                                            </td>
                                            <td>
                                                {{ $request->start_date->format('d/m/Y') }} 
                                                @if($request->start_date != $request->end_date)
                                                    - {{ $request->end_date->format('d/m/Y') }}
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-blue">{{ $request->total_days }} ngày</span>
                                            </td>
                                            <td>{{ \Str::limit($request->reason, 50) }}</td>
                                            <td>{!! $request->status_badge !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center" style="margin-top: 10px;">
                            <a href="{{ admin_url('leave-requests') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-list"></i> Xem tất cả đơn xin nghỉ
                            </a>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> Không có đơn xin nghỉ nào trong 7 ngày tới.
                        </div>
                    @endif
                </div>

                <!-- Swap Requests Tab -->
                <div role="tabpanel" class="tab-pane" id="swap-tab">
                    @if($swap_requests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th width="35%">Hoán đổi</th>
                                        <th width="30%">Lý do</th>
                                        <th width="20%">Ngày tạo</th>
                                        <th width="15%">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($swap_requests as $request)
                                        <tr>
                                            <td>
                                                <small>
                                                    <strong>{{ $request->requester->name ?? 'N/A' }}</strong>
                                                    ({{ $request->original_requester_shift_date->format('d/m/Y') }})
                                                    <br>
                                                    <i class="fa fa-exchange text-muted"></i>
                                                    <strong>{{ $request->targetUser->name ?? 'N/A' }}</strong>
                                                    ({{ $request->original_target_shift_date->format('d/m/Y') }})
                                                </small>
                                            </td>
                                            <td>{{ \Str::limit($request->reason, 40) }}</td>
                                            <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                                            <td>{!! $request->status_badge !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center" style="margin-top: 10px;">
                            <a href="{{ admin_url('shift-swap-requests') }}" class="btn btn-sm btn-purple">
                                <i class="fa fa-exchange"></i> Xem tất cả đơn hoán đổi
                            </a>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> Không có đơn hoán đổi ca nào trong 7 ngày tới.
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="alert alert-success text-center">
                <i class="fa fa-check-circle"></i> 
                <strong>Tuyệt vời!</strong> Không có đơn xin nghỉ hay hoán đổi ca nào trong 7 ngày tới.
            </div>
        @endif
    </div>
</div>

<style>
.info-box {
    margin-bottom: 0;
}
.nav-tabs > li > a .badge {
    background-color: #999;
    margin-left: 5px;
}
.btn-purple {
    background-color: #9c27b0;
    border-color: #9c27b0;
    color: white;
}
.btn-purple:hover {
    background-color: #8e24aa;
    border-color: #8e24aa;
    color: white;
}
</style>