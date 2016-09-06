/**
 * Created by bartosz.zielinski on 2016-08-10.
 */

jQuery(document).ready(function () {

    jQuery('.payMethodEnable .payMethodLabel').click(function () {
        jQuery('.payMethod').removeClass('payMethodActive');
        jQuery(this).closest('.payMethod').addClass('payMethodActive');
    });
    jQuery('#payuForm').submit(function () {
        return doubleClickPrevent(this);
    })


//        jQuery('#payuCondition').change(function(){
//        if (this.checked){
//             jQuery(".payMethods").show();
//             jQuery("#buttonPayu").show();
//
//             buttonPayu
//             }
//             else {
//             alert("Musisz zaakceptować regulamin");
//             jQuery(".payMethods").css("display", "none");
//             jQuery("#buttonPayu").css("display", "none");
//             }
//             })

    jQuery('input[name=payMethod]').attr('checked',false);
});

function doubleClickPrevent(object) {
    if (jQuery(object).data('clicked')) {
        return false;
    }
    jQuery(object).data('clicked', true);
    return true;


}

jQuery(document).ready(function() {
    window.onbeforeunload = function(e) {
        var dialogText = 'dddddd';
        e.returnValue = dialogText;
        return dialogText;
    };

});
        
