jQuery(function($) {
    $("#bspf_btn_edit").on('click', function (event) {
        $form = $("#frmSalon");
        $("#mode").val("edit");
    
        $form.submit();
    });
    
    $("#bspf_btn_delete").on('click', function (event) {
        $form = $("#frmSalon");
        $("#mode").val("delete");
    
        $form.submit();
    });

    $("#bspf_btn_request").on('click', function (event) {
        $form = $("#frmSalon");
        $("#mode").val("public_request");
    
        $form.submit();
    });

    $("#bspf_btn_bank_edit").on('click', function (event) {
        $form = $("#frmBank");
        $("#mode").val("edit");
    
        $form.submit();
    });
});

