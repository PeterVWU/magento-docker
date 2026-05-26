require(['jquery'], function ($) {
    var formKey = $('[name="form_key"]').val();
    $(document).ready(function () {
        var selectedRepresentativeId = $("#sales_representative").val();
        var url = $("#salesrep_change_selectsalesrepadmin_url").val();
        if (selectedRepresentativeId) {
            $.ajax({
                showLoader: true,
                url: url,
                type: "POST",
                data: {
                    selectedSalesrep: selectedRepresentativeId,
                    form_key: formKey
                }
            });
        }
    });
    $(document).ready(function () {
        $(document).on('change', "#sales_representative", function (el) {
            var selectedRepresentativeId = el.target.value;
            var url = $("#salesrep_change_selectsalesrepadmin_url").val();

            // Проверка на наличие selectedRepresentativeId
            if (selectedRepresentativeId) {
                $.ajax({
                    showLoader: true,
                    url: url,
                    type: "POST",
                    data: {
                        selectedSalesrep: selectedRepresentativeId,
                        form_key: formKey
                    }
                });
            }
        });
    });
});