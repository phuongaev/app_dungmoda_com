{{-- resources/views/admin/widgets/employee-requests.blade.php --}}
<div class="box box-warning">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-file-text-o text-yellow"></i> Đơn của tôi
        </h3>
        <div class="box-tools pull-right">
            <span class="badge bg-yellow">{{ $total_pending + $total_approved }}</span>
            <button type="button" class="btn btn-box-tool" onclick="window.location.reload()" data-toggle="tooltip" title="Refresh">
                <i class="fa fa-refresh"></i>
            </button>
        </div>
    </div>
    
    <div class="box-body">
        <!-- Current Leave Status -->
        @if($active_leave)
            <div class="alert alert-info">
                <h4><i class="fa fa-info-circle"></i> Đang nghỉ phép!</h4>
                <p>
                    <strong>Từ:</strong> {{ $active_leave->start_date->format('d/m/Y') }}
                    <strong>Đến:</strong> {{ $active_leave->end_date->format('d/m/Y') }}
                    <span class="pull-right">
                        <span class="badge bg-blue">{{ $active_leave->total_days }} ngày</span>
                    </span>
                </p>
                <p><strong>Lý do:</strong> {{ $active_leave->reason }}</p>
            </div>
        @endif

        <!-- Upcoming Leaves -->
        @if($upcoming_leaves->count() > 0)
            <div class="alert alert-warning">
                <h4><i class="fa fa-calendar"></i> Nghỉ phép sắp tới:</h4>
                @foreach($upcoming_leaves as $leave)
                    <p>
                        <strong>{{ $leave->start_date->format('d/m/Y') }}</strong>
                        @if($leave->start_date != $leave->end_date)
                            - {{ $leave->end_date->format('d/m/Y') }}
                        @endif
                        ({{ $leave->total_days }} ngày)
                        <small class="text-muted">- {{ $leave->reason }}</small>
                    </p>
                @endforeach
            </div>
        @endif

        <!-- Statistics -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-yellow">
                    <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Chờ duyệt</span>
                        <span class="info-box-number">{{ $total_pending }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="fa fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Đã duyệt</span>
                        <span class="info-box-number">{{ $total_approved }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-blue">
                    <span class="info-box-icon"><i class="fa fa-bed"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Xin nghỉ</span>
                        <span class="info-box-number">{{ $pending_leaves + $approved_leaves }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box bg-purple">
                    <span class="info-box-icon"><i class="fa fa-exchange"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Hoán đổi</span>
                        <span class="info-box-number">{{ $pending_swaps + $approved_swaps }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Requests -->
        @if($leave_requests->count() > 0 || $swap_requests->count() > 0)
            <div style="margin-top: 15px;">
                <!-- Tabs for different request types -->
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#my-leaves-tab" role="tab" data-toggle="tab">
                            <i class="fa fa-bed"></i> Đơn xin nghỉ
                            <span class="badge">{{ $leave_requests->count() }}</span>
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#my-swaps-tab" role="tab" data-toggle="tab">
                            <i class="fa fa-exchange"></i> Hoán đổi ca
                            <span class="badge">{{ $swap_requests->count() }}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content" style="margin-top: 15px;">
                    <!-- Leave Requests Tab -->
                    <div role="tabpanel" class="tab-pane active" id="my-leaves-tab">
                        @if($leave_requests->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-condensed table-striped">
                                    <thead>
                                        <tr>
                                            <th width="25%">Thời gian</th>
                                            <th width="35%">Lý do</th>
                                            <th width="20%">Trạng thái</th>
                                            <th width="20%">Ngày tạo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($leave_requests as $request)
                                            <tr>
                                                <td>
                                                    <small>
                                                        {{ $request->start_date->format('d/m/Y') }}
                                                        @if($request->start_date != $request->end_date)
                                                            <br>{{ $request->end_date->format('d/m/Y') }}
                                                        @endif
                                                        <span class="badge bg-blue">{{ $request->total_days }}</span>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small>{{ \Str::limit($request->reason, 40) }}</small>
                                                </td>
                                                <td>{!! $request->status_badge !!}</td>
                                                <td>
                                                    <small>{{ $request->created_at->format('d/m H:i') }}</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Bạn chưa có đơn xin nghỉ nào.
                            </div>
                        @endif
                    </div>

                    <!-- Swap Requests Tab -->
                    <div role="tabpanel" class="tab-pane" id="my-swaps-tab">
                        @if($swap_requests->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-condensed table-striped">
                                    <thead>
                                        <tr>
                                            <th width="15%">Vai trò</th>
                                            <th width="35%">Hoán đổi</th>
                                            <th width="20%">Trạng thái</th>
                                            <th width="15%">Ngày tạo</th>
                                            <th width="15%">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($swap_requests as $request)
                                            <tr>
                                                <td>
                                                    @if($request->requester_id == $user->id)
                                                        <span class="label label-primary">YC</span>
                                                    @else
                                                        <span class="label label-info">ĐX</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <small>
                                                        <strong>{{ $request->requester->name ?? 'N/A' }}</strong>
                                                        ({{ $request->original_requester_shift_date->format('d/m') }})
                                                        <br>
                                                        <i class="fa fa-exchange text-muted"></i>
                                                        <strong>{{ $request->targetUser->name ?? 'N/A' }}</strong>
                                                        ({{ $request->original_target_shift_date->format('d/m') }})
                                                    </small>
                                                </td>
                                                <td>{!! $request->status_badge !!}</td>
                                                <td>
                                                    <small>{{ $request->created_at->format('d/m H:i') }}</small>
                                                </td>
                                                <td>
                                                    <a href="{{ admin_url('employee-shift-swaps/' . $request->id) }}" class="btn btn-xs btn-default" title="Xem chi tiết">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Bạn chưa có đơn hoán đổi ca nào.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Quick Actions -->
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-6">
                <a href="{{ admin_url('employee-leave-requests') }}" class="btn btn-warning btn-block">
                    <i class="fa fa-bed"></i> Quản lý đơn xin nghỉ
                </a>
            </div>
            <div class="col-md-6">
                <a href="{{ admin_url('employee-shift-swaps') }}" class="btn btn-purple btn-block">
                    <i class="fa fa-exchange"></i> Quản lý hoán đổi ca
                </a>
            </div>
        </div>

        <div class="row" style="margin-top: 10px;">
            <div class="col-md-6">
                <a href="{{ admin_url('employee-leave-requests/create') }}" class="btn btn-success btn-block">
                    <i class="fa fa-plus"></i> Tạo đơn xin nghỉ
                </a>
            </div>
            <div class="col-md-6">
                <a href="{{ admin_url('employee-shift-swaps/create') }}" class="btn btn-info btn-block">
                    <i class="fa fa-plus"></i> Tạo đơn hoán đổi ca
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.info-box {
    margin-bottom: 10px;
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

.btn-purple:hover,
.btn-purple:focus,
.btn-purple:active {
    background-color: #8e24aa;
    border-color: #8e24aa;
    color: white;
}

.table-condensed > thead > tr > th,
.table-condensed > tbody > tr > th,
.table-condensed > tfoot > tr > th,
.table-condensed > thead > tr > td,
.table-condensed > tbody > tr > td,
.table-condensed > tfoot > tr > td {
    padding: 5px;
}

.alert {
    margin-bottom: 15px;
}

.info-box .info-box-content {
    padding: 5px 10px;
}

.info-box .info-box-number {
    font-size: 18px;
}
</style>