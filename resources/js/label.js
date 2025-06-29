const jobs = {
    setTimeout: []
};
$(document).ready(function () {
    $(document).on("change","select[name='labels']", function () {
        let self = $(this);
        let id = self.data("id");
        let type = self.data("type");
        let value = self.val();
        if (id in jobs.setTimeout) clearTimeout(jobs.setTimeout[id]);
        jobs.setTimeout[id] = setTimeout(function () {
            window.request(`${type}/update/${id}`,"post",{
                _token: window.csrf,
                labels: value
            })
                .done(function (res) {
                    if (!res.success) {
                        toastr.error(`Error ID ${id}: ` + res.message)
                    }
                })
        }, 1000)
    })


    $(document).on("click", "#labels .btn-delete", function () {
        let self = $(this);
        let id = $(this).data("id");
        if (confirm("Bạn có chắc chắn muốn xóa thẻ này ?")) {
            window.request("/labels/" + id, "delete",{_token: window.csrf})
                .done(() => {
                    self.closest("tr").remove()
                })
        }
    })

    $(document).on("submit", "form#create", function (e) {
        e.preventDefault();
        let data = $(this).serialize();
        window.request("/labels/create", "post", data)
            .done((res) => {
                if (res.success) {
                    $("#labels").find("tbody").append(res.data);
                    $("#create-label").modal('hide')
                } else {
                    toastr.error(res.message)
                }
            })
            .fail(function () {
                toastr.error("Error, open console tab view log");
            })
    })
})
