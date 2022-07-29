jQuery(function($) {
    $("#btn_select_salon").on('click', function (event) {
        $form = $("#frm_bs_platform_payment");
        
        var select_salon = $("#sb_payment_salon").val();
        if(select_salon == ""){
            alert("サロンを選択してください。");
            return false;
        }

        $form.submit();
    });

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

    $(function () {
        
    });
});

