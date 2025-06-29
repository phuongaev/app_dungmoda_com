console.log("run");
const _token = window.csrf = $("meta[name='csrf-token']").attr("content")

const pageSize = [25, 50, 100]
const urlParams = new URLSearchParams(window.location.search);

window.request = function (url = "",method = "get",data = {}) {
    return $.ajax({
        url: url,
        type: method,
        data: data
    })
}
$(document).ready(function () {
    // Add active state to sidbar nav links
    var path = window.location.origin + location.pathname + '/'; // because the 'href' property of the DOM element is the absolute path
    $("#kt_header_navs a.nav-link").each(function () {
        if (!new RegExp(this.href + '/').test(path)) {
            $(this).addClass("btn-light-primary");
        } else {
            $(this).addClass("btn-primary");
            $(`.header-tabs a.nav-link[href='#${$(this).closest(".tab-pane").attr("id")}']`).tab("show");
            $(this).closest(".tab-pane").tab("show")
        }
    });

    $(".flatpickr").flatpickr({
        altInput: !0,
        altFormat: "Y-m-d",
        dateFormat: "Y-m-d",
        mode: "range",
    });
    $(".flatpickr_clear").on("click", function () {
        $(".flatpickr").val(null)
    })

    $(".btn-search").on("click", function () {
        $(this).closest("form").submit();
    })

    $(".save").on("click", function (e) {
        e.preventDefault();
        var data = $(".form-update .data").html("")
        $("tbody .form-check-input").each(function () {
            if($(this).prop("checked")) {
                data.append(`<input type="hidden" name="checks[]" value="${$(this).val()}">`)
            }
        })
        $(this).closest("form").submit();
    })


    $(document).on("change",".accounts input[name='sync']", function () {
        let self = $(this);
        let id = self.data("id");
        let value = self.prop("checked") ? 1 : 0;
        window.request(`accounts/update/${id}`,"post",{
            _token: window.csrf,
            sync: value
        })
            .done(function (res) {
                if (!res.success) {
                    toastr.error(`Error ID ${id}: ` + res.message)
                }
            })
    })
    $(document).on("change",".accounts input[name='is_auto_set_spend_cap']", function () {
        let self = $(this);
        let id = self.data("id");
        let value = self.prop("checked") ? 1 : 0;
        window.request(`accounts/update/${id}`,"post",{
            _token: window.csrf,
            is_auto_set_spend_cap: value
        })
            .done(function (res) {
                if (!res.success) {
                    toastr.error(`Error ID ${id}: ` + res.message)
                }
            })
    })

    customFilter();

    let getPageSize = urlParams.get("page_size");

    pageSize.forEach(function (e, index) {
        let selected;
        if (!getPageSize) {
            if (index == 0) {
                selected = "selected";
            }
        }else {
            if (getPageSize == e) {
                selected = "selected";
            }
        }
        $(".form-select.page-size").append(`<option ${selected}>${e}</option>`);
    })

    if (getPageSize) {
        $("input.search-page_size").val(getPageSize)
    }

    $(".form-select.page-size").on("change", function () {
        let param = window.location.search;
        let size = $(this).val();
        if (!param) {
            param = "?page_size=" + size
        }else {
            if (!getPageSize) {
                param = param + "&page_size=" + size
            }else {
                param = param.replace("page_size=" + getPageSize, "page_size=" + size)
            }
        }

        window.location.href = param
    })

    $(document).on("click", ".text-note", function () {
        $(this).hide().next().show().focus()
    })

    $(document).on("blur", ".t_note", function () {
        const value = $(this).val();
        $(this).hide().prev().html(value ? value : "<i class='small'>Click here to edit</i>").show()
        window.request(`${location.pathname}/update/${$(this).data("id")}`,"post",{
            _token: window.csrf,
            note: value
        })
            .done(function (res) {
                if (!res.success) {
                    toastr.error(res.message);
                }
            })
    })

    $(document).on("click", ".multi-update .btn-update", function () {
        const ids = [];
        const type = $(this).data("type");
        const value = $(".multi-update select[name='labels[]']").val();
        $("input[name='checks[]']:checked").each(function () {
            ids.push($(this).val());
        })

        if (!ids.length) {
            toastr.error("Chưa có đối tượng nào được chọn");
            return;
        }

        window.request(`${location.pathname}/updates`,"post",{
            _token: window.csrf,
            ids: ids,
            type: type,
            labels: value,
        })
            .done(function (res) {
                if (!res.success) {
                    toastr.error(`Error ID ${id}: ` + res.message)
                }else {
                    location.reload()
                }
            })
    })

    $(document).on("click", ".menu a", function (e) {
        e.preventDefault();
        let href = $(this).attr("href");
        let name = $("#kt_ecommerce_sales_table").data("type");
        let params = "";
        let i_self = Number($(".menu .btn-primary").data("sort"))
        if (!$(this).hasClass(name) && i_self < Number($(this).data("sort"))) {
            $("input[name='checks[]']:checked").each(function () {
                params += `${name}[]=` + $(this).val() + "&";
            })
            if (params) {
                href = href + "?" + params;
            }
        }

        location.assign(href);
    })

    $(document).on('click','.revoke-permission', function () {
        let self = $(this);
        let confirm = swal("Bạn có chắc muốn gỡ quyền này?", {
            dangerMode: true,
            buttons: true
        }).then((confirm) => {
            if (confirm) {
                let role_id = $(this).data('role-id');
                let permission_id = $(this).data('permission-id');
                request('/roles/revoke-permission','post',{_token, role_id, permission_id})
                    .then((res) => {
                        if (res.success) {
                            toastr.success(res.message)
                            self.parent().parent().remove();
                            let total_permissions = $(`#role_${role_id}`).find('.total-permissions');
                            total_permissions.html(Number(total_permissions.text() - 1));
                            $(".select-add-permission").append(`
                                <option value="${permission_id}">${self.parent().parent().find('.display-name').text()}</option>
                            `);
                        } else {
                            toastr.error(res.message)
                        }
                    })
            }
        });
    })

    $(document).on('click','.remove-user', function () {
        let self = $(this);
        let confirm = swal("Bạn có chắc muốn gỡ người này?", {
            dangerMode: true,
            buttons: true
        }).then((confirm) => {
            if (confirm) {
                let role_id = $(this).data('role-id');
                let user_id = $(this).data('user-id');
                request('/roles/assign-or-remove-user/delete','post',{_token, role_id, user_id})
                    .then((res) => {
                        if (res.success) {
                            toastr.success(res.message)
                            self.parent().parent().remove();
                            let total_users = $(`#role_${role_id}`).find('.total-users');
                            total_users.html(Number(total_users.text() - 1));
                            $(".select-add-user").append(`
                                <option value="${user_id}">${self.parent().parent().find('.name').text()}</option>
                            `);
                        } else {
                            toastr.error(res.message)
                        }
                    })
            }
        });
    })

    $(".select-add-permission").on("change", function () {
        let self = $(this);
        let role_id = $(this).data('role-id');
        let permission_id = $(this).val();
        self.find("button[type='submit']").addClass('rainbow')
        self.find("button[type='submit']").attr('type','button')
        request("/roles/give-permission",'post', {_token, role_id, permission_id})
            .then((res) => {
                if (res.success) {
                    self.closest('table').find('tbody').append(`
                        <td>
                            <h4>${res.permission.display_name ? res.permission.display_name : res.permission.name}</h4>
                            <i class="small">${res.permission.name}</i>
                        </td>
                        <td>${res.permission.description}</td>
                        <td>
                            <a href="javascript:void(0);" class="btn btn-icon btn-active-light-primary w-30px h-30px revoke-permission" data-role-id="${role_id}" data-permission-id="${permission_id}">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor"></path>
                                    <path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor"></path>
                                    <path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor"></path>
                                </svg>
                            </a>
                        </td>
                    `);
                    self.find(`.item-${permission_id}`).remove();
                    let total_permissions = $(`#role_${role_id}`).find('.total-permissions');
                    total_permissions.html(Number(total_permissions.text() + 1))
                } else {
                    toastr.error(res.message)
                }
                self.find("button[type='submit']").removeClass('rainbow')
                self.find("button[type='submit']").attr('type','submit')
            })
    })

    $(".select-add-user").on("change", function () {
        let self = $(this);
        let role_id = $(this).data('role-id');
        let user_id = $(this).val();
        self.find("button[type='submit']").addClass('rainbow')
        self.find("button[type='submit']").attr('type','button')
        request("/roles/assign-or-remove-user/assign",'post', {_token, role_id, user_id})
            .then((res) => {
                if (res.success) {
                    self.closest('table').find('tbody').append(`
                        <td>
                            <h4>${res.user.name}</h4>
                        </td>
                        <td>${res.user.email}</td>
                        <td>
                            <a href="javascript:void(0);" class="btn btn-icon btn-active-light-primary w-30px h-30px revoke-permission" data-role-id="${role_id}" data-permission-id="${user_id}">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z" fill="currentColor"></path>
                                    <path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z" fill="currentColor"></path>
                                    <path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z" fill="currentColor"></path>
                                </svg>
                            </a>
                        </td>
                    `);
                    self.find(`.item-${user_id}`).remove();
                    let total_users = $(`#role_${role_id}`).find('.total-users');
                    total_users.html(Number(total_users.text() + 1))
                } else {
                    toastr.error(res.message)
                }
                self.find("button[type='submit']").removeClass('rainbow')
                self.find("button[type='submit']").attr('type','submit')
            })
    })

    $(document).on('click','.delete-permission', function () {
        let self = $(this);
        let confirm = swal("Bạn có chắc muốn xóa quyền này?", {
            dangerMode: true,
            buttons: true
        }).then((confirm) => {
            if (confirm) {
                let permission_id = $(this).data('permission-id');
                request('/roles/permissions/delete','delete',{_token, permission_id})
                    .then((res) => {
                        if (res.success) {
                            toastr.success(res.message)
                            self.parent().parent().remove()
                        } else {
                            toastr.error(res.message)
                        }
                    })
            }
        });
    })

    $(".role-update-info").on("submit", function (e) {
        e.preventDefault();
        let self = $(this);
        let data = $(this).serialize();
        self.find("button[type='submit']").addClass('rainbow')
        self.find("button[type='submit']").attr('type','button')
        request("/roles/update",'post', data)
            .then((res) => {
                if (res.success) {
                    location.reload();
                } else {
                    toastr.error(res.message)
                }
                self.find("button[type='submit']").removeClass('rainbow')
                self.find("button[type='submit']").attr('type','submit')
            })
    })

    $("#kt_modal_add_role_form").on("submit", function (e) {
        e.preventDefault();
        let self = $(this);
        let data = $(this).serialize();
        self.find("button[type='submit']").addClass('rainbow')
        self.find("button[type='submit']").attr('type','button')
        request("/roles/store",'post', data)
            .then((res) => {
                if (res.success) {
                    location.reload();
                } else {
                    toastr.error(res.message)
                }
                self.find("button[type='submit']").removeClass('rainbow')
                self.find("button[type='submit']").attr('type','submit')
            })
    })

    $("#kt_modal_add_permission_form").on("submit", function (e) {
        e.preventDefault();
        let self = $(this);
        let data = $(this).serialize();
        self.find("button[type='submit']").addClass('rainbow')
        self.find("button[type='submit']").attr('type','button')
        request("/roles/permissions/store",'post', data)
            .then((res) => {
                if (res.success) {
                    location.reload();
                } else {
                    toastr.error(res.message)
                }
                self.find("button[type='submit']").removeClass('rainbow')
                self.find("button[type='submit']").attr('type','submit')
            })
    })

    $(".kt_modal_update_permission").on("submit", function (e) {
        e.preventDefault();
        let self = $(this);
        let data = $(this).serialize();
        self.find("button[type='submit']").addClass('rainbow')
        self.find("button[type='submit']").attr('type','button')
        request("/roles/permissions/update",'post', data)
            .then((res) => {
                if (res.success) {
                    location.reload();
                } else {
                    toastr.error(res.message)
                }
                self.find("button[type='submit']").removeClass('rainbow')
                self.find("button[type='submit']").attr('type','submit')
            })
    })

    $("select.form-select2").select2();

    chooseHostAccounts();
})

function customFilter()
{
    let today = moment().format("YYYY-MM-DD");
    let last_week = moment().subtract(1, "weeks");
    let last_month = moment().subtract(1, "months");
    $(".flatpickr-calendar").prepend(`<div class="options">
        <ul>
            <li><a href="#" data-value="${today}">hôm nay</a></li>
            <li><a href="#" data-value="${moment().subtract(1, "days").format("YYYY-MM-DD")}">hôm qua</a></li>
            <li><a href="#" data-value="${moment().subtract(3, "days").format("YYYY-MM-DD") + " to " + today}">3 ngày trước</a></li>
            <li><a href="#" data-value="${moment().subtract(7, "days").format("YYYY-MM-DD") + " to " + today}">7 ngày trước</a></li>
            <li><a href="#" data-value="${moment().subtract(1, "months").format("YYYY-MM-DD") + " to " + today}">1 tháng trước</a></li>
            <li><a href="#" data-value="${moment().subtract(1, "years").format("YYYY-MM-DD") + " to " + today}">1 năm trước</a></li>
            <li><a href="#" data-value="${moment().startOf('week').format("YYYY-MM-DD") + " to " + today}">tuần này</a></li>
            <li><a href="#" data-value="${last_week.startOf('week').format("YYYY-MM-DD") + " to " + last_week.endOf('week').format("YYYY-MM-DD")}">tuần trước</a></li>
            <li><a href="#" data-value="${moment().startOf('month').format("YYYY-MM-DD") + " to " + today}">tháng này</a></li>
            <li><a href="#" data-value="${last_month.startOf('month').format("YYYY-MM-DD") + " to " + last_month.endOf('month').format("YYYY-MM-DD")}">tháng trước</a></li>
            <li><a href="#" data-value="all">Trọn đời</a></li>
        </ul>
    </div>`)

    $(document).on("click",".flatpickr-calendar .options a", function () {
        let value = $(this).data("value");
        $(".flatpickr_clear").trigger("click")
        $("form.filter .flatpickr").val(value);
    })

    let date_selected = urlParams.get("date");
    document.querySelectorAll(".options a").forEach(function (e) {
        console.log(e.getAttribute("data-value"), date_selected)
        if (e.getAttribute("data-value") === date_selected) {
            e.parentElement.classList.add("active");
        } else {
            e.parentElement.classList.remove("active");
        }
    })
}

function chooseHostAccounts()
{
    let choose_host_accounts = false;
    let this_act, account_id = null;
    $(".account-name").on("mouseout mouseover", function (event) {
        let self = $(this);
        if ((!choose_host_accounts || !this_act) && event.type === "mouseover") {
            choose_host_accounts = setTimeout(() => {
                clearTimeout(choose_host_accounts)
                $("body").trigger('click')
                this_act = account_id = self.parent().data('id');
                self.parent().find('.host-accounts').css({
                    "display" : 'flex',
                    "top": event.layerY,
                    "left" : event.layerX
                })
            }, 800)
        } else if (((choose_host_accounts || this_act) && event.type === "mouseout")) {
            this_act = null;
            clearTimeout(choose_host_accounts)
        }
    });
    $("body").click(function(){
        clearTimeout(choose_host_accounts)
        $('.host-accounts').css('display','none')
        choose_host_accounts = false;
    })

    $(".host").click(function () {
        let self = $(this);
        let host_threshold_uid = self.data('host-threshold-uid');
        let host_sync_data_uid = self.data('host-sync-data-uid');
        window.request(`${location.pathname}/update/${account_id}`,"post",{
            _token: window.csrf,
            host_threshold_uid,
            host_sync_data_uid,
            type: 'set_host_account'
        })
            .done(function (res) {
                if (res.success) {
                    self.parent().find('.host').removeClass('active')
                    self.parent().find(`.host[data-host-threshold-uid='${res.host_threshold_uid}']`).addClass('active')
                    self.parent().find(`.host[data-host-sync-data-uid='${res.host_sync_data_uid}']`).addClass('active')
                    toastr.success('Cập nhật host account cho tài khoản ' + this_act + ' thành công')
                }else {
                    toastr.error('Cập nhật host account cho tài khoản ' + this_act + ' thất bại')
                }
            })
    })
}

