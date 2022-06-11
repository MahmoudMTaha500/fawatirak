<?php

$rooms = WC()->session->get('rooms');
$services = WC()->session->get('services');

foreach ($rooms as $item => $item_data) {
  $data = $item_data->data()->get_request();

  $product_name  = $item_data->get('name');

  $adults_count = $data->get_guest_counts()->get_adults()->get_count();
  $adults = $adults_count . _n(' adult', ' adults', $adults_count, 'awebooking');

  $nights = abrs_ngettext_nights($data->get('nights'));
  $product_price = $item_data->get_total();
  $item_quantity = $item_data->get_quantity();

  $cartItems[$i] = [
    "name" => $product_name . ' For ' . $nights . ',' . $adults,
    "price" => $product_price,
    "quantity" => $item_quantity
  ];
  $i++;
}
if ($services) {

  foreach ($services as $service_item => $service_data) {

    $cartItems[$i] = [
      "name" => $service_data->get('name'),
      "price" => $service_data->get_total(),
      "quantity" => $service_data->get_quantity()
    ];
    $i++;
  }
}
