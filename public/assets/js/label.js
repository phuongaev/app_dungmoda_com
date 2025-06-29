/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!*******************************!*\
  !*** ./resources/js/label.js ***!
  \*******************************/
var jobs = {
  setTimeout: []
};
$(document).ready(function () {
  $(document).on("change", "select[name='labels']", function () {
    var self = $(this);
    var id = self.data("id");
    var type = self.data("type");
    var value = self.val();
    if (id in jobs.setTimeout) clearTimeout(jobs.setTimeout[id]);
    jobs.setTimeout[id] = setTimeout(function () {
      window.request("".concat(type, "/update/").concat(id), "post", {
        _token: window.csrf,
        labels: value
      }).done(function (res) {
        if (!res.success) {
          toastr.error("Error ID ".concat(id, ": ") + res.message);
        }
      });
    }, 1000);
  });
  $(document).on("click", "#labels .btn-delete", function () {
    var self = $(this);
    var id = $(this).data("id");
    if (confirm("Bạn có chắc chắn muốn xóa thẻ này ?")) {
      window.request("/labels/" + id, "delete", {
        _token: window.csrf
      }).done(function () {
        self.closest("tr").remove();
      });
    }
  });
  $(document).on("submit", "form#create", function (e) {
    e.preventDefault();
    var data = $(this).serialize();
    window.request("/labels/create", "post", data).done(function (res) {
      if (res.success) {
        $("#labels").find("tbody").append(res.data);
        $("#create-label").modal('hide');
      } else {
        toastr.error(res.message);
      }
    }).fail(function () {
      toastr.error("Error, open console tab view log");
    });
  });
});
/******/ })()
;