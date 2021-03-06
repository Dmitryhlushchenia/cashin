$('.payment-checkbox').change(function () {

    const checkbox = $(this);
    const id = checkbox.attr('data-id');
    $.ajax({
        url: "/confirmPayment",
        data: {id:id},
        success: function(response){
            checkbox.attr('disabled', 'disabled')
        },
        error:function () {
            checkbox.prop('checked', false);
        }

    });


});

$('.rate-add').off().on('click', function () {

    const rateInput = $('.rate-sum-add');

    if (rateInput) {
        $.ajax({
            url: "/addRate",
            data: {rate: rateInput.val()},
            success: function (response) {

                $('.row-head').after('<tr><td>'+response.date+'</td><td>'+response.rate+'</td></tr>')
                rateInput.val('')

            }


        });
    }

});

$('.vip-add').on('click', function () {

    const emailInput = $('.vip-mail-add');

    const email = emailInput.val();
    if (email) {
        $.ajax({
            url: "/addVip",
            data: {'email': email},
            success: function (response) {

                if (response.status == 'success') {

                    $('.row-head').after('<tr><td>' + email + '</td></tr>');
                    emailInput.val('')
                }


            }


        });
    }

});

$('.tariff-add').off().on('click', function () {

    const rateInput = $('.tariff-rate');

    if (rateInput) {
        $.ajax({
            url: "/addTariff",
            data: {rate: rateInput.val(), vip: $('.tariff-vip').val()},
            success: function (response) {

                $('.row-head').after('<tr><td>'+response.date+'</td><td>'+response.rate+'</td></tr>')
                rateInput.val('')

            }


        });
    }

});
