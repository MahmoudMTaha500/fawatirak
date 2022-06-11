<?php
add_action('abrs_prepare_checkout_process', 'store_data', 10);
function store_data()
{
  define('FAWATERAK_AWEBOOKING_STORED', true);
  WC()->session->set('rooms', abrs_reservation()->get_room_stays());
  WC()->session->set('services', abrs_reservation()->get_services());
}
