{{-- resources/views/admin/pos_orders/search_box.blade.php --}}
<div class="pos-orders-search-wrapper" style="display: inline-block; margin-right: 10px;">
    <form id="pos-orders-search-form" method="GET" style="display: inline-block;">
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

/* Responsive */
@media (max-width: 768px) {
    .pos-orders-search-wrapper {
        display: block !important;
        margin-bottom: 10px;
        margin-right: 0 !important;
    }
    
    .pos-orders-search-wrapper .input-group {
        width: 100% !important;
    }
}
</style>

<script>
$(document).ready(function() {
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
});
</script>