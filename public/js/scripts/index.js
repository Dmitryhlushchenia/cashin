const paymentAmount = $('[name="LMI_PAYMENT_AMOUNT"]');

$('.cash-form').submit(function () {



    if (!paymentAmount.val() || paymentAmount.val() === '0'){

        console.log(paymentAmount.val());
        return false;
    }

});

$('.enter-button').on('click', function () {
    $(location).attr('href', '/login');
});
$('.logo').on('click', function () {
    $(location).attr('href', '/');
});


$('.exit-button').on('click', function () {
    $(location).attr('href', '/logout');
});

const selectCurrency = $('.currency, .select');
const sumInput = $('.sum-input');
const sumForPay = $('.sum-for-pay');
const emailInput = $('.email-input');


$('.account').on('keyup', function (eventObject) {

    const accountValue = $(this).val();

    let currency = '0';

    let substr = '';
    if (accountValue.length >= 8) {

        substr = accountValue.slice(0, 8);
    }

    if (substr === '40817840') {
        currency = 'USD';
    } else if (substr === '40817810') {
        currency = 'RUR';
    }

    selectCurrency.val(currency);
});


sumInput.on('keyup', counterSumForPayWithCurrency);
sumInput.on('change', counterSumForPayWithCurrency);
emailInput.on('keyup', counterSumForPayWithCurrency);
emailInput.on('change', counterSumForPayWithCurrency);
selectCurrency.on('change', counterSumForPayWithCurrency);



function counterSumForPayWithCurrency() {

    const currency = selectCurrency.val();
    const sum = sumInput.val();
    const email = emailInput.val();



    paymentAmount.val(0);

    $.ajax({
        data: {currency: currency, sum: sum, email: email},
        url: "/calculationAmountPaid",
        success: function (response) {

            sumForPay.html(response.sumPay);
            paymentAmount.val(response.sumPay);
        }


    });


}
