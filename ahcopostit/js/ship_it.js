/**
 * Ahco Oy 2017
 * 
 * @type type
 */
$('#ahco_shipit_service_selector').on('change', function () {

    // disable all pickaup point options
    $('.ahco_shipit_pickup_locations').prop('disabled', true);
    $('.ahco_shipit_pickup_locations').hide();
    // reset value
    $('#pickup_location_selector').val('');
    // enable  and show only selected few
    var selector = "option[id^=\"ahco_shipit_pickup_courier_" + $(this).val() + "\"]";
    console.log($(this).val());
    console.log(selector);
    $(selector).prop('disabled', false);
    $(selector).show();


})